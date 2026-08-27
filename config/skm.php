<?php

/*
|--------------------------------------------------------------------------
| Survei Kepuasan Masyarakat (SKM)
|--------------------------------------------------------------------------
|
| Kuesioner RESMI DINAS (berkas Word "Daftar Pertanyaan Survei Kepuasan
| Masyarakat Disdukcapil Kabupaten Pesisir Barat", diterima 17 Agu 2026):
| **16 pertanyaan, skala 1–4**, ditambah identitas responden dan kolom keluhan.
| Dasarnya tetap Permenpan RB No. 14 Tahun 2017.
|
| 🔴 KUNCI JAWABAN `p1`–`p16` — SENGAJA BUKAN "0"–"15".
| 204 responden lama menyimpan jawabannya sebagai { "0": nilai, …, "8": nilai }
| (9 unsur, lihat `warisan` di bawah), dan di produksi ada bentuk ketiga
| `u0`–`u8`. Kalau kuesioner baru ikut memakai indeks angka, jawaban lama akan
| terbaca sebagai jawaban 9 pertanyaan PERTAMA kuesioner baru — padahal
| pertanyaannya beda sama sekali (unsur lama no. 1 "kesesuaian persyaratan"
| vs pertanyaan baru no. 1 "informasi pelayanan tersedia melalui media
| elektronik"). Rekapnya akan tampak wajar dan diam-diam salah.
|
| Rumus IKM: **NRR × 25** (rata-rata skala 1–4 dikali 25), bukan rata-rata
| dibagi 5. Kesalahan itu pernah membuat nilai tampil 0,00 / mutu D di project
| saudara padahal datanya ada.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Sakelar: survei dibuka untuk warga atau belum
    |--------------------------------------------------------------------------
    |
    | 🔴 Bawaannya TERTUTUP. Keputusan user 18 Agu: kuesioner baru ditunjukkan
    | ke dinas dulu sebelum warga boleh mengisinya — dan cutover ke cPanel
    | terjadi sebelum persetujuan itu turun.
    |
    | Tertutup TIDAK berarti halamannya hilang: petugas yang login tetap melihat
    | formulir lengkap (dengan spanduk pratinjau) supaya dinas bisa memeriksanya
    | langsung di portal produksi, sementara warga melihat pemberitahuan singkat.
    | Penjaganya ada di DUA tempat — halaman dan endpoint `POST /api/skm` —
    | karena halaman yang cuma menyembunyikan formulir masih bisa dilewati
    | dengan mengirim permintaan langsung.
    |
    | Membukanya: `SKM_TERBUKA=true` di `.env`, lalu `php artisan config:clear`
    | (atau `config:cache` lagi bila konfigurasinya di-cache — di server memang
    | di-cache, dan tanpa langkah itu perubahan `.env` tidak berpengaruh apa pun).
    |
    */
    'terbuka' => filter_var(env('SKM_TERBUKA', false), FILTER_VALIDATE_BOOLEAN),

    'skala_max' => 4,

    /*
    | Dua ragam label, sesuai berkas dinas: sebagian pertanyaan memakai
    | "setuju", sebagian "sesuai". Bukan gaya — kalimat pertanyaannya memang
    | berbeda bentuk, dan menyeragamkannya membuat pilihan jawaban terbaca
    | janggal ("Sangat setuju" untuk "Biaya layanan sesuai dengan yang
    | diinformasikan").
    */
    'skala_label' => [
        'setuju' => ['Sangat tidak setuju', 'Tidak setuju', 'Setuju', 'Sangat setuju'],
        'sesuai' => ['Sangat tidak sesuai', 'Tidak sesuai', 'Sesuai', 'Sangat sesuai'],
    ],

    /** Label ringkas untuk rekap dashboard (tidak per-pertanyaan). */
    'skala_label_umum' => ['Tidak Baik', 'Kurang Baik', 'Baik', 'Sangat Baik'],

    /*
    | 16 pertanyaan, urutannya PERSIS berkas dinas (kolom kiri 1–8, kanan 9–16).
    | `ringkas` dipakai label grafik rekap yang tidak muat kalimat penuh.
    */
    'pertanyaan' => [
        ['kunci' => 'p1', 'skala' => 'setuju', 'ringkas' => 'Ketersediaan informasi', 'teks' => 'Informasi pelayanan tersedia melalui media elektronik maupun nonelektronik'],
        ['kunci' => 'p2', 'skala' => 'sesuai', 'ringkas' => 'Kesesuaian persyaratan', 'teks' => 'Kesesuaian persyaratan dengan informasi yang diberikan'],
        ['kunci' => 'p3', 'skala' => 'setuju', 'ringkas' => 'Kejelasan standar & prosedur', 'teks' => 'Standar dan prosedur layanan diinformasikan dengan jelas'],
        ['kunci' => 'p4', 'skala' => 'setuju', 'ringkas' => 'Kemudahan alur layanan', 'teks' => 'Prosedur/Alur layanan mudah dipahami dan dilakukan'],
        ['kunci' => 'p5', 'skala' => 'setuju', 'ringkas' => 'Layanan tanpa kecurangan', 'teks' => 'Layanan diberikan sesuai prosedur tanpa kecurangan'],
        ['kunci' => 'p6', 'skala' => 'sesuai', 'ringkas' => 'Jangka waktu layanan', 'teks' => 'Jangka waktu layanan sesuai dengan yang diinformasikan'],
        ['kunci' => 'p7', 'skala' => 'sesuai', 'ringkas' => 'Biaya layanan', 'teks' => 'Biaya layanan sesuai dengan yang diinformasikan'],
        ['kunci' => 'p8', 'skala' => 'setuju', 'ringkas' => 'Bebas pungli', 'teks' => 'Tidak ada pungutan liar (pungli) dalam pelayanan'],
        ['kunci' => 'p9', 'skala' => 'setuju', 'ringkas' => 'Bebas percaloan', 'teks' => 'Tidak ada percaloan/perantara tidak resmi dalam pelayanan'],
        ['kunci' => 'p10', 'skala' => 'sesuai', 'ringkas' => 'Produk sesuai publikasi', 'teks' => 'Produk layanan yang diterima sesuai dengan yang dipublikasikan'],
        ['kunci' => 'p11', 'skala' => 'setuju', 'ringkas' => 'Kecepatan aplikasi', 'teks' => 'Aplikasi sistem pelayanan merespon kebutuhan dengan cepat (membuka halaman, konten, pencarian informasi, unduh/unggah)'],
        ['kunci' => 'p12', 'skala' => 'setuju', 'ringkas' => 'Kemudahan fitur aplikasi', 'teks' => 'Fitur pada aplikasi sistem layanan mudah digunakan'],
        ['kunci' => 'p13', 'skala' => 'setuju', 'ringkas' => 'Tanpa diskriminasi', 'teks' => 'Seluruh pengguna layanan dilayani secara adil tanpa diskriminasi'],
        ['kunci' => 'p14', 'skala' => 'setuju', 'ringkas' => 'Tanpa imbalan', 'teks' => 'Pelayanan diberikan tanpa imbalan uang, barang, atau fasilitas di luar aturan'],
        ['kunci' => 'p15', 'skala' => 'setuju', 'ringkas' => 'Konsultasi & pengaduan', 'teks' => 'Layanan konsultasi dan pengaduan mudah diakses'],
        ['kunci' => 'p16', 'skala' => 'setuju', 'ringkas' => 'Kenyamanan layanan online', 'teks' => 'Sistem layanan online nyaman dan mudah digunakan'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Identitas responden
    |--------------------------------------------------------------------------
    |
    | Seluruhnya OPSIONAL, mengikuti berkas dinas: nama "boleh inisial atau
    | tidak diisi", instansi ditandai opsional. Yang wajib hanya 16 penilaian —
    | jawaban setengah terisi merusak NRR tanpa terlihat rusak.
    |
    */
    'pendidikan' => [
        'Tidak sekolah', 'SD', 'SMP/SLTP', 'SLTA',
        'Diploma (D1/D2/D3)', 'Sarjana (D4/S1)', 'Pasca Sarjana (S2)', 'Pasca Sarjana (S3)',
    ],

    'pekerjaan' => [
        'ASN', 'TNI', 'POLRI', 'Wirausaha', 'Swasta', 'Petani / Nelayan',
        'Pensiunan', 'Pelajar / Mahasiswa', 'Ibu Rumah Tangga',
        'Pekerja Lepas / Freelance', 'Lainnya',
    ],

    /*
    | Pertanyaan disabilitas ikut ditanyakan karena dinas memang memakainya
    | untuk menilai keterjangkauan layanan — dan portal ini punya widget
    | aksesibilitas 14 kontrol, jadi angkanya berguna, bukan sekadar kolom.
    */
    'jenis_disabilitas' => [
        'Disabilitas Fisik', 'Disabilitas Intelektual',
        'Disabilitas Mental', 'Disabilitas Sensorik',
        // Bukan dari berkas dinas — tambahan atas permintaan user. Gunanya
        // untuk pendamping (yang menjawab "Ya" tapi dirinya sendiri bukan
        // penyandang) dan responden yang tidak ingin menyebutkan jenisnya:
        // tanpa pilihan ini mereka harus meninggalkan isian kosong, yang di
        // rekap tidak bisa dibedakan dari "belum sempat diisi".
        'Tidak ada',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kuesioner WARISAN — 9 unsur, jangan dihapus
    |--------------------------------------------------------------------------
    |
    | 204 responden (di produksi 203 + data lokal) menjawab kuesioner ini, dan
    | jawabannya tersimpan dengan kunci angka `0`–`8` (bentuk lain di produksi:
    | `u0`–`u8`). Daftar di bawah dipakai rekap dashboard supaya jawaban mereka
    | tetap punya makna dan tetap ikut dihitung ke IKM — menghapusnya berarti
    | membuang 204 responden dari laporan dinas.
    |
    | 🔴 URUTANNYA TIDAK BOLEH DIUBAH: indeks jawaban menunjuk posisi di sini.
    |
    */
    'warisan' => [
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
