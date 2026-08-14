<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Data yang dibagikan ke SETIAP halaman Inertia.
 *
 * Ini pengganti Redux `authSlice` di portal Next.js: dulu sesi diambil client
 * lewat `GET /api/auth/session` lalu disimpan di store. Di sini keadaan sesi
 * ikut menempel pada setiap respons, jadi tidak ada lagi permintaan tambahan
 * dan tidak ada jeda "belum tahu siapa yang login" saat halaman pertama render.
 *
 * ⚠️ Hanya kirim field yang memang dipakai UI. Model User punya kolom sensitif
 * (kode aktivasi, kode lupa-sandi) — serialisasi utuh akan membocorkannya ke
 * HTML setiap halaman.
 */
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'user_id' => $user->user_id,
                    'nama' => $user->user_fullname,
                    'email' => $user->user_email,
                    'level' => $user->userlevel_id,
                    'foto' => $user->user_foto,
                    'petugas' => $user->isPetugas(),
                ] : null,
            ],
            'situs' => [
                'nama_populer' => config('situs.nama_populer'),
                'nama_lengkap' => config('situs.nama_lengkap'),
                'tenant' => config('situs.tenant'),
                'tahun' => config('situs.tahun_copyright'),
                'recaptcha_site_key' => config('services.recaptcha.site_key'),
            ],
            // Isi footer — SATU sumber dengan versi Blade-nya
            // (`publik/partials/footer.blade.php` membaca config yang sama).
            // Ditutup closure supaya hanya ikut pada permintaan yang benar-benar
            // merender halaman penuh, bukan pada kunjungan parsial Inertia.
            'footer' => fn () => config('footer'),
            // Petunjuk "Cek Status" saat login ditolak karena keadaan akun.
            'cekStatus' => fn () => $request->session()->get('cekStatus'),
            'flash' => [
                'sukses' => fn () => $request->session()->get('sukses'),
                'galat' => fn () => $request->session()->get('galat'),
            ],
        ]);
    }
}
