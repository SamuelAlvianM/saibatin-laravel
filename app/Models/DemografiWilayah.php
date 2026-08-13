<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;

/**
 * Agregat demografi per wilayah, hasil impor Excel Dukcapil (SIAK).
 *
 * `kategori` = jenis-kelamin | agama | gol-darah | pekerjaan | kk | pendidikan |
 *              status-kawin | wajib-ktp
 * `level`    = 4 kecamatan · 5 pekon/kelurahan (hierarki lewat `parent_kode`)
 * `data`     = kolom nilai per kategori, mis. { L, P, JML }
 *
 * Baris di-upsert setiap impor, jadi tabel ini sengaja tak punya created_at.
 */
class DemografiWilayah extends Model
{
    use PresisiMilidetik;

    public const LEVEL_KECAMATAN = 4;
    public const LEVEL_KELURAHAN = 5;

    protected $table = 'm_demografi_wilayah';

    /** Hanya updated_at yang bermakna. */
    public const CREATED_AT = null;

    protected $fillable = ['kategori', 'kode', 'wilayah', 'level', 'parent_kode', 'data'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'level' => 'integer',
        ];
    }
}
