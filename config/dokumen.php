<?php

/*
|--------------------------------------------------------------------------
| Registry kategori dokumen publikasi (t_produk kolom `jenis`)
|--------------------------------------------------------------------------
|
| Port `lib/dokumen-registry.ts`. Satu sumber kebenaran untuk dua hal:
| dropdown kategori di dashboard, dan halaman publik mana yang menampilkan
| berkasnya — sehingga admin tahu "PDF ini bakal muncul di halaman apa".
|
| 🔴 `key` = nilai kolom `jenis` yang SUDAH ada di produksi. Jangan dirapikan
| (mis. `SKM_LAPORAN` → `LAPORAN_SKM`): dokumen lama akan hilang dari halaman
| publiknya tanpa satu pun pesan galat.
|
| `DAFDUK` sah sebagai nilai lama tapi tidak punya kartu sendiri di sini —
| sama seperti aslinya, ia hanya diterima saat menyimpan.
|
*/

return [

    'legacy' => ['DAFDUK'],

    'kategori' => [
        // ── Produk layanan ──────────────────────────────────────────────────
        ['key' => 'PERSYARATAN', 'label' => 'Formulir & Persyaratan', 'group' => 'Produk Layanan', 'halaman' => [
            ['label' => 'Produk → Formulir & Persyaratan', 'href' => '/produk/formulir-persyaratan'],
        ]],
        ['key' => 'HUKUM', 'label' => 'Produk Hukum', 'group' => 'Produk Layanan', 'halaman' => [
            ['label' => 'Produk → Produk Hukum', 'href' => '/produk/hukum'],
        ]],
        ['key' => 'SOP', 'label' => 'SOP', 'group' => 'Produk Layanan', 'halaman' => [
            ['label' => 'Produk → SOP', 'href' => '/produk/sop'],
            ['label' => 'PPID → SOP', 'href' => '/ppid/sop'],
        ]],
        ['key' => 'STANDAR_PELAYANAN', 'label' => 'Standar Pelayanan', 'group' => 'Produk Layanan', 'halaman' => [
            ['label' => 'PPID → Standar Pelayanan', 'href' => '/ppid/standar-pelayanan'],
            ['label' => 'Produk → Standar Pelayanan (SP)', 'href' => '/produk/standar-pelayanan'],
        ]],
        ['key' => 'ALUR_PELAYANAN', 'label' => 'Alur Pelayanan', 'group' => 'Produk Layanan', 'halaman' => [
            ['label' => 'Produk → Alur Pelayanan', 'href' => '/produk/alur-pelayanan'],
        ]],

        // ── PPID / transparansi ─────────────────────────────────────────────
        ['key' => 'PROFIL_PPID', 'label' => 'Profil PPID', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Profil PPID', 'href' => '/ppid/profil-ppid'],
        ]],
        ['key' => 'PEMBENTUKAN_PPID', 'label' => 'Gambaran Pembentukan PPID', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Gambaran Pembentukan', 'href' => '/ppid/gambaran-pembentukan-ppid'],
        ]],
        ['key' => 'VISI_MISI_PPID', 'label' => 'Visi & Misi PPID', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Visi & Misi', 'href' => '/ppid/visi-misi-ppid'],
        ]],
        ['key' => 'STRUKTUR_PPID', 'label' => 'Struktur Organisasi PPID', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Struktur Organisasi', 'href' => '/ppid/struktur-organisasi-ppid'],
        ]],
        ['key' => 'MAKLUMAT_PPID', 'label' => 'Maklumat PPID', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Maklumat', 'href' => '/ppid/maklumat-ppid'],
        ]],
        ['key' => 'TUGAS_PPID', 'label' => 'Tugas & Tanggung Jawab PPID', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Tugas & Tanggung Jawab', 'href' => '/ppid/tugas-tanggungjawab-ppid'],
        ]],
        ['key' => 'LHKPN', 'label' => 'LHKPN', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → LHKPN', 'href' => '/ppid/lhkpn'],
        ]],
        ['key' => 'LAPORAN_PPID', 'label' => 'Laporan PPID Pelaksana', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Laporan PPID Pelaksana', 'href' => '/ppid/laporan-ppid-pelaksana'],
        ]],
        ['key' => 'LKJIP', 'label' => 'LKJIP', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → LKJIP', 'href' => '/ppid/lkjip'],
        ]],
        ['key' => 'SKM_LAPORAN', 'label' => 'Laporan Survey Kepuasan Masyarakat', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Survey Kepuasan Masyarakat', 'href' => '/ppid/survey-kepuasan-masyarakat'],
        ]],
        ['key' => 'BUKU_PROFIL', 'label' => 'Buku Profil Kependudukan', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Buku Profil Kependudukan', 'href' => '/ppid/buku-profil-kependudukan'],
        ]],
        ['key' => 'DPA', 'label' => 'Dokumen Pelaksana Anggaran (DPA)', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → DPA', 'href' => '/ppid/dpa'],
        ]],
        ['key' => 'IKI', 'label' => 'Indikator Kinerja Individu (IKI)', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → IKI', 'href' => '/ppid/iki'],
        ]],
        ['key' => 'RKT', 'label' => 'Rencana Kinerja Tahunan (RKT)', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → RKT', 'href' => '/ppid/rkt'],
        ]],
        ['key' => 'RENKA', 'label' => 'Rencana Kerja (Renka)', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Renka', 'href' => '/ppid/renka'],
        ]],
        ['key' => 'PERJANJIAN_KERJASAMA', 'label' => 'Perjanjian Kerjasama', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Perjanjian Kerjasama', 'href' => '/ppid/perjanjian-kerjasama'],
        ]],
        ['key' => 'RENSTRA_OPD', 'label' => 'Renstra OPD', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Renstra OPD', 'href' => '/ppid/renstra-opd'],
        ]],
        ['key' => 'IKU', 'label' => 'Indikator Kinerja Utama (IKU)', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → IKU', 'href' => '/ppid/iku'],
        ]],
        ['key' => 'PERJANJIAN_KINERJA', 'label' => 'Perjanjian Kinerja', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Perjanjian Kinerja', 'href' => '/ppid/perjanjian-kinerja'],
        ]],
        ['key' => 'ZONA_INTEGRITAS', 'label' => 'Zona Integritas', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Zona Integritas', 'href' => '/ppid/zona-integritas'],
        ]],
        ['key' => 'PENGENDALIAN_GRATIFIKASI', 'label' => 'Pengendalian Gratifikasi', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → Pengendalian Gratifikasi', 'href' => '/ppid/pengendalian-gratifikasi'],
        ]],
        ['key' => 'SPIP', 'label' => 'SPIP', 'group' => 'PPID / Transparansi', 'halaman' => [
            ['label' => 'PPID → SPIP', 'href' => '/ppid/spip'],
        ]],

        // ── Anggaran & kinerja (menyusul daftar SIDAKO) ─────────────────────
        ['key' => 'RKA', 'label' => 'Rencana Kerja dan Anggaran (RKA)', 'group' => 'Anggaran & Kinerja', 'halaman' => [
            ['label' => 'PPID → RKA', 'href' => '/ppid/rka'],
        ]],
        ['key' => 'LRA', 'label' => 'Laporan Realisasi Anggaran (LRA)', 'group' => 'Anggaran & Kinerja', 'halaman' => [
            ['label' => 'PPID → LRA', 'href' => '/ppid/lra'],
        ]],
        ['key' => 'RFK', 'label' => 'Realisasi Fisik dan Keuangan (RFK)', 'group' => 'Anggaran & Kinerja', 'halaman' => [
            ['label' => 'PPID → RFK', 'href' => '/ppid/rfk'],
        ]],
        ['key' => 'RUP', 'label' => 'RUP Pengadaan', 'group' => 'Anggaran & Kinerja', 'halaman' => [
            ['label' => 'PPID → RUP Pengadaan', 'href' => '/ppid/rup-pengadaan'],
        ]],
        ['key' => 'CAKIN', 'label' => 'Capaian Indikator Kinerja (Cakin)', 'group' => 'Anggaran & Kinerja', 'halaman' => [
            ['label' => 'PPID → Cakin', 'href' => '/ppid/cakin'],
        ]],
        ['key' => 'LAPKIN', 'label' => 'Laporan Kinerja (Lapkin)', 'group' => 'Anggaran & Kinerja', 'halaman' => [
            ['label' => 'PPID → Lapkin', 'href' => '/ppid/lapkin'],
        ]],
        ['key' => 'SAKIP', 'label' => 'SAKIP', 'group' => 'Anggaran & Kinerja', 'halaman' => [
            ['label' => 'PPID → SAKIP', 'href' => '/ppid/sakip'],
        ]],
        ['key' => 'LPPD', 'label' => 'LPPD', 'group' => 'Anggaran & Kinerja', 'halaman' => [
            ['label' => 'PPID → LPPD', 'href' => '/ppid/lppd'],
        ]],
        ['key' => 'RENCANA_AKSI', 'label' => 'Rencana Aksi (RA)', 'group' => 'Anggaran & Kinerja', 'halaman' => [
            ['label' => 'PPID → Rencana Aksi', 'href' => '/ppid/rencana-aksi'],
        ]],
        ['key' => 'CALK', 'label' => 'Catatan Atas Laporan Keuangan (CALK)', 'group' => 'Anggaran & Kinerja', 'halaman' => [
            ['label' => 'PPID → CALK', 'href' => '/ppid/calk'],
        ]],
        ['key' => 'PPTK', 'label' => 'Pejabat Pelaksana Teknis Kegiatan', 'group' => 'Anggaran & Kinerja', 'halaman' => [
            ['label' => 'PPID → Pejabat Pelaksana Teknis', 'href' => '/ppid/pejabat-pelaksana-teknis'],
        ]],
        ['key' => 'BMD', 'label' => 'Barang Milik Daerah (BMD)', 'group' => 'Anggaran & Kinerja', 'halaman' => [
            ['label' => 'PPID → BMD', 'href' => '/ppid/bmd'],
        ]],

        // ── Layanan & formulir PPID ─────────────────────────────────────────
        ['key' => 'FORMULIR_PERMOHONAN', 'label' => 'Formulir Permohonan Informasi', 'group' => 'Layanan PPID', 'halaman' => [
            ['label' => 'PPID → Formulir PPID', 'href' => '/ppid/formulir-ppid'],
        ]],
        ['key' => 'FORMULIR_KEBERATAN', 'label' => 'Formulir Pernyataan Keberatan', 'group' => 'Layanan PPID', 'halaman' => [
            ['label' => 'PPID → Formulir PPID', 'href' => '/ppid/formulir-ppid'],
        ]],
        ['key' => 'SK_DISDUKCAPIL', 'label' => 'SK Disdukcapil', 'group' => 'Layanan PPID', 'halaman' => [
            ['label' => 'PPID → SK Disdukcapil', 'href' => '/ppid/sk-disdukcapil'],
        ]],
        ['key' => 'REGISTER_PERMINTAAN', 'label' => 'Register Permintaan Informasi', 'group' => 'Layanan PPID', 'halaman' => [
            ['label' => 'PPID → Register PPID', 'href' => '/ppid/register-ppid'],
        ]],
        ['key' => 'REGISTER_KEBERATAN', 'label' => 'Register Keberatan', 'group' => 'Layanan PPID', 'halaman' => [
            ['label' => 'PPID → Register PPID', 'href' => '/ppid/register-ppid'],
        ]],
        ['key' => 'UJI_KONSEKUENSI', 'label' => 'Uji Konsekuensi', 'group' => 'Layanan PPID', 'halaman' => [
            ['label' => 'PPID → Uji Konsekuensi', 'href' => '/ppid/uji-konsekuensi'],
        ]],
        ['key' => 'SENGKETA_INFORMASI', 'label' => 'Penyelesaian Sengketa Informasi', 'group' => 'Layanan PPID', 'halaman' => [
            ['label' => 'PPID → Sengketa Informasi', 'href' => '/ppid/sengketa-informasi'],
        ]],
        ['key' => 'INOVASI_LAYANAN', 'label' => 'Inovasi Layanan', 'group' => 'Layanan PPID', 'halaman' => [
            ['label' => 'PPID → Inovasi Layanan', 'href' => '/ppid/inovasi-layanan'],
            ['label' => 'Produk → Inovasi', 'href' => '/produk/inovasi'],
        ]],
    ],

];
