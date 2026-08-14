<?php

/*
|--------------------------------------------------------------------------
| Isi halaman informasi statis (Produk & PPID)
|--------------------------------------------------------------------------
|
| Port `lib/info-content.ts`. Tiap kunci = segmen URL-nya:
|   'produk' => ['sop' => …]   →  /produk/sop
|   'ppid'   => ['lhkpn' => …] →  /ppid/lhkpn
|
| 🔴 Kunci ini ikut membentuk kunci blok CMS-nya (`info.produk.sop`), dan
| sebagian sudah ada di produksi — mengganti slug berarti konten yang pernah
| disunting petugas berhenti muncul tanpa pesan galat apa pun.
|
| Halaman yang punya kategori dokumen (lihat `config/dokumen.php`) otomatis
| menampilkan tabel berkas unggahan di bawah isinya; yang tidak, menampilkan
| catatan bahwa dokumennya belum tersedia digital.
|
| 🔴 Isinya MENGIKUTI SIDAKO (`sidako-platform/lib/info-content.ts`) — keputusan
| user 14 Agu 2026. Portal SIDAKO sudah menerima permintaan dinas dan daftar
| halamannya satu generasi lebih maju daripada SAIBATIN Next.js yang jadi
| sumber port. Yang disalin isi & strukturnya; nama daerah tetap Pesisir Barat.
|
| Empat grup: `produk`, `ppid`, `wbs`, `pusat-bantuan`, `hubungi-kami`.
|
*/

return [

    'produk' => [

        'produk-disdukcapil' => [
            'title' => 'Produk Disdukcapil',
            'description' => 'Daftar dokumen kependudukan dan pencatatan sipil yang diterbitkan.',
            'list' => [
                'Kartu Keluarga (KK)',
                'Kartu Tanda Penduduk Elektronik (KTP-el)',
                'Kartu Identitas Anak (KIA)',
                'Akta Kelahiran',
                'Akta Kematian',
                'Akta Perkawinan',
                'Akta Perceraian',
                'Surat Keterangan Pindah Datang',
            ],
        ],

        'formulir-persyaratan' => [
            'title' => 'Formulir & Persyaratan',
            'description' => 'Persyaratan dokumen untuk setiap jenis permohonan layanan adminduk.',
            'body' => [
                'Setiap permohonan layanan administrasi kependudukan memerlukan dokumen pendukung yang berbeda-beda sesuai jenis layanannya. Persyaratan lengkap dapat dilihat saat mengisi formulir Permohonan Online, atau ditanyakan langsung ke loket pelayanan.',
            ],
            'list' => [
                'KK & KTP-el: fotokopi KK lama, surat pengantar RT/RW',
                'Akta Kelahiran: surat keterangan lahir dari bidan/rumah sakit, KK orang tua',
                'Akta Kematian: surat keterangan kematian, KTP/KK almarhum',
                'Pindah Datang: surat pengantar dari daerah asal/tujuan',
            ],
        ],

        'hukum' => [
            'title' => 'Produk Hukum',
            'description' => 'Dasar hukum penyelenggaraan administrasi kependudukan.',
            'list' => [
                'UU No. 23 Tahun 2006 tentang Administrasi Kependudukan',
                'UU No. 24 Tahun 2013 tentang Perubahan UU Adminduk',
                'Peraturan Pemerintah No. 40 Tahun 2019',
                'Peraturan Menteri Dalam Negeri terkait pelayanan Dukcapil',
                'Peraturan Daerah Kabupaten Pesisir Barat terkait Disdukcapil',
            ],
        ],

        'sop' => [
            'title' => 'Standar Operasional Prosedur (SOP)',
            'description' => 'SOP pelayanan administrasi kependudukan Disdukcapil Pesisir Barat.',
            'body' => [
                'SOP pelayanan disusun untuk menjamin kepastian waktu, biaya (gratis), dan prosedur dalam setiap layanan adminduk.',
            ],
        ],

        // ── Tiga halaman tambahan mengikuti SIDAKO ───────────────────────────
        // Ketiganya menerima DUA macam unggahan sekaligus: dokumen PDF lewat
        // kategori di `config/dokumen.php`, dan gambar/infografis lewat field
        // `gambar` pada blok CMS `info.produk.*`.
        'standar-pelayanan' => [
            'title' => 'Standar Pelayanan (SP)',
            'description' => 'Standar pelayanan publik Disdukcapil Pesisir Barat — jenis layanan, persyaratan, jangka waktu, dan biaya.',
            'body' => [
                'Standar Pelayanan memuat ketentuan penyelenggaraan pelayanan publik: persyaratan, sistem dan prosedur, jangka waktu penyelesaian, biaya, produk layanan, serta penanganan pengaduan. Dokumen dan infografis resminya dapat dilihat atau diunduh di bawah ini.',
            ],
        ],

        'alur-pelayanan' => [
            'title' => 'Alur Pelayanan',
            'description' => 'Tahapan pelayanan administrasi kependudukan dari pendaftaran sampai dokumen diserahkan.',
            'body' => [
                'Berikut alur pelayanan pada Disdukcapil Kabupaten Pesisir Barat beserta perkiraan waktu tiap tahapannya.',
            ],
        ],

        'inovasi' => [
            'title' => 'Inovasi',
            'description' => 'Inovasi layanan Disdukcapil Pesisir Barat untuk mempermudah dan mempercepat pelayanan kepada masyarakat.',
            'body' => [
                'Inovasi layanan dikembangkan agar pelayanan administrasi kependudukan makin dekat, cepat, dan mudah dijangkau masyarakat.',
            ],
        ],

    ],

    'ppid' => [

        'profil-ppid' => [
            'title' => 'Profil PPID',
            'description' => 'Pejabat Pengelola Informasi dan Dokumentasi Disdukcapil Pesisir Barat.',
            'body' => [
                'PPID (Pejabat Pengelola Informasi dan Dokumentasi) bertugas mengelola dan menyajikan informasi publik di lingkungan Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat, sesuai amanat UU No. 14 Tahun 2008 tentang Keterbukaan Informasi Publik.',
            ],
        ],

        // ── Lima tab lain dari grup "Tentang PPID" ──────────────────────────
        'gambaran-pembentukan-ppid' => [
            'title' => 'Gambaran Singkat Pembentukan PPID',
            'description' => 'Latar belakang dan dasar hukum pembentukan PPID Disdukcapil Pesisir Barat.',
            'body' => [
                'PPID dibentuk untuk melaksanakan amanat Undang-Undang Nomor 14 Tahun 2008 tentang Keterbukaan Informasi Publik, yang mewajibkan setiap badan publik — termasuk Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat — menyediakan, memberikan, dan/atau menerbitkan informasi publik yang berada di bawah kewenangannya.',
                'Sebagai PPID Pelaksana, Disdukcapil Kabupaten Pesisir Barat ditunjuk untuk menjalankan tugas pengelolaan dan pelayanan informasi publik di bawah koordinasi PPID Utama Pemerintah Kabupaten Pesisir Barat.',
            ],
        ],

        'visi-misi-ppid' => [
            'title' => 'Visi dan Misi PPID',
            'description' => 'Arah dan komitmen PPID Disdukcapil Pesisir Barat dalam keterbukaan informasi publik.',
            'body' => [
                'Visi: Terwujudnya layanan informasi publik Disdukcapil Kabupaten Pesisir Barat yang cepat, akurat, dan akuntabel.',
            ],
            'list' => [
                'Menjamin hak masyarakat memperoleh informasi publik sesuai peraturan perundang-undangan',
                'Meningkatkan kualitas pelayanan informasi publik yang cepat, tepat waktu, dan biaya ringan',
                'Mewujudkan penyelenggaraan pemerintahan yang baik, transparan, efektif, dan akuntabel',
            ],
        ],

        'struktur-organisasi-ppid' => [
            'title' => 'Struktur Organisasi PPID',
            'description' => 'Susunan pengelola informasi dan dokumentasi Disdukcapil Pesisir Barat.',
            'list' => [
                'Atasan PPID: Kepala Dinas Kependudukan dan Pencatatan Sipil',
                'PPID Pelaksana: Sekretaris Dinas',
                'Petugas Layanan Informasi: pejabat/staf yang ditunjuk pada tiap bidang',
            ],
        ],

        'maklumat-ppid' => [
            'title' => 'Maklumat PPID',
            'description' => 'Pernyataan komitmen PPID Disdukcapil Pesisir Barat dalam layanan informasi publik.',
            'body' => [
                '"Kami PPID Disdukcapil Kabupaten Pesisir Barat berkomitmen untuk memberikan pelayanan informasi publik yang cepat, tepat, mudah, dan transparan sesuai dengan peraturan perundang-undangan yang berlaku."',
            ],
        ],

        'tugas-tanggungjawab-ppid' => [
            'title' => 'Tugas dan Tanggung Jawab PPID',
            'description' => 'Wewenang dan kewajiban PPID dalam mengelola informasi publik.',
            'list' => [
                'Mengumpulkan, mengelola, dan mendokumentasikan seluruh informasi publik dari unit kerja',
                'Menyediakan, menyimpan, mendokumentasikan, dan mengamankan informasi publik',
                'Melakukan verifikasi bahan informasi publik',
                'Melakukan uji konsekuensi atas informasi yang dikecualikan',
                'Menyelesaikan sengketa informasi publik sesuai ketentuan yang berlaku',
            ],
        ],

        'laporan-ppid-pelaksana' => [
            'title' => 'Laporan PPID Pelaksana',
            'description' => 'Laporan pelaksanaan tugas PPID pelaksana tahunan.',
        ],

        'lkjip' => [
            'title' => 'LKJIP',
            'description' => 'Laporan Kinerja Instansi Pemerintah Disdukcapil Pesisir Barat.',
        ],

        'survey-kepuasan-masyarakat' => [
            'title' => 'Survey Kepuasan Masyarakat',
            'description' => 'Hasil survey kepuasan masyarakat terhadap pelayanan publik.',
        ],

        'buku-profil-kependudukan' => [
            'title' => 'Buku Profil Kependudukan',
            'description' => 'Buku profil data kependudukan Kabupaten Pesisir Barat.',
        ],

        'dpa' => [
            'title' => 'Dokumen Pelaksana Anggaran (DPA)',
            'description' => 'Dokumen pelaksanaan anggaran tahunan Disdukcapil.',
        ],

        'iki' => [
            'title' => 'Indikator Kinerja Individu (IKI)',
            'description' => 'Indikator kinerja individu pegawai Disdukcapil Pesisir Barat.',
        ],

        'rkt' => [
            'title' => 'Rencana Kinerja Tahunan (RKT)',
            'description' => 'Rencana kinerja tahunan Disdukcapil Pesisir Barat.',
        ],

        'renka' => [
            'title' => 'Rencana Kerja (Renka)',
            'description' => 'Rencana kerja tahunan instansi.',
        ],

        'perjanjian-kerjasama' => [
            'title' => 'Perjanjian Kerjasama',
            'description' => 'Daftar perjanjian kerjasama Disdukcapil dengan pihak lain.',
        ],

        // ── Sisa kategori "Informasi Setiap Saat" (mengikuti SIDAKO) ────────
        'rka' => [
            'title' => 'Rencana Kerja dan Anggaran (RKA)',
            'description' => 'Dokumen rencana kerja dan anggaran tahunan perangkat daerah.',
        ],

        'lra' => [
            'title' => 'Laporan Realisasi Anggaran (LRA)',
            'description' => 'Laporan realisasi anggaran pendapatan dan belanja instansi.',
        ],

        'rfk' => [
            'title' => 'Realisasi Fisik dan Keuangan (RFK)',
            'description' => 'Laporan realisasi fisik dan keuangan pelaksanaan kegiatan.',
        ],

        'rup-pengadaan' => [
            'title' => 'RUP Pengadaan',
            'description' => 'Rencana Umum Pengadaan (RUP) barang/jasa Disdukcapil Pesisir Barat.',
        ],

        'cakin' => [
            'title' => 'Capaian Indikator Kinerja (Cakin)',
            'description' => 'Capaian indikator kinerja Disdukcapil Kabupaten Pesisir Barat.',
        ],

        'lapkin' => [
            'title' => 'Laporan Kinerja (Lapkin)',
            'description' => 'Laporan kinerja pelaksanaan program dan kegiatan Disdukcapil.',
        ],

        'sakip' => [
            'title' => 'Sistem Akuntabilitas Kinerja Instansi Pemerintah (SAKIP)',
            'description' => 'Dokumen SAKIP Disdukcapil Kabupaten Pesisir Barat.',
        ],

        'lppd' => [
            'title' => 'LPPD',
            'description' => 'Laporan Penyelenggaraan Pemerintahan Daerah (LPPD).',
        ],

        'rencana-aksi' => [
            'title' => 'Rencana Aksi (RA)',
            'description' => 'Rencana aksi pelaksanaan program dan kegiatan instansi.',
        ],

        'calk' => [
            'title' => 'Catatan Atas Laporan Keuangan (CALK)',
            'description' => 'Catatan atas laporan keuangan Disdukcapil Pesisir Barat.',
        ],

        'pejabat-pelaksana-teknis' => [
            'title' => 'Pejabat Pelaksana Teknis Kegiatan',
            'description' => 'Daftar pejabat pelaksana teknis kegiatan (PPTK) di lingkungan Disdukcapil Pesisir Barat.',
        ],

        'bmd' => [
            'title' => 'Barang Milik Daerah (BMD)',
            'description' => 'Daftar dan pengelolaan barang milik daerah pada Disdukcapil.',
        ],

        'spip' => [
            'title' => 'Sistem Pengendalian Intern Pemerintah (SPIP)',
            'description' => 'Penyelenggaraan Sistem Pengendalian Intern Pemerintah (SPIP) di Disdukcapil.',
        ],

        'renstra-opd' => [
            'title' => 'Renstra OPD',
            'description' => 'Rencana Strategis Organisasi Perangkat Daerah.',
        ],

        'standar-pelayanan' => [
            'title' => 'Standar Pelayanan',
            'description' => 'Standar pelayanan publik Disdukcapil Pesisir Barat.',
        ],

        'iku' => [
            'title' => 'Indikator Kinerja Utama (IKU)',
            'description' => 'Indikator kinerja utama instansi.',
        ],

        'perjanjian-kinerja' => [
            'title' => 'Perjanjian Kinerja',
            'description' => 'Perjanjian kinerja pejabat Disdukcapil Pesisir Barat.',
        ],

        'sop' => [
            'title' => 'Standar Operasional Prosedur (SOP)',
            'description' => 'SOP PPID Disdukcapil Pesisir Barat.',
        ],

        'lhkpn' => [
            'title' => 'LHKPN',
            'description' => 'Laporan Harta Kekayaan Penyelenggara Negara pejabat di lingkungan Disdukcapil Pesisir Barat.',
            'body' => [
                'LHKPN (Laporan Harta Kekayaan Penyelenggara Negara) adalah laporan seluruh harta kekayaan yang wajib disampaikan penyelenggara negara kepada Komisi Pemberantasan Korupsi (KPK), sesuai UU No. 28 Tahun 1999 dan Peraturan KPK No. 2 Tahun 2020. Kewajiban ini berlaku antara lain bagi pejabat struktural di lingkungan Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat.',
                'Pelaporan dilakukan setiap tahun melalui aplikasi e-LHKPN milik KPK. Pengumuman harta kekayaan pejabat yang telah dilaporkan dapat diakses publik melalui menu e-Announcement pada situs resmi e-LHKPN.',
            ],
            'list' => [
                'Penyampaian LHKPN: paling lambat 31 Maret setiap tahun (periode pelaporan tahun sebelumnya)',
                'Wajib lapor: Kepala Dinas, Sekretaris, dan pejabat struktural sesuai ketentuan',
                'Kanal pelaporan: aplikasi e-LHKPN KPK (elhkpn.kpk.go.id)',
                'Pengumuman harta kekayaan dapat dicari publik melalui menu e-Announcement',
            ],
            'links' => [
                ['label' => 'Buka e-LHKPN KPK (e-Announcement)', 'href' => 'https://elhkpn.kpk.go.id', 'external' => true],
            ],
        ],

        'zona-integritas' => [
            'title' => 'Zona Integritas',
            'description' => 'Pembangunan zona integritas menuju WBK/WBBM.',
        ],

        'pengendalian-gratifikasi' => [
            'title' => 'Pengendalian Gratifikasi',
            'description' => 'Kebijakan dan pelaporan pengendalian gratifikasi.',
        ],

        // ── Grup ketiga: "Layanan & Formulir PPID" ──────────────────────────
        // `formulir-ppid` & `register-ppid` TIDAK di sini — keduanya halaman
        // dua-seksi tersendiri (lihat `config/ppid-layanan.php`), bukan halaman
        // informasi satu blok.
        'sk-disdukcapil' => [
            'title' => 'SK Disdukcapil',
            'description' => 'Surat Keputusan Kepala Dinas terkait penetapan PPID dan pengelolaan informasi publik di lingkungan Disdukcapil Pesisir Barat.',
            'body' => [
                'Surat Keputusan (SK) menjadi dasar hukum penetapan pejabat dan tim pengelola layanan informasi publik. Dokumen resmi dapat diunduh pada tabel berkas di bawah.',
            ],
        ],

        'uji-konsekuensi' => [
            'title' => 'Uji Konsekuensi',
            'description' => 'Hasil uji konsekuensi atas informasi yang dikecualikan, sesuai Pasal 17 UU No. 14 Tahun 2008 tentang Keterbukaan Informasi Publik.',
            'body' => [
                'Uji konsekuensi adalah pengujian yang dilakukan PPID untuk menetapkan suatu informasi termasuk dikecualikan atau tidak, dengan mempertimbangkan konsekuensi yang timbul apabila informasi tersebut dibuka. Dokumen hasil uji konsekuensi dapat diunduh pada tabel berkas di bawah.',
            ],
        ],

        'sengketa-informasi' => [
            'title' => 'Tata Cara Penyelesaian Sengketa Informasi',
            'description' => 'Mekanisme penyelesaian sengketa informasi publik bila pemohon tidak puas atas tanggapan keberatan.',
            'body' => [
                'Apabila pemohon informasi tidak puas terhadap tanggapan atas keberatan, pemohon dapat mengajukan penyelesaian sengketa informasi kepada Komisi Informasi sesuai ketentuan yang berlaku.',
            ],
            'list' => [
                'Ajukan keberatan lebih dulu kepada Atasan PPID paling lambat 30 hari kerja sejak alasan keberatan ditemukan',
                'Atasan PPID menanggapi keberatan paling lambat 30 hari kerja sejak keberatan dicatat',
                'Bila tanggapan tidak memuaskan, ajukan sengketa ke Komisi Informasi paling lambat 14 hari kerja setelahnya',
                'Komisi Informasi menempuh mediasi dan/atau ajudikasi nonlitigasi',
                'Putusan Komisi Informasi dapat diajukan keberatan ke pengadilan sesuai ketentuan',
            ],
        ],

        'inovasi-layanan' => [
            'title' => 'Inovasi Layanan',
            'description' => 'Inovasi layanan administrasi kependudukan dan keterbukaan informasi publik Disdukcapil Kabupaten Pesisir Barat.',
            'body' => [
                'Disdukcapil Pesisir Barat terus mengembangkan inovasi untuk mempercepat dan mempermudah layanan kepada masyarakat. Dokumen dan materi terkait inovasi layanan dapat diunduh pada tabel berkas di bawah.',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | WBS — Whistle Blowing System
    |--------------------------------------------------------------------------
    |
    | Menu Pengaduan & WBS disatukan mengikuti SIDAKO: dua kanal ini isinya sama
    | dan menuju endpoint yang sama (`POST /api/pengaduan`). `/pengaduan` lama
    | di-redirect ke `/wbs/tentang-wbs` supaya tautan lama tidak mati.
    |
    */
    'wbs' => [

        'tentang-wbs' => [
            'title' => 'Tentang WBS',
            'description' => 'Whistle Blowing System Disdukcapil Kabupaten Pesisir Barat.',
            'body' => [
                'Whistle Blowing System (WBS) adalah sarana pelaporan dugaan penyalahgunaan wewenang, pelanggaran kode etik, kecurangan, gratifikasi, atau perbuatan lain yang merugikan masyarakat/instansi di lingkungan Disdukcapil Kabupaten Pesisir Barat. Identitas pelapor dijamin kerahasiaannya.',
            ],
            'list' => [
                'Laporkan hanya dugaan pelanggaran yang Anda ketahui atau alami sendiri',
                'Sertakan kronologi: apa, siapa, kapan, di mana, dan bagaimana kejadiannya',
                'Lampirkan bukti pendukung bila ada — laporan berbukti lebih cepat ditindaklanjuti',
                'Identitas pelapor hanya diketahui petugas yang menangani laporan',
            ],
            // 🔴 Formulirnya memang dipasang di halaman ini (bukan halaman
            // terpisah): warga yang membaca tata caranya bisa langsung melapor
            // tanpa berpindah halaman dan kehilangan konteksnya.
            //
            // Varian `wbs`, BUKAN `pengaduan`: di sini nama boleh dikosongkan
            // (pelapor anonim) dan ada dua field khusus — waktu/tempat kejadian
            // dan pihak yang diduga terlibat.
            'formulir' => 'wbs',
        ],

        'form-pengaduan' => [
            'title' => 'Form Pengaduan WBS',
            'description' => 'Sampaikan laporan dugaan pelanggaran melalui form pengaduan resmi.',
            'body' => [
                'Isi formulir di bawah ini selengkap mungkin. Untuk pengaduan layanan pada umumnya (bukan dugaan pelanggaran), gunakan halaman Pengaduan & Konsultasi di menu Pusat Bantuan.',
            ],
            'formulir' => 'wbs',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Pusat Bantuan — menu baru mengikuti SIDAKO (permintaan dinas, poin 3)
    |--------------------------------------------------------------------------
    |
    | Isi FAQ TIDAK di sini — daftar tanya-jawabnya blok CMS tersendiri
    | (`pusat-bantuan.faq`, lihat `config/konten.php`) supaya petugas bisa
    | menambah/mengubah pertanyaan lewat dashboard tanpa menyentuh kode.
    |
    */
    'pusat-bantuan' => [

        'faq' => [
            'title' => 'Pertanyaan yang Sering Diajukan (FAQ)',
            'description' => 'Jawaban atas pertanyaan yang paling sering ditanyakan warga seputar layanan Disdukcapil Kabupaten Pesisir Barat.',
            'body' => [
                'Belum menemukan jawabannya? Sampaikan lewat halaman Pengaduan & Konsultasi, atau hubungi kanal layanan yang tercantum di sana.',
            ],
            'faq' => true,
        ],

        'pengaduan-konsultasi' => [
            'title' => 'Pengaduan & Konsultasi',
            'description' => 'Sampaikan pengaduan atau konsultasi layanan kependudukan — kami siap mendengar dan membantu Anda.',
            'body' => [
                'Pengaduan dan konsultasi dapat disampaikan melalui kanal yang tersedia: datang langsung ke kantor Disdukcapil, WhatsApp, email, website, SP4N-Lapor, maupun media sosial resmi. Setiap laporan dicatat, diverifikasi, lalu diteruskan kepada bidang terkait untuk ditindaklanjuti.',
                'Anda juga dapat langsung mengisi formulir di bawah ini. Kami akan menyampaikan jawaban atau solusinya melalui kanal yang Anda gunakan.',
            ],
            'formulir' => 'pengaduan',
        ],

        'penipuan-ikd' => [
            'title' => 'Waspada Penipuan Aktivasi IKD',
            'description' => 'Kenali modus penipuan yang mengatasnamakan Disdukcapil Pesisir Barat dalam aktivasi Identitas Kependudukan Digital.',
            'body' => [
                'Beredar upaya penipuan berupa video call atau telepon dari pihak yang mengatasnamakan Disdukcapil Kabupaten Pesisir Barat dengan dalih aktivasi Identitas Kependudukan Digital (IKD). Kenali ciri-cirinya agar Anda tidak menjadi korban.',
            ],
            'list' => [
                'Disdukcapil TIDAK melakukan panggilan video call atau telepon untuk aktivasi IKD.',
                'Disdukcapil TIDAK pernah meminta kata sandi, PIN, foto dokumen, atau data perbankan.',
                'Aktivasi IKD TIDAK dipungut biaya alias gratis.',
                'Aktivasi IKD hanya dilakukan di kantor Disdukcapil resmi atau melalui petugas resmi yang melakukan jemput bola.',
                'Unduh aplikasi IKD resmi hanya melalui PlayStore atau AppStore.',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Hubungi Kami
    |--------------------------------------------------------------------------
    |
    | Tidak lagi di navbar (mengikuti SIDAKO) — kontaknya sudah permanen di
    | footer. Halamannya tetap ada dan ditautkan dari sana.
    |
    | ⚠️ Alamat & kontak di bawah ini PESISIR BARAT, bukan salinan Tana Tidung.
    | Zona waktunya **WIB** — Lampung, bukan WITA (jebakan lama: halaman
    | Pengaduan portal Next.js sempat menulis WITA karena disalin dari SIDAKO).
    |
    */
    'hubungi-kami' => [

        'alamat' => [
            'title' => 'Alamat Disdukcapil',
            'description' => 'Alamat dan informasi kontak kantor Disdukcapil Kabupaten Pesisir Barat.',
            'body' => [
                'Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat',
                'Jl. Raya Krui — Liwa, Way Mengaku, Kecamatan Pesisir Tengah, Kabupaten Pesisir Barat, Lampung',
            ],
        ],

        'kontak' => [
            'title' => 'Kontak Kami',
            'description' => 'Hubungi Disdukcapil Kabupaten Pesisir Barat melalui kanal berikut.',
            'list' => [
                'Email: disdukcapil@pesisirbaratkab.go.id',
                'Jam Layanan: Senin–Jumat, 08.00–16.00 WIB',
            ],
        ],

        'kritik-saran' => [
            'title' => 'Kritik & Saran',
            'description' => 'Sampaikan kritik dan saran Anda untuk peningkatan pelayanan Disdukcapil Kabupaten Pesisir Barat.',
            'body' => [
                'Setiap masukan menjadi bahan evaluasi peningkatan mutu layanan. Isi formulir di bawah ini — masukan Anda langsung masuk ke meja petugas.',
            ],
            'formulir' => 'kritik-saran',
        ],

        'pengaduan-masyarakat' => [
            'title' => 'Pengaduan Masyarakat',
            'description' => 'Sampaikan keluhan atau saran terkait pelayanan Disdukcapil.',
            'formulir' => 'pengaduan',
        ],

    ],

];
