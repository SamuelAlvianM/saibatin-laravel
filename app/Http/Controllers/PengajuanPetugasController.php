<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Admin\PengaturanController;
use App\Services\JamLayanan;
use App\Support\Layanan;
use Illuminate\Http\Request;
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
 * 🔴 LAYANAN YANG DIMATIKAN BENAR-BENAR BERHENTI — termasuk di loket.
 *
 * Sampai 2 Sep 2026 berkas ini sengaja TIDAK menyaring visibilitas, dengan
 * alasan "kanal loket tetap harus jalan saat layanan online ditutup". Dua hal
 * membatalkan alasan itu:
 *
 * 1. Penyaringannya ternyata cuma ada di pemilih layanan warga. `form()` di
 *    bawah dan `LayananController::buat()` tidak memeriksa apa pun, jadi siapa
 *    pun yang mengetik URL formulirnya tetap bisa MENGIRIM permohonan untuk
 *    layanan yang petugas kira sudah mati. "Nonaktif" tidak menonaktifkan apa
 *    pun — ia hanya menyembunyikan kartunya.
 * 2. Keputusan dinas: kalau sebuah layanan dimatikan, ia harus benar-benar
 *    berhenti, bukan berhenti untuk warga saja.
 *
 * Yang dilewatkan HANYA Super Admin, bukan seluruh petugas — dialah yang
 * mematikannya dan perlu mengujinya. Baginya kartunya tetap tampil, DIBERI
 * TANDA, bukan disaring: ia perlu melihat akibat pengaturannya sendiri.
 */
class PengajuanPetugasController extends Controller
{
    public function __construct(private readonly JamLayanan $jam) {}

    public function tampilkan(Request $request)
    {
        $admin = $request->user()->isSuperAdmin();
        $mati = PengaturanController::hidden();
        $daftar = config('layanan.daftar');

        // Bagi selain Super Admin layanan mati disaring habis. Kalau dibiarkan
        // tampil, kartunya jadi tautan mati: `form()` di bawah menolaknya 404.
        if (! $admin) {
            $daftar = array_values(array_filter(
                $daftar,
                fn ($l) => ! in_array($l['kunci'], $mati, true),
            ));
        }

        return Inertia::render('Dashboard/PengajuanBaru', [
            'daftar' => $daftar,
            'tersembunyi' => $admin ? $mati : [],
            // Daftar kategori dikirim dari server, bukan disimpulkan dari
            // `daftar` — supaya urutan dan namanya sama persis dengan yang
            // dipakai halaman pemohon, dan kategori kosong tidak diam-diam
            // hilang dari tab hanya karena layanannya sedang dimatikan.
            'kategori' => config('layanan.kategori'),
            'jam' => $this->jam->status(),
        ]);
    }

    public function form(Request $request, string $slug)
    {
        $form = Layanan::formDariRute($slug);

        if (! $form) {
            abort(404);
        }

        /*
         * 🔴 Sampai 2 Sep 2026 metode ini TIDAK punya penjaga sama sekali, jadi
         * petugas mana pun bisa membuka formulir layanan yang sudah dimatikan
         * hanya dengan mengetik URL dashboard-nya — pintu belakang yang tidak
         * terlihat dari layar mana pun.
         */
        if (! $request->user()->isSuperAdmin()
            && PengaturanController::tersembunyi($form['slug'])) {
            abort(404);
        }

        return Inertia::render('Dashboard/PengajuanForm', [
            'layanan' => $form,
            'jam' => $this->jam->status(),
        ]);
    }
}
