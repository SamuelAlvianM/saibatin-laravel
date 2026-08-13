<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notifikasi in-app (lonceng navbar/dashboard).
 *
 * Sengaja BUKAN tabel `notifications` bawaan Laravel — bentuk kolomnya sudah
 * dipakai data produksi dan dibaca langsung oleh UI. `link` adalah tujuan saat
 * notifikasi diklik; halaman tujuan menyorot baris terkait lewat `?sorot=<id>`.
 */
class Notifikasi extends Model
{
    use PresisiMilidetik;

    public const PERMOHONAN_STATUS = 'PERMOHONAN_STATUS';
    public const PERMOHONAN_BARU = 'PERMOHONAN_BARU';
    public const PENGADUAN_BARU = 'PENGADUAN_BARU';
    public const KRITIK_BARU = 'KRITIK_BARU';
    public const AKUN_BARU = 'AKUN_BARU';

    protected $table = 't_notifikasi';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'tipe', 'judul', 'isi', 'link', 'ref_type', 'ref_id', 'dibaca',
    ];

    protected function casts(): array
    {
        return ['dibaca' => 'boolean'];
    }

    public function scopeBelumDibaca(Builder $q): Builder
    {
        return $q->where('dibaca', false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
