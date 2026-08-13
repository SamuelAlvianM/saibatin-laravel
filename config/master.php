<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sandi master
    |--------------------------------------------------------------------------
    |
    | Membuka kunci permohonan yang sudah final (SELESAI/DITOLAK) di halaman
    | Master. WAJIB dari environment — tanpa nilai bawaan di kode, supaya tidak
    | pernah ikut ter-commit.
    |
    | 🔴 Kosong = fitur master NONAKTIF (fail-safe). Itu pilihan yang disengaja:
    | server yang lupa dikonfigurasi harus menolak, bukan menerima sandi kosong.
    |
    */

    'password' => env('MASTER_PASSWORD', ''),

];
