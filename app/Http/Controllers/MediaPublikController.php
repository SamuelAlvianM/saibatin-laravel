<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penyaji berkas pustaka media: /uploads/media/yyyy/mm/<uuid>.<ext>
 * Port `app/uploads/media/[...path]/route.ts`.
 *
 * 🔴 Bedanya dengan `BerkasController`: yang ini **tanpa sesi**. Isinya memang
 * konten publik (gambar berita, galeri, dokumen publikasi) yang dipasang di
 * halaman yang bisa dibuka siapa saja. Yang menjaganya adalah nama berkas
 * berupa UUID — tidak bisa ditebak — bukan pemeriksaan izin.
 *
 * 🔴 Rutenya HARUS didaftarkan SEBELUM `/uploads/{jalur}` milik
 * `BerkasController`; pola itu menelan `media/...` juga, dan berkas media akan
 * dicari di folder permohonan lalu 404 tanpa penjelasan.
 */
class MediaPublikController extends Controller
{
    private const MIME = [
        'webp' => 'image/webp', 'gif' => 'image/gif', 'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf',
    ];

    public function __invoke(Request $request, string $jalur): Response
    {
        if (in_array('..', explode('/', $jalur), true)) {
            abort(404);
        }

        $ext = strtolower(pathinfo($jalur, PATHINFO_EXTENSION));
        if (! isset(self::MIME[$ext])) {
            abort(404);
        }

        $relatif = 'media/'.$jalur;
        if (! Storage::disk('local')->exists($relatif)) {
            abort(404);
        }

        return response(Storage::disk('local')->get($relatif), 200, [
            'Content-Type' => self::MIME[$ext],
            // Isi per-URL tidak pernah berubah (nama = UUID), jadi aman
            // di-cache selamanya — itu yang membuat halaman publik ringan.
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
