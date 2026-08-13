<?php

/*
|--------------------------------------------------------------------------
| Blok konten statis (t_static_contents)
|--------------------------------------------------------------------------
|
| Cikal bakal port `lib/static-content-registry.ts` (557 baris, 12 blok CMS +
| kunci dinamis PPID/info). Yang dicantumkan di sini BARU kunci yang benar-benar
| sudah dipakai port ini; sisanya menyusul bersama halaman publik (Fase 7),
| karena blok CMS memang disunting dari halaman publiknya sendiri
| (lihat `_analisis/06-PARITAS-NEXTJS.md` §3.4).
|
| 🔴 `kunci` sudah ada di produksi — jangan diganti.
|
*/

return [

    'blok' => [
        'beranda.statistik' => 'Kartu Statistik Beranda',
        'pelayanan.jam' => 'Jam Layanan Permohonan Online',
        'pelayanan.visibilitas' => 'Visibilitas Layanan Permohonan Online',
    ],

    /*
    | Susunan kartu "Statistik Demografi" di beranda — port `DEFAULT_KARTU`
    | di `lib/beranda-statistik.ts`. Dipakai tombol "Reset Kartu Beranda"
    | di halaman Data Demografi.
    */
    'kartu_beranda' => [
        ['title' => 'Jumlah Penduduk', 'icon' => 'Users', 'kategori' => 'jenis-kelamin', 'kolom' => 'JML', 'warna' => 'biru'],
        ['title' => 'Kepala Keluarga', 'icon' => 'Home', 'kategori' => 'kk', 'kolom' => 'JML', 'warna' => 'amber'],
        ['title' => 'Laki-laki', 'icon' => 'User', 'kategori' => 'jenis-kelamin', 'kolom' => 'L', 'warna' => 'sky', 'badgeKolom' => 'JML'],
        ['title' => 'Perempuan', 'icon' => 'UserCircle', 'kategori' => 'jenis-kelamin', 'kolom' => 'P', 'warna' => 'rose', 'badgeKolom' => 'JML'],
        ['title' => 'Wajib KTP', 'icon' => 'IdCard', 'kategori' => 'wajib-ktp', 'kolom' => 'JML', 'warna' => 'teal'],
        ['title' => 'Sudah Rekam KTP-el', 'icon' => 'ScanLine', 'kategori' => 'wajib-ktp', 'kolom' => 'JML_WKTP', 'warna' => 'emerald', 'badgeKolom' => 'JML'],
    ],

];
