<?php

namespace App\Http\Controllers;

use App\Services\JamLayanan;
use App\Support\Layanan;
use Inertia\Inertia;

/**
 * "Pengajuan Baru" di dashboard — petugas mengisikan permohonan ATAS NAMA warga
 * yang datang ke loket. Port `app/dashboard/pengajuan-baru/**`.
 *
 * Renderer formulirnya sama persis dengan yang dipakai warga (`FormLayanan`);
 * yang berbeda hanya `mandiri=false` — pengantar dan prefill-nya. Itu memang
 * inti keputusan Fase 4: satu renderer, bukan dua salinan yang lambat laun
 * berbeda diam-diam.
 *
 * ⚠️ Daftar layanan di sini TIDAK disaring visibilitas. Menyembunyikan layanan
 * ditujukan untuk warga di portal publik; petugas di loket tetap harus bisa
 * memprosesnya — itulah gunanya kanal loket saat layanan online ditutup.
 */
class PengajuanPetugasController extends Controller
{
    public function __construct(private readonly JamLayanan $jam) {}

    public function tampilkan()
    {
        return Inertia::render('Dashboard/PengajuanBaru', [
            'daftar' => config('layanan.daftar'),
            'jam' => $this->jam->status(),
        ]);
    }

    public function form(string $slug)
    {
        $form = Layanan::formDariRute($slug);

        if (! $form) {
            abort(404);
        }

        return Inertia::render('Dashboard/PengajuanForm', [
            'layanan' => $form,
            'jam' => $this->jam->status(),
        ]);
    }
}
