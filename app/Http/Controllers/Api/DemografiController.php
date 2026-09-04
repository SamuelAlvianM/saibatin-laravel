<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemografiWilayah;
use App\Support\Balasan;
use App\Support\PeriodeDemografi;
use Illuminate\Http\Request;

/**
 * Data demografi publik — port `app/api/demografi/route.ts`.
 *
 *   ?kategori=jenis-kelamin                        → kecamatan, periode terbaru
 *   ?kategori=jenis-kelamin&parent=KODE            → pekon di kecamatan itu
 *   &tahun=2025&semester=1                         → periode tertentu
 *
 * 🔴 SELALU satu periode. Tanpa penyaringan, angka dua semester ikut terjumlah
 * dan tabelnya menampilkan penduduk dua kali lipat — kesalahan yang tak
 * mungkin terjadi sebelum tabel ini punya dimensi waktu, dan kini mungkin.
 *
 * 🔴 Angka kecamatan adalah **penjumlahan seluruh pekon** di bawahnya, bukan
 * baris kecamatan dari Excel. Baris level 4 hanya dipakai sebagai sumber nama
 * dan sebagai cadangan bila data pekonnya belum diimpor — kalau tidak, tabel
 * ringkasan dan tabel rinciannya bisa menunjukkan dua angka berbeda.
 */
class DemografiController extends Controller
{
    public function __invoke(Request $request)
    {
        $kategori = trim((string) $request->query('kategori'));
        $parent = trim((string) $request->query('parent'));

        if (! in_array($kategori, array_column(config('demografi.kategori'), 'slug'), true)) {
            return Balasan::gagal(['Kategori tidak dikenal']);
        }

        $tahun = $request->query('tahun');
        $semester = $request->query('semester');

        if ($tahun !== null && $semester !== null && $tahun !== '' && $semester !== '') {
            if (! PeriodeDemografi::tahunSah($tahun) || ! PeriodeDemografi::semesterSah($semester)) {
                return Balasan::gagal(['Periode tidak dikenal']);
            }
            $periode = ['tahun' => (int) $tahun, 'semester' => (int) $semester];
        } else {
            $periode = DemografiWilayah::periodeTerbaru();
        }

        // Tabel masih kosong sama sekali — jawab kosong, jangan menebak periode.
        if ($periode === null) {
            return Balasan::ok([
                'kolom' => [],
                'items' => [],
                'periode' => null,
                'periodeTersedia' => [],
            ]);
        }

        $saring = fn ($q) => $q->periode($periode['tahun'], $periode['semester']);

        if ($parent !== '') {
            $rows = DemografiWilayah::where('kategori', $kategori)
                ->tap($saring)
                ->where('level', DemografiWilayah::LEVEL_KELURAHAN)
                ->where('parent_kode', $parent)
                ->orderBy('kode')
                ->get(['kode', 'wilayah', 'data']);

            return Balasan::ok([
                'kolom' => $rows->isNotEmpty() ? array_keys($rows->first()->data ?? []) : [],
                'items' => $rows,
                'periode' => $periode,
            ]);
        }

        $kecamatan = DemografiWilayah::where('kategori', $kategori)
            ->tap($saring)
            ->where('level', DemografiWilayah::LEVEL_KECAMATAN)
            ->orderBy('kode')
            ->get(['kode', 'wilayah', 'data']);

        $pekon = DemografiWilayah::where('kategori', $kategori)
            ->tap($saring)
            ->where('level', DemografiWilayah::LEVEL_KELURAHAN)
            ->orderBy('kode')
            ->get(['parent_kode', 'data']);

        $kolom = array_keys(($kecamatan->first()?->data ?? $pekon->first()?->data) ?? []);

        $jumlah = [];
        $cacah = [];
        foreach ($pekon as $p) {
            $induk = $p->parent_kode;
            if (blank($induk)) {
                continue;
            }
            foreach ((array) $p->data as $k => $v) {
                $jumlah[$induk][$k] = ($jumlah[$induk][$k] ?? 0) + (int) $v;
            }
            $cacah[$induk] = ($cacah[$induk] ?? 0) + 1;
        }

        $items = $kecamatan->map(fn ($k) => [
            'kode' => $k->kode,
            'wilayah' => $k->wilayah,
            'data' => $jumlah[$k->kode] ?? $k->data,
            'jumlahPekon' => $cacah[$k->kode] ?? 0,
        ])->all();

        // Kecamatan yang hanya muncul lewat pekonnya (baris level 4 tidak ada).
        $dikenal = $kecamatan->pluck('kode')->flip();
        foreach ($jumlah as $kode => $data) {
            if (! $dikenal->has($kode)) {
                $items[] = [
                    'kode' => $kode,
                    'wilayah' => "Kecamatan {$kode}",
                    'data' => $data,
                    'jumlahPekon' => $cacah[$kode] ?? 0,
                ];
            }
        }

        usort($items, fn ($a, $b) => strcmp($a['kode'], $b['kode']));

        return Balasan::ok([
            'kolom' => $kolom,
            'items' => $items,
            'periode' => $periode,
            // Semua periode yang punya data, supaya warga bisa berpindah
            // tanpa permintaan kedua.
            'periodeTersedia' => DemografiWilayah::periodeTersedia($kategori),
        ]);
    }
}
