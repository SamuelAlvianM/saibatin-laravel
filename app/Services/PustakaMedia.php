<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pustaka media terpusat — port `lib/media.ts` + `app/api/media/upload`.
 *
 * Konvensi yang WAJIB dipertahankan karena datanya sudah ada di produksi:
 *   berkas fisik : storage/app/private/media/yyyy/mm/<uuid>.<ext>
 *   URL publik   : /uploads/media/yyyy/mm/<uuid>.<ext>
 *   kolom `path` : "yyyy/mm/<uuid>.<ext>" (relatif, tanpa awalan)
 *
 * Namanya UUID, jadi tidak bisa ditebak — itulah alasan berkasnya boleh
 * disajikan tanpa sesi, sementara berkas warga (KTP/KK) tidak pernah boleh.
 *
 * 🔴 Gambar dikonversi ke **WebP** seperti di portal Next.js (di sana pakai
 * sharp, di sini GD). Kalau tidak, ukuran unggahan dari kamera HP bisa 6 MB
 * per foto dan halaman publik jadi berat. GIF dibiarkan apa adanya — GD tidak
 * mempertahankan animasinya kalau dikonversi.
 */
class PustakaMedia
{
    public const MAKS_UKURAN = 10 * 1024 * 1024; // 10 MB
    public const SISI_MAKS = 2560;
    public const KUALITAS = 82;

    public const MIME_GAMBAR = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    public const MIME_LAIN = ['application/pdf'];

    /** Simpan satu unggahan ke pustaka; melempar bila tak layak. */
    public function simpan(UploadedFile $berkas, User $pengunggah): Media
    {
        $mime = $berkas->getMimeType();
        $gambar = in_array($mime, self::MIME_GAMBAR, true);

        if (! $gambar && ! in_array($mime, self::MIME_LAIN, true)) {
            throw new \RuntimeException('Format harus JPG, PNG, WebP, GIF, atau PDF');
        }

        if ($berkas->getSize() > self::MAKS_UKURAN) {
            throw new \RuntimeException('Ukuran file maksimal 10 MB');
        }

        $id = (string) Str::uuid();
        $subfolder = now()->format('Y/m');

        $lebar = null;
        $tinggi = null;

        if ($gambar && $mime !== 'image/gif') {
            [$isi, $lebar, $tinggi] = $this->keWebp($berkas);
            $ext = 'webp';
            $mimeAkhir = 'image/webp';
        } elseif ($mime === 'image/gif') {
            $isi = file_get_contents($berkas->getRealPath());
            [$lebar, $tinggi] = $this->ukuran($berkas->getRealPath());
            $ext = 'gif';
            $mimeAkhir = $mime;
        } else {
            $isi = file_get_contents($berkas->getRealPath());
            $ext = 'pdf';
            $mimeAkhir = $mime;
        }

        $namaFile = "{$id}.{$ext}";
        $relatif = "{$subfolder}/{$namaFile}";
        Storage::disk('local')->put("media/{$relatif}", $isi);

        return Media::create([
            'id' => $id,
            'nama_asli' => $berkas->getClientOriginalName(),
            'nama_file' => $namaFile,
            'mime_type' => $mimeAkhir,
            'ukuran' => strlen($isi),
            'lebar' => $lebar,
            'tinggi' => $tinggi,
            'path' => $relatif,
            'url' => "/uploads/media/{$relatif}",
            'uploaded_by' => $pengunggah->id,
        ]);
    }

    /** Hapus berkas fisiknya; record-nya urusan pemanggil. */
    public function hapusBerkas(Media $media): void
    {
        // Berkas yang sudah tidak ada di disk bukan alasan menggagalkan
        // penghapusan — record yatim justru lebih merepotkan.
        Storage::disk('local')->delete('media/'.$media->path);
    }

    /**
     * Konversi ke WebP + batasi sisi terpanjang 2560 px.
     *
     * @return array{0:string,1:int,2:int} isi berkas, lebar, tinggi
     */
    private function keWebp(UploadedFile $berkas): array
    {
        $jalur = $berkas->getRealPath();
        $sumber = match ($berkas->getMimeType()) {
            'image/jpeg' => imagecreatefromjpeg($jalur),
            'image/png' => imagecreatefrompng($jalur),
            'image/webp' => imagecreatefromwebp($jalur),
            default => false,
        };

        if ($sumber === false) {
            throw new \RuntimeException('Gambar tidak bisa dibaca');
        }

        $lebarAsli = imagesx($sumber);
        $tinggiAsli = imagesy($sumber);

        $skala = min(1, self::SISI_MAKS / max($lebarAsli, $tinggiAsli));
        $lebar = (int) round($lebarAsli * $skala);
        $tinggi = (int) round($tinggiAsli * $skala);

        $tujuan = imagecreatetruecolor($lebar, $tinggi);

        // PNG transparan → latar putih. WebP mendukung alpha, tapi gambar ini
        // dipakai di kartu berita/galeri berlatar putih; menyimpan alpha
        // membuat logo hitam "hilang" saat dipasang di kartu gelap.
        $putih = imagecolorallocate($tujuan, 255, 255, 255);
        imagefilledrectangle($tujuan, 0, 0, $lebar, $tinggi, $putih);
        imagecopyresampled($tujuan, $sumber, 0, 0, 0, 0, $lebar, $tinggi, $lebarAsli, $tinggiAsli);

        ob_start();
        imagewebp($tujuan, null, self::KUALITAS);
        $isi = (string) ob_get_clean();

        imagedestroy($sumber);
        imagedestroy($tujuan);

        return [$isi, $lebar, $tinggi];
    }

    /** @return array{0:?int,1:?int} */
    private function ukuran(string $jalur): array
    {
        $info = @getimagesize($jalur);

        return $info ? [$info[0], $info[1]] : [null, null];
    }
}
