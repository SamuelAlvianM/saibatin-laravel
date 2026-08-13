<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;

/**
 * Dokumen publikasi: produk hukum, persyaratan, SOP, standar pelayanan, dafduk.
 *
 * `uploaded_by_name` sengaja denormalized — dokumen dapat diunggah beberapa
 * admin berbeda, dan jejak siapa yang mengunggah harus tetap terbaca meski
 * akunnya berubah.
 */
class Produk extends Model
{
    public const HUKUM = 'HUKUM';
    public const PERSYARATAN = 'PERSYARATAN';
    public const SOP = 'SOP';
    public const STANDAR_PELAYANAN = 'STANDAR_PELAYANAN';
    public const DAFDUK = 'DAFDUK';

    use PresisiMilidetik;

    protected $table = 't_produk';

    protected $fillable = [
        'jenis', 'judul', 'konten', 'file', 'uploaded_by', 'uploaded_by_name',
    ];
}
