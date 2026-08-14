<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FotoProfil;
use App\Support\Balasan;
use App\Support\StatusAkun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** Biodata, sandi, dan foto wajah milik pengguna yang sedang login. */
class ProfilController extends Controller
{
    public function __construct(private readonly FotoProfil $foto) {}

    public function tampilkan(Request $request)
    {
        $u = $request->user();

        return Balasan::ok(['user' => [
            'id' => $u->id,
            'userId' => $u->user_id,
            'userFullname' => $u->user_fullname,
            'userNik' => $u->user_nik,
            'userNokk' => $u->user_nokk,
            'userHp' => $u->user_hp,
            'userEmail' => $u->user_email,
            'userKecamatan' => $u->user_kecamatan,
            'userFoto' => $u->user_foto,
            'ket' => $u->ket,
        ]]);
    }

    public function perbarui(Request $request)
    {
        $u = $request->user();

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:191'],
            'hp' => ['nullable', 'regex:/^[0-9+]{8,15}$/'],
            'email' => ['nullable', 'email', 'max:191'],
            'alamat' => ['nullable', 'string'],
        ], [
            'nama.required' => 'Info: Nama wajib diisi',
            'hp.regex' => 'Info: Nomor HP tidak valid (8-15 digit)',
            'email.email' => 'Info: Format email tidak valid',
        ]);

        if (filled($data['email'] ?? null)) {
            $dipakai = User::where('user_email', $data['email'])
                ->where('status', StatusAkun::AKTIF)
                ->where('id', '!=', $u->id)->exists();

            if ($dipakai) {
                return Balasan::gagal(['Info: Email sudah dipakai akun lain']);
            }
        }

        $u->forceFill([
            'user_fullname' => trim($data['nama']),
            'user_hp' => $data['hp'] ?? null,
            'user_email' => $data['email'] ?? null,
            // `ket` dipakai bersama fitur lain (alasan penolakan). Hanya ditimpa
            // bila alamat memang dikirim — jangan dikosongkan diam-diam.
            ...(array_key_exists('alamat', $data) ? ['ket' => $data['alamat']] : []),
            'updated_by' => $u->id,
        ])->save();

        return Balasan::ok(['user' => $u->only([
            'id', 'user_id', 'user_fullname', 'user_hp', 'user_email', 'ket',
        ])], ['Info: Profil berhasil diperbarui']);
    }

    public function gantiSandi(Request $request)
    {
        $u = $request->user();

        // 🔴 Nama field mengikuti kontrak portal Next.js
        // (`app/api/profil/change-password/route.ts`): passwordLama /
        // passwordBaru / konfirmasi. Versi pertama di sini memakai
        // `lama`/`baru`/`baru2` — nama karangan sendiri yang tidak cocok
        // dengan klien mana pun, dan endpointnya memang belum pernah dipanggil
        // sampai halaman /profil dibuat. Nama lama tetap diterima sebagai
        // cadangan supaya tidak ada yang patah diam-diam.
        $request->merge(array_filter([
            'passwordLama' => $request->input('passwordLama') ?? $request->input('lama'),
            'passwordBaru' => $request->input('passwordBaru') ?? $request->input('baru'),
            'konfirmasi' => $request->input('konfirmasi') ?? $request->input('baru2'),
        ], fn ($v) => $v !== null));

        $data = $request->validate([
            'passwordLama' => ['required', 'string'],
            'passwordBaru' => ['required', 'string', 'min:6'],
            'konfirmasi' => ['required', 'string'],
        ], [
            'passwordLama.required' => 'Info: Semua field wajib diisi',
            'passwordBaru.required' => 'Info: Semua field wajib diisi',
            'konfirmasi.required' => 'Info: Semua field wajib diisi',
            'passwordBaru.min' => 'Info: Password baru minimal 6 karakter',
        ]);

        // Sandi lama diperiksa lagi meski sesi sudah sah — mencegah orang yang
        // menemukan perangkat tak terkunci mengambil alih akunnya.
        if (! Hash::check($data['passwordLama'], $u->password)) {
            return Balasan::gagal(['Info: Password lama salah']);
        }
        if (preg_match('/^\d+$/', $data['passwordBaru'])) {
            return Balasan::gagal(['Info: Password baru tidak boleh angka semua']);
        }
        if ($data['passwordBaru'] !== $data['konfirmasi']) {
            return Balasan::gagal(['Info: Konfirmasi password tidak sama']);
        }
        // Ada di portal lama dan sempat hilang di port ini: mengganti sandi
        // dengan sandi yang sama persis bukan penggantian.
        if (Hash::check($data['passwordBaru'], $u->password)) {
            return Balasan::gagal(['Info: Password baru tidak boleh sama dengan password lama']);
        }

        $u->forceFill(['password' => Hash::make($data['passwordBaru'])])->save();

        return Balasan::ok(null, ['Info: Password berhasil diubah']);
    }

    /** Simpan/ganti foto wajah. Foto lama dihapus supaya tidak menumpuk. */
    public function simpanFoto(Request $request)
    {
        $u = $request->user();
        $dataUrl = (string) $request->input('foto');

        if (! $this->foto->adalahDataUrlGambar($dataUrl)) {
            return Balasan::gagal(['Info: Foto tidak valid']);
        }

        $lama = $u->user_foto;
        $url = $this->foto->simpan($dataUrl, $u->id);

        if (! $url) {
            return Balasan::gagal(['Info: Gagal menyimpan foto'], 500);
        }

        $u->forceFill(['user_foto' => $url])->save();
        $this->foto->hapus($lama);

        return Balasan::ok(['url' => $url], ['Info: Foto profil diperbarui']);
    }

    public function hapusFoto(Request $request)
    {
        $u = $request->user();

        $this->foto->hapus($u->user_foto);
        $u->forceFill(['user_foto' => null])->save();

        return Balasan::ok(null, ['Info: Foto profil dihapus']);
    }
}
