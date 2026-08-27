<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Services\FotoProfil;
use App\Support\Layanan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Unduh tanda terima permohonan sebagai PDF — `GET /api/permohonan/{id}/pdf`.
 *
 * Fungsi terakhir yang belum ada di halaman-halaman yang sudah jadi (Fase 8 di
 * peta port). Isinya sengaja **tanda terima**, bukan dokumen kependudukan:
 * dokumen aslinya (KK, akta, KTP) tetap dicetak dinas dengan blangko resmi.
 * Berkas ini hanya bukti bahwa permohonan diajukan dan status akhirnya apa.
 *
 * 🔴 Kontrol aksesnya sama dengan penyaji berkas: petugas boleh semua, warga
 * hanya miliknya sendiri, sisanya **404** — bukan 403, karena 403 sudah
 * mengonfirmasi bahwa nomor registrasi itu ada.
 *
 * 🔴 Lampiran DISEMATKAN, tapi selalu lewat `kecilkan()` lebih dulu. Menyematkan
 * berkas aslinya apa adanya bukan pilihan: dompdf membongkar setiap gambar jadi
 * bitmap tak terkompresi di memori, jadi satu scan 5 MB beresolusi penuh
 * (± 4000×3000) memakan ± 48 MB sendirian — enam lampiran akan menembus
 * `memory_limit` 128 MB cPanel dan berakhir sebagai layar putih tanpa pesan.
 * Setelah dikecilkan ke lebar 1.000 px, masing-masing tinggal ± 4 MB saat
 * diproses dan dilepas lagi sebelum gambar berikutnya dibuka.
 */
class PermohonanPdfController extends Controller
{
    /** Lebar maksimum lampiran di dalam PDF, dalam piksel. */
    private const LEBAR_LAMPIRAN = 1000;

    /**
     * Bagian memori bebas yang boleh dipakai satu gambar saat didekode.
     * Sisanya untuk dompdf, yang memegang seluruh halaman sampai selesai.
     */
    private const PORSI_MEMORI = 0.45;

    public function __invoke(Request $request, int $id)
    {
        $u = $request->user();

        $permohonan = Permohonan::with(['jenis:id,nama,kode', 'user:id,user_id,user_fullname,user_email,user_hp', 'berkas'])
            ->find($id);

        if (! $permohonan) {
            abort(404);
        }
        if (! $u->isPetugas() && $permohonan->user_id !== $u->id) {
            abort(404);
        }

        $form = Layanan::formDariKode($permohonan->jenis->kode ?? null);
        $isi = Layanan::tampilkanPayload($form, $permohonan->payload);

        // Menaikkan batas seperlunya: dompdf memegang seluruh halaman di memori
        // sampai berkasnya selesai ditulis. Sama polanya dengan ekspor Excel.
        // Kalau hosting menolak kenaikan ini, yang gagal hanya lampirannya —
        // `kecilkan()` mengembalikan null dan PDF tetap terbit.
        @ini_set('memory_limit', '256M');

        $pdf = Pdf::loadView('pdf.permohonan', [
            'p' => $permohonan,
            'data' => $isi['data'],
            'berkas' => $this->siapkanLampiran($isi['berkas']),
            'dicetakOleh' => $u->user_fullname ?: $u->user_id,
            'logo' => $this->logoKop(),
        ])->setPaper('a4');

        // `download` (bukan `stream`) supaya peramban ponsel menyimpannya
        // alih-alih membukanya di penampil bawaan yang sering gagal.
        return $pdf->download("Permohonan-{$permohonan->no_register}.pdf");
    }

    /**
     * Lengkapi tiap lampiran dengan data URI gambarnya yang sudah dikecilkan.
     *
     * Berkas yang gagal dibaca TIDAK menggagalkan PDF-nya — entrinya tetap
     * tampil sebagai nama saja, dengan keterangan. Sebuah tanda terima yang
     * kehilangan satu gambar masih berguna; tanda terima yang gagal terbit
     * karena satu berkas rusak tidak.
     *
     * @param  array<int,array{label:string,path:string}>  $berkas
     * @return array<int,array{label:string,path:string,gambar:?string}>
     */
    private function siapkanLampiran(array $berkas): array
    {
        return array_map(fn ($b) => $b + ['gambar' => $this->kecilkan($b['path'])], $berkas);
    }

    /**
     * Baca berkas lampiran dari storage privat lalu kecilkan jadi JPEG.
     *
     * 🔴 Jalur berkas dipetakan dengan aturan yang SAMA seperti
     * `BerkasController` — `/uploads/selfie|ktp/…` ada di `profil/`, sisanya di
     * `permohonan/`. Menebak satu pola saja membuat foto KTP diam-diam hilang
     * dari PDF tanpa galat apa pun.
     *
     * Kepemilikan TIDAK diperiksa lagi di sini: pemanggilnya sudah memastikan
     * permohonan ini milik pemohon yang meminta (atau ia petugas), dan seluruh
     * berkas berasal dari `t_berkas` milik permohonan itu.
     */
    private function kecilkan(string $jalurUrl): ?string
    {
        if (! str_starts_with($jalurUrl, '/uploads/')) {
            return null;
        }

        $jalur = substr($jalurUrl, strlen('/uploads/'));
        $segmen = explode('/', $jalur);
        if (in_array('..', $segmen, true)) {
            return null;
        }

        $ext = strtolower(pathinfo(end($segmen), PATHINFO_EXTENSION));
        // PDF lampiran tidak bisa disematkan sebagai gambar; biarkan jadi entri
        // nama saja. Berkas permohonan memang hanya gambar (lihat LayananController).
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $relatif = in_array($segmen[0], [FotoProfil::SELFIE, FotoProfil::KTP], true)
            ? 'profil/'.$segmen[0].'/'.end($segmen)
            : 'permohonan/'.$jalur;

        $disk = Storage::disk('local');
        if (! $disk->exists($relatif)) {
            return null;
        }

        $absolut = $disk->path($relatif);

        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        /**
         * 🔴 Penjaganya JUMLAH PIKSEL, bukan ukuran berkas. Ukuran berkas
         * praktis tidak berhubungan dengan biaya dekode: JPEG 900 KB bisa saja
         * 4000×3000 dan menempati **48 MB** begitu dibuka GD (lebar × tinggi ×
         * 4 byte). Diukur 17 Agu 2026: satu gambar seperti itu memuncakkan
         * proses ke 74 MB — di `memory_limit` bawaan cPanel 128 MB, dua di
         * antaranya sudah cukup untuk layar putih tanpa pesan.
         *
         * `getimagesize` membaca header saja, jadi ukurannya diketahui SEBELUM
         * ada yang didekode. Gambar yang tidak muat dilewati dengan anggun —
         * entrinya tetap tampil sebagai nama berkas.
         */
        $ukuran = @getimagesize($absolut);
        if (! $ukuran) {
            return null;
        }

        [$lebarAsli, $tinggiAsli] = $ukuran;
        $butuh = $lebarAsli * $tinggiAsli * 4;
        if ($butuh > $this->memoriTersedia() * self::PORSI_MEMORI) {
            return null;
        }

        $asli = @imagecreatefromstring(file_get_contents($absolut));
        if (! $asli) {
            return null;
        }
        $skala = min(1, self::LEBAR_LAMPIRAN / max(1, $lebarAsli));
        $lebar = max(1, (int) round($lebarAsli * $skala));
        $tinggi = max(1, (int) round($tinggiAsli * $skala));

        $kecil = imagecreatetruecolor($lebar, $tinggi);
        // Scan sering ber-latar transparan (PNG); tanpa dasar putih, area itu
        // jadi hitam pekat begitu dikonversi ke JPEG.
        imagefilledrectangle($kecil, 0, 0, $lebar, $tinggi, imagecolorallocate($kecil, 255, 255, 255));
        imagecopyresampled($kecil, $asli, 0, 0, 0, 0, $lebar, $tinggi, $lebarAsli, $tinggiAsli);
        imagedestroy($asli);

        ob_start();
        // JPEG, bukan PNG: isinya foto/pindaian, dan PNG untuk gambar seperti
        // itu berlipat ukurannya tanpa terlihat lebih baik.
        imagejpeg($kecil, null, 72);
        $data = ob_get_clean();
        imagedestroy($kecil);

        return 'data:image/jpeg;base64,'.base64_encode($data);
    }

    /**
     * Sisa memori yang masih boleh dipakai proses ini, dalam byte.
     *
     * Dibaca dari `memory_limit` SESUDAH percobaan menaikkannya, bukan dari
     * angka yang kita harapkan — di hosting yang memakai `php_admin_value`,
     * `ini_set` gagal diam-diam dan menghitung dari angka harapan berarti
     * membuka gambar yang tidak akan pernah muat.
     */
    private function memoriTersedia(): int
    {
        $batas = trim((string) ini_get('memory_limit'));

        // "-1" = tanpa batas. Tetap dipatok supaya satu unduhan tidak menyeret
        // seluruh server; 512 MB jauh di atas kebutuhan nyata.
        if ($batas === '-1' || $batas === '') {
            return 512 * 1024 * 1024;
        }

        $angka = (int) $batas;
        $bytes = match (strtolower(substr($batas, -1))) {
            'g' => $angka * 1024 * 1024 * 1024,
            'm' => $angka * 1024 * 1024,
            'k' => $angka * 1024,
            default => $angka,
        };

        return max(0, $bytes - memory_get_usage(true));
    }

    /**
     * Logo kop sebagai data URI, dikecilkan lebih dulu ke tinggi 104 px.
     *
     * 🔴 Kenapa dikecilkan. dompdf menyimpan PNG ke dalam PDF sebagai bitmap
     * yang sudah dibongkar, jadi ukuran BERKAS SUMBER hampir tidak berpengaruh —
     * yang menentukan adalah jumlah pikselnya. Logo asli (178 KB, resolusi
     * penuh) menghasilkan PDF **1.007 KB** untuk selembar tanda terima. Pada
     * tinggi cetak 52 px, seluruh piksel di atas ~104 px (2× untuk layar
     * beresolusi tinggi) tidak menambah apa pun yang bisa dilihat.
     *
     * Hasilnya di-cache: menskalakan ulang gambar yang sama untuk setiap
     * unduhan membakar CPU cPanel tanpa alasan.
     *
     * Data URI dipakai, bukan path berkas: dompdf menolak path relatif kecuali
     * `isRemoteEnabled` dinyalakan, dan opsi itu membuat PDF bisa menarik URL
     * luar — permukaan serangan yang tidak perlu untuk sebuah kop surat.
     */
    private function logoKop(): ?string
    {
        $sumber = public_path('logo-saibatin.png');
        if (! is_file($sumber)) {
            return null;
        }

        return cache()->remember('pdf.logo-kop.'.filemtime($sumber), now()->addDays(30), function () use ($sumber) {
            // Tanpa GD (mis. ekstensi belum aktif di cPanel) tetap jalan —
            // hanya PDF-nya lebih besar, bukan gagal.
            if (! function_exists('imagecreatefrompng')) {
                return 'data:image/png;base64,'.base64_encode(file_get_contents($sumber));
            }

            $asli = @imagecreatefrompng($sumber);
            if (! $asli) {
                return 'data:image/png;base64,'.base64_encode(file_get_contents($sumber));
            }

            $tinggi = 104;
            $lebar = (int) round(imagesx($asli) * ($tinggi / imagesy($asli)));

            $kecil = imagecreatetruecolor($lebar, $tinggi);
            // Logo instansi berlatar transparan; tanpa dua baris ini latarnya
            // jadi hitam pekat di dalam PDF.
            imagealphablending($kecil, false);
            imagesavealpha($kecil, true);
            imagecopyresampled($kecil, $asli, 0, 0, 0, 0, $lebar, $tinggi, imagesx($asli), imagesy($asli));

            ob_start();
            imagepng($kecil, null, 9);
            $data = ob_get_clean();

            imagedestroy($asli);
            imagedestroy($kecil);

            return 'data:image/png;base64,'.base64_encode($data);
        });
    }
}
