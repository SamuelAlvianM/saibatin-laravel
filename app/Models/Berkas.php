<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lampiran permohonan (scan KTP/KK/akta).
 *
 * 🔴 `path` adalah URL publik `/uploads/<layanan>/<uid>_<ts>.<ext>` — berkas
 * fisiknya WAJIB di luar `public/`. Kepemilikan dibaca dari prefix `<uid>_`
 * pada nama berkas, itulah dasar kontrol akses saat menyajikannya.
 */
class Berkas extends Model
{
    use PresisiMilidetik;

    protected $table = 't_berkas';

    /** Hanya created_at. */
    public const UPDATED_AT = null;

    protected $fillable = ['permohonan_id', 'nama_file', 'path', 'mime_type', 'ukuran'];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }
}
