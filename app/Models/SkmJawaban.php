<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;

/**
 * Jawaban Survei Kepuasan Masyarakat.
 *
 * 🔴 `jawaban` (JSON) memakai DUA bentuk kunci yang harus sama-sama dihitung:
 *   - warisan  : "u0".."u8"   (203 responden lama)
 *   - formulir baru: kunci unsur SKM
 * Di project saudara, mengabaikan bentuk warisan membuat nilai IKM tampil
 * 0,00 / mutu D padahal datanya ada. Rumusnya juga bukan rata-rata dibagi 5,
 * melainkan NRR tertimbang × 25 (Permenpan RB 14/2017).
 */
class SkmJawaban extends Model
{
    use PresisiMilidetik;

    protected $table = 't_skm_jawaban';

    public const UPDATED_AT = null;

    protected $fillable = [
        'nama', 'umur', 'jenis_kelamin', 'pekerjaan', 'jawaban', 'saran',
    ];

    protected function casts(): array
    {
        return [
            'jawaban' => 'array',
            'umur' => 'integer',
        ];
    }
}
