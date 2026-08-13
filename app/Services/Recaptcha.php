<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifikasi token reCAPTCHA v3 di sisi server.
 *
 * 🔴 CATATAN KEAMANAN — perilaku "fail-open" ini disengaja untuk DEV saja:
 * bila `RECAPTCHA_SECRET_KEY` kosong, verifikasi langsung lolos supaya alur
 * pengembangan tidak terblokir. Di project saudara hal ini pernah terbawa ke
 * PRODUKSI dengan kunci kosong, dan akibatnya 6 endpoint publik (login,
 * register, lupa/reset sandi, kritik-saran, permohonan) berjalan tanpa
 * proteksi bot sama sekali tanpa ada yang menyadarinya.
 *
 * Karena itu di sini kondisi tersebut DICATAT ke log sebagai peringatan setiap
 * kali terjadi di lingkungan production — supaya tidak lagi senyap.
 */
class Recaptcha
{
    private const ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    public function verifikasi(?string $token, float $skorMinimal = 0.5): bool
    {
        $secret = config('services.recaptcha.secret');

        if (blank($secret)) {
            if (app()->isProduction()) {
                Log::warning('reCAPTCHA dilewati: RECAPTCHA_SECRET_KEY kosong di production — endpoint publik tanpa proteksi bot.');
            }

            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $res = Http::asForm()->timeout(8)->post(self::ENDPOINT, [
                'secret' => $secret,
                'response' => $token,
            ]);

            return $res->successful()
                && $res->json('success') === true
                && (float) $res->json('score', 0) >= $skorMinimal;
        } catch (\Throwable $e) {
            Log::warning('Verifikasi reCAPTCHA gagal dihubungi: '.$e->getMessage());

            // Gagal menghubungi Google → tolak. Lebih baik pengguna mencoba lagi
            // daripada membuka pintu saat layanan verifikasi sedang tidak bisa dicek.
            return false;
        }
    }
}
