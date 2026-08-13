<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Pengguna portal — warga maupun petugas.
 *
 * 🔴 Tiga hal yang TIDAK boleh diubah tanpa migrasi data (1.386 akun produksi):
 *
 * 1. `user_id` adalah identitas login, bukan `email`. Warga memakai NIK 16 digit,
 *    OPD/petugas memakai USERNAME. Karena itu `username()` di controller login
 *    menunjuk ke kolom ini.
 * 2. `password` berisi hash bcrypt cost 10 buatan bcryptjs (`$2a$…`). Hash::check()
 *    Laravel membacanya tanpa masalah — akun lama tetap bisa login tanpa reset.
 *    Yang berbeda hanya prefix (`$2a$` vs `$2y$`), dan itu kompatibel.
 * 3. `status` bukan boolean: 0 Menunggu · 1 Aktif · 2 Ditolak · 3 Nonaktif.
 *    HANYA status 1 yang boleh masuk. Status lain memunculkan pesan berbeda
 *    beserta jalan keluarnya (cek status / ajukan ulang / hubungi petugas).
 */
class User extends Authenticatable
{
    use Notifiable, PresisiMilidetik;

    /** Nilai kolom `status`. */
    public const STATUS_MENUNGGU = 0;
    public const STATUS_AKTIF = 1;
    public const STATUS_DITOLAK = 2;
    public const STATUS_NONAKTIF = 3;

    protected $table = 'users';

    protected $fillable = [
        'user_id', 'password', 'userlevel_id', 'user_fullname', 'user_nik',
        'user_nokk', 'user_hp', 'user_email', 'user_kecamatan', 'user_foto', 'user_ktp',
        'activation_code', 'activation_code_url', 'activation_time',
        'forgotten_code', 'forgotten_time', 'login_last', 'ip_address',
        'status', 'ket', 'email_verified_at', 'created_by', 'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        // Kode aktivasi & lupa-sandi adalah rahasia sekali-pakai; jangan sampai
        // ikut terserialisasi ke props Inertia atau respons JSON.
        'activation_code', 'activation_code_url', 'forgotten_code',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'userlevel_id' => 'integer',
            'status' => 'integer',
            'activation_time' => 'datetime',
            'forgotten_time' => 'datetime',
            'login_last' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    // ── Peran ────────────────────────────────────────────────────────────────

    /** Petugas = Super Admin (1) atau Operator (2). Dasar seluruh kontrol akses. */
    public function isPetugas(): bool
    {
        return in_array($this->userlevel_id, [UserLevel::SUPER_ADMIN, UserLevel::OPERATOR], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->userlevel_id === UserLevel::SUPER_ADMIN;
    }

    public function isWarga(): bool
    {
        return $this->userlevel_id === UserLevel::WARGA;
    }

    public function isAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }

    // ── Relasi ───────────────────────────────────────────────────────────────

    public function level(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class, 'userlevel_id');
    }

    public function permohonan(): HasMany
    {
        return $this->hasMany(Permohonan::class, 'user_id');
    }

    /**
     * 🔴 Dua relasi berikut SENGAJA DIPERTAHANKAN meski fitur tiket & chat
     * dihapus user (13 Agu 2026).
     *
     * Tabel `t_tiket` & `t_tiket_pesan` masih berisi tiket warga di produksi,
     * dan FK-nya `ON DELETE RESTRICT`. Penjaga penghapusan akun
     * (`UserAdminController::destroy`) menghitung relasi ini supaya akun yang
     * punya jejak tiket ditolak dengan pesan yang jelas. Tanpa relasi ini,
     * penghapusan baru gagal di lapisan database — petugas menerima galat 500,
     * bukan penjelasan.
     */
    public function tiket(): HasMany
    {
        return $this->hasMany(Tiket::class, 'user_id');
    }

    public function tiketPesan(): HasMany
    {
        return $this->hasMany(TiketPesan::class, 'user_id');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }

    public function logAktivitas(): HasMany
    {
        return $this->hasMany(LogAktivitas::class, 'user_id');
    }
}
