<?php

/*
|--------------------------------------------------------------------------
| Front controller untuk cPanel — SALIN JADI public_html/index.php
|--------------------------------------------------------------------------
|
| Bedanya dengan `public/index.php` bawaan hanya SATU hal: setiap `__DIR__.'/..'`
| diganti `$app` yang menunjuk ke folder aplikasi di luar `public_html`.
|
| 🔴 KENAPA APLIKASINYA DI LUAR public_html. Semua yang berada di dalam
| `public_html` disajikan Apache apa adanya. Kalau seluruh project ditaruh di
| sana, maka `https://domain/.env` mengembalikan kredensial database, dan
| `https://domain/storage/app/private/permohonan/…` mengembalikan scan KTP warga
| tanpa cek sesi apa pun. Aturan ini sudah dilanggar dua kali di portal Next.js
| (lihat HANDOFF §6 no. 1) — jangan diulang di sini.
|
| Susunan yang benar:
|
|   /home/<akun>/saibatin-app/     app bootstrap config database lang routes
|                                  resources storage vendor .env artisan
|   /home/<akun>/public_html/      index.php (berkas INI) + .htaccess
|                                  + seluruh ISI folder public/ project
|
| ⚠️ Ganti `saibatin-app` di bawah kalau Anda menamai foldernya lain.
| Tidak perlu `php artisan storage:link`: berkas warga disajikan
| `BerkasController` dari storage, bukan lewat symlink (yang sering diblokir
| cPanel).
|
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/**
 * Folder aplikasi, satu tingkat di atas public_html.
 *
 * `dirname(__DIR__)` = /home/<akun>  →  ditambah nama folder aplikasinya.
 * Ditulis begini, bukan path absolut yang di-hardcode, supaya berkas ini tetap
 * benar kalau nama akun cPanel berubah atau dipindah ke hosting lain.
 */
$app = dirname(__DIR__).'/saibatin-app';

// Pesan yang bisa dibaca manusia kalau foldernya salah — tanpa ini yang muncul
// hanya "Failed to open stream" dari autoload.php, yang tidak memberi tahu
// apa pun tentang penyebabnya.
if (! is_file($app.'/vendor/autoload.php')) {
    http_response_code(500);
    exit('Folder aplikasi tidak ditemukan di: '.$app.' — periksa $app pada index.php.');
}

// Mode pemeliharaan (php artisan down)...
if (file_exists($maintenance = $app.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoloader Composer...
require $app.'/vendor/autoload.php';

// Bootstrap Laravel lalu tangani permintaannya...
/** @var Application $laravel */
$laravel = require_once $app.'/bootstrap/app.php';

$laravel->handleRequest(Request::capture());
