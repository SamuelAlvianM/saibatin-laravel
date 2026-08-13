<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit ringan tindakan petugas: siapa, aksi apa, objek apa, kapan.
 *
 * Hanya diisi untuk pelaku level 1/2 — tindakan warga tidak dicatat di sini.
 * `ringkasan` sudah berupa kalimat siap tampil ("Mengubah status REG… →
 * SELESAI"), bukan potongan data yang harus dirakit ulang di tampilan.
 */
class LogAktivitas extends Model
{
    use PresisiMilidetik;

    public const BUAT = 'BUAT';
    public const UBAH = 'UBAH';
    public const HAPUS = 'HAPUS';
    public const UNGGAH = 'UNGGAH';
    public const IMPOR = 'IMPOR';
    public const LAINNYA = 'LAINNYA';

    protected $table = 't_log_aktivitas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'aksi', 'entitas', 'entitas_id', 'ringkasan', 'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
