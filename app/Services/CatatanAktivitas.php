<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Http\Request;

/**
 * Audit ringan aktivitas petugas.
 *
 * Dua prinsip yang diwarisi apa adanya dari portal Next.js:
 *
 * 1. **Hanya mencatat petugas (level 1 & 2).** Warga dan OPD diabaikan supaya
 *    log tetap soal "kegiatan admin" — bukan jejak seluruh pengguna.
 * 2. **Tidak pernah melempar.** Audit tidak boleh menggagalkan aksi utamanya;
 *    permohonan yang sudah tersimpan tidak boleh dibatalkan karena barisan log
 *    gagal ditulis.
 */
class CatatanAktivitas
{
    public function catat(
        ?User $pelaku,
        string $aksi,
        string $entitas,
        string $ringkasan,
        string|int|null $entitasId = null,
        ?Request $request = null,
    ): void {
        try {
            if (! $pelaku || ! in_array($pelaku->userlevel_id, [UserLevel::SUPER_ADMIN, UserLevel::OPERATOR], true)) {
                return;
            }

            LogAktivitas::create([
                'user_id' => $pelaku->id,
                'aksi' => $aksi,
                'entitas' => $entitas,
                'entitas_id' => $entitasId !== null ? (string) $entitasId : null,
                'ringkasan' => $ringkasan,
                'ip_address' => $request?->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
