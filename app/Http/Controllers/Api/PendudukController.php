<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Balasan;
use App\Support\StatusAkun;
use Illuminate\Http\Request;

/**
 * Pengecekan NIK.
 *
 * ⚠️ Portal ini **tidak punya tabel master kependudukan (dapduk)** — tabel
 * `m_maps_dapduks` ada di DB Laravel lama dan tidak ikut dimigrasikan. Jadi
 * "pengecekan" di sini terbatas pada format 16 digit dan keberadaan akun,
 * bukan verifikasi ke data SIAK. Jangan menjanjikan lebih dari itu di UI.
 */
class PendudukController extends Controller
{
    /**
     * Deteksi NIK pada formulir permohonan (butuh login).
     *
     * 🔴 Data pribadi HANYA dikembalikan bila NIK yang dicek milik pengguna yang
     * sedang login. Untuk NIK orang lain, balasannya cuma "terdaftar / belum" —
     * tanpa nama, KK, HP, atau email. Endpoint ini dipanggil dari formulir yang
     * bisa diisi siapa pun yang punya akun.
     */
    public function cek(Request $request)
    {
        $nik = (string) $request->input('nik');

        if (! preg_match('/^\d{16}$/', $nik)) {
            return Balasan::gagal(['NIK harus 16 digit angka']);
        }

        $pemilik = User::where(fn ($q) => $q->where('user_nik', $nik)->orWhere('user_id', $nik))
            ->where('status', StatusAkun::AKTIF)
            ->first(['id', 'user_fullname', 'user_nokk', 'user_hp', 'user_email']);

        $terdaftar = (bool) $pemilik;
        $miliknya = $pemilik && $pemilik->id === $request->user()->id;

        return Balasan::ok([
            'nik' => $nik,
            'terdaftar' => $terdaftar,
            'autofill' => $miliknya ? [
                'nama' => $pemilik->user_fullname ?? '',
                'nokk' => $pemilik->user_nokk ?? '',
                'hp' => $pemilik->user_hp ?? '',
                'email' => $pemilik->user_email ?? '',
            ] : null,
        ], [
            $terdaftar
                ? 'NIK sudah terdaftar di database'
                : 'NIK belum terdaftar — dianggap pemohon baru',
        ]);
    }

    /**
     * Pra-pengecekan NIK & KK sebelum registrasi (publik).
     *
     * Kode galat C-xx dipertahankan seperti portal lama.
     */
    public function cekNikKk(Request $request)
    {
        $nik = (string) $request->input('nik');
        $kk = (string) $request->input('kk');

        if (blank($nik) || blank($kk)) {
            return Balasan::gagal(['Info: NIK dan Nomor KK wajib diisi (C-10)']);
        }
        if (strlen($nik) !== 16) {
            return Balasan::gagal(['Info: NIK Harus 16 Digit (C-15)']);
        }
        if (strlen($kk) !== 16) {
            return Balasan::gagal(['Info: Nomor KK Harus 16 Digit (C-16)']);
        }
        if (! preg_match('/^\d{16}$/', $nik) || ! preg_match('/^\d{16}$/', $kk)) {
            return Balasan::gagal(['Info: NIK dan KK hanya boleh berisi angka (C-17)']);
        }

        $sudahAktif = User::where('user_id', $nik)->where('status', StatusAkun::AKTIF)->exists();

        if ($sudahAktif) {
            return Balasan::gagal(['Info: NIK sudah terdaftar, silakan login atau gunakan NIK lain (C-03)']);
        }

        return Balasan::ok(
            ['nik' => $nik, 'kk' => $kk, 'eligible' => true],
            ['Info: NIK & KK valid, silakan lanjutkan pendaftaran']
        );
    }
}
