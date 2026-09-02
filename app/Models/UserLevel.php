<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Level/peran pengguna.
 *
 * Produksi berisi EMPAT baris — komentar lama yang menyebut "1=superadmin,
 * 2=operator, 3=warga" tidak lengkap:
 *   1 Super Admin · 2 Operator · 3 Warga · 4 Operator OPD (140 akun)
 */
class UserLevel extends Model
{
    use PresisiMilidetik;

    public const SUPER_ADMIN = 1;
    public const OPERATOR = 2;
    public const WARGA = 3;
    public const OPERATOR_OPD = 4;

    /**
     * Petugas — yang boleh melihat & memproses data orang lain.
     *
     * ⚠️ Cerminan `PastikanPeran` alias `petugas` dan `User::isPetugas()`.
     * Operator OPD SENGAJA tidak di sini meski memakai kerangka dashboard yang
     * sama: ia mengajukan, bukan memproses.
     */
    public const PETUGAS = [
        self::SUPER_ADMIN,
        self::OPERATOR,
    ];

    /**
     * Level yang identitas login-nya berupa NIK, bukan username.
     *
     * 🔴 `MASUK_NIK` dan `PETUGAS` saling meniadakan — satu level di kedua
     * daftar membuat akunnya mustahil dibuat: `user_id` yang sama dituntut 16
     * digit angka sekaligus berhuruf.
     */
    public const MASUK_NIK = [
        self::WARGA,
    ];

    /** Nama tampilan tiap level. */
    public const NAMA = [
        self::SUPER_ADMIN => 'Super Admin',
        self::OPERATOR => 'Operator',
        self::WARGA => 'Warga',
        self::OPERATOR_OPD => 'Operator OPD',
    ];

    /**
     * Level yang WAJIB menyebut wilayah (kecamatan, dan untuk OPD juga desa).
     *
     * 🔴 Wilayah sebuah permohonan dibaca dari AKUN pengajunya — tidak ada
     * tempat lain yang mencatatnya per baris (`t_permohonan` tidak punya kolom
     * wilayah, dan payload-nya tidak memuat kecamatan). Jadi kolom ini bukan
     * pelengkap biodata: ia satu-satunya yang membuat saringan wilayah dan
     * rekap per kecamatan bisa berarti apa pun.
     *
     * ⚠️ Sampai 2 Sep 2026 `UserAdminController` justru menulis `null` untuk
     * setiap akun non-warga, sehingga 141 akun OPD — pengirim sebagian besar
     * permohonan — dirancang TIDAK punya wilayah. Hasilnya kolom itu terisi
     * pada 2 dari 1.390 akun.
     *
     * Petugas (1, 2) sengaja TIDAK di sini: mereka bekerja untuk seluruh
     * kabupaten, bukan mewakili satu tempat.
     */
    public const WAJIB_WILAYAH = [
        self::OPERATOR_OPD,
        self::WARGA,
    ];

    protected $table = 'm_userlevels';

    /** Hanya punya created_at; updated_at tidak ada di tabel. */
    public const UPDATED_AT = null;

    protected $fillable = ['nama'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'userlevel_id');
    }
}
