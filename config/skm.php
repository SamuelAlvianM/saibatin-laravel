<?php

/*
|--------------------------------------------------------------------------
| Survei Kepuasan Masyarakat (SKM)
|--------------------------------------------------------------------------
|
| Standar nasional Permenpan RB No. 14 Tahun 2017: **9 unsur, skala 1–4**
| (bukan 1–5).
|
| 🔴 Daftar unsur di bawah diambil PERSIS dari portal lama (tabel
| `m_mediainformasi_skm_pertanyaan`, urut kolom `sort`). Jawaban disimpan di
| `t_skm_jawaban.jawaban` sebagai { "0": nilai, …, "8": nilai } — indeksnya
| menunjuk ke posisi di array ini. **Mengubah urutan = merusak makna 203
| jawaban warga yang sudah dimigrasikan**, tanpa ada yang terlihat rusak.
|
| Catatan perhitungan IKM (dipakai nanti di dashboard): rumusnya NRR tertimbang
| × 25, BUKAN rata-rata dibagi 5. Di project saudara kesalahan itu membuat nilai
| tampil 0,00 / mutu D padahal datanya ada.
|
*/

return [

    'skala_max' => 4,

    /** Label tiap nilai (indeks = nilai − 1). */
    'skala_label' => [
        'Tidak Baik',
        'Kurang Baik',
        'Baik',
        'Sangat Baik',
    ],

    'aspek' => [
        'Kesesuaian persyaratan pelayanan dengan jenis pelayanan yang didapatkan',
        'Kemudahan prosedur pelayanan Administrasi Kependudukan',
        'Kecepatan waktu dalam pelayanan Administrasi Kependudukan',
        'Biaya/tarif pelayanan dokumen Kependudukan dan Pencatatan Sipil',
        'Kesesuaian SOP dan Standar Pelayanan setiap produk pelayanan dengan hasil yang diterima',
        'Kemampuan dan kecakapan petugas dalam memberikan pelayanan',
        'Perilaku petugas (kesopanan dan keramahan) dalam memberikan pelayanan kepada masyarakat',
        'Kualitas sarana dan prasarana pada Dinas Kependudukan dan Pencatatan Sipil',
        'Ketersediaan pelayanan pengaduan bagi pengguna layanan',
    ],

];
