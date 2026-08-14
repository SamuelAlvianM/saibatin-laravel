<?php

/*
|--------------------------------------------------------------------------
| Isi footer situs
|--------------------------------------------------------------------------
|
| 🔴 Footer dirender DUA KALI dengan teknologi berbeda: Blade untuk halaman
| publik (`publik/partials/footer.blade.php`) dan React untuk halaman Inertia
| area warga (`Components/FooterPublik.jsx`). Blade tidak bisa mengimpor modul
| JS dan sebaliknya, jadi markup-nya memang terpaksa ada dua.
|
| Yang TIDAK boleh ada dua adalah ISINYA. Alamat kantor, surel, jam layanan,
| dan daftar tautan hidup di sini saja — kalau tidak, dinas mengganti alamat,
| yang berubah cuma separuh situs, dan tidak ada yang menyadarinya sampai ada
| warga datang ke alamat lama.
|
| Blade membacanya lewat `config('footer')`; React menerimanya sebagai prop
| bersama Inertia (`HandleInertiaRequests::share`).
|
*/

return [

    'tautan' => [
        [
            'judul' => 'Layanan',
            'items' => [
                ['label' => 'Ajukan Permohonan', 'href' => '/user/pengajuan/baru'],
                ['label' => 'Riwayat Permohonan', 'href' => '/user/pengajuan'],
                ['label' => 'Pengaduan & Konsultasi', 'href' => '/pusat-bantuan/pengaduan-konsultasi'],
                ['label' => 'Survei Kepuasan', 'href' => '/survei-kepuasan'],
            ],
        ],
        [
            'judul' => 'Informasi',
            // "Hubungi Kami" tidak lagi di navbar (mengikuti SIDAKO), jadi
            // footer inilah satu-satunya jalan masuk tetapnya.
            'items' => [
                ['label' => 'Berita', 'href' => '/media/berita'],
                ['label' => 'Galeri', 'href' => '/galeri'],
                ['label' => 'Informasi Produk', 'href' => '/produk/produk-disdukcapil'],
                ['label' => 'PPID', 'href' => '/ppid/profil-ppid'],
                ['label' => 'Hubungi Kami', 'href' => '/hubungi-kami'],
            ],
        ],
    ],

    /*
    | Kantor. ⚠️ Zona waktunya **WIB** — Pesisir Barat ada di Lampung. Halaman
    | Pengaduan portal Next.js sempat menulis WITA karena disalin dari SIDAKO.
    */
    'kantor' => [
        ['ikon' => 'peta-pin', 'teks' => "Komplek Perkantoran Pemda Kabupaten Pesisir Barat,\nKec. Pesisir Tengah, Kabupaten Pesisir Barat, Lampung"],
        ['ikon' => 'surel', 'teks' => 'disdukcapil@pesisirbaratkab.go.id'],
        ['ikon' => 'jam', 'teks' => 'Senin – Jumat: 08.00 – 16.00 WIB'],
    ],

    'legal' => [
        ['label' => 'Kebijakan Privasi', 'href' => '/kebijakan-privasi'],
        ['label' => 'Syarat & Ketentuan', 'href' => '/syarat'],
        ['label' => 'Sitemap', 'href' => '/sitemap'],
    ],

];
