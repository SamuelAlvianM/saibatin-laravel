<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLevel;
use App\Services\Pemberitahuan;
use App\Support\AlasanTolak;
use App\Support\Balasan;
use App\Support\StatusAkun;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Cek status pendaftaran (tanpa login) + Ajukan Ulang.
 *
 * Inilah penutup loop 4-status: warga yang ditolak melihat ALASANNYA di sini,
 * lalu memperbaiki datanya dan mengajukan ulang. Tanpa halaman ini, status
 * DITOLAK jadi jalan buntu.
 */
class CekStatusController extends Controller
{
    public function __construct(private readonly Pemberitahuan $notif) {}

    public function tampilkan(Request $request)
    {
        return Inertia::render('Auth/CekStatus', [
            'nikAwal' => preg_replace('/\D/', '', (string) $request->query('nik')),
        ]);
    }

    /** Dipanggil halaman Cek Status lewat fetch — balasannya kontrak lama. */
    public function periksa(Request $request)
    {
        $nik = preg_replace('/\D/', '', (string) $request->input('nik'));

        if (! preg_match('/^\d{16}$/', $nik)) {
            return Balasan::gagal(['Info: NIK harus 16 digit angka']);
        }

        $user = User::where('user_id', $nik)
            ->where('userlevel_id', UserLevel::WARGA)
            ->orderByDesc('id')->first();

        if (! $user) {
            return Balasan::ok(['ada' => false]);
        }

        $info = StatusAkun::info($user->status);
        $ditolak = $user->status === StatusAkun::DITOLAK;
        $tolak = $ditolak ? AlasanTolak::uraikan($user->ket) : null;

        return Balasan::ok([
            'ada' => true,
            'nama' => $user->user_fullname,
            'status' => $user->status,
            'label' => $info['label'],
            'pesan' => $info['pesan'],
            // $tolak hanya terisi saat DITOLAK; jangan diakses sebagai array
            // begitu saja — status lain membuatnya null.
            'alasan' => $tolak ? ($tolak['alasan'] ?: null) : null,
            // `kolom` = kunci (dipakai form pendaftaran untuk menyorot field),
            // `kolomLabel` = teks siap tampil. Keduanya dikirim supaya kamus
            // labelnya tetap satu di PHP, tidak disalin lagi ke sisi JS.
            'kolom' => $tolak ? $tolak['kolom'] : [],
            'kolomLabel' => $tolak ? AlasanTolak::label($tolak['kolom']) : [],
            // 🔴 Data pendaftaran hanya dikirim saat DITOLAK — satu-satunya status
            // yang boleh daftar ulang. Untuk status lain, data pribadi TIDAK
            // diekspos: halaman ini terbuka tanpa login, jadi siapa pun yang tahu
            // NIK bisa memanggilnya.
            'prefill' => $ditolak ? [
                'nama' => $user->user_fullname ?? '',
                'kk' => $user->user_nokk ?? '',
                'hp' => $user->user_hp ?? '',
                'email' => $user->user_email ?? '',
                'kecamatan' => $user->user_kecamatan ?? '',
            ] : null,
            'terdaftar' => $user->created_at,
            'diperbarui' => $user->updated_at,
        ]);
    }

    /** Status DITOLAK (2) → MENUNGGU (0), lalu petugas dinotifikasi. */
    public function ajukanUlang(Request $request)
    {
        $nik = preg_replace('/\D/', '', (string) $request->input('nik'));

        if (! preg_match('/^\d{16}$/', $nik)) {
            return Balasan::gagal(['Info: NIK harus 16 digit angka']);
        }

        $user = User::where('user_id', $nik)
            ->where('userlevel_id', UserLevel::WARGA)
            ->orderByDesc('id')->first();

        if (! $user) {
            return Balasan::gagal(['Info: NIK tidak ditemukan']);
        }
        if ($user->status !== StatusAkun::DITOLAK) {
            return Balasan::gagal(['Info: Pengajuan ulang hanya untuk pendaftaran yang ditolak']);
        }

        $user->forceFill(['status' => StatusAkun::MENUNGGU])->save();

        $this->notif->aman(fn () => $this->notif->kePetugas(
            tipe: 'AKUN_BARU',
            judul: 'Pengajuan ulang pendaftaran',
            isi: ($user->user_fullname ?? $user->user_id)." (NIK {$nik}) mengajukan ulang pendaftaran setelah ditolak — mohon ditinjau kembali.",
            link: '/dashboard/users',
            refType: 'User',
            refId: $user->id,
        ));

        return Balasan::ok(null, ['Info: Pengajuan ulang terkirim. Akun Anda kembali menunggu verifikasi petugas.']);
    }
}
