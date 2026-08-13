<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pengajuan layanan — satu tabel untuk seluruh jenis permohonan.
 *
 * Field yang berbeda-beda per layanan hidup di `payload` (JSON), bukan sebagai
 * kolom. Kunci di dalamnya memakai nama field formulir Laravel 9 asli
 * (`pemohonnik`, `namalengkap`, `filekk`, …) supaya konsisten dengan 11.902
 * baris hasil migrasi.
 *
 * ⚠️ Nilai di payload data lama sering berupa KODE ANGKA (agama "1",
 * pekerjaan "88") karena begitulah portal lama menyimpannya. Menampilkannya
 * apa adanya akan memunculkan angka di layar warga — terjemahkan lewat kamus
 * `config/kode-options.php`.
 */
class Permohonan extends Model
{
    use PresisiMilidetik;

    public const STATUS_MENUNGGU = 'MENUNGGU';
    public const STATUS_DIPROSES = 'DIPROSES';
    public const STATUS_SELESAI = 'SELESAI';
    public const STATUS_DITOLAK = 'DITOLAK';

    protected $table = 't_permohonan';

    protected $fillable = [
        'no_register', 'user_id', 'jenis_id', 'status', 'payload', 'catatan',
        'proses_by', 'proses_by_name', 'proses_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'proses_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisPermohonan::class, 'jenis_id');
    }

    public function berkas(): HasMany
    {
        return $this->hasMany(Berkas::class, 'permohonan_id');
    }
}
