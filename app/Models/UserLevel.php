<?php

namespace App\Models;

use App\Models\Concerns\PresisiMilidetik;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Level/peran pengguna.
 *
 * Produksi berisi EMPAT baris — komentar lama yang menyebut "1=superadmin,
 * 2=operator, 3=warga" tidak lengkap:
 *   1 Super Admin · 2 Operator · 3 Warga · 4 Operator OPD (140 akun)
 */
class UserLevel extends Model
{
    use PresisiMilidetik;

    public const SUPER_ADMIN = 1;
    public const OPERATOR = 2;
    public const WARGA = 3;
    public const OPERATOR_OPD = 4;

    protected $table = 'm_userlevels';

    /** Hanya punya created_at; updated_at tidak ada di tabel. */
    public const UPDATED_AT = null;

    protected $fillable = ['nama'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'userlevel_id');
    }
}
