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
        'beranda.hero' => 'Teks Hero Beranda',
        'beranda.carousel' => 'Carousel Hero',
        'beranda.statistik' => 'Kartu Statistik Beranda',
        'profil.visi-misi' => 'Profil — Visi & Misi',
        'profil.motto' => 'Profil — Motto & Tujuan',
        'profil.maklumat' => 'Profil — Maklumat Pelayanan',
        'profil.tugas' => 'Profil — Tugas & Fungsi',
        'profil.struktur' => 'Profil — Struktur Organisasi',
        'pelayanan.jam' => 'Jam Layanan Permohonan Online',
        'pelayanan.visibilitas' => 'Visibilitas Layanan Permohonan Online',
    ],

    /*
    |--------------------------------------------------------------------------
    | Isi bawaan tiap blok
    |--------------------------------------------------------------------------
    |
    | 🔴 Ini BUKAN sekadar contoh. Selama sebuah blok belum pernah disunting
    | petugas, isi di bawah inilah yang tampil di situs publik — jadi portal
    | tidak perlu di-seed dan tidak pernah tampil kosong. Nilai dari
    | `t_static_contents` menimpanya per-kunci (lihat `App\Support\Konten`).
    |
    | Disalin dari `lib/static-content-registry.ts` + nilai `CONTENT` bawaan di
    | `components/landingpage/profile-tabs.tsx` portal Next.js.
    |
    */
    'bawaan' => [

        'beranda.hero' => [
            'heading' => 'Layanan Kependudukan Kabupaten Pesisir Barat',
            'subheading' => 'Urus akta kelahiran, KTP-el, Kartu Keluarga, dan layanan kependudukan lainnya secara online — cepat, mudah, dan gratis.',
            'searchPlaceholder' => 'Mau mengurus apa hari ini?',
        ],

        // Kosong = carousel memakai slide bawaannya sendiri di sisi klien.
        'beranda.carousel' => ['slides' => []],

        'profil.visi-misi' => [
            'visi' => 'Terwujudnya Pusat Pelayanan Data Base Kependudukan yang Akurat dan Aktual Berbasis Sistem Informasi Administrasi Kependudukan',
            'misi' => [
                'Meningkatkan profesionalitas, efisiensi dan efektifitas organisasi',
                'Mengoptimalkan dan meningkatkan pengelolaan administrasi kependudukan',
                'Meningkatkan kualitas kinerja pelayanan administrasi kependudukan secara prima',
            ],
        ],

        'profil.motto' => [
            'motto' => 'Profesional, Integritas, Prima',
            'tujuan' => [
                'Memberikan pelayanan kependudukan yang cepat, tepat, dan akurat',
                'Mewujudkan database kependudukan yang berkualitas dan terintegrasi',
                'Meningkatkan kepuasan masyarakat melalui pelayanan berbasis teknologi',
            ],
            'sasaran' => [
                'Tersedianya data kependudukan yang akurat dan mutakhir',
                'Terwujudnya pelayanan administrasi kependudukan yang prima',
                'Terbangunnya sistem informasi kependudukan yang terintegrasi',
            ],
        ],

        'profil.maklumat' => [
            'janji' => [
                ['title' => 'Cepat', 'desc' => '15 menit', 'icon' => 'Clock'],
                ['title' => 'Akurat', 'desc' => 'Data valid', 'icon' => 'ShieldCheck'],
                ['title' => 'Gratis', 'desc' => 'Tanpa biaya', 'icon' => 'Gift'],
                ['title' => 'Ramah', 'desc' => 'Sikap prima', 'icon' => 'Smile'],
            ],
            'standar' => 'Kami berkomitmen memberikan pelayanan terbaik sesuai Standar Pelayanan Publik',
        ],

        'profil.tugas' => [
            'utama' => 'Melaksanakan urusan pemerintahan bidang kependudukan dan pencatatan sipil',
            'fungsi' => [
                'Penyelenggaraan administrasi kependudukan',
                'Pelayanan pencatatan sipil',
                'Pengelolaan data dan informasi kependudukan',
                'Pelaksanaan identifikasi kependudukan',
                'Fasilitasi perpindahan penduduk',
            ],
        ],

        // `parent` menentukan bentuk bagannya; jabatan tanpa parent jadi akar.
        'profil.struktur' => [
            'organisasi' => [
                ['jabatan' => 'Kepala Dinas', 'nama' => '-', 'status' => 'Pimpinan'],
                ['jabatan' => 'Sekretaris', 'nama' => '-', 'status' => 'Pengawas', 'parent' => 'Kepala Dinas'],
                ['jabatan' => 'Kabid Pelayanan Pendaftaran Penduduk', 'nama' => '-', 'status' => 'Pelaksana', 'parent' => 'Kepala Dinas'],
                ['jabatan' => 'Kabid Pelayanan Pencatatan Sipil', 'nama' => '-', 'status' => 'Pelaksana', 'parent' => 'Kepala Dinas'],
                ['jabatan' => 'Kabid Pengelolaan Informasi Administrasi Kependudukan', 'nama' => '-', 'status' => 'Pelaksana', 'parent' => 'Kepala Dinas'],
            ],
        ],

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
