<?php

namespace App\Http\Controllers;

use App\Models\Berkas;
use App\Services\FotoProfil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penyaji berkas unggahan: /uploads/{jalur}
 *
 * 🔴 ALASAN ROUTE INI ADA. Berkas warga (KTP, KK, akta, foto wajah) TIDAK BOLEH
 * berada di `public/` — apa pun di sana disajikan web server tanpa cek sesi.
 * Aturan ini sudah dilanggar dua kali di portal Next.js, dan di produksi masih
 * ada 560 MB scan warga yang terlanjur di `public/uploads/`.
 *
 * Kontrol aksesnya:
 *   petugas (level 1/2) → boleh semua
 *   warga               → hanya berkas miliknya sendiri
 *   selain itu          → 404
 *
 * Kepemilikan dibaca dari PREFIX nama berkas (`<uid>_<timestamp>.<ext>`), yang
 * ditulis saat mengunggah — jadi tidak perlu query tambahan ke database.
 *
 * Setiap penolakan memakai 404, bukan 403: 403 mengonfirmasi bahwa berkasnya ada.
 */
class BerkasController extends Controller
{
    private const MIME = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'gif' => 'image/gif', 'pdf' => 'application/pdf',
    ];

    /**
     * Folder yang memang untuk konsumsi publik. DEFAULT-DENY: sisanya berizin.
     *
     * Keempatnya berisi konten yang dipasang di halaman yang bisa dibuka siapa
     * saja — gambar berita, foto galeri, dokumen publikasi. Inilah 147,7 MB
     * yang SAH publik di produksi; sisa 560,2 MB di `public/uploads/` adalah
     * scan KTP/KK/akta warga dan tidak boleh ikut (lihat HANDOFF §7).
     *
     * `gallery` DAN `galeri` dua-duanya ada di produksi — warisan penamaan yang
     * pernah berganti. Menghapus salah satunya membuat foto lama hilang.
     */
    private const FOLDER_PUBLIK = ['produk', 'berita', 'galeri', 'gallery'];

    public function tampilkan(Request $request, string $jalur): Response
    {
        // Tolak path traversal per-segmen (aman lintas OS).
        $segmen = explode('/', $jalur);
        if ($segmen === [] || in_array('..', $segmen, true)) {
            abort(404);
        }

        $folder = $segmen[0];
        $namaFile = end($segmen);
        $ext = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

        if (! isset(self::MIME[$ext])) {
            abort(404);
        }

        $publik = in_array($folder, self::FOLDER_PUBLIK, true);

        if (! $publik) {
            $user = $request->user();
            if (! $user) {
                abort(404);
            }

            if (! $user->isPetugas() && ! self::miliknya($user->id, $namaFile, $jalur)) {
                abort(404);
            }
        }

        // Selfie & scan KTP punya folder sendiri di storage; sisanya berkas
        // permohonan. Keduanya sama-sama berizin — pemisahannya hanya supaya
        // bisa dihapus/diaudit terpisah.
        $relatif = in_array($folder, [FotoProfil::SELFIE, FotoProfil::KTP], true)
            ? 'profil/'.$folder.'/'.$namaFile
            : 'permohonan/'.$jalur;

        if (! Storage::disk('local')->exists($relatif)) {
            abort(404);
        }

        return response(Storage::disk('local')->get($relatif), 200, [
            'Content-Type' => self::MIME[$ext],
            // Berkas pribadi TIDAK boleh singgah di cache bersama/CDN.
            'Cache-Control' => $publik ? 'public, max-age=31536000, immutable' : 'private, no-store',
        ]);
    }

    /**
     * Apakah berkas ini milik warga tersebut?
     *
     * DUA konvensi penamaan hidup berdampingan, dan itu yang membuat versi
     * pertama fungsi ini salah:
     *
     * 1. **Port ini** menulis `<uid>_<timestamp>.<ext>` — pemiliknya terbaca
     *    langsung dari nama berkas, tanpa menyentuh database.
     * 2. **1.485 berkas WARISAN** berbentuk lain sama sekali:
     *    `/uploads/kkpisahkk/KKP01001.1778552208/1778552123_ngm.desa_KKP01_….jpg`
     *    Segmen pertamanya **timestamp**, bukan id. Memperlakukannya sebagai id
     *    berarti setiap warga mendapat **404 untuk berkasnya sendiri** —
     *    menolak, bukan membocorkan, tapi tetap salah dan wajib ditutup
     *    sebelum cutover (HANDOFF §7 no. 4).
     *
     * Karena itu prefix dicoba lebih dulu (jalur cepat, tanpa kueri), lalu
     * jatuh ke `t_berkas.path` → `t_permohonan.user_id` untuk yang warisan.
     *
     * ⚠️ Prefix hanya boleh dipercaya karena rentang angkanya tidak mungkin
     * bertabrakan: timestamp warisan 10 digit, id pengguna paling banyak 4–5
     * digit. Kalau suatu saat id tumbuh sampai 10 digit, jalur cepat ini harus
     * dibuang dan semuanya lewat database.
     */
    private static function miliknya(int $uid, string $namaFile, string $jalur): bool
    {
        $prefix = filter_var(explode('_', $namaFile)[0] ?? '', FILTER_VALIDATE_INT);

        if ($prefix !== false && $prefix === $uid) {
            return true;
        }

        // Berkas warisan: kepemilikannya hanya diketahui database. Dicocokkan
        // dengan path LENGKAP seperti tersimpan, bukan nama berkasnya saja —
        // nama berkas warisan tidak dijamin unik antar-folder layanan.
        return Berkas::where('path', '/uploads/'.$jalur)
            ->whereHas('permohonan', fn ($q) => $q->where('user_id', $uid))
            ->exists();
    }
}
