<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | 🔴 Laravel 12 MEM-HARDCODE 'UTC' di sini dan TIDAK membaca APP_TIMEZONE —
    | menaruhnya di .env saja tidak berpengaruh apa pun (dan tidak ada
    | peringatannya). Baris ini sengaja dijadikan env-driven.
    |
    | Zona harus Asia/Jakarta, bukan UTC: seluruh kolom waktu di DB warisan
    | disimpan sebagai waktu LOKAL Jakarta oleh portal Laravel 9 maupun Next.js.
    | Kalau aplikasi membacanya sebagai UTC, setiap tanggal yang ditampilkan —
    | dan setiap perhitungan "hari ini" (kartu pengunjung, filter periode,
    | rekap harian) — meleset 7 jam.
    |
    | Pelajaran ini mahal: di project saudara, MySQL yang restart tanpa
    | `default-time-zone` terkunci membuat 17 kolom created_at melompat +9 jam.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    // 🔴 Bawaannya `id`, bukan `en` — portal ini berbahasa Indonesia dan
    // `.env` di server bisa saja dibuat ulang tanpa baris APP_LOCALE.
    'locale' => env('APP_LOCALE', 'id'),

    // 🔴 Fallback WAJIB `en`, jangan disamakan dengan `locale`. Kalau keduanya
    // `id` (keadaan sampai 17 Agu 2026), aturan validasi yang belum ada di
    // `lang/id` tidak punya tempat jatuh dan Laravel menampilkan KUNCI-nya —
    // warga melihat "validation.required" di formulir pendaftaran.
    // `lang/en/validation.php` sudah diterbitkan sebagai jaring pengaman.
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
