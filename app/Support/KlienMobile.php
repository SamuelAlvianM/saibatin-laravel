<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Penanda permintaan dari aplikasi mobile.
 *
 * Seluruh API mobile berada di bawah satu prefix. Controller yang dipakai
 * BERSAMA oleh website dan aplikasi (daftar akun, lupa sandi) membaca penanda
 * ini untuk memilih bentuk balasan: website mendapat redirect + flash,
 * aplikasi mendapat JSON `Balasan`. Logikanya tetap satu.
 */
final class KlienMobile
{
    public const PREFIX = 'api/mobile/v1';

    public static function dari(Request $request): bool
    {
        return $request->is(self::PREFIX, self::PREFIX.'/*');
    }
}
