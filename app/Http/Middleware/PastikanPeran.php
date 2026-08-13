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
 *   ->middleware('peran:petugas')   // level 1 & 2
 *   ->middleware('peran:1')         // Super Admin saja
 *   ->middleware('peran:3,4')       // warga & OPD
 *
 * ⚠️ Ada EMPAT level di DB, bukan tiga: 1 Super Admin · 2 Operator · 3 Warga ·
 * 4 Operator OPD (140 akun). Alias `petugas` sengaja disediakan supaya arti
 * "petugas = 1 dan 2" ditulis di satu tempat saja, bukan diulang sebagai angka
 * di puluhan rute.
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
