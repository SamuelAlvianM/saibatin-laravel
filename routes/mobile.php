<?php

use App\Http\Controllers\Api\Mobile\AuthMobileController;
use App\Http\Controllers\Api\Mobile\LayananMobileController;
use App\Http\Controllers\Auth\CekStatusController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SandiController;
use App\Http\Controllers\BerkasController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API aplikasi mobile — prefix /api/mobile/v1
|--------------------------------------------------------------------------
|
| Dimuat dari `bootstrap/app.php` dengan middleware `PakaiTokenMobile` + `api`:
| STATELESS, tanpa sesi, tanpa CSRF. Autentikasinya token Sanctum di header
| `Authorization: Bearer <token>`. Dokumentasi untuk pengembang aplikasi:
| `docs/API-MOBILE.md`.
|
| Isinya dua bagian:
|   1. Endpoint KHUSUS mobile di bawah — padanan JSON untuk hal yang di website
|      berupa halaman (login, daftar, formulir layanan, beranda dashboard).
|   2. SELURUH `routes/api.php` dimuat ulang di akhir berkas, jadi setiap
|      endpoint website `/api/x` juga tersedia sebagai `/api/mobile/v1/x`.
|
| 🔴 Bagian 1 HARUS didaftarkan SEBELUM bagian 2. `api.php` berisi catch-all
| `POST {layanan}/{aksi}` yang akan menelan rute dua-segmen seperti
| `auth/login` kalau urutannya terbalik (Laravel memakai rute pertama yang
| cocok). Rute baru di sini pun harus tetap di atas baris `require`.
|
*/

// ── Tamu ────────────────────────────────────────────────────────────────────
Route::post('/auth/login', [AuthMobileController::class, 'masuk'])->middleware('throttle:5,1');

Route::get('/auth/register', [RegisterController::class, 'formulirJson']);
Route::post('/auth/register', [RegisterController::class, 'daftar'])->middleware('throttle:5,1');
Route::post('/auth/otp/send', [OtpController::class, 'kirim'])->middleware('throttle:10,1');
Route::post('/auth/otp/verify', [OtpController::class, 'verifikasi'])->middleware('throttle:20,1');

Route::post('/auth/cek-status', [CekStatusController::class, 'periksa'])->middleware('throttle:20,1');
Route::post('/auth/ajukan-ulang', [CekStatusController::class, 'ajukanUlang'])->middleware('throttle:5,1');

Route::post('/auth/forgot-password', [SandiController::class, 'kirimTautan'])->middleware('throttle:5,1');

// ── Berizin (token) ─────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [AuthMobileController::class, 'keluar']);
    Route::get('/auth/me', [AuthMobileController::class, 'saya']);

    Route::get('/layanan', [LayananMobileController::class, 'daftar']);
    Route::get('/layanan/{slug}', [LayananMobileController::class, 'form'])
        ->where('slug', '[a-z0-9-]+');

    // Beranda dashboard — petugas saja (diperiksa di controller).
    Route::get('/dashboard', [DashboardController::class, 'ringkasanJson']);
});

// Berkas lampiran (KTP/KK/scan dokumen). Kontrol aksesnya di controller —
// petugas semua, warga hanya miliknya — dan `$request->user()` di sana membaca
// token karena guard bawaan rute ini `sanctum`. URL berkas yang dibalas API
// (`/uploads/...`) cukup diberi awalan `/api/mobile/v1`.
Route::get('/uploads/{jalur}', [BerkasController::class, 'tampilkan'])->where('jalur', '.*');

// ── Seluruh endpoint website ────────────────────────────────────────────────
require base_path('routes/api.php');
