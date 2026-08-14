<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\News;
use App\Models\Produk;
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

    /**
     * Halaman informasi statis — `/produk/{slug}` & `/ppid/{slug}`.
     *
     * Isinya dari `config/info-halaman.php`, ditimpa blok CMS `info.<grup>.<slug>`
     * bila petugas pernah menyuntingnya. Berkasnya diambil dari Dokumen
     * Publikasi (`t_produk`) berdasarkan kategori yang dipetakan
     * `config/dokumen.php` ke alamat halaman ini — jadi petugas cukup
     * mengunggah PDF dengan kategori yang benar, tanpa menyentuh halaman.
     */
    public function produk(string $slug)
    {
        return $this->info('produk', $slug);
    }

    /**
     * Produk Disdukcapil — `/produk/produk-disdukcapil`.
     *
     * 🔴 Satu-satunya alamat `/produk/*` yang punya TAMPILAN SENDIRI (akordeon
     * bergambar), jadi tidak lewat `info()`. Rutenya harus didaftarkan SEBELUM
     * `/produk/{slug}` — kalau tidak, catch-all itu menelannya dan halamannya
     * kembali jadi view informasi generik tanpa satu pun galat.
     */
    public function produkDisdukcapil()
    {
        $bawaan = config('konten.bawaan', [])['produk.disdukcapil'] ?? [];

        return view('publik.produk-disdukcapil', [
            'isi' => array_merge($bawaan, Konten::satu('produk.disdukcapil')),
            // Dokumen yang dipetakan ke alamat ini tetap ikut, sama seperti
            // halaman produk lainnya.
            'berkas' => ($jenis = self::jenisDokumen('/produk/produk-disdukcapil')) === []
                ? collect()
                : Produk::whereIn('jenis', $jenis)
                    ->whereNotNull('file')
                    ->orderByDesc('created_at')
                    ->get(['id', 'judul', 'file', 'created_at']),
        ]);
    }

    /**
     * Pusat Bantuan — `/pusat-bantuan/{slug}`.
     *
     * Menu baru mengikuti SIDAKO (permintaan dinas): FAQ, Pengaduan &
     * Konsultasi, Penipuan IKD.
     */
    public function pusatBantuan(string $slug)
    {
        return $this->info('pusat-bantuan', $slug);
    }

    /**
     * WBS — `/wbs/{slug}`.
     *
     * Kanal Pengaduan Masyarakat dan Whistle Blowing System disatukan mengikuti
     * SIDAKO: isinya sama dan keduanya menyimpan ke `POST /api/pengaduan`.
     */
    public function wbs(string $slug)
    {
        return $this->info('wbs', $slug);
    }

    /** Hubungi Kami — `/hubungi-kami/{slug}`. */
    public function hubungiSlug(string $slug)
    {
        return $this->info('hubungi-kami', $slug);
    }

    /**
     * Dua halaman layanan PPID dua-seksi: `/ppid/formulir-ppid` &
     * `/ppid/register-ppid`.
     *
     * Dipisah dari `info()` karena satu alamat memuat DUA blok isi + DUA tabel
     * berkas, masing-masing dengan kategori dokumennya sendiri.
     */
    private function ppidLayanan(string $slug)
    {
        $halaman = config("ppid-layanan.{$slug}");

        $seksi = collect($halaman['seksi'])->map(fn ($s) => [
            'isi' => array_merge($s['isi'], Konten::satu("info.ppid.{$s['slug']}")),
            'berkas' => Produk::where('jenis', $s['dokumen'])
                ->whereNotNull('file')
                ->orderByDesc('created_at')
                ->get(['id', 'judul', 'file', 'created_at']),
        ]);

        return view('publik.ppid-layanan', [
            'halaman' => $halaman,
            'seksi' => $seksi,
            'subnav' => config('ppid-subnav.layanan'),
        ]);
    }

    /**
     * PPID: dua slug adalah halaman INDEKS berisi kartu klasifikasi informasi
     * publik (UU 14/2008); sisanya halaman informasi biasa.
     *
     * Keduanya berbagi satu rute supaya `/ppid/...` tidak perlu dua pola yang
     * urutannya harus dijaga — indeks diperiksa lebih dulu di sini.
     */
    public function ppid(string $slug)
    {
        // Dua halaman layanan (formulir & register) berbentuk dua-seksi, bukan
        // halaman informasi satu blok — diperiksa lebih dulu.
        if (config("ppid-layanan.{$slug}")) {
            return $this->ppidLayanan($slug);
        }

        if ($grup = config("ppid.{$slug}")) {
            // Kartu menampilkan berapa dokumen yang sudah terunggah di halaman
            // tujuannya, supaya warga tahu mana yang sudah ada isinya sebelum
            // membuka satu per satu.
            $jumlah = Produk::whereNotNull('file')
                ->selectRaw('jenis, COUNT(*) c')->groupBy('jenis')->pluck('c', 'jenis');

            $items = collect($grup['items'])->map(function ($it) use ($jumlah) {
                $it['dokumen'] = collect(self::jenisDokumen($it['href']))
                    ->sum(fn ($j) => (int) ($jumlah[$j] ?? 0));

                return $it;
            });

            return view('publik.ppid-indeks', [
                'grup' => $grup,
                'slug' => $slug,
                'items' => $items,
                'subnav' => config('ppid-subnav.informasi'),
            ]);
        }

        return $this->info('ppid', $slug);
    }

    /**
     * Ikon kepala halaman per grup — supaya Pusat Bantuan tidak memakai ikon
     * "berkas" yang sama dengan halaman dokumen.
     */
    private const IKON_GRUP = [
        'wbs' => 'perisai-seru',
        'pusat-bantuan' => 'pelampung',
        'hubungi-kami' => 'telepon',
    ];

    private function info(string $grup, string $slug)
    {
        $bawaan = config("info-halaman.{$grup}.{$slug}");

        if (! $bawaan) {
            abort(404);
        }

        // Blok CMS menimpa per-kunci, jadi petugas yang hanya mengganti judul
        // tidak menghapus daftar & tautannya.
        $isi = array_merge($bawaan, Konten::satu("info.{$grup}.{$slug}"));

        $jenis = self::jenisDokumen("/{$grup}/{$slug}");

        $data = [
            'isi' => $isi,
            'ikon' => self::IKON_GRUP[$grup] ?? 'berkas',
            'berkas' => $jenis === []
                ? collect()
                : Produk::whereIn('jenis', $jenis)
                    ->whereNotNull('file')
                    ->orderByDesc('created_at')
                    ->get(['id', 'judul', 'file', 'created_at']),
        ];

        // Bar sub-tab hanya pada halaman PPID yang memang anggota sebuah grup.
        if ($grup === 'ppid' && $subnav = self::subnavUntuk("/ppid/{$slug}")) {
            $data['subnav'] = $subnav;
        }

        // Daftar tanya-jawab: blok CMS TERSENDIRI (`pusat-bantuan.faq`), bukan
        // bagian dari blok `info.*` — supaya petugas bisa menambah pertanyaan
        // tanpa ikut menyunting judul & deskripsi halamannya.
        if (! empty($isi['faq'])) {
            $data['faq'] = Konten::satu('pusat-bantuan.faq')['daftar']
                ?? config('konten.bawaan', [])['pusat-bantuan.faq']['daftar']
                ?? [];
        }

        if (! empty($isi['formulir'])) {
            $data['propsFormulir'] = ['varian' => $isi['formulir']];
        }

        return view('publik.info', $data);
    }

    /**
     * Grup sub-tab yang memuat sebuah alamat PPID, atau null bila tidak ada.
     *
     * @return array<string,mixed>|null
     */
    private static function subnavUntuk(string $jalur): ?array
    {
        foreach (config('ppid-subnav') as $grup) {
            if (collect($grup['items'])->contains(fn ($i) => $i['href'] === $jalur)) {
                return $grup;
            }
        }

        return null;
    }

    /**
     * Kategori dokumen (`t_produk.jenis`) yang tampil di sebuah alamat halaman.
     *
     * Satu halaman bisa menampilkan lebih dari satu kategori, dan satu kategori
     * bisa muncul di beberapa halaman (mis. SOP ada di Produk maupun PPID) —
     * karena itu dipetakan dari `halaman[].href`, bukan ditebak dari slug.
     *
     * @return array<int,string>
     */
    private static function jenisDokumen(string $jalur): array
    {
        return collect(config('dokumen.kategori'))
            ->filter(fn ($k) => collect($k['halaman'] ?? [])->contains(fn ($h) => ($h['href'] ?? '') === $jalur))
            ->pluck('key')
            ->all();
    }

    /**
     * Hubungi Kami — `/hubungi-kami`.
     *
     * Menggabungkan alamat, kontak, dan jam layanan jadi satu halaman;
     * `/hubungi-kami/{slug}` tetap ada untuk tautan lama yang menunjuk
     * bagian-bagiannya secara terpisah.
     */
    public function hubungiKami()
    {
        $grup = config('info-halaman.hubungi-kami');

        return view('publik.hubungi-kami', [
            'alamat' => array_merge($grup['alamat'], Konten::satu('info.hubungi-kami.alamat')),
            'kontak' => array_merge($grup['kontak'], Konten::satu('info.hubungi-kami.kontak')),
        ]);
    }

    /**
     * Survei Kepuasan Masyarakat — `/survei-kepuasan`.
     *
     * 🔴 Memakai formulir SENDIRI, bukan menyematkan skm.go.id lewat iframe
     * seperti SIDAKO. Alasannya: alamat iframe SIDAKO menunjuk instansi Tana
     * Tidung, dan menyalinnya berarti jawaban warga Pesisir Barat masuk ke
     * rekap dinas lain. Portal ini sudah punya kuesioner resminya sendiri
     * (9 unsur Permenpan RB 14/2017) berikut rekap IKM di dashboard, jadi
     * fiturnya setara — yang tidak ikut hanyalah instansi milik orang lain.
     */
    public function survei()
    {
        return view('publik.survei-kepuasan', [
            'aspek' => config('skm.aspek'),
            'skalaLabel' => config('skm.skala_label'),
        ]);
    }

    /**
     * Kebijakan & Privasi / Syarat & Ketentuan — `/kebijakan-privasi`, `/syarat`.
     *
     * Keduanya satu view: bentuknya sama persis (pengantar + bagian bernomor),
     * yang berbeda hanya daftar bagiannya di `config/ketentuan.php`.
     */
    public function ketentuan(string $halaman)
    {
        $def = config("ketentuan.{$halaman}");

        if (! $def) {
            abort(404);
        }

        return view('publik.ketentuan', [
            'halaman' => $def,
            'isi' => array_merge(
                config('ketentuan.bawaan', [])[$def['kunci']] ?? [],
                Konten::satu($def['kunci']),
            ),
        ]);
    }

    /** GIS Dukcapil — `/media/gis`. Peta sebaran penduduk per kecamatan. */
    public function gis()
    {
        return view('publik.gis');
    }

    /** Laporan Data Demografi — `/media/demografi`. */
    public function demografi()
    {
        return view('publik.demografi', [
            'kategori' => config('demografi.kategori'),
        ]);
    }

    /**
     * Peta situs untuk MANUSIA — `/sitemap`.
     *
     * Dirakit dari sumber yang sama dengan navbar & halaman informasi, jadi
     * halaman baru otomatis ikut terdaftar. Versi tulis-tangan pasti basi:
     * halaman ditambah di config, sementara daftarnya diperbarui belakangan
     * atau tidak sama sekali.
     */
    public function petaSitus()
    {
        $judulInfo = fn (string $grup) => collect(config("info-halaman.{$grup}", []))
            ->map(fn ($isi, $slug) => ['judul' => $isi['title'], 'href' => "/{$grup}/{$slug}"])
            ->values();

        return view('publik.sitemap', [
            'bagian' => [
                ['judul' => 'Layanan', 'items' => [
                    ['judul' => 'Beranda', 'href' => '/'],
                    ['judul' => 'Ajukan Permohonan', 'href' => '/user/pengajuan/baru'],
                    ['judul' => 'Pengajuan Saya', 'href' => '/user/pengajuan'],
                    ['judul' => 'Masuk', 'href' => '/login'],
                    ['judul' => 'Daftar Akun', 'href' => '/register'],
                    ['judul' => 'Cek Status Pendaftaran', 'href' => '/cek-status'],
                ]],
                ['judul' => 'Informasi Produk', 'items' => $judulInfo('produk')],
                ['judul' => 'Media Informasi', 'items' => [
                    ['judul' => 'Berita', 'href' => '/media/berita'],
                    ['judul' => 'Galeri', 'href' => '/galeri'],
                    ['judul' => 'GIS Dukcapil — Peta Sebaran Penduduk', 'href' => '/media/gis'],
                    ['judul' => 'Laporan Data Demografi', 'href' => '/media/demografi'],
                ]],
                ['judul' => 'PPID', 'items' => collect([
                    ['judul' => 'Informasi Wajib Tersedia Setiap Saat', 'href' => '/ppid/informasi-setiap-saat'],
                    ['judul' => 'Informasi Wajib Diumumkan Secara Berkala', 'href' => '/ppid/informasi-berkala'],
                    ['judul' => 'Formulir PPID', 'href' => '/ppid/formulir-ppid'],
                    ['judul' => 'Register PPID', 'href' => '/ppid/register-ppid'],
                ])->concat($judulInfo('ppid'))],
                ['judul' => 'WBS', 'items' => $judulInfo('wbs')],
                ['judul' => 'Pusat Bantuan', 'items' => $judulInfo('pusat-bantuan')],
                ['judul' => 'Lainnya', 'items' => collect([
                    ['judul' => 'Survei Kepuasan Masyarakat', 'href' => '/survei-kepuasan'],
                    ['judul' => 'Hubungi Kami', 'href' => '/hubungi-kami'],
                ])->concat($judulInfo('hubungi-kami'))],
            ],
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
