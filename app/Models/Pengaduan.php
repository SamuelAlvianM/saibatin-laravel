<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;

/**
 * Pengaduan masyarakat — termasuk jalur WBS (pelaporan dugaan pelanggaran).
 *
 * ⚠️ Identitas pelapor WBS dijanjikan dirahasiakan di halaman publiknya. Jangan
 * menampilkan `nama`/`nik`/`hp` pengadu di mana pun selain dashboard petugas.
 */
class Pengaduan extends Model
{
    use PresisiMilidetik;

    public const STATUS_BARU = 'BARU';
    public const STATUS_DIPROSES = 'DIPROSES';
    public const STATUS_SELESAI = 'SELESAI';

    protected $table = 't_pengaduanmasyarakat';

    protected $fillable = [
        'nama', 'nik', 'email', 'hp', 'subjek', 'isi', 'status', 'balasan',
    ];
}
