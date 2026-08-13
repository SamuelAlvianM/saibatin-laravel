<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Pengirim pesan WhatsApp lewat Fonnte (dipakai untuk OTP pendaftaran). */
class Fonnte
{
    private const ENDPOINT = 'https://api.fonnte.com/send';

    public function aktif(): bool
    {
        return filled(config('services.fonnte.token'));
    }

    public function kirim(string $target, string $pesan): bool
    {
        if (! $this->aktif()) {
            return false;
        }

        try {
            $res = Http::withHeaders(['Authorization' => config('services.fonnte.token')])
                ->asForm()->timeout(15)
                ->post(self::ENDPOINT, ['target' => $target, 'message' => $pesan]);

            return $res->successful() && $res->json('status') === true;
        } catch (\Throwable $e) {
            Log::warning('Fonnte gagal mengirim: '.$e->getMessage());

            return false;
        }
    }
}
