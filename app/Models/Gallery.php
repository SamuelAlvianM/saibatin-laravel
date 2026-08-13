<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;

/** Galeri foto kegiatan. `kategori`: BUPATI | PELAYANAN. */
class Gallery extends Model
{
    use PresisiMilidetik;

    protected $table = 't_galleries';

    public const UPDATED_AT = null;

    protected $fillable = ['judul', 'kategori', 'gambar', 'deskripsi'];
}
