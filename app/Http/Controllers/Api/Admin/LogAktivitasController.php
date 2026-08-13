<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Support\Balasan;
use App\Support\Periode;
use Illuminate\Http\Request;

/**
 * Log aktivitas petugas — port `app/api/admin/log-aktivitas/route.ts`.
 *
 * 🔴 KHUSUS Super Admin (level 1). Operator tidak boleh melihat jejak
 * rekan-rekannya; ini catatan pengawasan, bukan riwayat kerja bersama.
 * Pembatasnya ada di rute (`peran:1`) DAN diulang sebagai lapis kedua di sini.
 */
class LogAktivitasController extends Controller
{
    private const PER_HALAMAN = 25;

    public function index(Request $request)
    {
        if (! $request->user()?->isSuperAdmin()) {
            return Balasan::gagal(['Akses ditolak'], 403);
        }

        $page = max(1, (int) $request->query('page', 1));
        $userId = $request->query('userId');
        $q = trim((string) $request->query('q'));

        $query = fn () => LogAktivitas::query()
            ->when(is_string($userId) && ctype_digit($userId), fn ($w) => $w->where('user_id', (int) $userId))
            ->when(filled($q), fn ($w) => $w->where('ringkasan', 'like', "%{$q}%"))
            ->tap(fn ($w) => Periode::saring($w, $request->query('periode'), $request->query('acuan')));

        $total = $query()->count();

        $items = $query()
            ->with('user:id,user_id,user_fullname,userlevel_id')
            ->orderByDesc('created_at')
            ->forPage($page, self::PER_HALAMAN)
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'userId' => $l->user_id,
                'aksi' => $l->aksi,
                'entitas' => $l->entitas,
                'entitasId' => $l->entitas_id,
                'ringkasan' => $l->ringkasan,
                'ipAddress' => $l->ip_address,
                'createdAt' => $l->created_at,
                'user' => $l->user ? [
                    'userFullname' => $l->user->user_fullname,
                    'userId' => $l->user->user_id,
                    'userlevelId' => $l->user->userlevel_id,
                ] : null,
            ])->values();

        // Opsi filter: petugas yang PERNAH punya log — supaya dropdown tidak
        // kosong dan tidak pula memuat 1.386 akun yang tak relevan.
        $petugas = LogAktivitas::with('user:id,user_id,user_fullname')
            ->select('user_id')->distinct()->orderBy('user_id')->get()
            ->map(fn ($r) => [
                'id' => $r->user_id,
                'nama' => $r->user->user_fullname ?? $r->user->user_id ?? "#{$r->user_id}",
            ])->values();

        return Balasan::ok([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / self::PER_HALAMAN)),
            'petugas' => $petugas,
        ]);
    }
}
