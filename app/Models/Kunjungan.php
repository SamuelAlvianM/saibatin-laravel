<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;

/**
 * Pencacah kunjungan situs publik — satu baris per pengunjung (cookie anonim)
 * per hari. Kartu "Pengunjung" membacanya sebagai:
 *   online   = last_seen dalam 5 menit terakhir
 *   hari ini = baris bertanggal hari ini
 *   total    = jumlah seluruh hits
 *
 * ⚠️ "Hari ini" dihitung pada zona waktu aplikasi (Asia/Jakarta), bukan zona
 * server. Di project saudara, MySQL yang restart tanpa `default-time-zone`
 * terkunci pernah menggeser belasan kolom tanggal sejauh +9 jam.
 */
class Kunjungan extends Model
{
    use PresisiMilidetik;

    /** Ambang "sedang online". */
    public const JENDELA_ONLINE_DETIK = 300;

    protected $table = 't_kunjungan';

    /** Tak punya created_at/updated_at — waktunya diwakili `last_seen`. */
    public $timestamps = false;

    protected $fillable = ['visitor_id', 'tanggal', 'hits', 'last_seen'];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'last_seen' => 'datetime',
            'hits' => 'integer',
        ];
    }
}
