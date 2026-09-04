<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLevel;
use App\Models\Wilayah;
use App\Services\FotoProfil;
use App\Services\Otp;
use App\Services\Pemberitahuan;
use App\Support\AlasanTolak;
use App\Support\StatusAkun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Pendaftaran akun warga — port dari `app/api/auth/register/route.ts`
 * (dan sebelumnya RegisterController@postDatas Laravel 9).
 *
 * Aturan lama dipertahankan apa adanya: NIK 16 digit, sandi ≥ 6 karakter dan
 * tidak boleh angka semua, konfirmasi harus sama, kode galat N-xx.
 *
 * 🔴 Satu hal yang SENGAJA TIDAK dibawa dari Laravel 9: kolom `passwordnote`
 * yang menyimpan sandi dalam bentuk PLAINTEKS. Portal Next.js sudah membuangnya
 * dan itu tidak boleh dihidupkan lagi.
 */
class RegisterController extends Controller
{
    public function __construct(
        private readonly Otp $otp,
        private readonly FotoProfil $foto,
        private readonly Pemberitahuan $notif,
    ) {}

    public function tampilkan(Request $request)
    {
        // Warga yang pendaftarannya DITOLAK datang ke sini dari Cek Status
        // membawa NIK-nya, supaya cukup memperbaiki bagian yang ditandai petugas
        // alih-alih mengetik ulang seluruh formulir.
        //
        // 🔴 Data lama hanya dikeluarkan untuk akun berstatus DITOLAK. Halaman
        // ini terbuka tanpa login: kalau status lain ikut dilayani, siapa pun
        // yang tahu NIK seseorang bisa memanen nama, KK, HP, dan emailnya.
        $prefill = null;
        $perbaiki = [];

        $nik = preg_replace('/\D/', '', (string) $request->query('nik'));
        if (preg_match('/^\d{16}$/', $nik)) {
            $lama = User::where('user_id', $nik)
                ->where('userlevel_id', UserLevel::WARGA)
                ->where('status', StatusAkun::DITOLAK)
                ->orderByDesc('id')->first();

            if ($lama) {
                $prefill = [
                    'nama' => $lama->user_fullname ?? '',
                    'kk' => $lama->user_nokk ?? '',
                    'hp' => $lama->user_hp ?? '',
                    'email' => $lama->user_email ?? '',
                    'kecamatan' => $lama->user_kecamatan ?? '',
                    'nik' => $nik,
                ];
                $perbaiki = AlasanTolak::uraikan($lama->ket)['kolom'];
            }
        }

        return Inertia::render('Auth/Register', [
            'kecamatan' => Wilayah::where('jenis', Wilayah::KECAMATAN)
                ->orderBy('nama')->pluck('nama'),
            'otpWajib' => $this->otp->wajib(),
            'otpKanal' => $this->otp->kanal() ?? 'email',
            'prefill' => $prefill,
            'perbaiki' => $perbaiki,
        ]);
    }

    public function daftar(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:191'],
            'nik' => ['required', 'digits:16'],
            'kk' => ['required', 'digits:16'],
            'hp' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:191'],
            'kecamatan' => ['required', 'string', 'max:191'],
            'pass' => ['required', 'string', 'min:6'],
            'pass2' => ['required', 'string'],
            'foto' => ['required', 'string'],
            'ktp' => ['required', 'string'],
            'otpBukti' => ['nullable', 'string'],
        ], [
            'nik.digits' => 'Info: NIK Harus 16 Digit (N-15)',
            'kk.digits' => 'Info: Nomor KK harus 16 digit (N-15)',
            'kecamatan.required' => 'Info: Kecamatan domisili wajib dipilih (N-17)',
            'foto.required' => 'Info: Foto wajah/selfie wajib dilampirkan (N-18)',
            // Wajib, mengikuti foto selfie — keduanya baru berguna kalau
            // disandingkan petugas saat verifikasi. Bila dinas ingin opsional,
            // cukup hapus aturan `required` di atas.
            'ktp.required' => 'Info: Foto KTP wajib diunggah (N-19)',
            'pass.min' => 'Info: Password Minimal 6 Karakter (N-08)',
        ]);

        if (preg_match('/^\d+$/', $data['pass'])) {
            throw ValidationException::withMessages(['pass' => 'Info: Password Tidak Boleh Angka Semua (N-07)']);
        }
        if ($data['pass'] !== $data['pass2']) {
            throw ValidationException::withMessages(['pass2' => 'Info: Password Konfirmasi Tidak Sama (N-09)']);
        }

        // Identitas (email/WA sesuai kanal aktif) wajib lolos OTP — kecuali
        // layanan OTP memang belum dikonfigurasi di production.
        if ($this->otp->wajib()) {
            $kanal = $this->otp->kanal() ?? 'email';
            $identitas = $kanal === 'email'
                ? $this->otp->normalisasiEmail($data['email'])
                : $this->otp->normalisasiHp($data['hp']);

            if (! $identitas || ! $this->otp->cekBukti($identitas, $data['otpBukti'] ?? null)) {
                throw ValidationException::withMessages([
                    'otpBukti' => $kanal === 'email'
                        ? 'Info: Alamat email belum diverifikasi OTP (N-16)'
                        : 'Info: Nomor WhatsApp belum diverifikasi OTP (N-16)',
                ]);
            }
        }

        // NIK yang sudah pernah didaftarkan — perlakuannya bergantung status:
        //   AKTIF    → sudah punya akun, arahkan login
        //   MENUNGGU → sedang diverifikasi, jangan buat baris duplikat
        //   NONAKTIF → diblokir petugas, arahkan lewat Cek Status
        //   DITOLAK  → BOLEH daftar ulang: perbarui baris yang sama, kembali MENUNGGU
        $akunLama = User::where('user_id', $data['nik'])
            ->where('userlevel_id', UserLevel::WARGA)
            ->orderByDesc('id')->first();

        if ($akunLama) {
            if ($akunLama->status === StatusAkun::AKTIF) {
                throw ValidationException::withMessages(['nik' => 'Info: NIK sudah terdaftar dan aktif. Silahkan Login (N-03)']);
            }
            if ($akunLama->status !== StatusAkun::DITOLAK) {
                return redirect('/cek-status?nik='.$data['nik'])->with(
                    'sukses',
                    'Info: NIK ini sudah pernah didaftarkan. Kami arahkan ke halaman status pendaftaran.'
                );
            }
        }

        $emailTerpakai = User::where('user_email', $data['email'])
            ->where('status', StatusAkun::AKTIF)
            ->when($akunLama, fn ($q) => $q->where('id', '!=', $akunLama->id))
            ->exists();

        if ($emailTerpakai) {
            throw ValidationException::withMessages([
                'email' => 'Info: Alamat Email sudah terdaftar, Gunakan Email yg Berbeda atau Silahkan Login (N-15)',
            ]);
        }

        $isiAkun = [
            'user_id' => $data['nik'],
            'password' => Hash::make($data['pass']),
            'userlevel_id' => UserLevel::WARGA,
            'user_fullname' => $data['nama'],
            'user_nik' => $data['nik'],
            'user_nokk' => $data['kk'],
            'user_hp' => $data['hp'],
            'user_email' => $data['email'],
            'user_kecamatan' => trim($data['kecamatan']),
            'activation_code' => (string) random_int(1000, 9999),
            'activation_code_url' => Hash::make($data['nik'].now()->getTimestampMs()),
            'ip_address' => $request->ip(),
            'status' => StatusAkun::MENUNGGU,
        ];

        if ($akunLama) {
            // Daftar ulang setelah ditolak: bersihkan sisa penolakan.
            $akunLama->fill($isiAkun + ['ket' => null, 'activation_time' => null, 'updated_by' => UserLevel::WARGA])->save();
            $user = $akunLama;
        } else {
            $user = User::create($isiAkun + ['created_by' => UserLevel::WARGA]);
        }

        // Foto baru bisa disimpan setelah akun punya id — nama berkasnya diawali
        // id pemilik, dan itulah dasar kontrol aksesnya.
        $urlFoto = $this->foto->simpan($data['foto'], $user->id);
        $urlKtp = $this->foto->simpanKtp($data['ktp'], $user->id);

        if ($urlFoto || $urlKtp) {
            $user->forceFill(array_filter([
                'user_foto' => $urlFoto,
                'user_ktp' => $urlKtp,
            ]))->save();
        }

        // Surel konfirmasi — kegagalan mengirim tidak boleh menggagalkan pendaftaran.
        try {
            Mail::send('emails.registrasi-diterima', ['nama' => $data['nama']], function ($m) use ($data) {
                $m->to($data['email'])->subject('Pendaftaran Diterima — SAIBATIN');
            });
        } catch (\Throwable $e) {
            report($e);
        }

        $this->notif->aman(fn () => $this->notif->kePetugas(
            tipe: 'AKUN_BARU',
            judul: $akunLama ? 'Pengajuan ulang pendaftaran' : 'Pendaftaran akun baru',
            isi: $akunLama
                ? "{$data['nama']} (NIK {$data['nik']}) memperbaiki data & mengajukan ulang pendaftaran — mohon ditinjau kembali."
                : "{$data['nama']} (NIK {$data['nik']}) mendaftar dan menunggu aktivasi akun.",
            link: '/dashboard/users',
            refType: 'User',
            refId: $user->id,
        ));

        return redirect('/cek-status?nik='.$data['nik'])->with('sukses', $akunLama
            ? 'Info: Pendaftaran ulang terkirim. Data Anda diperbarui dan kembali menunggu verifikasi petugas.'
            : 'Info: Permohonan akun Anda sedang diproses dan menunggu verifikasi/aktivasi');
    }
}
