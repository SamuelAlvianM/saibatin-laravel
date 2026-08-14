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
        // Dipisah dari blok `info.pusat-bantuan.faq` (yang mengatur judul &
        // deskripsi halamannya) supaya petugas bisa menambah pertanyaan tanpa
        // ikut menyunting teks pengantarnya.
        'pusat-bantuan.faq' => 'Pusat Bantuan — Daftar FAQ',
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

        /*
        | Daftar tanya-jawab `/pusat-bantuan/faq`.
        |
        | Isi awal ini menjawab pertanyaan yang paling sering masuk lewat
        | pengaduan portal. Petugas menggantinya lewat Konten Halaman; begitu
        | blok ini pernah disimpan, isi di bawah tidak lagi terpakai.
        */
        'pusat-bantuan.faq' => [
            'daftar' => [
                [
                    'pertanyaan' => 'Apakah pengurusan dokumen kependudukan dipungut biaya?',
                    'jawaban' => 'Tidak. Seluruh layanan administrasi kependudukan dan pencatatan sipil GRATIS, sesuai UU No. 24 Tahun 2013. Bila ada pihak yang meminta biaya, laporkan melalui menu WBS.',
                ],
                [
                    'pertanyaan' => 'Berapa lama dokumen selesai setelah permohonan dikirim?',
                    'jawaban' => 'Permohonan yang berkasnya lengkap dan benar umumnya selesai dalam beberapa hari kerja. Anda dapat memantau statusnya kapan saja lewat menu "Pengajuan Saya" setelah masuk ke akun.',
                ],
                [
                    'pertanyaan' => 'Saya sudah mendaftar, tetapi belum bisa masuk. Kenapa?',
                    'jawaban' => 'Akun baru harus diverifikasi petugas lebih dulu. Gunakan halaman "Cek Status Pendaftaran" di bawah formulir login untuk melihat status akun Anda beserta alasannya bila ditolak.',
                ],
                [
                    'pertanyaan' => 'Permohonan saya ditolak. Apa yang harus dilakukan?',
                    'jawaban' => 'Buka permohonan tersebut untuk membaca catatan petugas. Perbaiki bagian yang ditandai, lalu ajukan ulang — Anda tidak perlu mengisi seluruh formulir dari awal.',
                ],
                [
                    'pertanyaan' => 'Berkas seperti apa yang harus diunggah?',
                    'jawaban' => 'Setiap layanan meminta berkas yang berbeda dan daftarnya tampil langsung di formulirnya. Unggah hasil foto atau pindaian yang terbaca jelas, berformat JPG, PNG, atau PDF.',
                ],
                [
                    'pertanyaan' => 'Apakah Disdukcapil menelepon warga untuk aktivasi IKD?',
                    'jawaban' => 'Tidak pernah. Disdukcapil tidak melakukan panggilan telepon maupun video call untuk aktivasi Identitas Kependudukan Digital, dan tidak pernah meminta PIN, kata sandi, atau data perbankan. Lihat halaman "Penipuan IKD" untuk ciri-ciri lengkapnya.',
                ],
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
