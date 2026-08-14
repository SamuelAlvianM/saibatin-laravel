<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Halaman "Profil Saya" — port `app/profil/page.tsx`.
 *
 * 🔴 Halaman ini sempat TIDAK ADA di port, padahal `LoginController` sudah
 * mengarahkan ke sini (`/profil?lengkapi=foto`) setiap kali warga login pertama
 * kali tanpa foto. Akibatnya login yang berhasil berujung 404 — dan seluruh
 * endpoint `/api/profil*` tidak punya pemanggil sama sekali.
 *
 * Datanya dikirim sebagai props (bukan `fetch` sesudah render) supaya formulir
 * langsung tampil terisi, sama seperti aslinya yang server component.
 */
class ProfilPageController extends Controller
{
    public function __invoke(Request $request)
    {
        $u = $request->user()->loadMissing('level:id,nama');

        return Inertia::render('Profil', [
            'awal' => [
                'userId' => $u->user_id,
                'nama' => $u->user_fullname ?? '',
                'nik' => $u->user_nik ?? '',
                'nokk' => $u->user_nokk ?? '',
                'hp' => $u->user_hp ?? '',
                'email' => $u->user_email ?? '',
                'alamat' => $u->ket ?? '',
                'levelNama' => $u->level->nama ?? '',
                'foto' => $u->user_foto,
            ],
            // Datang dari login pertama warga (LoginController::masuk).
            // Sifatnya anjuran, bukan gerbang: halaman tetap bisa dilewati.
            'dimintaFoto' => $request->query('lengkapi') === 'foto',
        ]);
    }
}
