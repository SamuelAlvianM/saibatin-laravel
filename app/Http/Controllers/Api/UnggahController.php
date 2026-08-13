<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Balasan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Unggah berkas umum (dokumen PPID, produk hukum, lampiran dashboard).
 *
 * 🔴 PERUBAHAN DISENGAJA dari portal Next.js: berkasnya ditulis ke
 * `storage/app/private/…`, **bukan** `public/uploads/…`. URL publiknya tetap
 * sama (`/uploads/<folder>/<berkas>`) dan dilayani `BerkasController` yang
 * memeriksa izin.
 *
 * Alasannya: menulis ke `public/` persis yang membuat 560 MB scan KTP/KK warga
 * terekspos tanpa cek sesi di produksi sekarang. Kontraknya tidak berubah — yang
 * berubah hanya letak fisiknya.
 */
class UnggahController extends Controller
{
    private const MAKS_GAMBAR = 5 * 1024 * 1024;   // 5 MB
    private const MAKS_PDF = 25 * 1024 * 1024;     // 25 MB — dokumen PPID hasil pindai
    private const EKSTENSI = ['jpg', 'jpeg', 'png', 'pdf'];

    public function __invoke(Request $request)
    {
        $berkas = $request->file('file');

        if (! $berkas || ! $berkas->isValid()) {
            return Balasan::gagal(['File tidak ditemukan']);
        }

        $ext = strtolower($berkas->getClientOriginalExtension());
        if (! in_array($ext, self::EKSTENSI, true)) {
            return Balasan::gagal(['Format file harus JPG, PNG, atau PDF']);
        }

        $maks = $ext === 'pdf' ? self::MAKS_PDF : self::MAKS_GAMBAR;
        if ($berkas->getSize() > $maks) {
            return Balasan::gagal(['Ukuran file maksimal '.($maks / 1024 / 1024).' MB']);
        }

        $folder = $this->folderAman((string) $request->input('folder', 'berkas'));
        $u = $request->user();

        // Nama diawali id pengunggah — itulah dasar kontrol akses saat berkasnya
        // disajikan kembali (lihat BerkasController).
        $nama = $u->id.'_'.now()->getTimestampMs().'.'.$ext;

        // putFileAs mengalirkan berkas dari lokasi sementara, tidak memuatnya
        // utuh ke memori. Portal Next.js sempat kena OOM karena menahan dua
        // salinan PDF puluhan MB sekaligus di RAM.
        Storage::disk('local')->putFileAs("permohonan/{$folder}", $berkas, $nama);

        return Balasan::ok(['url' => "/uploads/{$folder}/{$nama}"], ['File berhasil diunggah']);
    }

    /**
     * `folder` datang dari klien dan ikut menyusun path di disk. Tanpa
     * pembatasan ini, nilai seperti `../../..` bisa menulis berkas di luar
     * direktori unggahan.
     */
    private function folderAman(string $nilai): string
    {
        $bersih = preg_replace('/[^a-z0-9-]/', '', strtolower($nilai));

        return $bersih !== '' ? $bersih : 'berkas';
    }
}
