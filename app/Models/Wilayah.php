<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master wilayah — hierarki KECAMATAN → KELURAHAN lewat `parent_id`.
 *
 * ⚠️ `users.user_kecamatan` menyimpan NAMA kecamatan, bukan relasi ke tabel ini.
 * Jadi mengganti ejaan di sini memutus kecocokan data warga. Ejaan resmi yang
 * sudah diselaraskan: NGARAS (dulu BENGKUNAT BELIMBING), PULAU PISANG (dulu
 * PULAUPISANG).
 */
class Wilayah extends Model
{
    public const KECAMATAN = 'KECAMATAN';
    public const KELURAHAN = 'KELURAHAN';

    protected $table = 'm_wilayah';

    public $timestamps = false;

    protected $fillable = ['kode', 'nama', 'jenis', 'parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
