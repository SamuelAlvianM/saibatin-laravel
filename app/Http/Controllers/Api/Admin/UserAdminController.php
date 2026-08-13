<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLevel;
use App\Services\CatatanAktivitas;
use App\Services\FotoProfil;
use App\Services\Pemberitahuan;
use App\Services\Surel;
use App\Support\AlasanTolak;
use App\Support\Balasan;
use App\Support\StatusAkun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Manajemen akun — port `app/api/admin/users/**`.
 *
 * Inilah tempat pendaftaran warga diverifikasi: petugas menyandingkan selfie
 * dengan scan KTP, lalu mengaktifkan atau menolak beserta alasannya.
 *
 * ⚠️ EMPAT level, bukan tiga: 1 Super Admin · 2 Operator · 3 Warga ·
 * 4 Operator OPD. Level 2 hanya boleh dibuat Super Admin.
 */
class UserAdminController extends Controller
{
    private const NAMA_LEVEL = [2 => 'Staff', 3 => 'Warga', 4 => 'Operator OPD'];

    public function __construct(
        private readonly CatatanAktivitas $log,
        private readonly Pemberitahuan $notif,
        private readonly Surel $surel,
        private readonly FotoProfil $foto,
    ) {}

    /**
     * Daftar akun. Batas 200 baris dipertahankan dari portal Next.js —
     * penyaringannya (status/kelompok/pencarian) dijalankan di database, jadi
     * batas itu berlaku pada hasil yang sudah tersaring, bukan pada 1.386 akun.
     */
    public function index(Request $request)
    {
        $status = (string) $request->query('status');
        $level = (string) $request->query('level');   // "3" | "4" | "staff"
        $q = trim((string) $request->query('q'));

        $items = User::query()
            ->when(in_array($status, ['0', '1', '2', '3'], true), fn ($w) => $w->where('status', (int) $status))
            ->when($level === 'staff', fn ($w) => $w->whereIn('userlevel_id', [UserLevel::SUPER_ADMIN, UserLevel::OPERATOR]))
            ->when(in_array($level, ['3', '4'], true), fn ($w) => $w->where('userlevel_id', (int) $level))
            ->when(filled($q), fn ($w) => $w->where(fn ($o) => $o
                ->where('user_id', 'like', "%{$q}%")
                ->orWhere('user_fullname', 'like', "%{$q}%")
                ->orWhere('user_email', 'like', "%{$q}%")
                ->orWhere('user_nik', 'like', "%{$q}%")))
            ->with('level:id,nama')
            ->orderByDesc('created_at')
            ->take(200)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'userId' => $u->user_id,
                'userlevelId' => $u->userlevel_id,
                'userFullname' => $u->user_fullname,
                'userNik' => $u->user_nik,
                'userNokk' => $u->user_nokk,
                'userHp' => $u->user_hp,
                'userEmail' => $u->user_email,
                'userKecamatan' => $u->user_kecamatan,
                'userFoto' => $u->user_foto,
                'status' => $u->status,
                'createdAt' => $u->created_at,
                'level' => ['nama' => $u->level->nama ?? '-'],
            ]);

        return Balasan::ok(['items' => $items]);
    }

    /**
     * Detail satu akun untuk panel samping.
     *
     * Dipisah dari daftar supaya tabel tetap ringan: riwayat login, IP, foto KTP
     * dan permohonan terakhir hanya diambil saat barisnya benar-benar dibuka.
     */
    public function show(int $id)
    {
        $u = User::with('level:id,nama')->withCount('permohonan')->find($id);

        if (! $u) {
            return Balasan::gagal(['Akun tidak ditemukan'], 404);
        }

        // Beberapa permohonan terakhir — konteks cepat sebelum petugas
        // memutuskan mengaktifkan atau menolak sebuah akun.
        $terakhir = $u->permohonan()
            ->with('jenis:id,nama')->orderByDesc('id')->take(5)->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'noregister' => $p->no_register,
                'status' => $p->status,
                'createdAt' => $p->created_at,
                'jenisNama' => $p->jenis->nama ?? '-',
            ]);

        // Alasan penolakan diurai jadi {kolom, alasan} supaya panel bisa
        // menyorot bagian data yang ditandai, bukan sekadar menampilkan teksnya.
        $tolak = AlasanTolak::uraikan($u->ket);

        return Balasan::ok([
            'id' => $u->id,
            'userId' => $u->user_id,
            'userlevelId' => $u->userlevel_id,
            'userFullname' => $u->user_fullname,
            'userNik' => $u->user_nik,
            'userNokk' => $u->user_nokk,
            'userHp' => $u->user_hp,
            'userEmail' => $u->user_email,
            'userKecamatan' => $u->user_kecamatan,
            'userFoto' => $u->user_foto,
            'userKtp' => $u->user_ktp,
            'status' => $u->status,
            'ket' => $u->ket,
            'ketKolom' => $tolak['kolom'],
            'ketAlasan' => $tolak['alasan'],
            'ipAddress' => $u->ip_address,
            'loginLast' => $u->login_last,
            'activationTime' => $u->activation_time,
            'createdAt' => $u->created_at,
            'updatedAt' => $u->updated_at,
            'level' => ['nama' => $u->level->nama ?? '-'],
            'jumlahPermohonan' => $u->permohonan_count,
            'permohonanTerakhir' => $terakhir,
        ]);
    }

    /**
     * Buat akun baru (level 3 Warga / 4 Operator OPD; level 2 khusus Super Admin).
     * Akun langsung AKTIF karena dibuat petugas.
     */
    public function store(Request $request)
    {
        $petugas = $request->user();

        $nama = trim((string) $request->input('nama'));
        $userId = trim((string) $request->input('userId'));
        $password = (string) $request->input('password');
        $level = (int) $request->input('level');
        $nik = trim((string) $request->input('nik'));
        $kk = trim((string) $request->input('kk'));
        $kecamatan = trim((string) $request->input('kecamatan'));

        if ($nama === '' || $userId === '' || $password === '') {
            return Balasan::gagal(['Info: Nama, NIK/Username, dan password wajib diisi']);
        }
        if (! in_array($level, [2, 3, 4], true)) {
            return Balasan::gagal(['Info: Level akun tidak valid']);
        }
        if ($level === UserLevel::OPERATOR && ! $petugas->isSuperAdmin()) {
            return Balasan::gagal(['Info: Hanya Super Admin yang dapat membuat akun Staff'], 403);
        }
        if ($level === UserLevel::WARGA) {
            if (! preg_match('/^\d{16}$/', $userId)) {
                return Balasan::gagal(['Info: NIK warga harus 16 digit angka']);
            }
            if ($kk !== '' && ! preg_match('/^\d{16}$/', $kk)) {
                return Balasan::gagal(['Info: Nomor Kartu Keluarga harus 16 digit angka']);
            }
            // Sama seperti pendaftaran mandiri: kecamatan menentukan wilayah layanan.
            if ($kecamatan === '') {
                return Balasan::gagal(['Info: Kecamatan domisili wajib dipilih untuk akun warga']);
            }
        }
        // OPD login memakai USERNAME instansi (mis. rs.saibatin); NIK perwakilan
        // disimpan terpisah untuk fitur lupa password.
        if ($level === UserLevel::OPERATOR_OPD) {
            if (! preg_match('/^[a-z0-9][a-z0-9._-]{3,29}$/i', $userId)) {
                return Balasan::gagal(['Info: Username OPD 4-30 karakter (huruf/angka/titik/underscore/strip)']);
            }
            if (! preg_match('/^\d{16}$/', $nik)) {
                return Balasan::gagal(['Info: NIK perwakilan OPD harus 16 digit angka']);
            }
        }
        if ($level === UserLevel::OPERATOR && mb_strlen($userId) < 4) {
            return Balasan::gagal(['Info: Username minimal 4 karakter']);
        }
        if (mb_strlen($password) < 6) {
            return Balasan::gagal(['Info: Password minimal 6 karakter']);
        }

        // Yang dilarang adalah bentrok dengan akun AKTIF. Baris lama yang
        // ditolak/nonaktif dibiarkan — itu arsip pendaftaran, bukan penghalang.
        if (User::where('user_id', $userId)->where('status', StatusAkun::AKTIF)->exists()) {
            return Balasan::gagal(['Info: NIK/Username sudah terdaftar dan aktif']);
        }

        // Pastikan level Operator OPD ada (DB lama mungkin belum punya barisnya).
        // `forceFill` karena `id` sengaja tidak fillable — di sini nilainya
        // memang harus dipaksa 4, bukan diserahkan ke auto-increment.
        if (! UserLevel::whereKey(UserLevel::OPERATOR_OPD)->exists()) {
            (new UserLevel)->forceFill(['id' => UserLevel::OPERATOR_OPD, 'nama' => 'Operator OPD'])->save();
        }

        $user = User::create([
            'user_id' => $userId,
            'password' => Hash::make($password),
            'userlevel_id' => $level,
            'user_fullname' => $nama,
            // NIK untuk fitur lupa password: warga = NIK login-nya sendiri,
            // OPD = NIK perwakilan instansi (field terpisah dari username).
            'user_nik' => $level === UserLevel::OPERATOR_OPD
                ? $nik
                : (preg_match('/^\d{16}$/', $userId) ? $userId : null),
            'user_nokk' => $kk ?: null,
            'user_hp' => trim((string) $request->input('hp')) ?: null,
            'user_email' => trim((string) $request->input('email')) ?: null,
            'user_kecamatan' => $level === UserLevel::WARGA ? ($kecamatan ?: null) : null,
            'status' => StatusAkun::AKTIF,   // dibuat petugas = langsung aktif
            'activation_time' => now(),
            'ip_address' => $request->ip(),
            'created_by' => $petugas->id,
        ]);

        // Foto bersifat opsional di sini: warga belum tentu hadir saat petugas
        // membuatkan akunnya. Nama berkasnya diawali id pemilik — dasar kontrol
        // aksesnya di BerkasController.
        $perubahan = array_filter([
            'user_foto' => $this->foto->simpan($request->input('foto'), $user->id),
            'user_ktp' => $this->foto->simpanKtp($request->input('ktp'), $user->id),
        ]);
        if ($perubahan !== []) {
            $user->forceFill($perubahan)->save();
        }

        $this->surel->kirim(
            $user->user_email,
            'Akun SAIBATIN Anda Telah Disetujui',
            'emails.akun-disetujui',
            ['nama' => $user->user_fullname ?: $user->user_id],
        );

        $this->log->catat(
            $petugas, 'BUAT', 'Akun',
            'Membuat akun '.($user->user_fullname ?: $user->user_id)
                .' ('.(self::NAMA_LEVEL[$level] ?? "level {$level}").')',
            $user->id, $request,
        );

        return Balasan::ok(['id' => $user->id], ['Info: Akun berhasil dibuat dan langsung aktif']);
    }

    /**
     * Ubah status akun: aktivasi, penolakan (+alasan), nonaktifkan.
     *
     * Penolakan WAJIB disertai alasan, dan bagian data yang bermasalah ditandai
     * lewat `kolom[]` — supaya warga tahu apa yang harus diperbaiki saat
     * mengajukan ulang, bukan cuma bahwa pendaftarannya gagal.
     */
    public function ubahStatus(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');

        if (! is_numeric($id) || ! in_array((int) $status, [0, 1, 2, 3], true)) {
            return Balasan::gagal(['Info: Parameter id/status tidak valid']);
        }

        $status = (int) $status;
        $alasan = trim((string) $request->input('alasan'));

        if ($status === StatusAkun::DITOLAK && $alasan === '') {
            return Balasan::gagal(['Info: Alasan penolakan wajib diisi']);
        }

        $user = User::find((int) $id);
        if (! $user) {
            return Balasan::gagal(['Info: Akun tidak ditemukan'], 404);
        }

        // Penolakan → `ket` menggabungkan daftar kolom bermasalah + alasan jadi
        // satu teks terbaca manusia. Status lain menyimpan alasan apa adanya.
        $kolom = $request->input('kolom');
        $ket = $status === StatusAkun::DITOLAK
            ? AlasanTolak::susun(is_array($kolom) ? $kolom : [], $alasan)
            : ($alasan ?: null);

        $sebelum = $user->status;

        $user->status = $status;
        $user->updated_by = $request->user()->id;
        if ($status === StatusAkun::AKTIF) {
            $user->activation_time = now();
        }
        if (filled($ket)) {
            $user->ket = $ket;
        }
        $user->save();

        // Surel hanya saat status BERUBAH: disetujui atau ditolak (+alasan).
        // Menunggu & nonaktif tidak disurati — warga melihatnya saat login/cek status.
        if ($sebelum !== $status) {
            $nama = $user->user_fullname ?: $user->user_id;

            if ($status === StatusAkun::AKTIF) {
                $this->surel->kirim($user->user_email, 'Akun SAIBATIN Anda Telah Disetujui',
                    'emails.akun-disetujui', ['nama' => $nama]);
            } elseif ($status === StatusAkun::DITOLAK) {
                $this->surel->kirim($user->user_email, 'Pendaftaran Akun SAIBATIN Ditolak',
                    'emails.akun-ditolak', ['nama' => $nama, 'alasan' => $ket]);
            }
        }

        // Notifikasi in-app saat DIAKTIFKAN — terlihat begitu ia login pertama
        // kali. (Nonaktif tidak dinotifkan: pemiliknya memang tak bisa masuk.)
        if ($status === StatusAkun::AKTIF && $sebelum !== StatusAkun::AKTIF) {
            $this->notif->aman(fn () => $this->notif->buat(
                userId: $user->id,
                tipe: 'AKUN_STATUS',
                judul: 'Akun Anda telah diaktifkan',
                isi: 'Selamat datang! Akun Anda sudah aktif dan siap digunakan untuk mengajukan permohonan online.',
                link: '/user/pengajuan',
                refType: 'User',
                refId: $user->id,
            ));
        }

        $aksi = [
            StatusAkun::AKTIF => 'Mengaktifkan',
            StatusAkun::DITOLAK => 'Menolak',
            StatusAkun::NONAKTIF => 'Menonaktifkan',
            StatusAkun::MENUNGGU => 'Mengembalikan ke menunggu',
        ][$status] ?? 'Mengubah status';

        $this->log->catat(
            $request->user(), 'UBAH', 'Akun',
            "{$aksi} akun ".($user->user_fullname ?: $user->user_id),
            $user->id, $request,
        );

        $pesan = [
            StatusAkun::AKTIF => 'Info: Akun berhasil diaktifkan',
            StatusAkun::DITOLAK => 'Info: Akun ditolak, alasan dikirim ke pemohon',
            StatusAkun::NONAKTIF => 'Info: Akun dinonaktifkan',
            StatusAkun::MENUNGGU => 'Info: Akun dikembalikan ke status menunggu',
        ][$status] ?? 'Info: Status akun diperbarui';

        return Balasan::ok(null, [$pesan]);
    }

    /**
     * Hapus akun PERMANEN — hanya Super Admin.
     *
     * 🔴 Akun yang pernah dipakai tidak boleh dihapus: permohonan dan tiket
     * adalah arsip pelayanan yang harus tetap bisa ditelusuri pemiliknya (dan
     * relasinya pun restrict di DB, jadi penghapusan akan gagal di tengah jalan).
     * Untuk kasus itu petugas diarahkan MENONAKTIFKAN — efeknya sama bagi
     * pengguna, arsipnya utuh.
     */
    public function destroy(Request $request, int $id)
    {
        $petugas = $request->user();

        if (! $petugas->isSuperAdmin()) {
            return Balasan::gagal(['Info: Hanya Super Admin yang dapat menghapus akun'], 403);
        }
        if ($id === $petugas->id) {
            return Balasan::gagal(['Info: Anda tidak dapat menghapus akun Anda sendiri']);
        }

        $user = User::withCount(['permohonan', 'tiket', 'tiketPesan'])->find($id);
        if (! $user) {
            return Balasan::gagal(['Info: Akun tidak ditemukan'], 404);
        }

        $jejak = $user->permohonan_count + $user->tiket_count + $user->tiket_pesan_count;
        if ($jejak > 0) {
            return Balasan::gagal([
                "Info: Akun ini punya {$user->permohonan_count} permohonan dan tidak bisa dihapus. "
                    .'Nonaktifkan saja agar riwayat pelayanan tetap tersimpan.',
            ]);
        }

        $nama = $user->user_fullname ?: $user->user_id;
        $foto = $user->user_foto;
        $ktp = $user->user_ktp;

        // Notifikasi & log milik akun ini ikut terhapus lewat cascade di skema.
        $user->delete();

        // Scan KTP ikut dibuang bersama akunnya — kalau tidak, KTP warga
        // tertinggal di storage tanpa pemilik.
        $this->foto->hapus($foto);
        $this->foto->hapusKtp($ktp);

        $this->log->catat($petugas, 'HAPUS', 'Akun', "Menghapus akun {$nama} ({$user->user_id})", $id, $request);

        return Balasan::ok(null, ["Info: Akun {$nama} telah dihapus permanen"]);
    }
}
