<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;

/**
 * Konten statis yang bisa disunting dari dashboard.
 *
 * Baris di sini hanya MENG-OVERRIDE nilai default yang hidup di config —
 * tabel kosong berarti situs tetap tampil lengkap dengan isi bawaan.
 *
 * ⚠️ Tabel ini juga menampung KONFIGURASI, bukan cuma konten:
 *   `pelayanan.jam`         → jam layanan permohonan online
 *   `pelayanan.visibilitas` → layanan mana yang ditampilkan
 * Jadi jangan menyaring isinya hanya dengan daftar blok konten.
 */
class StaticContent extends Model
{
    use PresisiMilidetik;

    /** Kunci konfigurasi (bukan blok konten) yang ikut menumpang di tabel ini. */
    public const KUNCI_JAM_LAYANAN = 'pelayanan.jam';
    public const KUNCI_VISIBILITAS = 'pelayanan.visibilitas';

    protected $table = 't_static_contents';

    protected $fillable = ['kunci', 'judul', 'konten', 'updated_by'];

    protected function casts(): array
    {
        return ['konten' => 'array'];
    }

    public function getRouteKeyName(): string
    {
        return 'kunci';
    }
}
