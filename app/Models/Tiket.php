<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tiket bantuan. Warga membuka tiket ke petugas; petugas juga berkomunikasi
 * antar mereka lewat kategori INTERNAL.
 *
 * `updated_at` = waktu aktivitas terakhir, dan itulah dasar auto-close setelah
 * sekian hari. Pengecekannya MALAS (saat daftar tiket dibuka) — target hosting
 * cPanel tidak punya proses hidup terus untuk menjalankan worker.
 */
class Tiket extends Model
{
    use PresisiMilidetik;

    public const TERBUKA = 'TERBUKA';
    public const TERTUTUP = 'TERTUTUP';

    public const KATEGORI_LAYANAN = 'LAYANAN';
    public const KATEGORI_TEKNIS = 'TEKNIS';
    public const KATEGORI_INTERNAL = 'INTERNAL';

    protected $table = 't_tiket';

    protected $fillable = ['nomor', 'user_id', 'subjek', 'kategori', 'status', 'closed_at'];

    protected function casts(): array
    {
        return ['closed_at' => 'datetime'];
    }

    /**
     * 🔴 Batasi ke tiket yang boleh dibaca $user.
     *
     * Di project saudara pernah terjadi kebocoran: daftar tiket tidak menyaring
     * kepemilikan sehingga warga bisa membaca tiket warga lain. Penyaringan
     * HARUS di query seperti ini, bukan di komponen tampilan.
     */
    public function scopeUntuk(Builder $q, User $user): Builder
    {
        return $user->isPetugas() ? $q : $q->where('user_id', $user->id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pesan(): HasMany
    {
        return $this->hasMany(TiketPesan::class, 'tiket_id');
    }
}
