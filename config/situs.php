<?php

/*
|--------------------------------------------------------------------------
| Identitas Situs
|--------------------------------------------------------------------------
|
| Port dari `lib/site-config.ts` (portal Next.js), yang sendirinya adalah
| pemetaan dari `APP_SITE_*` di .env Laravel 9 asli.
|
| Semua nilai di sini AMAN dibagikan ke browser — tidak ada rahasia. Kunci
| rahasia (secret reCAPTCHA, token Fonnte, kredensial surel) tempatnya di
| config/services.php, dan tidak pernah dikirim ke sisi klien.
|
*/

return [

    'kode' => env('SITE_KODE', '1813'),
    'tenant' => env('SITE_TENANT', 'disdukcapil'),
    'tenant2' => env('SITE_TENANT2', 'dinas dukcapil'),
    'nama_lengkap' => env('SITE_NAMA_LENGKAP', 'pesisir barat'),
    'nama_pendek' => env('SITE_NAMA_PENDEK', 'pesisirbarat'),
    'nama_populer' => env('SITE_NAMA_POPULER', 'saibatin'),
    'keterangan' => env('SITE_KETERANGAN', 'pelayanan'),
    'versi' => env('SITE_VERSI', ''),
    'tahun_copyright' => env('SITE_TAHUN_COPYRIGHT', '2024'),

];
