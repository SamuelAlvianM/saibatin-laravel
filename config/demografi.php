<?php

/*
|--------------------------------------------------------------------------
| Kategori data demografi
|--------------------------------------------------------------------------
|
| Port `lib/demografi-kategori.ts`. `slug` = nilai kolom `kategori` di
| `m_demografi_wilayah` — sudah terpakai di produksi, jangan diganti.
|
| `fileHint` adalah nama berkas Excel Dukcapil (SIAK) yang sesuai; ditampilkan
| di dashboard supaya petugas tidak salah unggah antar kategori.
|
*/

return [

    'kategori' => [
        ['slug' => 'jenis-kelamin', 'label' => 'Jenis Kelamin', 'fileHint' => 'AGR_JK_DUSUN…'],
        ['slug' => 'agama', 'label' => 'Agama', 'fileHint' => 'AGR_AGAMA…'],
        ['slug' => 'gol-darah', 'label' => 'Golongan Darah', 'fileHint' => 'AGR_DRH_DUSUN / AGR_GOL_DRH…'],
        ['slug' => 'pekerjaan', 'label' => 'Pekerjaan', 'fileHint' => 'AGR_PEKERJAAN / AGR_PKRJN_DUSUN…'],
        ['slug' => 'pendidikan', 'label' => 'Pendidikan', 'fileHint' => 'AGR_PDDKN_DUSUN…'],
        ['slug' => 'status-kawin', 'label' => 'Status Perkawinan', 'fileHint' => 'AGR_STAT_KWN_DUSUN…'],
        ['slug' => 'kk', 'label' => 'Kartu Keluarga', 'fileHint' => 'AGR_KK_DUSUN…'],
        ['slug' => 'wajib-ktp', 'label' => 'Wajib KTP', 'fileHint' => 'AGR_WKTP…'],
    ],

    /** Batas unggah per berkas Excel. */
    'maks_unggah' => 10 * 1024 * 1024,

];
