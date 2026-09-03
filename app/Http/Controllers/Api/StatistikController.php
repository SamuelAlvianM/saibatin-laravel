<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemografiWilayah;
use App\Models\JenisPermohonan;
use App\Models\News;
use App\Models\Permohonan;
use App\Models\StaticContent;
use App\Support\Balasan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Statistik ringkas beranda publik.
 *
 * Dua sumber berbeda, jangan tertukar:
 * - **Pelayanan** — agregasi langsung dari `t_permohonan` (angka hidup).
 * - **Kependudukan** — rekap `m_demografi_wilayah` hasil impor Excel Dukcapil,
 *   dengan kartu yang judul/ikon/kolomnya dikonfigurasi admin.
 */
class StatistikController extends Controller
{
    public const KUNCI_KARTU = 'beranda.statistik';

    private const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public function __invoke()
    {
        $kini = Carbon::now();
        $awalBulan = $kini->copy()->startOfMonth();
        $awal6Bulan = $kini->copy()->startOfMonth()->subMonths(5);

        $kartuKonfig = $this->konfigurasiKartu();
        $kategori = collect($kartuKonfig)->pluck('kategori')->filter()->unique()->values();

        // ── Pelayanan ────────────────────────────────────────────────────────
        $pelayanan = [
            'total' => Permohonan::count(),
            'selesai' => Permohonan::where('status', Permohonan::STATUS_SELESAI)->count(),
            'aktif' => Permohonan::whereIn('status', [Permohonan::STATUS_MENUNGGU, Permohonan::STATUS_DIPROSES])->count(),
            'bulanIni' => Permohonan::where('created_at', '>=', $awalBulan)->count(),
        ];

        // Empat layanan terpopuler.
        $teratas = Permohonan::selectRaw('jenis_id, COUNT(*) c')
            ->groupBy('jenis_id')->orderByDesc('c')->take(4)->get();
        $namaJenis = JenisPermohonan::whereIn('id', $teratas->pluck('jenis_id'))->pluck('nama', 'id');
        $pelayanan['topJenis'] = $teratas->map(fn ($r) => [
            'nama' => $namaJenis[$r->jenis_id] ?? 'Lainnya',
            'count' => (int) $r->c,
        ])->all();

        // Tren 6 bulan. Dikelompokkan di SQL, bukan dengan menarik seluruh baris
        // ke PHP — produksi punya 11.902 permohonan dan angkanya terus naik.
        $trenDb = Permohonan::selectRaw("DATE_FORMAT(created_at,'%Y-%m') ym, COUNT(*) c")
            ->where('created_at', '>=', $awal6Bulan)
            ->groupBy('ym')->pluck('c', 'ym');

        $pelayanan['trend6'] = collect(range(0, 5))->map(function ($i) use ($awal6Bulan, $trenDb) {
            $b = $awal6Bulan->copy()->addMonths($i);

            return [
                'label' => self::BULAN[$b->month - 1],
                'count' => (int) ($trenDb[$b->format('Y-m')] ?? 0),
            ];
        })->all();

        // ── Kependudukan ─────────────────────────────────────────────────────
        $barisDemografi = $kategori->isEmpty()
            ? collect()
            : DemografiWilayah::whereIn('kategori', $kategori)
                ->whereIn('level', [DemografiWilayah::LEVEL_KECAMATAN, DemografiWilayah::LEVEL_KELURAHAN])
                ->get(['kategori', 'level', 'data']);

        return Balasan::ok([
            'pelayanan' => $pelayanan,
            'totalBerita' => News::terbit()->count(),
            'kartuDemografi' => collect($kartuKonfig)->map(function ($k) use ($barisDemografi) {
                /*
                 * 🔴 `null` = DATANYA BELUM ADA, dan itu berbeda dari nol.
                 * Nol adalah pernyataan — "kabupaten ini punya 0 kepala
                 * keluarga". Beranda menampilkan "—" untuk null, bukan angka.
                 */
                $nilai = ($k['kategori'] && $k['kolom'])
                    ? $this->jumlahKolom($barisDemografi, $k['kategori'], $k['kolom'])
                    : null;

                $badge = null;
                if (! empty($k['badgeKolom']) && $nilai !== null) {
                    $dasar = $this->jumlahKolom($barisDemografi, $k['kategori'], $k['badgeKolom']);
                    $badge = ($dasar !== null && $dasar > 0) ? round($nilai / $dasar * 100).'%' : null;
                }

                return [
                    'title' => $k['title'],
                    'icon' => $k['icon'],
                    'kategori' => $k['kategori'],
                    'kolom' => $k['kolom'],
                    // Nama preset, bukan kelas Tailwind: kelasnya harus literal
                    // agar ter-scan, jadi pemetaannya hidup di sisi klien
                    // (`lib/statistik-kartu.js`).
                    'warna' => $k['warna'],
                    'badge' => $badge,
                    'value' => $nilai,
                ];
            })->all(),
            'periodeKependudukan' => env('DKB_PERIODE', 'DKB Semester II 2024'),
        ]);
    }

    /**
     * Jumlahkan satu kolom untuk satu kategori.
     *
     * 🔴 Dihitung dari baris **pekon (level 5)** bila ada, jatuh ke kecamatan
     * (level 4) bila belum. Kalau keduanya dijumlah sekaligus, angkanya jadi
     * DUA KALI LIPAT — kecamatan adalah total pekon di bawahnya.
     */
    private function jumlahKolom($baris, string $kategori, string $kolom): ?int
    {
        $sekategori = $baris->where('kategori', $kategori);
        $pekon = $sekategori->where('level', DemografiWilayah::LEVEL_KELURAHAN);
        $dipakai = $pekon->isNotEmpty() ? $pekon : $sekategori;

        if ($dipakai->isEmpty()) {
            return null;
        }

        $nyata = $this->kolomNyata(array_keys($dipakai->first()->data ?? []), $kolom);

        if ($nyata === null) {
            return null;
        }

        return (int) $dipakai->sum(fn ($d) => (float) ($d->data[$nyata] ?? 0));
    }

    /**
     * Ejaan header yang menyebut KUANTITAS YANG SAMA di berkas agregat Dukcapil.
     *
     * 🔴 KENAPA PERLU. Importer mengambil nama kolom apa adanya dari header
     * Excel, dan header itu tidak seragam antar berkas maupun antar kabupaten:
     * berkas KK satu daerah menulis `KK_JML`, daerah lain `JML`, daerah lain
     * lagi `Total`. Kartu beranda menuntut satu nama pasti, jadi kartu yang
     * benar pun membaca kolom yang tidak ada — lalu menampilkan **0**.
     *
     * ⚠️ INI PENYETARAAN EJAAN, BUKAN TEBAKAN. Yang disetarakan hanya nama
     * yang benar-benar menyebut hal sama: "jumlah seluruhnya".
     *
     * 🔴 `JML_WKTP` SENGAJA TIDAK IKUT. "Sudah rekam KTP-el" kuantitas yang
     * BERBEDA dari "jumlah wajib KTP". Menyetarakannya akan menampilkan seolah
     * SELURUH wajib KTP sudah merekam — angka resmi yang salah, dan jauh lebih
     * berbahaya daripada kolom yang kosong.
     */
    private const SINONIM_JUMLAH = ['JML', 'JUMLAH', 'TOTAL', 'KK_JML', 'JML_KK'];

    /**
     * Cari nama kolom yang benar-benar ada di sebuah baris data.
     *
     * Urutannya menyempit: persis → abaikan besar-kecil huruf → sinonim
     * "jumlah". `null` bila tidak ketemu — dan itu HARUS dibedakan dari nol
     * oleh pemanggilnya.
     */
    private function kolomNyata(array $kunciData, string $kolom): ?string
    {
        if ($kolom === '') {
            return null;
        }
        if (in_array($kolom, $kunciData, true)) {
            return $kolom;
        }

        $naik = strtoupper($kolom);

        foreach ($kunciData as $k) {
            if (strtoupper($k) === $naik) {
                return $k;
            }
        }

        if (in_array($naik, self::SINONIM_JUMLAH, true)) {
            foreach ($kunciData as $k) {
                if (in_array(strtoupper($k), self::SINONIM_JUMLAH, true)) {
                    return $k;
                }
            }
        }

        return null;
    }

    /**
     * Konfigurasi kartu beranda dari `t_static_contents`.
     * Bentuk minimal supaya endpoint tetap jalan sebelum editor kartunya
     * dibangun di Fase 6.
     */
    private function konfigurasiKartu(): array
    {
        $konten = StaticContent::where('kunci', self::KUNCI_KARTU)->value('konten');
        $kartu = is_array($konten) ? ($konten['kartu'] ?? []) : [];

        // Belum pernah diatur petugas → pakai susunan bawaan, bukan kosong.
        // Beranda yang tampil tanpa satu kartu pun terbaca sebagai rusak,
        // padahal yang terjadi cuma "belum disentuh siapa-siapa".
        if (! is_array($kartu) || $kartu === []) {
            $kartu = config('konten.kartu_beranda', []);
        }

        return collect(is_array($kartu) ? $kartu : [])
            ->filter(fn ($k) => is_array($k))
            ->map(fn ($k) => [
                'title' => (string) ($k['title'] ?? ''),
                'icon' => (string) ($k['icon'] ?? ''),
                'kategori' => (string) ($k['kategori'] ?? ''),
                'kolom' => (string) ($k['kolom'] ?? ''),
                'warna' => (string) ($k['warna'] ?? 'biru'),
                'badgeKolom' => (string) ($k['badgeKolom'] ?? ''),
            ])->values()->all();
    }
}
