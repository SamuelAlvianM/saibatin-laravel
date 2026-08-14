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
        'produk.disdukcapil' => 'Produk Disdukcapil',
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

        /*
        | Produk Disdukcapil — halaman ini punya TAMPILAN SENDIRI (akordeon
        | bergambar), bukan view informasi generik. Karena itu isinya di blok
        | tersendiri, bukan di `config/info-halaman.php`.
        |
        | Ilustrasinya (`public/produk-layanan/*.png`) disalin dari portal
        | SAIBATIN Next.js — aset milik project yang sama, bukan pinjaman.
        |
        | 🔴 Kunci gambarnya `image`, BUKAN `gambar`. Blok ini SUDAH ADA di
        | database produksi dengan kunci itu (beserta isi yang jauh lebih
        | lengkap: persyaratan per produk dalam bentuk HTML). Memakai nama lain
        | membuat gambarnya hilang diam-diam — halamannya tetap 200, akordeonnya
        | tetap terbuka, cuma tanpa satu pun ilustrasi.
        |
        | `desc` boleh berisi HTML karena begitulah bentuknya di produksi.
        */
        'produk.disdukcapil' => [
            'intro' => 'Layanan Disdukcapil Pesisir Barat terdiri atas Layanan Pencatatan Sipil (Capil) dan Layanan Pendaftaran Penduduk (Dafduk). Pencatatan Sipil adalah pencatatan peristiwa penting yang dialami oleh seseorang dalam register pencatatan sipil pada Instansi Pelaksana; dokumen yang dicatat meliputi akta-akta serta catatan pinggir. Pendaftaran Penduduk adalah pencatatan biodata penduduk, pencatatan atas pelaporan peristiwa kependudukan dan pendataan penduduk rentan administrasi kependudukan, serta penerbitan dokumen penduduk berupa kartu identitas atau surat keterangan kependudukan.',

            'produk' => [
                ['image' => '/produk-layanan/kelahiran.png', 'nama' => 'Akta Kelahiran', 'desc' => 'Dokumen pencatatan resmi atas peristiwa kelahiran seseorang. Menjadi bukti sah identitas dan kewarganegaraan anak sejak lahir.'],
                ['image' => '/produk-layanan/kematian.png', 'nama' => 'Akta Kematian', 'desc' => 'Dokumen pencatatan resmi atas peristiwa kematian seseorang, diperlukan antara lain untuk pengurusan waris, asuransi, dan penataan data keluarga.'],
                ['image' => '/produk-layanan/perkawinan.png', 'nama' => 'Akta Perkawinan', 'desc' => 'Dokumen pencatatan perkawinan bagi penduduk non-muslim yang telah melangsungkan perkawinan sah menurut agama/kepercayaannya.'],
                ['image' => '/produk-layanan/perceraian.png', 'nama' => 'Akta Perceraian', 'desc' => 'Dokumen pencatatan perceraian berdasarkan putusan pengadilan yang telah berkekuatan hukum tetap.'],
                ['image' => '/produk-layanan/pengakuananak.png', 'nama' => 'Pengakuan & Pengesahan Anak', 'desc' => 'Pencatatan pengakuan anak oleh ayah biologis dan pengesahan anak setelah perkawinan sah orang tuanya.'],
                ['image' => '/produk-layanan/kutipankedua.png', 'nama' => 'Kutipan Kedua Akta', 'desc' => 'Penerbitan ulang kutipan akta pencatatan sipil (kelahiran, kematian, perkawinan, perceraian) yang hilang atau rusak.'],
                ['image' => '/produk-layanan/legalisasidokumen.png', 'nama' => 'Legalisasi Dokumen', 'desc' => 'Pengesahan fotokopi dokumen kependudukan dan akta pencatatan sipil agar sah digunakan untuk berbagai keperluan.'],
                ['image' => '/produk-layanan/suratketerangan.png', 'nama' => 'Surat Keterangan Kependudukan', 'desc' => 'Berbagai surat keterangan resmi terkait data kependudukan, misalnya surat keterangan pindah, domisili, atau pengganti identitas.'],
                ['image' => '/produk-layanan/catatanpinggir.png', 'nama' => 'Catatan Pinggir', 'desc' => 'Catatan resmi pada register dan kutipan akta atas perubahan peristiwa penting setelah akta diterbitkan.'],
                ['image' => '/produk-layanan/catatanpinggirperubahannama.png', 'nama' => 'Catatan Pinggir Perubahan Nama', 'desc' => 'Pencatatan perubahan nama berdasarkan penetapan pengadilan negeri pada register dan kutipan akta pencatatan sipil.'],
                ['image' => '/produk-layanan/catatanpinggirkewarganegaraan.png', 'nama' => 'Catatan Pinggir Perubahan Kewarganegaraan', 'desc' => 'Pencatatan perubahan status kewarganegaraan pada register dan kutipan akta pencatatan sipil.'],
                ['image' => '/produk-layanan/catatanpinggirpengangkatananak.png', 'nama' => 'Catatan Pinggir Pengangkatan Anak', 'desc' => 'Pencatatan pengangkatan anak berdasarkan penetapan pengadilan pada register dan kutipan akta kelahiran.'],
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
