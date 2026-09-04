<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StatusAkun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Lupa & reset sandi.
 *
 * Memakai kolom `forgotten_code` + `forgotten_time` di tabel `users` — BUKAN
 * tabel `password_reset_tokens` bawaan Laravel. Alasannya bukan selera:
 * mekanisme bawaan berkunci pada alamat email, sedangkan portal ini berkunci
 * pada NIK, dan satu email bisa dipakai beberapa NIK dalam data warisan.
 */
class SandiController extends Controller
{
    /** Tautan reset berlaku 1 jam. */
    private const UMUR_KODE_DETIK = 3600;


    public function formLupa()
    {
        return Inertia::render('Auth/LupaSandi');
    }

    public function kirimTautan(Request $request)
    {
        $data = $request->validate([
            'nik' => ['required', 'string'],
        ], ['nik.required' => 'Info: NIK wajib diisi']);

        $user = User::where(fn ($q) => $q->where('user_id', $data['nik'])->orWhere('user_nik', $data['nik']))
            ->where('status', StatusAkun::AKTIF)
            ->orderByDesc('id')->first();

        if ($user) {
            $kode = Str::lower(Str::random(8)).base_convert((string) now()->getTimestamp(), 10, 36);

            $user->forceFill([
                'forgotten_code' => $kode,
                'forgotten_time' => now(),
            ])->save();

            if (filled($user->user_email)) {
                try {
                    Mail::send('emails.reset-sandi', [
                        'nama' => $user->user_fullname ?? $user->user_id,
                        'tautan' => url('/reset-password?key='.$kode),
                    ], function ($m) use ($user) {
                        $m->to($user->user_email)->subject('Reset Password — SAIBATIN');
                    });
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        // 🔴 Balasannya SELALU sama, ada atau tidak ada akunnya. Kalau dibedakan,
        // halaman ini berubah jadi alat memastikan NIK mana yang punya akun aktif.
        return back()->with('sukses',
            'Info: Jika NIK terdaftar, instruksi reset password telah dikirim ke kontak terdaftar.');
    }

    public function formReset(Request $request)
    {
        return Inertia::render('Auth/ResetSandi', [
            // Dinamai `kunci`, BUKAN `key`: React memperlakukan prop bernama
            // `key` sebagai penanda internal dan tidak meneruskannya ke komponen —
            // halamannya akan selalu mengira tautannya tidak memuat kode reset.
            'kunci' => (string) $request->query('key'),
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
            'pass1' => ['required', 'string', 'min:6'],
            'pass2' => ['required', 'string'],
        ], [
            'key.required' => 'Info: Kode reset tidak valid',
            'pass1.required' => 'Info: Password wajib diisi',
            'pass1.min' => 'Info: Password minimal 6 karakter',
        ]);

        if (preg_match('/^\d+$/', $data['pass1'])) {
            throw ValidationException::withMessages(['pass1' => 'Info: Password tidak boleh angka semua']);
        }
        if ($data['pass1'] !== $data['pass2']) {
            throw ValidationException::withMessages(['pass2' => 'Info: Konfirmasi password tidak sama']);
        }

        $user = User::where('forgotten_code', $data['key'])->first();

        if (! $user || ! $user->forgotten_time) {
            throw ValidationException::withMessages(['key' => 'Info: Kode reset tidak ditemukan atau sudah dipakai']);
        }
        if ($user->forgotten_time->diffInSeconds(now()) > self::UMUR_KODE_DETIK) {
            throw ValidationException::withMessages(['key' => 'Info: Kode reset sudah kedaluwarsa, silakan ajukan ulang']);
        }

        $user->forceFill([
            'password' => Hash::make($data['pass1']),
            'forgotten_code' => null,
            'forgotten_time' => null,
        ])->save();

        return redirect('/login')->with('sukses', 'Info: Password berhasil direset, silakan login');
    }
}
