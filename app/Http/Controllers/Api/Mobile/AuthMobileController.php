<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLevel;
use App\Support\Balasan;
use App\Support\StatusAkun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Login aplikasi mobile — padanan `Auth\LoginController` untuk token Bearer.
 *
 * Aturannya SAMA dengan login website, termasuk kode galat L-02/L-03/L-04 yang
 * biasa disebut petugas dinas saat melapor. Yang berbeda hanya keluarannya:
 * website membuat sesi cookie, di sini dibuatkan token Sanctum.
 *
 * ⚠️ Kalau aturan login website berubah (status yang boleh masuk, pesan, kode
 * galat), ubah juga di sini. Keduanya sengaja tidak digabung: jalur website
 * membalas dengan redirect + flash ke halaman Cek Status, yang tidak punya
 * padanan di JSON.
 */
class AuthMobileController extends Controller
{
    public function masuk(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'string'],
            'password' => ['required', 'string'],
            // Nama perangkat, mis. "Samsung A15 — Android 14". Tampil di daftar
            // token sehingga sesi di ponsel tertentu bisa dicabut kelak.
            'perangkat' => ['nullable', 'string', 'max:100'],
        ], [
            'user_id.required' => 'NIK/User ID harus diisi',
            'password.required' => 'Password harus diisi',
        ]);

        $identitas = trim($data['user_id']);
        $user = User::where('user_id', $identitas)->orderByDesc('id')->first();

        if (! $user) {
            return Balasan::gagal(['Info: NIK/Username belum terdaftar (L-02)'], 401);
        }

        if ($user->status !== StatusAkun::AKTIF) {
            // Warga diberi petunjuk ke Cek Status (POST auth/cek-status), sama
            // seperti website mengantar mereka ke halaman itu.
            return Balasan::gagal(
                ['Info: '.StatusAkun::pesanLogin($user->status).' (L-03)'],
                403,
                $user->userlevel_id === UserLevel::WARGA
                    ? ['cekStatus' => ['status' => $user->status, 'nik' => $user->user_id]]
                    : null,
            );
        }

        if (! Hash::check($data['password'], $user->password)) {
            return Balasan::gagal(['Info: Password salah (L-04)'], 401);
        }

        $lengkapiFoto = $user->userlevel_id === UserLevel::WARGA
            && blank($user->user_foto)
            && $user->login_last === null;

        $token = $user->createToken($data['perangkat'] ?? 'Aplikasi Mobile')->plainTextToken;

        $user->forceFill([
            'login_last' => now(),
            'ip_address' => $request->ip(),
        ])->save();

        return Balasan::ok([
            'token' => $token,
            'tipeToken' => 'Bearer',
            'kedaluwarsaMenit' => config('sanctum.expiration'),
            'lengkapiFoto' => $lengkapiFoto,
            'user' => self::ringkasUser($user),
        ], ['Selamat datang, '.(strtok(trim((string) $user->user_fullname), ' ') ?: 'kembali').'.']);
    }

    /** Cabut token yang sedang dipakai — sesi di perangkat lain tidak ikut keluar. */
    public function keluar(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return Balasan::ok(null, ['Anda telah keluar.']);
    }

    public function saya(Request $request)
    {
        return Balasan::ok(['user' => self::ringkasUser($request->user())]);
    }

    /**
     * Identitas + peran. `peran` disediakan sebagai teks supaya aplikasi tidak
     * perlu menghafal angka level — tapi `level` tetap ikut untuk pemeriksaan.
     */
    public static function ringkasUser(User $u): array
    {
        return [
            'id' => $u->id,
            'userId' => $u->user_id,
            'nama' => $u->user_fullname,
            'level' => $u->userlevel_id,
            'peran' => match ($u->userlevel_id) {
                UserLevel::SUPER_ADMIN => 'super_admin',
                UserLevel::OPERATOR => 'operator',
                UserLevel::OPERATOR_OPD => 'operator_opd',
                default => 'warga',
            },
            'namaPeran' => UserLevel::NAMA[$u->userlevel_id] ?? 'Warga',
            'petugas' => $u->isPetugas(),
            'email' => $u->user_email,
            'hp' => $u->user_hp,
            'kecamatan' => $u->user_kecamatan,
            'foto' => $u->user_foto,
        ];
    }
}
