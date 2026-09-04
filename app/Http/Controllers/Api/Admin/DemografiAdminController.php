<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemografiWilayah;
use App\Services\CatatanAktivitas;
use App\Services\DemografiExcel;
use App\Support\Balasan;
use App\Support\PeriodeDemografi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Data demografi — port `app/api/admin/demografi` beserta
 * `import`, `export`, dan `parse`.
 *
 * Sumbernya berkas Excel agregat Dukcapil; setiap impor **mengganti total**
 * kategori yang bersangkutan (impor = kebenaran terbaru), sama seperti aslinya.
 */
class DemografiAdminController extends Controller
{
    public function __construct(
        private readonly DemografiExcel $excel,
        private readonly CatatanAktivitas $log,
    ) {}

    /** Seluruh baris (kecamatan + pekon) satu kategori untuk editor manual. */
    public function index(Request $request)
    {
        $kategori = trim((string) $request->query('kategori'));

        if (! $this->sah($kategori)) {
            return Balasan::gagal(['Kategori tidak dikenal']);
        }

        $periode = $this->periodeDari($request);
        if ($periode instanceof \Illuminate\Http\JsonResponse) {
            return $periode;
        }

        $rows = DemografiWilayah::where('kategori', $kategori)
            ->periode($periode['tahun'], $periode['semester'])
            ->orderBy('kode')
            ->get(['kode', 'wilayah', 'level', 'parent_kode', 'data'])
            ->map(fn ($r) => [
                'kode' => $r->kode,
                'wilayah' => $r->wilayah,
                'level' => $r->level,
                'parentKode' => $r->parent_kode,
                'data' => $r->data,
            ]);

        return Balasan::ok([
            'kolom' => $rows->isNotEmpty() ? array_keys($rows->first()['data'] ?? []) : [],
            'rows' => $rows,
            'periode' => $periode,
            // Seluruh periode yang punya data, supaya editor bisa menampilkan
            // pemilihnya tanpa permintaan kedua.
            'periodeTersedia' => DemografiWilayah::periodeTersedia(),
        ]);
    }

    /** Simpan (ganti total) satu kategori dari editor manual. */
    public function simpan(Request $request)
    {
        $kategori = trim((string) $request->input('kategori'));
        $mentah = $request->input('rows');

        if (! $this->sah($kategori)) {
            return Balasan::gagal(['Kategori tidak dikenal']);
        }
        if (! is_array($mentah)) {
            return Balasan::gagal(['Data tidak valid']);
        }

        $periode = $this->periodeDari($request, sumber: 'input');
        if ($periode instanceof \Illuminate\Http\JsonResponse) {
            return $periode;
        }

        $rows = $this->normalkan($mentah);
        $this->gantiTotal($kategori, $rows, $periode);

        $labelPeriode = PeriodeDemografi::label($periode['tahun'], $periode['semester']);

        $this->log->catat(
            $request->user(), 'UBAH', 'Demografi',
            "Menyimpan data demografi kategori {$kategori} {$labelPeriode} (".count($rows).' baris)',
            $kategori, $request,
        );

        return Balasan::ok(['tersimpan' => count($rows)], ['Data demografi disimpan']);
    }

    /** Tanpa `?kategori` → hapus SEMUA kategori. */
    public function hapus(Request $request)
    {
        $kategori = trim((string) $request->query('kategori'));

        if ($kategori !== '' && ! $this->sah($kategori)) {
            return Balasan::gagal(['Kategori tidak dikenal']);
        }

        /*
         * Periode di sini OPSIONAL. Tanpa periode = hapus seluruh periode
         * kategori itu — perilaku lama dipertahankan supaya tombol "Hapus
         * Semua" tetap berarti apa yang tertulis.
         */
        $periode = $this->periodeDari($request, wajib: false);
        if ($periode instanceof \Illuminate\Http\JsonResponse) {
            return $periode;
        }

        $jumlah = DemografiWilayah::query()
            ->when($kategori !== '', fn ($w) => $w->where('kategori', $kategori))
            ->when($periode, fn ($w) => $w->periode($periode['tahun'], $periode['semester']))
            ->delete();

        $this->log->catat(
            $request->user(), 'HAPUS', 'Demografi',
            $kategori !== ''
                ? "Menghapus data demografi kategori {$kategori}".
                    ($periode ? ' '.PeriodeDemografi::label($periode['tahun'], $periode['semester']) : '').
                    " ({$jumlah} baris)"
                : "Menghapus SEMUA data demografi ({$jumlah} baris)",
            $kategori !== '' ? $kategori : 'semua', $request,
        );

        return Balasan::ok(['dihapus' => $jumlah], [
            $kategori !== ''
                ? "Data kategori dihapus ({$jumlah} baris)"
                : "Semua data demografi dihapus ({$jumlah} baris)",
        ]);
    }

    /** Impor satu berkas Excel → mengganti data kategori tersebut. */
    public function impor(Request $request)
    {
        $kategori = trim((string) $request->input('kategori'));
        $berkas = $request->file('file');

        if (! $this->sah($kategori)) {
            return Balasan::gagal(['Kategori tidak dikenal']);
        }

        // Dibaca sebelum berkasnya disentuh: menolak lebih awal lebih murah
        // daripada mengurai 10 MB Excel lalu baru menyadari periodenya salah.
        $periode = $this->periodeDari($request, sumber: 'input');
        if ($periode instanceof \Illuminate\Http\JsonResponse) {
            return $periode;
        }

        if (! $berkas || ! $berkas->isValid()) {
            return Balasan::gagal(['Tidak ada file yang dikirim']);
        }
        if (strtolower($berkas->getClientOriginalExtension()) !== 'xlsx') {
            return Balasan::gagal(['File harus berformat .xlsx']);
        }
        if ($berkas->getSize() > config('demografi.maks_unggah')) {
            return Balasan::gagal(['Ukuran file maksimal 10 MB']);
        }

        try {
            $hasil = $this->excel->baca($berkas->getRealPath());
        } catch (\Throwable $e) {
            return Balasan::gagal([$e->getMessage() ?: 'Gagal membaca file Excel']);
        }

        if ($hasil['rows'] === []) {
            return Balasan::gagal(['Tidak ada baris kecamatan/desa yang terbaca dari file']);
        }

        $this->gantiTotal($kategori, $hasil['rows'], $periode);

        $labelPeriode = PeriodeDemografi::label($periode['tahun'], $periode['semester']);

        $this->log->catat(
            $request->user(), 'IMPOR', 'Demografi',
            "Impor Excel demografi kategori {$kategori} {$labelPeriode}: {$hasil['kecamatan']} kecamatan, {$hasil['pekon']} desa",
            $kategori, $request,
        );

        return Balasan::ok(
            [
                'kecamatan' => $hasil['kecamatan'],
                'pekon' => $hasil['pekon'],
                'kolom' => $hasil['kolom'],
                'periode' => $periode,
            ],
            ["Import {$labelPeriode} berhasil: {$hasil['kecamatan']} kecamatan, {$hasil['pekon']} desa"],
        );
    }

    /**
     * Baca beberapa berkas sekaligus TANPA menyimpan.
     *
     * Ini yang membuat impor berlapis aman: kalau dua berkas (atau berkas vs
     * data tersimpan) memberi angka berbeda untuk wilayah yang sama, barisnya
     * tidak diam-diam ditimpa — dikembalikan sebagai `conflicts` supaya petugas
     * yang memilih.
     */
    public function pratinjau(Request $request)
    {
        $kategori = trim((string) $request->input('kategori'));
        $berkas = $request->file('files', []);

        if (! $this->sah($kategori)) {
            return Balasan::gagal(['Kategori tidak dikenal']);
        }

        $periode = $this->periodeDari($request, sumber: 'input');
        if ($periode instanceof \Illuminate\Http\JsonResponse) {
            return $periode;
        }

        if ($berkas === [] || $berkas === null) {
            return Balasan::gagal(['Tidak ada file yang dikirim']);
        }

        $kolomUrut = [];
        $terbaca = [];

        foreach ($berkas as $f) {
            $nama = $f->getClientOriginalName();

            if (strtolower($f->getClientOriginalExtension()) !== 'xlsx') {
                return Balasan::gagal(["\"{$nama}\" bukan file .xlsx"]);
            }
            if ($f->getSize() > config('demografi.maks_unggah')) {
                return Balasan::gagal(["\"{$nama}\" melebihi 10 MB"]);
            }

            try {
                $p = $this->excel->baca($f->getRealPath());
            } catch (\Throwable $e) {
                return Balasan::gagal(["Gagal membaca \"{$nama}\": ".$e->getMessage()]);
            }

            $terbaca[] = ['nama' => $nama, 'rows' => $p['rows']];
            foreach ($p['kolom'] as $k) {
                if (! in_array($k, $kolomUrut, true)) {
                    $kolomUrut[] = $k;
                }
            }
        }

        /*
         * 🔴 Dibandingkan dengan periode YANG SAMA. Kalau tidak, berkas
         * semester I 2025 akan diadu dengan angka semester II 2024 dan setiap
         * wilayah muncul sebagai "konflik" — padahal keduanya memang berbeda,
         * dan memang seharusnya berbeda.
         */
        $tersimpan = DemografiWilayah::where('kategori', $kategori)
            ->periode($periode['tahun'], $periode['semester'])
            ->get(['kode', 'data'])
            ->keyBy('kode');

        // Kumpulkan variasi nilai per kode wilayah.
        $perKode = [];
        foreach ($terbaca as $berkasTerbaca) {
            foreach ($berkasTerbaca['rows'] as $r) {
                $kode = $r['kode'];
                $perKode[$kode] ??= [
                    'wilayah' => $r['wilayah'],
                    'level' => $r['level'],
                    'parentKode' => $r['parent_kode'],
                    'varian' => [],
                ];

                $tanda = $this->tandaTangan($r['data']);

                if (isset($perKode[$kode]['varian'][$tanda])) {
                    $label = &$perKode[$kode]['varian'][$tanda]['label'];
                    if (! str_contains($label, $berkasTerbaca['nama'])) {
                        $label .= ', '.$berkasTerbaca['nama'];
                    }
                    unset($label);
                } else {
                    $perKode[$kode]['varian'][$tanda] = ['label' => $berkasTerbaca['nama'], 'data' => $r['data']];
                }
            }
        }

        $rows = [];
        $konflik = [];
        $berubah = 0;
        $tetap = 0;

        foreach ($perKode as $kode => $agg) {
            // 🔴 PHP mengubah kunci array yang berupa angka menjadi INTEGER —
            // "181301" jadi 181301. Dikembalikan ke string di sini supaya
            // klien menerima kode wilayah apa adanya (dan nol di depan, kalau
            // ada, tidak hilang diam-diam).
            $kode = (string) $kode;
            $varian = array_values($agg['varian']);
            $lama = $tersimpan[$kode]->data ?? null;

            if (count($varian) === 1) {
                $satu = $varian[0];
                if ($lama !== null) {
                    $this->tandaTangan($lama) !== $this->tandaTangan($satu['data']) ? $berubah++ : $tetap++;
                }
                $rows[] = [
                    'kode' => $kode,
                    'wilayah' => $agg['wilayah'],
                    'level' => $agg['level'],
                    'parentKode' => $agg['parentKode'],
                    'data' => $satu['data'],
                ];

                continue;
            }

            $pilihan = $varian;
            if ($lama !== null && ! collect($varian)->contains(fn ($v) => $this->tandaTangan($v['data']) === $this->tandaTangan($lama))) {
                $pilihan[] = ['label' => 'Data tersimpan', 'data' => $lama];
            }

            $konflik[] = [
                'kode' => $kode,
                'wilayah' => $agg['wilayah'],
                'level' => $agg['level'],
                'parentKode' => $agg['parentKode'],
                'options' => $pilihan,
            ];
        }

        return Balasan::ok([
            'kolom' => $kolomUrut,
            'rows' => $rows,
            'conflicts' => $konflik,
            'ringkas' => [
                'totalWilayah' => count($perKode),
                'konflik' => count($konflik),
                'berubah' => $berubah,
                'tetap' => $tetap,
                'file' => count($berkas),
            ],
        ]);
    }

    public function ekspor(Request $request)
    {
        $kategori = trim((string) $request->query('kategori'));

        if ($kategori !== '' && ! $this->sah($kategori)) {
            return Balasan::gagal(['Kategori tidak dikenal']);
        }

        /*
         * 🔴 Ekspor SELALU satu periode, tidak pernah "semua".
         *
         * Lembar Excel-nya berkolom IDEM/KODE/WILAYAH tanpa kolom periode —
         * bentuk yang sama dengan yang dibaca importer, supaya berkasnya bisa
         * diunggah balik. Menuang dua semester ke lembar yang sama berarti
         * satu KODE muncul dua kali dengan angka berbeda dan tidak ada apa pun
         * yang membedakannya. Tanpa periode = periode terbaru.
         */
        $periode = $this->periodeDari($request);
        if ($periode instanceof \Illuminate\Http\JsonResponse) {
            return $periode;
        }

        $buku = $this->excel->tulis($kategori !== '' ? $kategori : null, $periode);

        if (! $buku) {
            return Balasan::gagal(['Belum ada data untuk diekspor'], 404);
        }

        /*
         * ⚠️ Periodenya masuk ke NAMA BERKAS. Berkas ekspor beredar lewat
         * WhatsApp dan folder bersama; tanpa periode di namanya, dua semester
         * berakhir sebagai dua "demografi-kk.xlsx" yang tak bisa dibedakan.
         */
        $tanda = '-'.$periode['tahun'].'-sem'.$periode['semester'];

        return $this->excel->keUnduhan(
            $buku,
            $kategori !== ''
                ? "demografi-{$kategori}{$tanda}.xlsx"
                : "demografi-semua{$tanda}.xlsx",
        );
    }

    // ── Pembantu ────────────────────────────────────────────────────────────

    private function sah(string $kategori): bool
    {
        return in_array($kategori, array_column(config('demografi.kategori'), 'slug'), true);
    }

    /**
     * Normalisasi baris dari editor manual.
     * Kode 6 digit = kecamatan, 10 digit = pekon; duplikat dibuang.
     */
    private function normalkan(array $mentah): array
    {
        $lihat = [];
        $rows = [];

        foreach ($mentah as $r) {
            $kode = preg_replace('/\D/', '', (string) ($r['kode'] ?? ''));
            $wilayah = trim((string) ($r['wilayah'] ?? ''));

            if ($wilayah === '' || $kode === '' || isset($lihat[$kode])) {
                continue;
            }
            $lihat[$kode] = true;

            $level = strlen($kode) === 10 ? 5 : 4;
            $data = [];
            foreach ((array) ($r['data'] ?? []) as $k => $v) {
                $data[$k] = (int) $v;
            }

            $rows[] = [
                'kode' => $kode,
                'wilayah' => $wilayah,
                'level' => $level,
                'parent_kode' => $level === 5 ? substr($kode, 0, 6) : null,
                'data' => $data,
            ];
        }

        return $rows;
    }

    /**
     * Ganti total isi satu kategori PADA SATU PERIODE, dalam satu transaksi.
     * Kalau penulisan gagal di tengah, penghapusannya ikut dibatalkan; tanpa
     * itu kategori bisa berakhir kosong setelah impor yang gagal.
     *
     * 🔴 Penghapusannya WAJIB disaring periode. Tanpa `->periode(...)` impor
     * semester baru akan menghapus semester lama — persis kerusakan yang
     * seluruh perubahan ini ada untuk mencegahnya.
     *
     * @param  array{tahun:int, semester:int}  $periode
     */
    private function gantiTotal(string $kategori, array $rows, array $periode): void
    {
        DB::transaction(function () use ($kategori, $rows, $periode) {
            DemografiWilayah::where('kategori', $kategori)
                ->periode($periode['tahun'], $periode['semester'])
                ->delete();

            foreach (array_chunk($rows, 200) as $bagian) {
                DemografiWilayah::insert(array_map(fn ($r) => [
                    'kategori' => $kategori,
                    'tahun' => $periode['tahun'],
                    'semester' => $periode['semester'],
                    'kode' => $r['kode'],
                    'wilayah' => $r['wilayah'],
                    'level' => $r['level'],
                    'parent_kode' => $r['parent_kode'],
                    'data' => json_encode($r['data']),
                    'updated_at' => now(),
                ], $bagian));
            }
        });
    }

    /**
     * Baca periode dari permintaan.
     *
     * - `$wajib = true`  : tanpa periode → jatuh ke periode TERBARU yang ada
     *   datanya, dan bila tabelnya masih kosong, ke periode bawaan. Editor
     *   lama yang belum mengirim periode tetap bekerja, tidak menabrak.
     * - `$wajib = false` : tanpa periode → `null`, artinya "semua periode".
     *
     * Nilai yang ada tapi tidak masuk akal SELALU ditolak, bahkan saat tidak
     * wajib: tahun 1900 atau semester 3 hampir pasti salah ketik, dan menerima
     * diam-diam berarti menyimpan data ke periode yang takkan pernah dicari
     * siapa pun.
     *
     * @return array{tahun:int, semester:int}|null|\Illuminate\Http\JsonResponse
     */
    private function periodeDari(Request $request, bool $wajib = true, string $sumber = 'query')
    {
        $tahun = $sumber === 'input' ? $request->input('tahun') : $request->query('tahun');
        $semester = $sumber === 'input' ? $request->input('semester') : $request->query('semester');

        $adaTahun = $tahun !== null && $tahun !== '';
        $adaSemester = $semester !== null && $semester !== '';

        if ($adaTahun !== $adaSemester) {
            return Balasan::gagal(['Tahun dan semester harus diisi bersamaan']);
        }

        if (! $adaTahun) {
            if (! $wajib) {
                return null;
            }

            return DemografiWilayah::periodeTerbaru() ?? [
                'tahun' => PeriodeDemografi::TAHUN_BAWAAN,
                'semester' => PeriodeDemografi::SEMESTER_BAWAAN,
            ];
        }

        if (! PeriodeDemografi::tahunSah($tahun)) {
            return Balasan::gagal(['Tahun tidak masuk akal (minimal '.PeriodeDemografi::TAHUN_MIN.')']);
        }
        if (! PeriodeDemografi::semesterSah($semester)) {
            return Balasan::gagal(['Semester hanya boleh 1 atau 2']);
        }

        return ['tahun' => (int) $tahun, 'semester' => (int) $semester];
    }

    private function tandaTangan(array $data): string
    {
        ksort($data);

        return json_encode($data);
    }
}
