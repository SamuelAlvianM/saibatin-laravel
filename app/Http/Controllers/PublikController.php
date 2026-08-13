<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\News;
use App\Support\Konten;
use Illuminate\Http\Request;

/**
 * Halaman-halaman situs PUBLIK — dirender Blade di server.
 *
 * 🔴 Sengaja BUKAN Inertia. Port ini tanpa Inertia SSR (butuh daemon Node yang
 * tidak ada di cPanel), sehingga halaman Inertia sampai ke mesin pencari sebagai
 * div kosong. Halaman inilah yang paling butuh terbaca Google, jadi isinya
 * dirender PHP dan hanya bagian interaktifnya dipasang sebagai React island.
 *
 * ⚠️ Beda dari portal Next.js — dan disengaja: di sana teks hero, isi tab
 * profil, dan daftar berita semuanya diambil klien lewat `fetch` setelah
 * halaman tampil. Di sini ketiganya dibaca DI SINI dan ikut dalam HTML, karena
 * justru itulah isi yang perlu terbaca mesin pencari. Islandnya menerima hasil
 * yang sama lewat props, jadi tidak ada permintaan kedua dan tidak ada teks
 * yang berkedip masuk setelah tata letak terbentuk.
 */
class PublikController extends Controller
{
    public function beranda()
    {
        $konten = Konten::ambil([
            'beranda.hero',
            'beranda.carousel',
            'profil.visi-misi',
            'profil.motto',
            'profil.maklumat',
            'profil.tugas',
            'profil.struktur',
        ]);

        return view('publik.beranda', [
            'hero' => $konten['beranda.hero'],
            'slides' => Konten::slideCarousel($konten['beranda.carousel']),
            'profil' => [
                'visi-misi' => $konten['profil.visi-misi'],
                'motto' => $konten['profil.motto'],
                'maklumat' => $konten['profil.maklumat'],
                'tugas' => $konten['profil.tugas'],
                'struktur' => $konten['profil.struktur'],
            ],
            'berita' => News::terbit()
                ->select('id', 'judul', 'slug', 'kategori', 'ringkasan', 'gambar', 'created_at')
                ->orderByDesc('created_at')
                ->take(3)
                ->get(),
        ]);
    }

    /**
     * Daftar berita — `/media/berita`.
     *
     * Paginasinya **bernomor lewat URL** (`?page=2`), bukan state React seperti
     * aslinya. Bedanya bukan gaya: halaman 2 di portal Next.js tidak punya URL
     * sendiri, jadi tidak bisa di-bookmark, tidak bisa dibagikan, dan tidak
     * pernah diindeks. Di sini tiap halaman punya alamatnya sendiri.
     */
    public function beritaIndeks()
    {
        $berita = News::terbit()
            ->select('id', 'judul', 'slug', 'kategori', 'ringkasan', 'gambar', 'created_at')
            ->orderByDesc('created_at')
            ->paginate(9)
            ->withQueryString();

        return view('publik.berita.indeks', ['berita' => $berita]);
    }

    /**
     * Galeri foto — `/galeri`.
     *
     * ⚠️ Beda dari portal Next.js, dan ini perbaikan: di sana halaman galeri
     * memanggil `/api/galeri` **tanpa parameter**, sedangkan endpoint itu
     * mem-paginasi 20 baris. Akibatnya galeri di portal yang sekarang live
     * hanya pernah menampilkan **20 dari 94 foto** — 74 sisanya tidak bisa
     * dilihat siapa pun, tanpa tombol "muat lagi" atau tanda apa pun.
     * Di sini semuanya bisa dijangkau lewat paginasi ber-URL.
     */
    public function galeri(Request $request)
    {
        $kategori = trim((string) $request->query('kategori'));

        // Penyaringnya lewat URL, bukan state klien: tiap kategori punya alamat
        // sendiri sehingga bisa dibagikan dan diindeks.
        $foto = Gallery::query()
            ->when($kategori !== '', fn ($q) => $q->where('kategori', $kategori))
            ->orderByDesc('created_at')
            ->paginate(24)
            ->withQueryString();

        return view('publik.galeri', [
            'foto' => $foto,
            'kategori' => $kategori,
            'daftarKategori' => Gallery::query()
                ->whereNotNull('kategori')->distinct()->orderBy('kategori')->pluck('kategori'),
        ]);
    }

    /** Satu artikel — `/media/berita/{slug}`. */
    public function beritaDetail(string $slug)
    {
        $berita = News::terbit()->where('slug', $slug)->first();

        if (! $berita) {
            abort(404);
        }

        // Data terstruktur schema.org dirakit di sini, bukan di view — larik
        // bersarang di dalam Blade sudah dua kali gagal diam-diam (lihat
        // komentar di `publik/berita/detail.blade.php`).
        $ld = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $berita->judul,
            'datePublished' => $berita->created_at?->toIso8601String(),
            'dateModified' => $berita->updated_at?->toIso8601String(),
            'image' => $berita->gambar ? [url($berita->gambar)] : [],
            'author' => ['@type' => 'Organization', 'name' => $berita->penulis ?: 'Disdukcapil Kabupaten Pesisir Barat'],
            'publisher' => ['@type' => 'Organization', 'name' => 'Disdukcapil Kabupaten Pesisir Barat'],
            'mainEntityOfPage' => url()->current(),
        ];

        return view('publik.berita.detail', [
            'berita' => $berita,
            'ldJson' => json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }
}
