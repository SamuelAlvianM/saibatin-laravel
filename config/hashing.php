<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver Hash Bawaan
    |--------------------------------------------------------------------------
    */

    'driver' => 'bcrypt',

    /*
    |--------------------------------------------------------------------------
    | 🔴 Verifikasi Algoritma — SENGAJA DIMATIKAN
    |--------------------------------------------------------------------------
    |
    | Berkas config ini tidak ada di kerangka Laravel 12; dibuat khusus HANYA
    | untuk baris 'verify' di bawah. Jangan dihapus.
    |
    | Tabel `users` berisi DUA varian hash bcrypt:
    |   $2y$  1.378 akun — warisan portal Laravel 9 (password_hash PHP)
    |   $2a$      7 akun — dibuat portal Next.js lewat paket `bcryptjs`
    |   selain itu 1 baris sentinel `!arsip-tidak-bisa-login` (akun arsip yang
    |   memang tidak boleh bisa masuk).
    |
    | Keduanya bcrypt yang sah dan `password_verify()` PHP memverifikasi keduanya
    | dengan benar (sudah diuji: cocok untuk sandi benar, menolak sandi salah).
    | MASALAHNYA ada di penjaga bawaan Laravel: `BcryptHasher::check()` lebih dulu
    | memanggil `password_get_info()`, dan PHP hanya mengenali `$2y$` sebagai
    | PASSWORD_BCRYPT — untuk `$2a$` ia mengembalikan algoName "unknown", lalu
    | Laravel MELEMPAR RuntimeException:
    |
    |     "This password does not use the Bcrypt algorithm."
    |
    | Akibatnya bukan sekadar login gagal, melainkan HTTP 500. Dengan verify=false,
    | pengecekan diserahkan ke `password_verify()` yang menangani $2a$/$2b$/$2y$
    | apa adanya, dan hash sentinel cukup mengembalikan false (bukan melempar).
    |
    | Alternatif yang SENGAJA TIDAK dipakai: menulis ulang prefix $2a$ → $2y$ di
    | database. Itu menyentuh hash sandi warga secara massal untuk masalah yang
    | bisa diselesaikan satu baris konfigurasi.
    |
    */

    'verify' => env('HASH_VERIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Opsi Bcrypt
    |--------------------------------------------------------------------------
    |
    | cost 10 menyamai hash lama (bcryptjs cost 10 & Laravel 9 cost 10) supaya
    | sandi baru tidak berbeda karakter dari yang sudah ada.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 10),
        'verify' => env('HASH_VERIFY', false),
        'limit' => null,
    ],

];
