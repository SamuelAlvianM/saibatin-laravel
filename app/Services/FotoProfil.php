<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Penyimpan foto wajah/selfie pendaftaran **dan** foto/scan KTP pendaftar.
 *
 * 🔴 Berkasnya WAJIB di luar `public/`. Ini foto wajah dan KTP warga; apa pun di
 * `public/` disajikan web server tanpa cek sesi. Aturan ini sudah pernah
 * dilanggar dua kali di portal Next.js — sekali di lokal, sekali di skrip deploy.
 *
 * Nama berkas diawali id pemiliknya (`<uid>_<timestamp>.jpg`), dan justru
 * prefix itulah dasar kontrol akses saat berkas disajikan: warga hanya boleh
 * membuka berkas yang prefiksnya = id-nya sendiri.
 *
 * Selfie dan KTP dipisah foldernya supaya bisa dihapus/diaudit sendiri-sendiri,
 * tapi keduanya tetap di bawah `profil/` sehingga aturan default-deny di
 * BerkasController berlaku sama untuk keduanya.
 *
 * ⚠️ Beda dari portal Next.js: di sana `sharp` menormalkan gambar (rotasi EXIF,
 * ubah ukuran, jadikan JPEG). Di sini berkas disimpan apa adanya — padanan sharp
 * di PHP menuntut ekstensi yang belum tentu tersedia di cPanel, dan pengecilan
 * sudah dilakukan peramban sebelum dikirim. Batas 4 MB di bawah adalah jaring
 * pengaman terakhirnya.
 */
class FotoProfil
{
    private const DISK = 'local';           // storage/app/private

    /** Sub-folder di storage sekaligus segmen URL-nya (`/uploads/selfie/…`). */
    public const SELFIE = 'selfie';

    public const KTP = 'ktp';

    private const INDUK = 'profil';

    /** Batas data URL mentah. Klien sudah mengecilkan; ini jaring pengaman saja. */
    private const MAKS_BYTE = 4 * 1024 * 1024;

    /**
     * Bentuk lama: data URL lengkap (`data:image/jpeg;base64,…`).
     *
     * 🔴 Bentuk yang dipakai klien SEKARANG adalah base64 POLOS tanpa awalan
     * `data:` — mod_security (WAF bawaan cPanel) memblokir badan permintaan yang
     * memuat tanda tangan data URI, dan penolakannya muncul sebagai halaman 404
     * alih-alih galat JSON. Lihat `resources/js/lib/gambar.js`.
     *
     * Kedua bentuk tetap diterima supaya klien versi lama tidak ikut rusak.
     */
    private const POLA_DATA_URL = '#^data:image/(jpeg|jpg|png|webp);base64,#i';

    private const POLA_BASE64 = '#^[A-Za-z0-9+/\r\n]+={0,2}$#';

    public function adalahDataUrlGambar(mixed $nilai): bool
    {
        if (! is_string($nilai) || strlen($nilai) < 100) {
            return false;
        }

        return preg_match(self::POLA_DATA_URL, $nilai) === 1
            || preg_match(self::POLA_BASE64, $nilai) === 1;
    }

    /**
     * Simpan gambar base64 (data URL atau polos) → kembalikan URL publiknya
     * (`/uploads/selfie/…`), atau null bila masukan bukan gambar / terlalu besar.
     */
    public function simpan(?string $dataUrl, int $userId, string $folder = self::SELFIE): ?string
    {
        if (! $this->adalahDataUrlGambar($dataUrl)) {
            return null;
        }

        // Tanpa awalan `data:` seluruh nilainya memang sudah base64.
        $posisi = strpos($dataUrl, 'base64,');
        $meta = $posisi === false ? '' : substr($dataUrl, 0, $posisi);
        $base64 = $posisi === false ? $dataUrl : substr($dataUrl, $posisi + 7);
        $biner = base64_decode($base64, true);

        if ($biner === false || $biner === '' || strlen($biner) > self::MAKS_BYTE) {
            return null;
        }

        // Tanpa meta, tipe ditentukan dari byte pertama berkasnya sendiri —
        // klien mengirim JPEG hasil canvas, tapi jangan bergantung pada janji itu.
        $ext = $meta !== ''
            ? (str_contains($meta, 'png') ? 'png' : (str_contains($meta, 'webp') ? 'webp' : 'jpg'))
            : $this->ekstensiDariBiner($biner);

        if ($ext === null) {
            return null;
        }

        $nama = $userId.'_'.now()->getTimestampMs().'.'.$ext;

        Storage::disk(self::DISK)->put(self::INDUK.'/'.$folder.'/'.$nama, $biner);

        return '/uploads/'.$folder.'/'.$nama;
    }

    /** Simpan foto/scan KTP — folder terpisah, kontrol akses sama. */
    public function simpanKtp(?string $dataUrl, int $userId): ?string
    {
        return $this->simpan($dataUrl, $userId, self::KTP);
    }

    public function hapus(?string $url, string $folder = self::SELFIE): void
    {
        if (blank($url) || ! str_starts_with($url, '/uploads/'.$folder.'/')) {
            return;
        }

        Storage::disk(self::DISK)->delete(self::INDUK.'/'.$folder.'/'.basename($url));
    }

    public function hapusKtp(?string $url): void
    {
        $this->hapus($url, self::KTP);
    }

    /**
     * Ekstensi dari angka ajaib berkasnya. Sekaligus penjaga: nilai base64 yang
     * ternyata bukan gambar (mis. teks acak sepanjang 100 karakter yang lolos
     * pola) ditolak di sini, bukan disimpan sebagai `.jpg` palsu.
     */
    private function ekstensiDariBiner(string $biner): ?string
    {
        if (str_starts_with($biner, "\xFF\xD8\xFF")) {
            return 'jpg';
        }
        if (str_starts_with($biner, "\x89PNG\r\n\x1A\n")) {
            return 'png';
        }
        if (str_starts_with($biner, 'RIFF') && substr($biner, 8, 4) === 'WEBP') {
            return 'webp';
        }

        return null;
    }
}
