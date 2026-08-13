<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;

/** Kritik & saran. Sekali kirim, tidak pernah disunting — tanpa updated_at. */
class KritikSaran extends Model
{
    use PresisiMilidetik;

    protected $table = 't_kritiksaran';

    public const UPDATED_AT = null;

    protected $fillable = ['nama', 'email', 'hp', 'pesan'];
}
