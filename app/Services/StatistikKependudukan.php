<?php

namespace App\Services;

use App\Models\DemografiWilayah;
use App\Support\KategoriDemografi;
use App\Support\PeriodeDemografi;
use Illuminate\Support\Collection;

/**
 * Statistik kependudukan satu wilayah pada satu periode — isi halaman GIS
 * Dukcapil (`/media/gis`) DAN berkas PDF-nya. Satu kelas untuk keduanya,
 * supaya angka di layar dan di kertas tidak pernah bisa berbeda.
 *
 * Sumbernya `m_demografi_wilayah` — tabel yang diisi petugas lewat impor DKB
 * di dasbor. Halaman ini tidak punya input sendiri.
 *
 * 🔴 ATURAN TINGKAT WILAYAH (sama dengan StatistikController::jumlahKolom):
 *   angka kecamatan = JUMLAH desa-desanya bila ada baris desa, kalau tidak
 *   baris kecamatan itu sendiri; angka kabupaten = jumlah kecamatan. Baris
 *   kabupaten (level 3) sengaja TIDAK dibaca — ia ringkasan dari angka yang
 *   sama, dan mencampurnya dengan kecamatan menggandakan penduduk.
 *
 * Diporting dari portal Next.js saudara (lib/statistik-kependudukan.ts,
 * 28 Sep 2026) — bentuk keluarannya sengaja sama.
 */
class StatistikKependudukan
{
    /** Kolom ringkasan: "JML", "Total", "JUMLAH", "JUMLAH PENDUDUK", … */
    private const POLA_JUMLAH = '/^(jml|jumlah|total)(\s|$)/i';

    private const POLA_LAKI = '/^(l|lk|laki|laki-laki|laki laki)$/i';

    private const POLA_PEREMPUAN = '/^(p|pr|perempuan|wanita)$/i';

    /** Kolom penanda berkas, bukan angka kependudukan. */
    private const BUKAN_BUTIR = '/^(jml|jumlah|total|level|idem|no\.?)(\s|$)/i';

    /** Urutan bagian komposisi di halaman (setelah kelompok usia). */
    private const KATEGORI_KOMPOSISI = ['status-kawin', 'agama', 'pendidikan', 'pekerjaan', 'gol-darah'];

    /**
     * Slug kategori kelompok umur. BELUM ADA di delapan kategori baku (28 Sep
     * 2026) — bagian usia otomatis tersembunyi sampai kategori ini ditambahkan
     * ke `config/demografi.php` dan datanya diimpor.
     */
    public const SLUG_USIA = 'kelompok-usia';

    /**
     * Label tampilan per kategori → [kunci SIAK (huruf besar) => label]. Kunci
     * yang tidak terdaftar ditampilkan dengan huruf kapital di awal kata.
     */
    private const LABEL = [
        'agama' => [
            'KATHOLIK' => 'Katolik', 'KATOLIK' => 'Katolik', 'BUDHA' => 'Buddha',
            'KHONGHUCU' => 'Konghucu', 'KONGHUCHU' => 'Konghucu', 'KONGHUCU' => 'Konghucu',
            'KEPERCAYAAN' => 'Kepercayaan terhadap Tuhan YME', 'LAINNYA' => 'Kepercayaan / lainnya',
        ],
        'pendidikan' => [
            'TIDAK/BLM SEKOLAH' => 'Tidak/belum sekolah', 'BELUM SEKOLAH' => 'Belum sekolah',
            'BELUM TAMAT SD/SEDERAJAT' => 'Belum tamat SD', 'TIDAK TAMAT SD' => 'Tidak tamat SD',
            'TAMAT SD/SEDERAJAT' => 'Tamat SD', 'TAMAT SD' => 'Tamat SD',
            'SLTP/SEDERAJAT' => 'SLTP / sederajat', 'SLTP' => 'SLTP / sederajat',
            'SLTA/SEDERAJAT' => 'SLTA / sederajat', 'SLTA' => 'SLTA / sederajat',
            'DIPLOMA I/II' => 'Diploma I/II', 'DIP II' => 'Diploma I/II',
            'AKADEMI/DIPL.III/S.MUDA' => 'Diploma III', 'DIP III' => 'Diploma III',
            'DIPLOMA IV/STRATA I' => 'Diploma IV / S1', 'STRATA I' => 'Diploma IV / S1',
            'STRATA-II' => 'S2', 'STRATA II' => 'S2', 'STRATA-III' => 'S3', 'STRATA III' => 'S3',
        ],
        'status-kawin' => [
            'BELUM KAWIN' => 'Belum kawin', 'KAWIN' => 'Kawin', 'SUDAH KAWIN' => 'Sudah kawin',
            'CERAI HIDUP' => 'Cerai hidup', 'CERAI MATI' => 'Cerai mati',
        ],
        'pekerjaan' => ['TDK BEKERJA' => 'Tidak bekerja', 'BELUM/TIDAK BEKERJA' => 'Belum/tidak bekerja'],
    ];

    /** Urutan tetap (kategori berjenjang). Kategori lain diurutkan dari terbesar. */
    private const URUTAN = [
        'pendidikan' => [
            'TIDAK/BLM SEKOLAH', 'BELUM SEKOLAH', 'BELUM TAMAT SD/SEDERAJAT', 'TIDAK TAMAT SD',
            'TAMAT SD/SEDERAJAT', 'TAMAT SD', 'SLTP/SEDERAJAT', 'SLTP', 'SLTA/SEDERAJAT', 'SLTA',
            'DIPLOMA I/II', 'DIP II', 'AKADEMI/DIPL.III/S.MUDA', 'DIP III',
            'DIPLOMA IV/STRATA I', 'STRATA I', 'STRATA-II', 'STRATA II', 'STRATA-III', 'STRATA III',
        ],
        'status-kawin' => ['BELUM KAWIN', 'KAWIN', 'SUDAH KAWIN', 'CERAI HIDUP', 'CERAI MATI'],
    ];

    // ── Ukuran peta ──────────────────────────────────────────────────────────

    public const UKURAN = [
        'penduduk' => ['label' => 'Jumlah penduduk', 'satuan' => 'jiwa'],
        'kk' => ['label' => 'Kepala keluarga', 'satuan' => 'KK'],
        'jiwa-kk' => ['label' => 'Jiwa per KK', 'satuan' => 'jiwa/KK'],
        'rasio-jk' => ['label' => 'Rasio jenis kelamin', 'satuan' => 'L per 100 P'],
    ];

    public static function bacaUkuran(?string $teks): string
    {
        return array_key_exists((string) $teks, self::UKURAN) ? (string) $teks : 'penduduk';
    }

    public static function nilaiUkuran(array $k, string $u): ?float
    {
        return match ($u) {
            'penduduk' => $k['penduduk'],
            'kk' => $k['kk'],
            'jiwa-kk' => ($k['penduduk'] && $k['kk']) ? $k['penduduk'] / $k['kk'] : null,
            'rasio-jk' => ($k['laki'] !== null && $k['perempuan']) ? $k['laki'] / $k['perempuan'] * 100 : null,
            default => null,
        };
    }

    /** "2024-2" → ['tahun'=>2024,'semester'=>2], atau null. */
    public static function bacaKunciPeriode(?string $teks): ?array
    {
        if (! preg_match('/^(\d{4})-([12])$/', (string) $teks, $m)) {
            return null;
        }
        if (! PeriodeDemografi::tahunSah($m[1]) || ! PeriodeDemografi::semesterSah($m[2])) {
            return null;
        }

        return ['tahun' => (int) $m[1], 'semester' => (int) $m[2]];
    }

    public static function kunciPeriode(array $p): string
    {
        return $p['tahun'].'-'.$p['semester'];
    }

    // ── Pembacaan kolom ──────────────────────────────────────────────────────

    private static function cariKolom(?array $data, string $pola): ?int
    {
        if (! $data) {
            return null;
        }
        foreach ($data as $k => $v) {
            if (preg_match($pola, trim((string) $k))) {
                return (int) $v;
            }
        }

        return null;
    }

    /** Kolom total bila ada, kalau tidak jumlah seluruh butir. */
    private static function jumlahBaris(?array $data): ?int
    {
        if (! $data) {
            return null;
        }
        $total = self::cariKolom($data, self::POLA_JUMLAH);
        if ($total !== null) {
            return $total;
        }
        $s = 0;
        foreach ($data as $k => $v) {
            if (! preg_match(self::BUKAN_BUTIR, trim((string) $k))) {
                $s += (float) $v;
            }
        }

        return (int) $s;
    }

    private static function tambah(array &$a, array $b): void
    {
        foreach ($b as $k => $v) {
            $a[$k] = ($a[$k] ?? 0) + (float) $v;
        }
    }

    public static function labelButir(string $slug, string $kunci): string
    {
        $naik = mb_strtoupper(trim($kunci));
        if (isset(self::LABEL[$slug][$naik])) {
            return self::LABEL[$slug][$naik];
        }
        // "PELAJAR DAN MAHASISWA" → "Pelajar dan mahasiswa"; ejaan campuran dibiarkan.
        if ($naik === $kunci) {
            return mb_strtoupper(mb_substr($kunci, 0, 1)).mb_strtolower(mb_substr($kunci, 1));
        }

        return $kunci;
    }

    /** @return array{butir: array<int,array{label:string,nilai:int}>, catatan: string[]} */
    private static function butirDari(string $slug, array $data): array
    {
        $catatan = [];
        $pasangan = [];
        foreach ($data as $k => $v) {
            if (! preg_match(self::BUKAN_BUTIR, trim((string) $k))) {
                $pasangan[] = [(string) $k, (int) $v];
            }
        }

        if ($slug === 'gol-darah') {
            // SIAK memecah tiap golongan jadi tiga: tanpa rhesus, _PLUS, _MIN.
            $gabung = [];
            foreach ($pasangan as [$k, $v]) {
                $dasar = preg_replace('/[_\s-]*(PLUS|MIN|\+|-)$/', '', mb_strtoupper($k));
                $label = in_array($dasar, ['A', 'B', 'AB', 'O'], true) ? "Golongan {$dasar}" : 'Tidak diketahui';
                $gabung[$label] = ($gabung[$label] ?? 0) + $v;
            }
            $butir = [];
            foreach (['Golongan A', 'Golongan B', 'Golongan AB', 'Golongan O', 'Tidak diketahui'] as $l) {
                if (isset($gabung[$l])) {
                    $butir[] = ['label' => $l, 'nilai' => $gabung[$l]];
                }
            }

            return ['butir' => $butir, 'catatan' => ['Rhesus positif dan negatif digabung ke golongan darahnya.']];
        }

        $urutan = self::URUTAN[$slug] ?? null;
        if ($urutan) {
            $posisi = fn ($k) => ($i = array_search(mb_strtoupper($k), $urutan, true)) === false ? count($urutan) : $i;
            usort($pasangan, fn ($a, $b) => $posisi($a[0]) <=> $posisi($b[0]));
        } else {
            usort($pasangan, fn ($a, $b) => $b[1] <=> $a[1]);
            // Butir nol di kategori tak berjenjang dipindah ke catatan —
            // deretan "0 · 0,0%" hanya menenggelamkan isinya.
            $nol = array_filter($pasangan, fn ($p) => $p[1] === 0);
            if ($nol) {
                $pasangan = array_values(array_filter($pasangan, fn ($p) => $p[1] > 0));
                $catatan[] = 'Tidak ada penduduk tercatat: '
                    .implode(', ', array_map(fn ($p) => self::labelButir($slug, $p[0]), $nol)).'.';
            }
        }

        return [
            'butir' => array_map(fn ($p) => ['label' => self::labelButir($slug, $p[0]), 'nilai' => $p[1]], $pasangan),
            'catatan' => $catatan,
        ];
    }

    private static function usiaDari(?array $data): ?array
    {
        if (! $data) {
            return null;
        }
        $butir = [];
        foreach ($data as $k => $v) {
            if (preg_match(self::BUKAN_BUTIR, trim((string) $k)) || ! preg_match_all('/\d+/', (string) $k, $m)) {
                continue;
            }
            $angka = $m[0];
            $butir[] = [
                'awal' => (int) $angka[0],
                'label' => count($angka) >= 2 ? "{$angka[0]}–{$angka[1]}" : "{$angka[0]}+",
                'nilai' => (int) $v,
            ];
        }
        if (! $butir) {
            return null;
        }
        usort($butir, fn ($a, $b) => $a['awal'] <=> $b['awal']);
        $antara = fn ($lo, $hi) => array_sum(array_map(
            fn ($b) => $b['nilai'],
            array_filter($butir, fn ($b) => $b['awal'] >= $lo && $b['awal'] <= $hi),
        ));

        return [
            'butir' => array_map(fn ($b) => ['label' => $b['label'], 'nilai' => $b['nilai']], $butir),
            'jumlah' => array_sum(array_column($butir, 'nilai')),
            'anak' => $antara(0, 14),
            'produktif' => $antara(15, 64),
            'lansia' => $antara(65, 999),
        ];
    }

    // ── Indeks satu periode ──────────────────────────────────────────────────

    /**
     * @param  Collection<int,DemografiWilayah>  $baris
     * @return array{kec: array<string,string>, desa: array<string,array{nama:string,induk:string}>, nilai: \Closure, kecamatan: \Closure}
     */
    private static function indeks(Collection $baris): array
    {
        $kec = [];
        $desa = [];
        $nilaiKec = [];
        $nilaiDesa = [];
        foreach ($baris as $b) {
            $data = is_array($b->data) ? $b->data : [];
            if ((int) $b->level === DemografiWilayah::LEVEL_KECAMATAN) {
                $kec[$b->kode] = $b->wilayah;
                $nilaiKec[$b->kategori][$b->kode] = $data;
            } elseif ((int) $b->level === DemografiWilayah::LEVEL_KELURAHAN && $b->parent_kode) {
                $desa[$b->kode] = ['nama' => $b->wilayah, 'induk' => $b->parent_kode];
                $nilaiDesa[$b->kategori][$b->kode] = $data;
            }
        }
        ksort($kec);
        ksort($desa);

        $kecamatan = function (string $kategori, string $kode) use ($nilaiDesa, $nilaiKec, $desa): ?array {
            $acc = [];
            $ada = false;
            foreach ($nilaiDesa[$kategori] ?? [] as $kd => $data) {
                if (($desa[$kd]['induk'] ?? null) !== $kode) {
                    continue;
                }
                self::tambah($acc, $data);
                $ada = true;
            }

            return $ada ? $acc : ($nilaiKec[$kategori][$kode] ?? null);
        };

        $nilai = function (string $kategori, array $w) use ($kec, $kecamatan, $nilaiDesa): ?array {
            if ($w['tingkat'] === 'kabupaten') {
                $acc = [];
                $ada = false;
                foreach (array_keys($kec) as $kode) {
                    $n = $kecamatan($kategori, (string) $kode);
                    if ($n) {
                        self::tambah($acc, $n);
                        $ada = true;
                    }
                }

                return $ada ? $acc : null;
            }
            if ($w['tingkat'] === 'kecamatan') {
                return $kecamatan($kategori, $w['kode']);
            }

            return $nilaiDesa[$kategori][$w['kode']] ?? null;
        };

        return ['kec' => $kec, 'desa' => $desa, 'nilai' => $nilai, 'kecamatan' => $kecamatan];
    }

    /**
     * Ambil statistik untuk periode & wilayah yang diminta. Periode yang tidak
     * ada datanya jatuh ke periode terbaru; wilayah yang tidak dikenal jatuh
     * ke seluruh kabupaten — tautan lama tidak pernah berakhir di halaman kosong.
     *
     * `null` hanya bila tabel demografi benar-benar kosong.
     */
    public function ambil(?array $periodeDiminta = null, ?string $wilayahDiminta = null): ?array
    {
        $tersedia = DemografiWilayah::periodeTersedia();
        if ($tersedia === []) {
            return null;
        }
        $periode = ['tahun' => $tersedia[0]['tahun'], 'semester' => $tersedia[0]['semester']];
        foreach ($tersedia as $t) {
            if ($periodeDiminta && $t['tahun'] === $periodeDiminta['tahun'] && $t['semester'] === $periodeDiminta['semester']) {
                $periode = ['tahun' => $t['tahun'], 'semester' => $t['semester']];
            }
        }

        $baris = DemografiWilayah::periode($periode['tahun'], $periode['semester'])
            ->whereIn('level', [DemografiWilayah::LEVEL_KECAMATAN, DemografiWilayah::LEVEL_KELURAHAN])
            ->get(['kategori', 'kode', 'wilayah', 'level', 'parent_kode', 'data']);
        $idx = self::indeks($baris);

        $induk = ['kode' => (string) config('situs.kode'), 'nama' => self::namaKabupaten(), 'tingkat' => 'kabupaten'];
        $daftarWilayah = [$induk];
        foreach ($idx['kec'] as $kode => $nama) {
            $daftarWilayah[] = ['kode' => (string) $kode, 'nama' => $nama, 'tingkat' => 'kecamatan'];
        }
        foreach ($idx['desa'] as $kode => $d) {
            $daftarWilayah[] = ['kode' => (string) $kode, 'nama' => $d['nama'], 'tingkat' => 'desa', 'induk' => $d['induk']];
        }
        $wilayah = collect($daftarWilayah)->firstWhere('kode', trim((string) $wilayahDiminta)) ?? $induk;

        $jk = ($idx['nilai'])('jenis-kelamin', $wilayah);
        $ringkas = [
            'penduduk' => self::jumlahBaris($jk),
            'laki' => self::cariKolom($jk, self::POLA_LAKI),
            'perempuan' => self::cariKolom($jk, self::POLA_PEREMPUAN),
            'kk' => self::jumlahBaris(($idx['nilai'])('kk', $wilayah)),
            'wajibKtp' => self::jumlahBaris(($idx['nilai'])('wajib-ktp', $wilayah)),
        ];

        $kecamatan = [];
        foreach ($idx['kec'] as $kode => $nama) {
            $j = ($idx['kecamatan'])('jenis-kelamin', (string) $kode);
            $kecamatan[] = [
                'kode' => (string) $kode,
                'nama' => $nama,
                'penduduk' => self::jumlahBaris($j),
                'laki' => self::cariKolom($j, self::POLA_LAKI),
                'perempuan' => self::cariKolom($j, self::POLA_PEREMPUAN),
                'kk' => self::jumlahBaris(($idx['kecamatan'])('kk', (string) $kode)),
            ];
        }

        $judul = collect(KategoriDemografi::semua())->pluck('label', 'slug');
        $komposisi = [];
        foreach (self::KATEGORI_KOMPOSISI as $slug) {
            $data = ($idx['nilai'])($slug, $wilayah);
            if (! $data) {
                continue;
            }
            ['butir' => $butir, 'catatan' => $catatan] = self::butirDari($slug, $data);
            if (! $butir) {
                continue;
            }
            $jumlah = array_sum(array_column($butir, 'nilai'));
            // Berkas DKB sebuah kategori bisa tidak mencakup seluruh penduduk.
            // Persentase dari jumlah kategorinya sendiri; selisihnya DISEBUT.
            if ($ringkas['penduduk'] && abs($jumlah - $ringkas['penduduk']) / $ringkas['penduduk'] > 0.005) {
                $catatan[] = 'Jumlah pada kategori ini '.self::angka($jumlah).' jiwa, berbeda dari jumlah penduduk '
                    .self::angka($ringkas['penduduk']).' jiwa — sesuai berkas DKB yang diterima. '
                    .'Persentase dihitung dari jumlah kategori ini.';
            }
            $komposisi[] = [
                'slug' => $slug, 'judul' => $judul[$slug] ?? $slug,
                'butir' => $butir, 'jumlah' => $jumlah, 'catatan' => $catatan,
            ];
        }

        $usia = self::usiaDari(($idx['nilai'])(self::SLUG_USIA, $wilayah));

        // Tren penduduk wilayah terpilih, periode terlama → terbaru.
        $semuaJk = DemografiWilayah::where('kategori', 'jenis-kelamin')
            ->whereIn('level', [DemografiWilayah::LEVEL_KECAMATAN, DemografiWilayah::LEVEL_KELURAHAN])
            ->get(['kategori', 'kode', 'wilayah', 'level', 'parent_kode', 'data', 'tahun', 'semester'])
            ->groupBy(fn ($b) => $b->tahun.'-'.$b->semester);
        $tren = [];
        foreach (array_reverse($tersedia) as $p) {
            $b = $semuaJk->get($p['tahun'].'-'.$p['semester']);
            if (! $b) {
                continue;
            }
            $n = self::jumlahBaris((self::indeks($b)['nilai'])('jenis-kelamin', $wilayah));
            if ($n !== null) {
                $tren[] = [
                    'periode' => ['tahun' => $p['tahun'], 'semester' => $p['semester']],
                    'label' => PeriodeDemografi::label($p['tahun'], $p['semester']),
                    'penduduk' => $n,
                ];
            }
        }
        $iKini = collect($tren)->search(fn ($t) => $t['periode'] == $periode);
        $pembanding = ($iKini !== false && $iKini > 0)
            ? ['label' => $tren[$iKini - 1]['label'], 'penduduk' => $tren[$iKini - 1]['penduduk']]
            : null;

        return [
            'periode' => $periode,
            'labelPeriode' => PeriodeDemografi::label($periode['tahun'], $periode['semester']),
            'periodeTersedia' => array_map(fn ($p) => [
                'kunci' => self::kunciPeriode($p),
                'label' => PeriodeDemografi::label($p['tahun'], $p['semester']),
            ], $tersedia),
            'wilayah' => $wilayah,
            'daftarWilayah' => $daftarWilayah,
            'ringkas' => $ringkas,
            'pembanding' => $pembanding,
            'kecamatan' => $kecamatan,
            'usia' => $usia,
            'komposisi' => $komposisi,
            'tren' => $tren,
        ];
    }

    // ── Format & teks ────────────────────────────────────────────────────────

    public static function namaKabupaten(): string
    {
        return 'Kabupaten '.self::namaCantik((string) config('situs.nama_lengkap'));
    }

    /** "OBA UTARA" → "Oba Utara". */
    public static function namaCantik(string $nama): string
    {
        return mb_convert_case(mb_strtolower($nama), MB_CASE_TITLE);
    }

    public static function judulWilayah(array $w): string
    {
        return match ($w['tingkat']) {
            'kabupaten' => self::namaKabupaten(),
            'kecamatan' => 'Kec. '.self::namaCantik($w['nama']),
            default => self::namaCantik($w['nama']),
        };
    }

    public static function labelTingkat(array $w): string
    {
        return match ($w['tingkat']) {
            'kabupaten' => 'Seluruh wilayah',
            'kecamatan' => 'Kecamatan',
            default => 'Desa / kelurahan',
        };
    }

    public static function angka(int|float|null $n): string
    {
        return $n === null ? '—' : number_format((float) $n, 0, ',', '.');
    }

    public static function desimal(int|float|null $n, int $digit = 1): string
    {
        return $n === null ? '—' : number_format((float) $n, $digit, ',', '.');
    }

    public static function persen(int|float $bagian, int|float $dari): string
    {
        return $dari ? self::desimal($bagian / $dari * 100).'%' : '—';
    }

    public static function formatUkuran(?float $n, string $u): string
    {
        return in_array($u, ['penduduk', 'kk'], true) ? self::angka($n) : self::desimal($n);
    }

    // ── Warna choropleth ─────────────────────────────────────────────────────

    /** Lima kelas, terang → gelap (biru SAIBATIN). Sama dengan island peta. */
    public const SKALA_WARNA = ['#dbe8f5', '#a9c8e8', '#6fa3d6', '#3a78b8', '#1b4b72'];

    public const WARNA_TANPA_DATA = '#f1f3f5';

    public static function warnaUntuk(?float $n, float $min, float $maks): string
    {
        if ($n === null) {
            return self::WARNA_TANPA_DATA;
        }
        if ($maks <= $min) {
            return self::SKALA_WARNA[2];
        }
        $i = min(count(self::SKALA_WARNA) - 1, (int) floor(($n - $min) / ($maks - $min) * count(self::SKALA_WARNA)));

        return self::SKALA_WARNA[$i];
    }
}
