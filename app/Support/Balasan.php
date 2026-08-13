<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Format respons seragam — kontrak yang diwarisi dari portal Laravel 9 asli dan
 * sengaja dipertahankan lewat portal Next.js:
 *
 *     { error: string[], success: string[], data: mixed, html: mixed }
 *
 * Bentuk ini dipakai SEMUA endpoint. Pesannya jamak (array), bukan tunggal,
 * karena validasi memang mengumpulkan seluruh kekurangan lalu membalasnya
 * sekaligus — warga tahu persis apa saja yang belum lengkap, bukan satu per satu.
 */
final class Balasan
{
    public static function ok(mixed $data = null, array $success = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'error' => [],
            'success' => $success,
            'data' => $data,
            'html' => [],
        ], $status);
    }

    public static function gagal(array $error, int $status = 400, mixed $data = null): JsonResponse
    {
        return response()->json([
            'error' => $error,
            'success' => [],
            'data' => $data,
            'html' => [],
        ], $status);
    }
}
