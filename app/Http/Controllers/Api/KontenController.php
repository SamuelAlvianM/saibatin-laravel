<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\News;
use App\Support\Balasan;
use Illuminate\Http\Request;

/** Konten publik yang hanya dibaca: berita & galeri. */
class KontenController extends Controller
{
    /** `?page=&limit=&kategori=&q=` — hanya berita yang sudah terbit. */
    public function berita(Request $request)
    {
        $limit = min(50, max(1, (int) $request->query('limit', 9)));

        $q = News::terbit()->latest('created_at');

        if ($kategori = $request->query('kategori')) {
            $q->where('kategori', $kategori);
        }
        if ($cari = trim((string) $request->query('q'))) {
            // Pencarian sengaja hanya judul + ringkasan. `konten` bertipe
            // LONGTEXT tanpa indeks fulltext; LIKE di sana memindai 67 baris
            // sekarang, tapi akan jadi beban begitu beritanya ribuan.
            $q->where(fn ($w) => $w->where('judul', 'like', "%{$cari}%")
                ->orWhere('ringkasan', 'like', "%{$cari}%"));
        }

        $hal = $q->paginate($limit, ['id', 'judul', 'slug', 'kategori', 'ringkasan', 'gambar', 'penulis', 'created_at']);

        return Balasan::ok([
            'items' => $hal->items(),
            'total' => $hal->total(),
            'page' => $hal->currentPage(),
            'lastPage' => $hal->lastPage(),
        ]);
    }

    public function beritaDetail(string $slug)
    {
        $berita = News::terbit()->where('slug', $slug)->first();

        if (! $berita) {
            return Balasan::gagal(['Berita tidak ditemukan'], 404);
        }

        return Balasan::ok([
            'berita' => $berita,
            // Tiga berita lain untuk blok "berita lainnya" di halaman detail.
            'lainnya' => News::terbit()->where('id', '!=', $berita->id)
                ->latest('created_at')->take(3)
                ->get(['judul', 'slug', 'gambar', 'created_at']),
        ]);
    }

    /** `?kategori=BUPATI|PELAYANAN` */
    public function galeri(Request $request)
    {
        $limit = min(50, max(1, (int) $request->query('limit', 20)));

        $q = Gallery::query()->latest('created_at');

        if ($kategori = $request->query('kategori')) {
            $q->where('kategori', strtoupper($kategori));
        }

        $hal = $q->paginate($limit);

        return Balasan::ok([
            'items' => $hal->items(),
            'total' => $hal->total(),
            'page' => $hal->currentPage(),
            'totalPages' => $hal->lastPage(),
        ]);
    }
}
