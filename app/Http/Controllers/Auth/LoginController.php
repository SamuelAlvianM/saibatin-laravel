<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLevel;
use App\Support\StatusAkun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Login portal — port dari `app/api/auth/login/route.ts` (dan sebelumnya
 * LoginController Laravel 9).
 *
 * Yang membedakannya dari login bawaan Laravel:
 *
 * 1. Identitasnya kolom **`user_id`**, bukan `email`. Warga memakai NIK 16 digit,
 *    OPD/petugas memakai username.
 * 2. `Auth::attempt()` TIDAK dipakai. Akun yang sandinya benar tapi statusnya
 *    belum aktif harus mendapat pesan berbeda-beda beserta jalan keluarnya, dan
 *    itu hanya bisa dibedakan bila user diambil lebih dulu.
 * 3. Kode galat (L-00…L-99) dipertahankan apa adanya — petugas dinas terbiasa
 *    menyebut kodenya saat melapor.
 */
class LoginController extends Controller
{

    public function tampilkan(Request $request): InertiaResponse
    {
        return Inertia::render('Auth/Login', [
            // Hanya path internal yang diterima, supaya `?redirect=` tidak bisa
            // dijadikan open redirect ke situs luar.
            'redirect' => $this->tujuanAman($request->query('redirect')),
        ]);
    }

    public function masuk(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ], [
            'user_id.required' => 'NIK/User ID harus diisi',
            'password.required' => 'Password harus diisi',
        ]);

        // Username OPD sering tersalin dengan spasi berlebih dari catatan/WA.
        $identitas = trim($data['user_id']);

        // orderBy id desc: bila `user_id` pernah terduplikat di data warisan,
        // yang dipakai adalah baris terbaru — perilaku ini diwarisi apa adanya.
        $user = User::where('user_id', $identitas)->orderByDesc('id')->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'user_id' => 'Info: NIK/Username belum terdaftar (L-02)',
            ]);
        }

        if ($user->status !== StatusAkun::AKTIF) {
            // Warga (level 3) diarahkan ke Cek Status — di sanalah alasan
            // penolakan terlihat dan tombol "Ajukan Ulang" berada. Akun petugas
            // tidak, karena jalurnya bukan lewat pendaftaran mandiri.
            $warga = $user->userlevel_id === UserLevel::WARGA;

            return back()
                ->withErrors(['user_id' => 'Info: '.StatusAkun::pesanLogin($user->status).' (L-03)'])
                ->with('cekStatus', $warga ? [
                    'status' => $user->status,
                    'nik' => $user->user_id,
                ] : null);
        }

        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Info: Password salah (L-04)',
            ]);
        }

        // Dibaca SEBELUM `login_last` ditimpa di bawah: warga yang baru pertama
        // kali masuk dan belum berfoto diantar melengkapinya. Sifatnya anjuran —
        // halaman tujuan tetap boleh ditinggalkan.
        $lengkapiFoto = $user->userlevel_id === UserLevel::WARGA
            && blank($user->user_foto)
            && $user->login_last === null;

        Auth::login($user, (bool) ($data['remember'] ?? false));
        $request->session()->regenerate();

        $user->forceFill([
            'login_last' => now(),
            'ip_address' => $request->ip(),
        ])->save();

        // Sambutan lewat toast. Nama depan saja — di ponsel, nama lengkap warga
        // (kerap 4–5 kata) membuat toast setinggi tiga baris.
        $sapaan = strtok(trim((string) $user->user_fullname), ' ') ?: 'kembali';

        return redirect()->intended(
            $lengkapiFoto ? '/profil?lengkapi=foto' : $this->tujuanAman($request->input('redirect'))
        )->with('sukses', "Selamat datang, {$sapaan}.");
    }

    public function keluar(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('sukses', 'Anda telah keluar.');
    }

    /** Terima hanya path internal; `//host` ditolak karena itu URL protokol-relatif. */
    private function tujuanAman(?string $tujuan): string
    {
        return is_string($tujuan) && str_starts_with($tujuan, '/') && ! str_starts_with($tujuan, '//')
            ? $tujuan
            : '/dashboard';
    }
}
