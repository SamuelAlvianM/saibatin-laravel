<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

/**
 * Pengirim surel transaksional.
 *
 * 🔴 Tidak pernah melempar. Surel adalah pemberitahuan, bukan bagian dari
 * transaksi: status permohonan yang sudah tersimpan tidak boleh gagal hanya
 * karena SMTP sedang tidak bisa dihubungi. Ini persis perilaku portal Next.js —
 * `sendMail()` di sana pun menelan galatnya.
 */
class Surel
{
    public function kirim(?string $ke, string $subjek, string $view, array $data = []): bool
    {
        if (blank($ke)) {
            return false;
        }

        try {
            Mail::send($view, $data, fn ($m) => $m->to($ke)->subject($subjek));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
