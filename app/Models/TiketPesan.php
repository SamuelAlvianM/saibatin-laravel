<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pesan/balasan di dalam sebuah tiket. */
class TiketPesan extends Model
{
    use PresisiMilidetik;

    protected $table = 't_tiket_pesan';

    public const UPDATED_AT = null;

    protected $fillable = ['tiket_id', 'user_id', 'isi'];

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(Tiket::class, 'tiket_id');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
