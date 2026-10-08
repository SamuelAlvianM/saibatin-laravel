<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Admin\PengaturanController;
use App\Http\Controllers\Controller;
use App\Services\JamLayanan;
use App\Support\Balasan;
use App\Support\KlienMobile;
use App\Support\Layanan;
use Illuminate\Http\Request;

/**
 * Daftar layanan + skema formulirnya untuk aplikasi mobile.
 *
 * Di website, data ini sampai ke React sebagai props Inertia
 * (`PengajuanController` untuk warga, `PengajuanPetugasController` untuk
 * petugas/OPD). Aturan penyaringannya disalin dari sana apa adanya:
 *
 *   - layanan yang dimatikan petugas DISARING untuk semua orang,
 *   - KECUALI Super Admin: ia tetap melihatnya, ditandai lewat `tersembunyi`.
 *
 * Pengirimannya TIDAK lewat sini, melainkan endpoint yang sama dengan website:
 * `POST /api/mobile/v1/{layanan.slug}/create` (lihat `LayananController`).
 */
class LayananMobileController extends Controller
{
    public function __construct(private readonly JamLayanan $jam) {}

    public function daftar(Request $request)
    {
        $admin = $request->user()->isSuperAdmin();
        $mati = PengaturanController::hidden();
        $daftar = config('layanan.daftar');

        if (! $admin) {
            $daftar = array_values(array_filter(
                $daftar,
                fn ($l) => ! in_array($l['kunci'], $mati, true),
            ));
        }

        return Balasan::ok([
            'daftar' => $daftar,
            'tersembunyi' => $admin ? $mati : [],
            'kategori' => config('layanan.kategori'),
            'jam' => $this->jam->status(),
        ]);
    }

    /** `$slug` = `slug` dari item `daftar` (slug RUTE, bukan slug formulir). */
    public function form(Request $request, string $slug)
    {
        $form = Layanan::formDariRute($slug);
        $u = $request->user();

        if (! $form || (! $u->isSuperAdmin() && PengaturanController::tersembunyi($form['slug']))) {
            return Balasan::gagal(['Layanan tidak ditemukan'], 404);
        }

        // Prefill data pemohon hanya untuk WARGA yang mengajukan untuk dirinya
        // sendiri — sama seperti `PengajuanController::form()`. Petugas & OPD
        // mengisikan atas nama orang lain, jadi formulirnya dibiarkan kosong.
        $prefill = [];
        if ($u->isWarga()) {
            if (preg_match('/^\d{16}$/', (string) $u->user_id)) {
                $prefill['pemohonnik'] = $u->user_id;
            }
            foreach ([
                'pemohonnama' => $u->user_fullname,
                'pemohonemail' => $u->user_email,
                'pemohonkk' => $u->user_nokk,
                'pemohonhp' => $u->user_hp,
            ] as $kolom => $nilai) {
                if (filled($nilai)) {
                    $prefill[$kolom] = $nilai;
                }
            }
        }

        return Balasan::ok([
            'layanan' => $form,
            'prefill' => $prefill,
            'jam' => $this->jam->status(),
            'kirimKe' => '/'.KlienMobile::PREFIX.'/'.$form['slug'].'/create',
            'unggahKe' => '/'.KlienMobile::PREFIX.'/'.$form['slug'].'/upload',
        ]);
    }
}
