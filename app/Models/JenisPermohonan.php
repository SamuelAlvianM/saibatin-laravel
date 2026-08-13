<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master jenis layanan.
 *
 * ⚠️ Produksi berisi 17 baris, tapi hanya 15 punya formulir — SAKINAH dan
 * PENCETAKAN_KTP ada di master tanpa form. Jangan memakai jumlah baris tabel
 * ini untuk membangun daftar layanan yang bisa diajukan; sumbernya adalah
 * config formulir (`config/layanan.php`).
 */
class JenisPermohonan extends Model
{
    protected $table = 'm_jenis_permohonan';

    /** Tabel master murni — tidak punya kolom waktu sama sekali. */
    public $timestamps = false;

    protected $fillable = ['kode', 'nama', 'kategori', 'aktif', 'urutan'];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function permohonan(): HasMany
    {
        return $this->hasMany(Permohonan::class, 'jenis_id');
    }
}
