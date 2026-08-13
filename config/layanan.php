<?php

/*
|--------------------------------------------------------------------------
| 15 Layanan Permohonan — SUMBER KEBENARAN FORMULIR
|--------------------------------------------------------------------------
|
| Port dari `lib/layanan-forms.ts` + `lib/permohonan-layanan.ts` portal Next.js,
| yang sendirinya diturunkan dari form asli portal Laravel 9
| (`resources/views/fronts/permohonans/*.blade.php` + validasi controller-nya).
|
| 🔴 NAMA FIELD JANGAN DIUBAH. Nilainya disimpan apa adanya ke
| `t_permohonan.payload` (JSON), dan 11.902 baris produksi sudah memakai nama
| ini. Mengganti `pemohonnik` jadi `nik_pemohon` tidak akan memunculkan galat
| apa pun — data lama hanya berhenti terbaca.
|
| ⚠️ Di project saudara pernah ada sesi audit yang dibangun di atas berkas
| `*Modal.tsx` yang sudah mati; nama field & isi dropdown-nya berbeda dari yang
| asli, dan hasil auditnya salah arah. Berkas INI yang dipakai, bukan itu.
|
| Struktur tiap field:
|   name      kunci di payload (JANGAN diubah)
|   label     teks tampil
|   type      text|nik|kk|phone|email|date|time|number|textarea|select|file
|   required  wajib diisi
|   half      tampil setengah lebar (grid 2 kolom)
|   options   pilihan untuk type=select
|
*/

/** Pembuat field ringkas — meniru helper `f()` di berkas TS aslinya. */
$f = function (string $name, string $label, string $type = 'text', array $opsi = []): array {
    return array_merge(['name' => $name, 'label' => $label, 'type' => $type], $opsi);
};

// ── Blok yang dipakai ulang ────────────────────────────────────────────────

/** Data pemohon — sama untuk SEMUA layanan (persis form lama). */
$pemohon = [
    'title' => 'Data Pemohon',
    'fields' => [
        $f('pemohonnik', 'NIK Pemohon', 'nik', ['required' => true, 'half' => true]),
        $f('pemohonnama', 'Nama Pemohon', 'text', ['required' => true, 'half' => true]),
        $f('pemohonkk', 'KK Pemohon', 'kk', ['required' => true, 'half' => true]),
        $f('pemohonhp', 'No. Telp Pemohon', 'phone', ['required' => true, 'half' => true]),
        $f('pemohonemail', 'Email Pemohon', 'email', ['required' => true]),
    ],
];

$catatan = [
    'title' => 'Catatan',
    'fields' => [
        $f('catatan', 'Catatan (opsional)', 'textarea', [
            'placeholder' => 'Silahkan isi catatan disini kalau ada pesan yang akan disampaikan ke petugas...',
        ]),
    ],
];

// ── Opsi dropdown (mengikuti m_options / form lama) ────────────────────────
$OPT_JK = ['Laki-laki', 'Perempuan'];
$OPT_TEMPAT_LAHIR = ['Rumah Sakit/Bersalin', 'Puskesmas', 'Polindes', 'Rumah', 'Lainnya'];
$OPT_JENIS_LAHIR = ['Tunggal', 'Kembar Dua', 'Kembar Tiga', 'Kembar Empat', 'Kembar Banyak/Lainnya'];
$OPT_PENOLONG = ['Dokter', 'Bidan/Perawat', 'Dukun', 'Lainnya'];
$OPT_AGAMA = ['Islam', 'Kristen', 'Katholik', 'Hindu', 'Budha', 'Konghuchu', 'Kepercayaan'];
$OPT_GOLDAR = ['A', 'A+', 'A-', 'B', 'B+', 'B-', 'AB', 'AB+', 'AB-', 'O', 'O+', 'O-', 'Tidak Tahu'];
$OPT_PENDIDIKAN = [
    'Tidak/Belum Sekolah', 'Belum Tamat SD/Sederajat', 'Tamat SD/Sederajat',
    'SLTP/Sederajat', 'SLTA/Sederajat', 'Diploma I/II',
    'Akademi/Diploma III/Sarjana Muda', 'Diploma IV/Strata I', 'Strata II', 'Strata III',
];
$OPT_KAWIN = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];
$OPT_JENIS_BIODATA = [
    'Nama Lengkap', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Golongan Darah',
    'Agama', 'Pendidikan', 'Pekerjaan', 'Nama Ayah', 'Nama Ibu', 'Status Perkawinan',
];

/** Blok kelahiran — dipakai kedua form akta kelahiran, dipecah per segmen. */
$seksiKelahiran = function (bool $denganNikBayi) use ($f, $OPT_JK, $OPT_TEMPAT_LAHIR, $OPT_JENIS_LAHIR, $OPT_PENOLONG): array {
    return [
        [
            'title' => 'Biodata Kelahiran',
            'fields' => array_values(array_filter([
                $denganNikBayi ? $f('nikbayi', 'NIK Bayi', 'nik', ['required' => true, 'half' => true]) : null,
                $f('namalengkap', 'Nama Lengkap', 'text', ['required' => true, 'half' => true]),
                $f('jeniskelamin', 'Jenis Kelamin', 'select', ['required' => true, 'half' => true, 'options' => $OPT_JK]),
                $f('tgllahir', 'Tanggal Lahir', 'date', ['required' => true, 'half' => true]),
                $f('nikayah', 'NIK Ayah', 'nik', ['required' => true, 'half' => true]),
                $f('namaayah', 'Nama Ayah', 'text', ['required' => true, 'half' => true]),
                $f('pekerjaan', 'Pekerjaan', 'text', ['required' => true, 'half' => true]),
                $f('nikibu', 'NIK Ibu', 'nik', ['required' => true, 'half' => true]),
                $f('namaibu', 'Nama Ibu', 'text', ['required' => true, 'half' => true]),
            ])),
        ],
        [
            'title' => 'Data Kelahiran',
            'fields' => [
                $f('anakke', 'Anak Ke', 'number', ['required' => true, 'half' => true]),
                $f('tempatdilahirkan', 'Tempat Dilahirkan', 'select', ['required' => true, 'half' => true, 'options' => $OPT_TEMPAT_LAHIR]),
                $f('tempatkelahiran', 'Tempat Kelahiran', 'text', ['required' => true, 'half' => true]),
                $f('jamkelahiran', 'Jam Kelahiran', 'time', ['required' => true, 'half' => true]),
                $f('jeniskelahiran', 'Jenis Kelahiran', 'select', ['required' => true, 'half' => true, 'options' => $OPT_JENIS_LAHIR]),
                $f('berat', 'Berat (kg)', 'text', ['required' => true, 'half' => true]),
                $f('panjang', 'Panjang (cm)', 'text', ['required' => true, 'half' => true]),
                $f('penolong', 'Penolong', 'select', ['required' => true, 'half' => true, 'options' => $OPT_PENOLONG]),
            ],
        ],
        [
            'title' => 'Saksi I',
            'fields' => [
                $f('niksaksi1', 'NIK Saksi I', 'nik', ['required' => true, 'half' => true]),
                $f('namasaksi1', 'Nama Lengkap Saksi I', 'text', ['required' => true, 'half' => true]),
            ],
        ],
        [
            'title' => 'Saksi II',
            'fields' => [
                $f('niksaksi2', 'NIK Saksi II', 'nik', ['required' => true, 'half' => true]),
                $f('namasaksi2', 'Nama Lengkap Saksi II', 'text', ['required' => true, 'half' => true]),
            ],
        ],
    ];
};

$dokumenKelahiran = [
    'title' => 'Dokumen Syarat',
    'fields' => [
        $f('filebukunikah', 'File Buku Nikah/SPTJM Asli', 'file', ['required' => true]),
        $f('filekk', 'File Kartu Keluarga', 'file', ['required' => true]),
        $f('fileketeranganlahir', 'File Keterangan Lahir/SPTJM Asli', 'file', ['required' => true]),
        $f('filektpsaksi1', 'File KTP Saksi I', 'file', ['required' => true]),
        $f('filektpsaksi2', 'File KTP Saksi II', 'file', ['required' => true]),
        $f('filektpayah', 'File KTP Ayah', 'file'),
        $f('filektpibu', 'File KTP Ibu', 'file'),
        $f('filependukung1', 'File Dokumen Pendukung I', 'file'),
        $f('filependukung2', 'File Dokumen Pendukung II', 'file'),
    ],
];

return [

    /*
    |--------------------------------------------------------------------------
    | Slug rute publik → slug skema formulir
    |--------------------------------------------------------------------------
    | Rute publik memakai slug yang enak dibaca & di-bookmark; skema formulir
    | dan endpoint catch-all memakai slug warisan Laravel. Keduanya menunjuk
    | layanan yang sama.
    */
    'rute_ke_form' => [
        'konsolidasi-update-data' => 'konsolidasi-update-data',
        'akta-kelahiran-belum-nik' => 'akta-kelahiran-nik-tidak-ada',
        'akta-kelahiran-ada-nik' => 'akta-kelahiran-nik-ada',
        'kk-perubahan-biodata' => 'kk-perubahan-biodata',
        'kk-pisah-kk' => 'kk-pisah',
        'kk-numpang-kk' => 'kk-numpang',
        'kk-penambahan-anak' => 'kk-tambah-anak',
        'kk-cetak-ulang' => 'kk-cetak-ulang',
        'akta-perceraian' => 'akta-perceraian',
        'akta-kematian' => 'akta-kematian',
        'akta-perkawinan' => 'akta-nikah',
        'kartu-identitas-anak' => 'kia',
        'perpindahan-penduduk' => 'perpindahan-penduduk',
        'kedatangan-penduduk' => 'kedatangan',
        'ktp-elektronik' => 'ktpel',
    ],

    /*
    |--------------------------------------------------------------------------
    | Slug formulir → kode di m_jenis_permohonan
    |--------------------------------------------------------------------------
    | Master `m_jenis_permohonan` punya 17 baris, tapi hanya 15 di sini yang
    | punya formulir — SAKINAH dan PENCETAKAN_KTP ada di master tanpa form.
    */
    'kode' => [
        'akta-kelahiran-nik-ada' => 'AKTA_KELAHIRAN_NIK_ADA',
        'akta-kelahiran-nik-tidak-ada' => 'AKTA_KELAHIRAN_NIK_BLM_ADA',
        'akta-kematian' => 'AKTA_KEMATIAN',
        'akta-nikah' => 'AKTA_NIKAH',
        'akta-perceraian' => 'AKTA_PERCERAIAN',
        'kia' => 'KIA',
        'ktpel' => 'KTP_EL',
        'perpindahan-penduduk' => 'PINDAH',
        'kedatangan' => 'KEDATANGAN',
        'konsolidasi-update-data' => 'KONSOLIDASI',
        'kk-tambah-anak' => 'KK_TAMBAH_ANAK',
        'kk-pisah' => 'KK_PISAH',
        'kk-numpang' => 'KK_NUMPANG',
        'kk-perubahan-biodata' => 'KK_UBAH_BIODATA',
        'kk-cetak-ulang' => 'KK_CETAK_ULANG',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kartu layanan di halaman pemilih (/user/pengajuan/baru)
    |--------------------------------------------------------------------------
    */
    'kategori' => [
        ['id' => 'all', 'name' => 'Semua Layanan', 'icon' => 'FileText'],
        ['id' => 'akta', 'name' => 'Akta', 'icon' => 'ScrollText'],
        ['id' => 'kk', 'name' => 'Kartu Keluarga', 'icon' => 'Users'],
        ['id' => 'identitas', 'name' => 'Identitas', 'icon' => 'IdCard'],
        ['id' => 'pindah', 'name' => 'Perpindahan', 'icon' => 'MapPin'],
        ['id' => 'data', 'name' => 'Data', 'icon' => 'FileText'],
    ],

    /*
    | 🔴 `kunci` BUKAN hiasan dan tidak boleh diseragamkan dengan `slug`.
    | Itu `modalType` warisan portal Next.js (`lib/pelayanan-list.ts`), dan
    | nilainya sudah tersimpan di produksi pada `t_static_contents` kunci
    | `pelayanan.visibilitas` → `{ hidden: [...] }`. Mengganti nilainya berarti
    | seluruh layanan yang pernah disembunyikan petugas muncul kembali diam-diam.
    */
    'daftar' => [
        ['slug' => 'konsolidasi-update-data', 'kunci' => 'konsolidasi', 'title' => 'Konsolidasi Update Data', 'description' => 'Pengecekan dan penyesuaian data kependudukan', 'icon' => 'FileText', 'category' => 'data'],
        ['slug' => 'akta-kelahiran-belum-nik', 'kunci' => 'aktaKelahiranNikTidakAda', 'title' => 'Akta Kelahiran (Blm Ada Nik)', 'description' => 'Penerbitan akta kelahiran untuk yang belum memiliki NIK', 'icon' => 'Baby', 'category' => 'akta'],
        ['slug' => 'akta-kelahiran-ada-nik', 'kunci' => 'aktaKelahiranNikAda', 'title' => 'Akta Kelahiran (Ada Nik)', 'description' => 'Penerbitan akta kelahiran untuk yang sudah memiliki NIK', 'icon' => 'Baby', 'category' => 'akta'],
        ['slug' => 'kk-perubahan-biodata', 'kunci' => 'kartuKeluargaPerubahanData', 'title' => 'Kartu Keluarga Perubahan Biodata', 'description' => 'Perubahan data pada Kartu Keluarga', 'icon' => 'Users', 'category' => 'kk'],
        ['slug' => 'kk-pisah-kk', 'kunci' => 'kartuKeluargaPisahKK', 'title' => 'Kartu Keluarga Pisah KK', 'description' => 'Pemisahan Kartu Keluarga', 'icon' => 'Users', 'category' => 'kk'],
        ['slug' => 'kk-numpang-kk', 'kunci' => 'kartuKeluargaNumpang', 'title' => 'Kartu Keluarga Numpang KK', 'description' => 'Penambahan anggota keluarga yang numpang', 'icon' => 'Users', 'category' => 'kk'],
        ['slug' => 'kk-penambahan-anak', 'kunci' => 'kartuKeluargaPenambahanAnak', 'title' => 'Kartu Keluarga Penambahan Anak', 'description' => 'Penambahan data anak dalam Kartu Keluarga', 'icon' => 'UserPlus', 'category' => 'kk'],
        ['slug' => 'kk-cetak-ulang', 'kunci' => 'kartuKeluargaCetakUlang', 'title' => 'Kartu Keluarga Cetak Ulang', 'description' => 'Pencetakan ulang Kartu Keluarga', 'icon' => 'Printer', 'category' => 'kk'],
        ['slug' => 'akta-perceraian', 'kunci' => 'aktaPerceraian', 'title' => 'Akta Perceraian', 'description' => 'Penerbitan akta perceraian', 'icon' => 'ScrollText', 'category' => 'akta'],
        ['slug' => 'akta-kematian', 'kunci' => 'aktaKematian', 'title' => 'Akta Kematian', 'description' => 'Penerbitan akta kematian', 'icon' => 'Heart', 'category' => 'akta'],
        ['slug' => 'akta-perkawinan', 'kunci' => 'aktaPerkawinan', 'title' => 'Akta Perkawinan', 'description' => 'Penerbitan akta perkawinan', 'icon' => 'Book', 'category' => 'akta'],
        ['slug' => 'kartu-identitas-anak', 'kunci' => 'kartuIdentitasAnak', 'title' => 'Kartu Identitas Anak (KIA)', 'description' => 'Penerbitan Kartu Identitas Anak', 'icon' => 'IdCard', 'category' => 'identitas'],
        ['slug' => 'perpindahan-penduduk', 'kunci' => 'perpindahanPenduduk', 'title' => 'Perpindahan Penduduk', 'description' => 'Layanan perpindahan alamat penduduk', 'icon' => 'MapPin', 'category' => 'pindah'],
        ['slug' => 'kedatangan-penduduk', 'kunci' => 'kedatanganPenduduk', 'title' => 'Kedatangan Penduduk', 'description' => 'Pencatatan kedatangan penduduk baru', 'icon' => 'Home', 'category' => 'pindah'],
        ['slug' => 'ktp-elektronik', 'kunci' => 'ktpElektronik', 'title' => 'KTP Elektronik', 'description' => 'Penerbitan dan pembaruan KTP Elektronik', 'icon' => 'Zap', 'category' => 'identitas'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Skema formulir per layanan
    |--------------------------------------------------------------------------
    */
    'form' => [

        'konsolidasi-update-data' => [
            'slug' => 'konsolidasi-update-data',
            'title' => 'Konsolidasi Data',
            'desc' => 'Pengecekan dan penyesuaian data kependudukan.',
            'icon' => 'FileText',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('namakepalakeluarga', 'Nama Kepala Keluarga', 'text', ['required' => true, 'half' => true]),
                    $f('alasankonsolidasidata', 'Alasan Konsolidasi Data', 'select', ['required' => true, 'half' => true,
                        'options' => ['BPJS', 'Imigrasi', 'Kartu Pra-Kerja', 'Kepolisian', 'Perbankan', 'Telekomunikasi', 'Vaksin', 'Lainnya']]),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('filektp', 'File KTP', 'file', ['required' => true]),
                    $f('filekk', 'File Kartu Keluarga', 'file', ['required' => true]),
                    $f('filependukung1', 'File Pendukung', 'file'),
                ]],
                $catatan,
            ],
        ],

        'akta-kelahiran-nik-tidak-ada' => [
            'slug' => 'akta-kelahiran-nik-tidak-ada',
            'title' => 'Akta Kelahiran (Blm Ada NIK)',
            'desc' => 'Penerbitan akta kelahiran untuk yang belum memiliki NIK.',
            'icon' => 'Baby',
            'sections' => array_merge([$pemohon], $seksiKelahiran(false), [$dokumenKelahiran, $catatan]),
        ],

        'akta-kelahiran-nik-ada' => [
            'slug' => 'akta-kelahiran-nik-ada',
            'title' => 'Akta Kelahiran (Ada NIK)',
            'desc' => 'Penerbitan akta kelahiran untuk yang sudah memiliki NIK.',
            'icon' => 'Baby',
            'sections' => array_merge([$pemohon], $seksiKelahiran(true), [$dokumenKelahiran, $catatan]),
        ],

        'kk-perubahan-biodata' => [
            'slug' => 'kk-perubahan-biodata',
            'title' => 'KK Perubahan Biodata',
            'desc' => 'Perubahan data pada Kartu Keluarga.',
            'icon' => 'Users',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('kk', 'Nomor KK', 'kk', ['required' => true, 'half' => true]),
                    $f('nik', 'Nomor NIK', 'nik', ['required' => true, 'half' => true]),
                    $f('jenisbiodata', 'Jenis Biodata yang Diubah', 'select', ['required' => true, 'options' => $OPT_JENIS_BIODATA]),
                    $f('namalengkap', 'Nama Lengkap', 'text', ['half' => true]),
                    $f('jeniskelamin', 'Jenis Kelamin', 'select', ['half' => true, 'options' => $OPT_JK]),
                    $f('tempatlahir', 'Tempat Lahir', 'text', ['half' => true]),
                    $f('tanggallahir', 'Tanggal Lahir', 'date', ['half' => true]),
                    $f('golongandarah', 'Golongan Darah', 'select', ['half' => true, 'options' => $OPT_GOLDAR]),
                    $f('agama', 'Agama', 'select', ['half' => true, 'options' => $OPT_AGAMA]),
                    $f('pendidikan', 'Pendidikan', 'select', ['half' => true, 'options' => $OPT_PENDIDIKAN]),
                    $f('pekerjaan', 'Pekerjaan', 'text', ['half' => true]),
                    $f('namaayah', 'Nama Ayah', 'text', ['half' => true]),
                    $f('namaibu', 'Nama Ibu', 'text', ['half' => true]),
                    $f('statusperkawinan', 'Status Perkawinan', 'select', ['half' => true, 'options' => $OPT_KAWIN]),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('filekk', 'File Kartu Keluarga', 'file', ['required' => true]),
                    $f('fileaktalahir', 'File Akta Lahir', 'file'),
                    $f('fileijazah', 'File Ijazah', 'file'),
                    $f('filebukunikah', 'File Buku Nikah', 'file'),
                    $f('filependukung1', 'File Pendukung', 'file'),
                ]],
                $catatan,
            ],
        ],

        'kk-pisah' => [
            'slug' => 'kk-pisah',
            'title' => 'KK Pisah KK',
            'desc' => 'Pemisahan Kartu Keluarga.',
            'icon' => 'Users',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('jenispisah', 'Jenis Pisah', 'select', ['required' => true, 'half' => true,
                        'options' => ['Pisah KK dengan Pasangan', 'Pisah KK dengan Anggota Keluarga']]),
                    $f('alasanpisah', 'Alasan Pisah', 'select', ['required' => true, 'half' => true, 'options' => [
                        'Menikah', 'Menikah & Pindah Alamat', 'Cerai', 'Cerai & Pindah Alamat',
                        'Cerai Mati', 'Cerai Mati & Pindah Alamat', 'Kepala Keluarga Meninggal',
                        'Kepala Keluarga Meninggal & Pindah Alamat', 'Anak Keluarga Meninggal',
                        'Anak Keluarga Meninggal & Pindah Alamat',
                    ]]),
                    // Berisi BANYAK NIK (satu per baris) — jadi textarea, bukan
                    // field NIK. Jangan divalidasi sebagai 16 digit tunggal.
                    $f('nikygpisah', 'NIK Yang Pisah', 'textarea', ['required' => true, 'placeholder' => 'Tulis NIK yang pisah — satu NIK per baris']),
                    $f('nikpasangan', 'Nomor NIK Pasangan', 'nik', ['half' => true]),
                    $f('kecamatantujuan', 'Kecamatan Tujuan', 'text', ['half' => true]),
                    $f('kelurahantujuan', 'Kelurahan Tujuan', 'text', ['half' => true]),
                    $f('norwtujuan', 'Nomor RW Tujuan', 'text', ['half' => true]),
                    $f('norttujuan', 'Nomor RT Tujuan', 'text', ['half' => true]),
                    $f('alamattujuan', 'Alamat Tujuan', 'textarea'),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('filekk', 'File Kartu Keluarga', 'file', ['required' => true]),
                    $f('filekkpasangan', 'File KK Pasangan', 'file'),
                    $f('filebukunikah', 'File Buku Nikah', 'file'),
                    $f('filesuratcerai', 'File Surat Cerai', 'file'),
                    $f('fileaktamati', 'File Akta Mati', 'file'),
                ]],
                $catatan,
            ],
        ],

        'kk-numpang' => [
            'slug' => 'kk-numpang',
            'title' => 'KK Numpang KK',
            'desc' => 'Penambahan anggota keluarga yang menumpang.',
            'icon' => 'Users',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('kklama', 'Nomor KK Lama', 'kk', ['required' => true, 'half' => true]),
                    $f('kkygditempati', 'Nomor KK Yang Ditempati', 'kk', ['required' => true, 'half' => true]),
                    $f('nikygnumpangkk', 'NIK Yang Numpang KK', 'textarea', ['required' => true, 'placeholder' => 'Tulis NIK yang menumpang — satu NIK per baris']),
                    // ⚠️ Berakhiran "kk" tapi isinya TEKS, bukan nomor KK.
                    $f('alasannumpangkk', 'Alasan Numpang KK', 'select', ['required' => true,
                        'options' => ['Pekerjaan', 'Pendidikan', 'Perawatan Kesehatan', 'Lainnya']]),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('filekklama', 'File Kartu Keluarga Lama', 'file', ['required' => true]),
                    $f('filekkygditempati', 'File KK Yang Ditempati', 'file', ['required' => true]),
                    $f('filependukung1', 'File Pendukung', 'file'),
                ]],
                $catatan,
            ],
        ],

        'kk-tambah-anak' => [
            'slug' => 'kk-tambah-anak',
            'title' => 'KK Penambahan Anak',
            'desc' => 'Penambahan data anak dalam Kartu Keluarga.',
            'icon' => 'UserPlus',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('namaanggotakeluarga', 'Nama Anggota Keluarga', 'text', ['required' => true, 'half' => true]),
                    $f('tempatlahir', 'Tempat Lahir', 'text', ['required' => true, 'half' => true]),
                    $f('tanggallahir', 'Tanggal Lahir', 'date', ['required' => true, 'half' => true]),
                    $f('jeniskelamin', 'Jenis Kelamin', 'select', ['required' => true, 'half' => true, 'options' => $OPT_JK]),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('filekk', 'File Kartu Keluarga', 'file', ['required' => true]),
                    $f('fileakta', 'File Akta / Surat Ket. Lahir', 'file', ['required' => true]),
                    $f('filebukunikah', 'File Buku Nikah', 'file'),
                ]],
                $catatan,
            ],
        ],

        'kk-cetak-ulang' => [
            'slug' => 'kk-cetak-ulang',
            'title' => 'KK Cetak Ulang',
            'desc' => 'Pencetakan ulang Kartu Keluarga.',
            'icon' => 'Printer',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('namakepalakeluarga', 'Nomor Kepala Keluarga', 'kk', ['required' => true, 'half' => true]),
                    $f('alasancetakulang', 'Alasan Cetak Ulang', 'select', ['required' => true, 'half' => true,
                        'options' => ['Hilang', 'Perubahan KK', 'Rusak']]),
                    $f('alamatkepalakeluarga', 'Alamat Kepala Keluarga', 'textarea', ['required' => true]),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('filekk', 'File Kartu Keluarga', 'file'),
                    $f('filesuratkehilangan', 'File Surat Kehilangan', 'file'),
                ]],
                $catatan,
            ],
        ],

        'akta-perceraian' => [
            'slug' => 'akta-perceraian',
            'title' => 'Akta Perceraian',
            'desc' => 'Penerbitan akta perceraian.',
            'icon' => 'ScrollText',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('nokk', 'Nomor KK', 'kk', ['required' => true, 'half' => true]),
                    $f('niksuami', 'Nomor NIK Suami', 'nik', ['required' => true, 'half' => true]),
                    $f('nikistri', 'Nomor NIK Istri', 'nik', ['required' => true, 'half' => true]),
                    $f('yangmengajukan', 'Yang Mengajukan', 'select', ['required' => true, 'half' => true, 'options' => ['Suami', 'Istri']]),
                    $f('alasancerai', 'Alasan Cerai', 'select', ['required' => true, 'options' => [
                        'Berbuat Zina', 'Pemabuk/Pemadat', 'Penjudi',
                        'Meninggalkan Pasangan Lebih dari 2 Tahun Tanpa Alasan',
                        'Hukuman Penjara di Atas 5 Tahun/Lebih Berat',
                        'Melakukan Kekejaman/Kekerasan dalam Rumah Tangga',
                        'Mendapat Cacat Badan/Penyakit',
                        'Perselisihan/Pertengkaran Terus Menerus', 'Lainnya',
                    ]]),
                    $f('noputusanpengadilan', 'Nomor Putusan Pengadilan', 'text', ['required' => true, 'half' => true]),
                    $f('tglputusan', 'Tanggal Putusan', 'date', ['required' => true, 'half' => true]),
                    $f('instansipemberiputusan', 'Instansi Pemberi Putusan', 'select', ['required' => true,
                        'options' => ['Pengadilan Negeri', 'Pengadilan Agama', 'Pengadilan Tinggi Negeri', 'Pengadilan Tinggi Agama', 'Mahkamah Agung']]),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('filekk', 'File Kartu Keluarga', 'file', ['required' => true]),
                    $f('filektpsuami', 'File KTP Suami', 'file', ['required' => true]),
                    $f('filektpistri', 'File KTP Istri', 'file', ['required' => true]),
                    $f('fileputusan', 'File Putusan', 'file', ['required' => true]),
                    $f('filependukung1', 'File Dokumen Pendukung I', 'file'),
                    $f('filependukung2', 'File Dokumen Pendukung II', 'file'),
                ]],
                $catatan,
            ],
        ],

        'akta-kematian' => [
            'slug' => 'akta-kematian',
            'title' => 'Akta Kematian',
            'desc' => 'Penerbitan akta kematian.',
            'icon' => 'Heart',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('nikjenazah', 'NIK Jenazah', 'nik', ['required' => true, 'half' => true]),
                    $f('namalengkap', 'Nama Lengkap', 'text', ['required' => true, 'half' => true]),
                    $f('jeniskelamin', 'Jenis Kelamin', 'select', ['required' => true, 'half' => true, 'options' => $OPT_JK]),
                    $f('tgllahir', 'Tanggal Lahir', 'date', ['required' => true, 'half' => true]),
                    $f('nikayah', 'NIK Ayah', 'nik', ['required' => true, 'half' => true]),
                    $f('namaayah', 'Nama Ayah', 'text', ['required' => true, 'half' => true]),
                    $f('pekerjaan', 'Pekerjaan', 'text', ['required' => true, 'half' => true]),
                    $f('nikibu', 'NIK Ibu', 'nik', ['required' => true, 'half' => true]),
                    $f('namaibu', 'Nama Ibu', 'text', ['required' => true, 'half' => true]),
                    $f('anakke', 'Anak Ke', 'number', ['required' => true, 'half' => true]),
                    $f('tglkematian', 'Tanggal Kematian', 'date', ['required' => true, 'half' => true]),
                    $f('jamkematian', 'Jam Kematian', 'time', ['required' => true, 'half' => true]),
                    $f('tempatkematian', 'Tempat Kematian', 'text', ['required' => true, 'half' => true]),
                    $f('sebabkematian', 'Sebab Kematian', 'select', ['required' => true, 'half' => true,
                        'options' => ['Sakit Biasa / Tua', 'Pandemi / Wabah Penyakit', 'Kecelakaan', 'Kriminalitas', 'Bunuh Diri', 'Lainnya']]),
                    $f('menerangkankematian', 'Menerangkan', 'select', ['required' => true, 'half' => true,
                        'options' => ['Dokter', 'Tenaga Kesehatan', 'Kepolisian', 'Lainnya']]),
                    $f('niksaksi1', 'NIK Saksi I', 'nik', ['required' => true, 'half' => true]),
                    $f('namasaksi1', 'Nama Lengkap Saksi I', 'text', ['required' => true, 'half' => true]),
                    $f('niksaksi2', 'NIK Saksi II', 'nik', ['required' => true, 'half' => true]),
                    $f('namasaksi2', 'Nama Lengkap Saksi II', 'text', ['required' => true, 'half' => true]),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('fileketkematian', 'File Ket. Kematian Asli', 'file', ['required' => true]),
                    $f('filekk', 'File Kartu Keluarga', 'file', ['required' => true]),
                    $f('filektppelapor', 'File KTP Pelapor', 'file', ['required' => true]),
                    $f('filektpjenazah', 'File KTP Jenazah', 'file', ['required' => true]),
                    $f('filependukung1', 'File Dokumen Pendukung I', 'file'),
                    $f('filependukung2', 'File Dokumen Pendukung II', 'file'),
                ]],
                $catatan,
            ],
        ],

        'akta-nikah' => [
            'slug' => 'akta-nikah',
            'title' => 'Akta Perkawinan',
            'desc' => 'Penerbitan akta perkawinan.',
            'icon' => 'Book',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('nokksuami', 'Nomor KK Suami', 'kk', ['required' => true, 'half' => true]),
                    $f('niksuami', 'Nomor NIK Suami', 'nik', ['required' => true, 'half' => true]),
                    $f('suamianakke', 'Suami Anak Ke', 'number', ['required' => true, 'half' => true]),
                    $f('nokkistri', 'Nomor KK Istri', 'kk', ['required' => true, 'half' => true]),
                    $f('nikistri', 'Nomor NIK Istri', 'nik', ['required' => true, 'half' => true]),
                    $f('istrianakke', 'Istri Anak Ke', 'number', ['required' => true, 'half' => true]),
                    $f('niksaksi1', 'Nomor NIK Saksi 1', 'nik', ['required' => true, 'half' => true]),
                    $f('niksaksi2', 'Nomor NIK Saksi 2', 'nik', ['required' => true, 'half' => true]),
                    $f('tglpemberkatan', 'Tanggal Pemberkatan', 'date', ['required' => true, 'half' => true]),
                    $f('tmptpemberkatan', 'Tempat Pemberkatan', 'text', ['required' => true, 'half' => true]),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('filekksuami', 'File Kartu Keluarga Suami', 'file', ['required' => true]),
                    $f('filektpsuami', 'File KTP Suami', 'file', ['required' => true]),
                    $f('filekkistri', 'File Kartu Keluarga Istri', 'file', ['required' => true]),
                    $f('filektpistri', 'File KTP Istri', 'file', ['required' => true]),
                    $f('filesuamiistri', 'File Foto Suami & Istri', 'file', ['required' => true]),
                    $f('filebukunikahagama', 'File Foto Buku Nikah Agama', 'file', ['required' => true]),
                    $f('filependukung1', 'File Dokumen Pendukung I', 'file'),
                    $f('filependukung2', 'File Dokumen Pendukung II', 'file'),
                ]],
                $catatan,
            ],
        ],

        'kia' => [
            'slug' => 'kia',
            'title' => 'Kartu Identitas Anak (KIA)',
            'desc' => 'Penerbitan Kartu Identitas Anak.',
            'icon' => 'IdCard',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('nikanak', 'NIK Anak', 'nik', ['required' => true, 'half' => true]),
                    $f('namalengkap', 'Nama Lengkap', 'text', ['required' => true, 'half' => true]),
                    $f('tgllahir', 'Tanggal Lahir', 'date', ['required' => true, 'half' => true]),
                    $f('tempatlahir', 'Tempat Lahir', 'text', ['required' => true, 'half' => true]),
                    $f('jeniskelamin', 'Jenis Kelamin', 'select', ['required' => true, 'half' => true, 'options' => $OPT_JK]),
                    $f('nikayah', 'NIK Ayah', 'nik', ['required' => true, 'half' => true]),
                    $f('namaayah', 'Nama Ayah', 'text', ['required' => true, 'half' => true]),
                    $f('nikibu', 'NIK Ibu', 'nik', ['required' => true, 'half' => true]),
                    $f('namaibu', 'Nama Ibu', 'text', ['required' => true, 'half' => true]),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('fileaktakelahiran', 'File Akta Kelahiran', 'file', ['required' => true]),
                    $f('filepassfoto', 'File Photo Anak Terbaru', 'file', ['required' => true]),
                    $f('filekk', 'File Kartu Keluarga', 'file', ['required' => true]),
                    $f('filependukung1', 'File Dokumen Pendukung I', 'file'),
                    $f('filependukung2', 'File Dokumen Pendukung II', 'file'),
                ]],
                $catatan,
            ],
        ],

        'perpindahan-penduduk' => [
            'slug' => 'perpindahan-penduduk',
            'title' => 'Perpindahan Penduduk',
            'desc' => 'Layanan perpindahan alamat penduduk.',
            'icon' => 'MapPin',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('klasifikasikepindahan', 'Klasifikasi Kepindahan', 'select', ['required' => true, 'half' => true,
                        'options' => ['Dalam Satu Desa/Kelurahan', 'Antar Desa/Kelurahan', 'Antar Kecamatan', 'Antar Kabupaten/Kota', 'Provinsi']]),
                    $f('jeniskepindahan', 'Jenis Kepindahan', 'select', ['required' => true, 'half' => true,
                        'options' => ['Kepala Keluarga', 'KK & Sebagian Anggota Keluarga', 'KK & Seluruh Anggota Keluarga', 'Anggota Keluarga']]),
                    $f('nikygpindah', 'NIK Yang Pindah', 'textarea', ['required' => true, 'placeholder' => 'Tulis NIK yang pindah — satu NIK per baris']),
                    $f('alasanpindah', 'Alasan Pindah', 'textarea', ['required' => true]),
                    $f('alamat', 'Alamat Tujuan', 'textarea', ['required' => true]),
                    $f('provinsi', 'Provinsi Tujuan', 'text', ['required' => true, 'half' => true]),
                    $f('kabupaten', 'Kabupaten Tujuan', 'text', ['required' => true, 'half' => true]),
                    $f('kecamatan', 'Kecamatan Tujuan', 'text', ['required' => true, 'half' => true]),
                    $f('kelurahan', 'Kelurahan Tujuan', 'text', ['required' => true, 'half' => true]),
                    $f('rt', 'RT Tujuan', 'number', ['required' => true, 'half' => true]),
                    $f('rw', 'RW Tujuan', 'number', ['required' => true, 'half' => true]),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('filekk', 'File Kartu Keluarga', 'file', ['required' => true]),
                    $f('filependukung1', 'File Dokumen Pendukung I', 'file'),
                    $f('filependukung2', 'File Dokumen Pendukung II', 'file'),
                ]],
                $catatan,
            ],
        ],

        'kedatangan' => [
            'slug' => 'kedatangan',
            'title' => 'Kedatangan Penduduk',
            'desc' => 'Pencatatan kedatangan penduduk baru.',
            'icon' => 'Home',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('skpwni', 'No Surat Pindah / SKPWNI', 'text', ['required' => true]),
                    $f('namapemohon', 'Nama Yang Pindah', 'text', ['required' => true, 'half' => true]),
                    $f('nikpemohon', 'NIK Yang Pindah', 'nik', ['required' => true, 'half' => true]),
                    $f('nohp', 'No. HP', 'phone', ['required' => true, 'half' => true]),
                    $f('email', 'Email', 'email', ['required' => true, 'half' => true]),
                    $f('kecamatantujuan', 'Kecamatan Tujuan', 'text', ['required' => true, 'half' => true]),
                    $f('desatujuan', 'Desa Tujuan', 'text', ['required' => true, 'half' => true]),
                    $f('dusuntujuan', 'Dusun Tujuan', 'text', ['required' => true, 'half' => true]),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('filesuratpindah', 'File Surat Pindah', 'file', ['required' => true]),
                    $f('filebukunikah', 'File Buku Nikah/SPTJM (kalau ada)', 'file'),
                    $f('filependukung1', 'File Dokumen Pendukung I', 'file'),
                    $f('filependukung2', 'File Dokumen Pendukung II', 'file'),
                ]],
                $catatan,
            ],
        ],

        'ktpel' => [
            'slug' => 'ktpel',
            'title' => 'KTP Elektronik',
            'desc' => 'Penerbitan dan pembaruan KTP Elektronik.',
            'icon' => 'Zap',
            'sections' => [
                $pemohon,
                ['title' => 'Kelengkapan Data', 'fields' => [
                    $f('nokk', 'Nomor KK', 'kk', ['required' => true, 'half' => true]),
                    $f('nik', 'Nomor NIK', 'nik', ['required' => true, 'half' => true]),
                    $f('nama', 'Nama Lengkap', 'text', ['required' => true, 'half' => true]),
                    $f('alasancetak', 'Alasan Cetak', 'select', ['required' => true, 'half' => true,
                        'options' => ['Baru (Pemula)', 'Hilang', 'Rusak', 'Pindah Datang', 'Perubahan Data', 'Cetak Ulang']]),
                ]],
                ['title' => 'Dokumen Syarat', 'fields' => [
                    $f('filekk', 'File Kartu Keluarga', 'file', ['required' => true]),
                    $f('filektplama', 'File KTP Lama', 'file'),
                    $f('filesuratkehilangan', 'File Surat Kehilangan', 'file'),
                    $f('fileketerangan', 'File Surat Keterangan', 'file'),
                ]],
                $catatan,
            ],
        ],

    ],
];
