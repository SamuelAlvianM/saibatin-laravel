<?php

namespace App\Http\Middleware;

use App\Models\UserLevel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pembatas akses berdasarkan `users.userlevel_id`.
 *
 * Pemakaian:
 *   ->middleware('peran:petugas')       // level 1 & 2
 *   ->middleware('peran:petugas,opd')   // + instansi (halaman yang dipakai bersama)
 *   ->middleware('peran:1')             // Super Admin saja
 *   ->middleware('peran:3,4')           // warga & OPD
 *
 * ⚠️ Ada EMPAT level di DB, bukan tiga: 1 Super Admin · 2 Operator · 3 Warga ·
 * 4 Operator OPD (140 akun). Alias `petugas` sengaja disediakan supaya arti
 * "petugas = 1 dan 2" ditulis di satu tempat saja, bukan diulang sebagai angka
 * di puluhan rute.
 *
 * 🔴 `opd` adalah alias TERPISAH, bukan bagian dari `petugas`, dan itu
 * disengaja. Sejak 2 Sep 2026 Operator OPD memakai kerangka dashboard yang sama
 * seperti petugas, sehingga sangat menggoda untuk sekadar melebarkan `petugas`
 * jadi `[1,2,4]`. Jangan. Alias itu dipakai belasan rute yang artinya "boleh
 * melihat & memproses data orang lain"; satu perubahan di sana membuka
 * Manajemen Akun, halaman Master, dan seluruh permohonan kabupaten kepada 140
 * akun instansi — tanpa satu pun galat yang menandainya.
 *
 * ⚠️ Angka level TIDAK ditulis di berkas rute. Kalau penomoran bergeser
 * (dan di project saudara ia memang pernah bergeser), yang diubah satu
 * konstanta di sini, bukan puluhan baris rute.
 */
class PastikanPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest('/login');
        }

        $diizinkan = [];
        foreach ($peran as $p) {
            if ($p === 'petugas') {
                $diizinkan[] = UserLevel::SUPER_ADMIN;
                $diizinkan[] = UserLevel::OPERATOR;
            } elseif ($p === 'opd') {
                $diizinkan[] = UserLevel::OPERATOR_OPD;
            } else {
                $diizinkan[] = (int) $p;
            }
        }

        if ($diizinkan !== [] && ! in_array($user->userlevel_id, $diizinkan, true)) {
            abort(403, 'Anda tidak berhak membuka halaman ini.');
        }

        return $next($request);
    }
}
