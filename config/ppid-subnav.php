<?php

/*
|--------------------------------------------------------------------------
| Sub-tab PPID
|--------------------------------------------------------------------------
|
| Port `lib/ppid-informasi.ts` SIDAKO (`TENTANG_PPID_TABS`,
| `INFORMASI_PUBLIK_TABS`, `LAYANAN_PPID_TABS`).
|
| Tiga menu PPID di navbar masing-masing sebenarnya BEBERAPA halaman terpisah.
| Rute aslinya tetap ada satu-satu; bar sub-tab hanya menaut mereka secara
| visual sehingga terlihat sebagai satu halaman bertab. Bedanya dari komponen
| Tabs: klik di sini benar-benar BERPINDAH HALAMAN, bukan menukar isi.
|
| 🔴 `href` di sini harus cocok dengan slug di `config/info-halaman.php` (atau
| rute layanan PPID). Slug yang tidak ada membuat tab-nya 404 — dan karena
| bar-nya tetap tampil rapi, kerusakannya baru ketahuan saat diklik.
|
| `pendek` dipakai di layar sempit; tanpa itu enam tab memaksa gulir horizontal.
|
*/

return [

    'tentang' => [
        'judul' => 'Tentang PPID',
        'items' => [
            ['href' => '/ppid/profil-ppid', 'label' => 'Profil PPID Pelaksana', 'pendek' => 'Profil'],
            ['href' => '/ppid/gambaran-pembentukan-ppid', 'label' => 'Gambaran Pembentukan', 'pendek' => 'Pembentukan'],
            ['href' => '/ppid/visi-misi-ppid', 'label' => 'Visi & Misi PPID', 'pendek' => 'Visi & Misi'],
            ['href' => '/ppid/struktur-organisasi-ppid', 'label' => 'Struktur Organisasi', 'pendek' => 'Struktur'],
            ['href' => '/ppid/maklumat-ppid', 'label' => 'Maklumat PPID', 'pendek' => 'Maklumat'],
            ['href' => '/ppid/tugas-tanggungjawab-ppid', 'label' => 'Tugas & Tanggung Jawab', 'pendek' => 'Tugas'],
        ],
    ],

    'informasi' => [
        'judul' => 'Informasi Publik',
        'items' => [
            ['href' => '/ppid/informasi-setiap-saat', 'label' => 'Informasi Wajib Tersedia Setiap Saat', 'pendek' => 'Setiap Saat'],
            ['href' => '/ppid/informasi-berkala', 'label' => 'Informasi Wajib Diumumkan Secara Berkala', 'pendek' => 'Berkala'],
        ],
    ],

    'layanan' => [
        'judul' => 'Layanan & Formulir PPID',
        'items' => [
            ['href' => '/ppid/formulir-ppid', 'label' => 'Formulir PPID', 'pendek' => 'Formulir'],
            ['href' => '/ppid/sk-disdukcapil', 'label' => 'SK Disdukcapil', 'pendek' => 'SK'],
            ['href' => '/ppid/register-ppid', 'label' => 'Register', 'pendek' => 'Register'],
            ['href' => '/ppid/uji-konsekuensi', 'label' => 'Uji Konsekuensi', 'pendek' => 'Uji Konsekuensi'],
            ['href' => '/ppid/sengketa-informasi', 'label' => 'Penyelesaian Sengketa Informasi', 'pendek' => 'Sengketa'],
            ['href' => '/ppid/inovasi-layanan', 'label' => 'Inovasi Layanan', 'pendek' => 'Inovasi'],
        ],
    ],

];
