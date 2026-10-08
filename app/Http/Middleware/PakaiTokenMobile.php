<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menjadikan guard `sanctum` (token Bearer) sebagai guard bawaan di rute mobile.
 *
 * 🔴 Inilah yang membuat `routes/api.php` bisa dimuat ULANG di bawah
 * `/api/mobile/v1` tanpa satu baris pun diubah. Berkas itu memakai `auth`
 * polos, yang memeriksa guard BAWAAN — di website itu `web` (sesi cookie),
 * di sini `sanctum`. `$request->user()`, `auth()->user()`, dan
 * `PastikanPeran` di seluruh controller ikut membaca pengguna dari token.
 *
 * ⚠️ Harus berjalan SEBELUM `Authenticate`. Urutannya dipaku lewat
 * `prependToPriorityList` di `bootstrap/app.php` — tanpa itu Laravel boleh
 * memindahkan `Authenticate` ke depan middleware ini saat mengurutkan.
 */
class PakaiTokenMobile
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        // Aplikasi mobile selalu ingin JSON. Dengan ini, kalau klien lupa
        // mengirim `Accept: application/json`, galat 401/403/404/422 tetap
        // berbentuk JSON — bukan halaman HTML atau redirect ke /login.
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
