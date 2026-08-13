<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Otp;
use App\Support\Balasan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Kirim & verifikasi kode OTP pendaftaran.
 *
 * Pembatas kirim-ulang memakai Cache (driver `database`), BUKAN variabel
 * in-memory seperti di portal Next.js. Alasannya: di PHP setiap request adalah
 * proses baru — penghitung di memori akan selalu kosong, sehingga pembatasnya
 * tak berfungsi sama sekali.
 */
class OtpController extends Controller
{
    private const JEDA_DETIK = 60;

    public function __construct(private readonly Otp $otp) {}

    public function kirim(Request $request)
    {
        // Production tanpa kanal OTP: dinonaktifkan supaya pendaftaran warga
        // tidak terkunci hanya karena layanan OTP belum dikonfigurasi.
        if (! $this->otp->wajib()) {
            return Balasan::ok(['dinonaktifkan' => true]);
        }

        $kanal = $this->otp->kanal() ?? 'email';
        $identitas = $kanal === 'email'
            ? $this->otp->normalisasiEmail($request->input('email'))
            : $this->otp->normalisasiHp($request->input('hp'));

        if (! $identitas) {
            return Balasan::gagal([$kanal === 'email'
                ? 'Alamat email tidak valid'
                : 'Nomor HP tidak valid — gunakan format 08xx/62xx']);
        }

        $kunci = 'otp:jeda:'.sha1($identitas);
        if ($sisa = Cache::get($kunci)) {
            $detik = max(1, $sisa - time());

            return Balasan::gagal(["Tunggu {$detik} detik sebelum meminta kode baru"], 429);
        }

        ['kode' => $kode, 'challenge' => $challenge] = $this->otp->buat($identitas);
        $terkirim = $this->otp->kirim($kanal, $identitas, $kode);

        if (! $terkirim && app()->isProduction()) {
            return Balasan::gagal(['Layanan OTP belum dikonfigurasi (MAIL_* / FONNTE_TOKEN)'], 503);
        }

        Cache::put($kunci, time() + self::JEDA_DETIK, self::JEDA_DETIK);

        return Balasan::ok([
            'kanal' => $kanal,
            'challenge' => $challenge,
            'ttlDetik' => 300,
            // Mode dev tanpa layanan kirim: kode ditampilkan supaya alurnya
            // tetap bisa diuji. Tidak pernah terjadi di production (dicegat di atas).
            ...($terkirim ? [] : ['devKode' => $kode]),
        ]);
    }

    public function verifikasi(Request $request)
    {
        $kanal = $this->otp->kanal() ?? 'email';
        $identitas = $kanal === 'email'
            ? $this->otp->normalisasiEmail($request->input('email'))
            : $this->otp->normalisasiHp($request->input('hp'));

        $kode = preg_replace('/\D/', '', (string) $request->input('kode'));
        $challenge = (string) $request->input('challenge');

        if (! $identitas || ! $kode || ! $challenge) {
            return Balasan::gagal(['Kode verifikasi tidak lengkap']);
        }

        if (! $this->otp->verifikasi($identitas, $kode, $challenge)) {
            return Balasan::gagal(['Kode salah atau sudah kedaluwarsa']);
        }

        return Balasan::ok(['bukti' => $this->otp->buatBukti($identitas)], ['Verifikasi berhasil']);
    }
}
