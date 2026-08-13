<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi in-app (lonceng navbar/dashboard).
 *
 * 🔴 Semua metode di sini bersifat "best-effort": kegagalan menulis notifikasi
 * TIDAK BOLEH menggagalkan aksi utamanya. Permohonan warga yang sudah tersimpan
 * tidak boleh dibatalkan hanya karena barisan notifikasi gagal ditulis — karena
 * itu pemanggilan dibungkus `aman()`.
 */
class Pemberitahuan
{
    public function buat(int $userId, string $tipe, string $judul, string $isi, ?string $link = null, ?string $refType = null, ?int $refId = null): void
    {
        Notifikasi::create([
            'user_id' => $userId,
            'tipe' => $tipe,
            'judul' => $judul,
            'isi' => $isi,
            'link' => $link,
            'ref_type' => $refType,
            'ref_id' => $refId,
        ]);
    }

    /** Kirim notifikasi yang sama ke SELURUH petugas aktif (level 1 & 2). */
    public function kePetugas(string $tipe, string $judul, string $isi, ?string $link = null, ?string $refType = null, ?int $refId = null, ?int $kecualiUserId = null): void
    {
        $petugas = User::query()
            ->whereIn('userlevel_id', [UserLevel::SUPER_ADMIN, UserLevel::OPERATOR])
            ->where('status', User::STATUS_AKTIF)
            ->when($kecualiUserId, fn ($q) => $q->where('id', '!=', $kecualiUserId))
            ->pluck('id');

        if ($petugas->isEmpty()) {
            return;
        }

        $sekarang = now();

        Notifikasi::insert($petugas->map(fn ($id) => [
            'user_id' => $id,
            'tipe' => $tipe,
            'judul' => $judul,
            'isi' => $isi,
            'link' => $link,
            'ref_type' => $refType,
            'ref_id' => $refId,
            'dibaca' => false,
            'created_at' => $sekarang,
        ])->all());
    }

    /** Jalankan pembuatan notifikasi tanpa pernah melempar ke pemanggil. */
    public function aman(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::error('[notifikasi] gagal dibuat: '.$e->getMessage());
        }
    }
}
