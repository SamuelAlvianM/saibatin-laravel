<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;

/**
 * Pustaka media terpusat untuk dashboard (gambar & PDF).
 *
 * `id` berupa UUID v4, bukan auto-increment — nama berkas fisik jadi tak
 * bisa ditebak. Berkas disimpan di storage (di luar public) dengan path
 * relatif `yyyy/mm/uuid.ext`; pengguna tidak pernah mengetik URL-nya, semua
 * lewat picker/drag-drop.
 */
class Media extends Model
{
    use PresisiMilidetik;

    protected $table = 't_media';

    /** Kunci utama string (uuid) — bukan integer yang menaik. */
    public $incrementing = false;
    protected $keyType = 'string';

    public const UPDATED_AT = null;

    protected $fillable = [
        'id', 'nama_asli', 'nama_file', 'mime_type', 'ukuran',
        'lebar', 'tinggi', 'path', 'url', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'ukuran' => 'integer',
            'lebar' => 'integer',
            'tinggi' => 'integer',
        ];
    }
}
