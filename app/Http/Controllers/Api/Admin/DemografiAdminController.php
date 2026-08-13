<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemografiWilayah;
use App\Services\CatatanAktivitas;
use App\Services\DemografiExcel;
use App\Support\Balasan;
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

        $rows = DemografiWilayah::where('kategori', $kategori)
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

        $rows = $this->normalkan($mentah);
        $this->gantiTotal($kategori, $rows);

        $this->log->catat(
            $request->user(), 'UBAH', 'Demografi',
            "Menyimpan data demografi kategori {$kategori} (".count($rows).' baris)',
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

        $jumlah = DemografiWilayah::query()
            ->when($kategori !== '', fn ($w) => $w->where('kategori', $kategori))
            ->delete();

        $this->log->catat(
            $request->user(), 'HAPUS', 'Demografi',
            $kategori !== ''
                ? "Menghapus data demografi kategori {$kategori} ({$jumlah} baris)"
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

        $this->gantiTotal($kategori, $hasil['rows']);

        $this->log->catat(
            $request->user(), 'IMPOR', 'Demografi',
            "Impor Excel demografi kategori {$kategori}: {$hasil['kecamatan']} kecamatan, {$hasil['pekon']} desa",
            $kategori, $request,
        );

        return Balasan::ok(
            ['kecamatan' => $hasil['kecamatan'], 'pekon' => $hasil['pekon'], 'kolom' => $hasil['kolom']],
            ["Import berhasil: {$hasil['kecamatan']} kecamatan, {$hasil['pekon']} desa"],
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

        $tersimpan = DemografiWilayah::where('kategori', $kategori)
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

        $buku = $this->excel->tulis($kategori !== '' ? $kategori : null);

        if (! $buku) {
            return Balasan::gagal(['Belum ada data untuk diekspor'], 404);
        }

        return $this->excel->keUnduhan(
            $buku,
            $kategori !== '' ? "demografi-{$kategori}.xlsx" : 'demografi-semua.xlsx',
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
     * Ganti total isi satu kategori dalam SATU transaksi — hapus lalu tulis.
     * Kalau penulisan gagal di tengah, penghapusannya ikut dibatalkan; tanpa
     * itu kategori bisa berakhir kosong setelah impor yang gagal.
     */
    private function gantiTotal(string $kategori, array $rows): void
    {
        DB::transaction(function () use ($kategori, $rows) {
            DemografiWilayah::where('kategori', $kategori)->delete();

            foreach (array_chunk($rows, 200) as $bagian) {
                DemografiWilayah::insert(array_map(fn ($r) => [
                    'kategori' => $kategori,
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

    private function tandaTangan(array $data): string
    {
        ksort($data);

        return json_encode($data);
    }
}
