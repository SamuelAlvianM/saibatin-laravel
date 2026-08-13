<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemografiWilayah;
use App\Support\Balasan;
use Illuminate\Http\Request;

/**
 * Data demografi publik — port `app/api/demografi/route.ts`.
 *
 *   ?kategori=jenis-kelamin              → daftar kecamatan (level 4)
 *   ?kategori=jenis-kelamin&parent=KODE  → daftar pekon di kecamatan itu
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

        if ($parent !== '') {
            $rows = DemografiWilayah::where('kategori', $kategori)
                ->where('level', DemografiWilayah::LEVEL_KELURAHAN)
                ->where('parent_kode', $parent)
                ->orderBy('kode')
                ->get(['kode', 'wilayah', 'data']);

            return Balasan::ok([
                'kolom' => $rows->isNotEmpty() ? array_keys($rows->first()->data ?? []) : [],
                'items' => $rows,
            ]);
        }

        $kecamatan = DemografiWilayah::where('kategori', $kategori)
            ->where('level', DemografiWilayah::LEVEL_KECAMATAN)
            ->orderBy('kode')
            ->get(['kode', 'wilayah', 'data']);

        $pekon = DemografiWilayah::where('kategori', $kategori)
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

        return Balasan::ok(['kolom' => $kolom, 'items' => $items]);
    }
}
