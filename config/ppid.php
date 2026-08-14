<?php

/*
|--------------------------------------------------------------------------
| Dua klasifikasi Informasi Publik PPID (UU No. 14 Tahun 2008)
|--------------------------------------------------------------------------
|
| Port `lib/ppid-informasi.ts`. Keduanya halaman INDEKS berisi kartu yang
| menunjuk ke halaman `/ppid/{slug}` di `config/info-halaman.php` — jadi kalau
| menambah kartu di sini, halaman tujuannya harus ada di sana juga.
|
| `icon` disimpan sebagai NAMA (bukan komponen) supaya kartunya kelak bisa
| diubah petugas lewat Konten Halaman tanpa menyentuh kode. Pemetaan nama →
| SVG ada di `resources/views/components/ikon.blade.php`.
|
| `gradasi` ditulis sebagai kelas Tailwind LENGKAP — kelas yang dirakit dari
| potongan tidak pernah ikut ter-scan ke CSS.
|
*/

return [

    'informasi-setiap-saat' => [
        'judul' => 'Informasi Wajib Tersedia Setiap Saat',
        'judulPendek' => 'Setiap Saat',
        'deskripsi' => 'Daftar Informasi Publik yang wajib disediakan dan dapat diakses masyarakat setiap saat, sesuai amanat Undang-Undang Nomor 14 Tahun 2008 tentang Keterbukaan Informasi Publik. Pilih salah satu kategori untuk melihat dokumen resminya.',
        'items' => [
            [
                'title' => 'Laporan PPID Pelaksana',
                'href' => '/ppid/laporan-ppid-pelaksana',
                'description' => 'Laporan pelaksanaan tugas layanan informasi publik PPID Pelaksana.',
                'icon' => 'FileText',
                'gradasi' => 'from-sky-400 to-sky-600',
            ],
            [
                'title' => 'LKJIP (Laporan Kinerja Instansi Pemerintah)',
                'href' => '/ppid/lkjip',
                'description' => 'Laporan kinerja tahunan Disdukcapil Kabupaten Pesisir Barat.',
                'icon' => 'ClipboardCheck',
                'gradasi' => 'from-amber-400 to-amber-600',
            ],
            [
                'title' => 'Survey Kepuasan Masyarakat',
                'href' => '/ppid/survey-kepuasan-masyarakat',
                'description' => 'Hasil pengukuran kepuasan masyarakat terhadap pelayanan publik.',
                'icon' => 'Smile',
                'gradasi' => 'from-emerald-400 to-emerald-600',
            ],
            [
                'title' => 'Buku Profil Kependudukan',
                'href' => '/ppid/buku-profil-kependudukan',
                'description' => 'Buku profil data kependudukan Kabupaten Pesisir Barat.',
                'icon' => 'BookOpen',
                'gradasi' => 'from-violet-400 to-violet-600',
            ],
            [
                'title' => 'Dokumen Pelaksana Anggaran (DPA)',
                'href' => '/ppid/dpa',
                'description' => 'Dokumen pelaksanaan anggaran tahunan perangkat daerah.',
                'icon' => 'Coins',
                'gradasi' => 'from-teal-400 to-teal-600',
            ],
            [
                'title' => 'Indikator Kinerja Individu (IKI)',
                'href' => '/ppid/iki',
                'description' => 'Indikator kinerja individu pegawai di lingkungan Disdukcapil.',
                'icon' => 'Target',
                'gradasi' => 'from-rose-400 to-rose-600',
            ],
            [
                'title' => 'Rencana Kinerja Tahunan (RKT)',
                'href' => '/ppid/rkt',
                'description' => 'Rencana kinerja tahunan Disdukcapil Kabupaten Pesisir Barat.',
                'icon' => 'CalendarDays',
                'gradasi' => 'from-[#2e6da4] to-[#1b4b72]',
            ],
            [
                'title' => 'Rencana Kerja (Renka)',
                'href' => '/ppid/renka',
                'description' => 'Rencana kerja tahunan instansi.',
                'icon' => 'ClipboardList',
                'gradasi' => 'from-slate-500 to-slate-700',
            ],
            [
                'title' => 'Perjanjian Kerjasama',
                'href' => '/ppid/perjanjian-kerjasama',
                'description' => 'Daftar perjanjian kerjasama Disdukcapil dengan pihak lain.',
                'icon' => 'Handshake',
                'gradasi' => 'from-cyan-400 to-cyan-600',
            ],
            [
                'title' => 'Rencana Kerja dan Anggaran (RKA)',
                'href' => '/ppid/rka',
                'description' => 'Dokumen rencana kerja dan anggaran tahunan perangkat daerah.',
                'icon' => 'Coins',
                'gradasi' => 'from-sky-400 to-sky-600',
            ],
            [
                'title' => 'Laporan Realisasi Anggaran (LRA)',
                'href' => '/ppid/lra',
                'description' => 'Laporan realisasi anggaran pendapatan dan belanja instansi.',
                'icon' => 'FileText',
                'gradasi' => 'from-emerald-400 to-emerald-600',
            ],
            [
                'title' => 'Realisasi Fisik dan Keuangan (RFK)',
                'href' => '/ppid/rfk',
                'description' => 'Laporan realisasi fisik dan keuangan pelaksanaan kegiatan.',
                'icon' => 'Gauge',
                'gradasi' => 'from-amber-400 to-amber-600',
            ],
            [
                'title' => 'RUP Pengadaan',
                'href' => '/ppid/rup-pengadaan',
                'description' => 'Rencana Umum Pengadaan barang/jasa Disdukcapil.',
                'icon' => 'ClipboardList',
                'gradasi' => 'from-violet-400 to-violet-600',
            ],
            [
                'title' => 'Capaian Indikator Kinerja (Cakin)',
                'href' => '/ppid/cakin',
                'description' => 'Capaian indikator kinerja Disdukcapil Kabupaten Pesisir Barat.',
                'icon' => 'Target',
                'gradasi' => 'from-teal-400 to-teal-600',
            ],
            [
                'title' => 'Laporan Kinerja (Lapkin)',
                'href' => '/ppid/lapkin',
                'description' => 'Laporan kinerja pelaksanaan program dan kegiatan Disdukcapil.',
                'icon' => 'ClipboardCheck',
                'gradasi' => 'from-rose-400 to-rose-600',
            ],
            [
                'title' => 'SAKIP',
                'href' => '/ppid/sakip',
                'description' => 'Sistem Akuntabilitas Kinerja Instansi Pemerintah.',
                'icon' => 'BadgeCheck',
                'gradasi' => 'from-[#2e6da4] to-[#1b4b72]',
            ],
            [
                'title' => 'LPPD',
                'href' => '/ppid/lppd',
                'description' => 'Laporan Penyelenggaraan Pemerintahan Daerah.',
                'icon' => 'FileCheck',
                'gradasi' => 'from-slate-500 to-slate-700',
            ],
            [
                'title' => 'Rencana Aksi (RA)',
                'href' => '/ppid/rencana-aksi',
                'description' => 'Rencana aksi pelaksanaan program dan kegiatan instansi.',
                'icon' => 'Flag',
                'gradasi' => 'from-cyan-400 to-cyan-600',
            ],
            [
                'title' => 'Catatan Atas Laporan Keuangan (CALK)',
                'href' => '/ppid/calk',
                'description' => 'Catatan atas laporan keuangan Disdukcapil Pesisir Barat.',
                'icon' => 'BookOpen',
                'gradasi' => 'from-amber-400 to-amber-600',
            ],
            [
                'title' => 'Pejabat Pelaksana Teknis Kegiatan',
                'href' => '/ppid/pejabat-pelaksana-teknis',
                'description' => 'Daftar PPTK di lingkungan Disdukcapil Pesisir Barat.',
                'icon' => 'Users',
                'gradasi' => 'from-sky-400 to-sky-600',
            ],
            [
                'title' => 'Barang Milik Daerah (BMD)',
                'href' => '/ppid/bmd',
                'description' => 'Daftar dan pengelolaan barang milik daerah pada Disdukcapil.',
                'icon' => 'Landmark',
                'gradasi' => 'from-emerald-400 to-emerald-600',
            ],
        ],
    ],

    'informasi-berkala' => [
        'judul' => 'Informasi Wajib Diumumkan Secara Berkala',
        'judulPendek' => 'Berkala',
        'deskripsi' => 'Daftar Informasi Publik yang wajib disediakan dan diumumkan secara berkala oleh Badan Publik, sesuai amanat Undang-Undang Nomor 14 Tahun 2008 tentang Keterbukaan Informasi Publik. Pilih salah satu kategori untuk melihat dokumen resminya.',
        'items' => [
            [
                'title' => 'Renstra OPD',
                'href' => '/ppid/renstra-opd',
                'description' => 'Rencana Strategis Organisasi Perangkat Daerah.',
                'icon' => 'Flag',
                'gradasi' => 'from-sky-400 to-sky-600',
            ],
            [
                'title' => 'Standar Pelayanan',
                'href' => '/ppid/standar-pelayanan',
                'description' => 'Standar pelayanan publik Disdukcapil Kabupaten Pesisir Barat.',
                'icon' => 'BadgeCheck',
                'gradasi' => 'from-emerald-400 to-emerald-600',
            ],
            [
                'title' => 'Indikator Kinerja Utama (IKU)',
                'href' => '/ppid/iku',
                'description' => 'Indikator kinerja utama instansi.',
                'icon' => 'Gauge',
                'gradasi' => 'from-amber-400 to-amber-600',
            ],
            [
                'title' => 'Perjanjian Kinerja',
                'href' => '/ppid/perjanjian-kinerja',
                'description' => 'Perjanjian kinerja pejabat Disdukcapil Kabupaten Pesisir Barat.',
                'icon' => 'FileCheck',
                'gradasi' => 'from-violet-400 to-violet-600',
            ],
            [
                'title' => 'Standar Operasional Prosedur (SOP)',
                'href' => '/ppid/sop',
                'description' => 'Standar operasional prosedur pelayanan.',
                'icon' => 'ClipboardCheck',
                'gradasi' => 'from-teal-400 to-teal-600',
            ],
            [
                'title' => 'LHKPN',
                'href' => '/ppid/lhkpn',
                'description' => 'Laporan Harta Kekayaan Penyelenggara Negara pejabat Disdukcapil.',
                'icon' => 'Wallet',
                'gradasi' => 'from-rose-400 to-rose-600',
            ],
            [
                'title' => 'Zona Integritas',
                'href' => '/ppid/zona-integritas',
                'description' => 'Pembangunan zona integritas menuju WBK/WBBM.',
                'icon' => 'ShieldCheck',
                'gradasi' => 'from-[#2e6da4] to-[#1b4b72]',
            ],
            [
                'title' => 'Pengendalian Gratifikasi',
                'href' => '/ppid/pengendalian-gratifikasi',
                'description' => 'Kebijakan dan pelaporan pengendalian gratifikasi.',
                'icon' => 'Gift',
                'gradasi' => 'from-cyan-400 to-cyan-600',
            ],
            [
                'title' => 'SPIP',
                'href' => '/ppid/spip',
                'description' => 'Sistem Pengendalian Intern Pemerintah di lingkungan Disdukcapil.',
                'icon' => 'ShieldCheck',
                'gradasi' => 'from-slate-500 to-slate-700',
            ],
        ],
    ],

];
