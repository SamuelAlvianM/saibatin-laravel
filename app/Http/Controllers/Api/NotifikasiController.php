<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Support\Balasan;
use Illuminate\Http\Request;

/** Lonceng notifikasi in-app. */
class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $limit = min(50, max(1, (int) $request->query('limit', 20)));
        $u = $request->user();

        return Balasan::ok([
            'items' => Notifikasi::where('user_id', $u->id)
                ->latest('created_at')->take($limit)->get(),
            'unread' => Notifikasi::where('user_id', $u->id)->belumDibaca()->count(),
        ]);
    }

    public function tandaiSemua(Request $request)
    {
        Notifikasi::where('user_id', $request->user()->id)
            ->belumDibaca()->update(['dibaca' => true]);

        return Balasan::ok(null, ['Semua notifikasi ditandai dibaca']);
    }

    /**
     * Tandai satu notifikasi.
     *
     * 🔴 Penyaringan `user_id` ada di query, bukan dicek setelah baris diambil —
     * tanpa itu siapa pun yang menebak id bisa menandai notifikasi orang lain.
     */
    public function tandaiSatu(Request $request, int $id)
    {
        $n = Notifikasi::where('id', $id)
            ->where('user_id', $request->user()->id)->first();

        if (! $n) {
            return Balasan::gagal(['Notifikasi tidak ditemukan'], 404);
        }

        $n->forceFill(['dibaca' => true])->save();

        return Balasan::ok(['id' => $n->id]);
    }
}
