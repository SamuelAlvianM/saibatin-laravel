<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PakaiTokenMobile;
use App\Support\Balasan;
use App\Support\KlienMobile;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // API aplikasi mobile — stateless, token Bearer. Lihat routes/mobile.php.
        then: function () {
            Route::middleware([PakaiTokenMobile::class, 'api'])
                ->prefix(KlienMobile::PREFIX)
                ->group(base_path('routes/mobile.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'peran' => App\Http\Middleware\PastikanPeran::class,
        ]);

        // Guard `sanctum` harus sudah terpasang sebelum `auth` memeriksa
        // pengguna — lihat docblock PakaiTokenMobile.
        $middleware->prependToPriorityList(
            Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            PakaiTokenMobile::class,
        );

        // Tamu yang membuka halaman berizin diarahkan ke /login (bukan ke rute
        // bernama 'login' bawaan Laravel yang tidak kita pakai).
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * Galat di rute mobile memakai kontrak `Balasan` yang sama dengan
         * balasan sukses — `{ error[], success[], data, html[] }` — supaya
         * aplikasi cukup membaca satu bentuk. Bawaan Laravel (`{message,
         * errors}`) hanya tersisa di website, yang memang sudah terbiasa.
         */
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (KlienMobile::dari($request)) {
                return Balasan::gagal(
                    array_values(array_unique(array_merge(...array_values($e->errors())))),
                    422,
                    ['kolom' => $e->errors()],
                );
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (KlienMobile::dari($request)) {
                return Balasan::gagal(['Sesi berakhir atau token tidak valid. Silakan login ulang.'], 401);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (KlienMobile::dari($request)) {
                $kode = $e->getStatusCode();

                return Balasan::gagal([match (true) {
                    $kode === 429 => 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.',
                    $kode === 404 && ! $e->getMessage() => 'Tidak ditemukan',
                    default => $e->getMessage() ?: 'Permintaan tidak dapat diproses',
                }], $kode);
            }
        });

        // Galat tak terduga (500). Didaftarkan TERAKHIR — callback dicoba
        // berurutan, jadi jenis yang lebih khusus di atas tetap menang. Pesan
        // aslinya hanya ditampilkan saat APP_DEBUG; di produksi cukup kalimat
        // umum (jejaknya tetap tercatat di log, `render` tidak menghentikan `report`).
        $exceptions->render(function (Throwable $e, Request $request) {
            if (KlienMobile::dari($request) && ! $e instanceof Illuminate\Http\Exceptions\HttpResponseException) {
                return Balasan::gagal([config('app.debug')
                    ? $e::class.': '.$e->getMessage()
                    : 'Terjadi kesalahan di server. Silakan coba lagi.'], 500);
            }
        });
    })->create();
