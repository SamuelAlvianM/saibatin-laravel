<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Admin\PengaturanController;
use App\Models\Permohonan;
use App\Services\JamLayanan;
use App\Support\Layanan;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Halaman pengajuan permohonan untuk warga/OPD.
 *
 * Tiap layanan punya URL sendiri (`/user/pengajuan/baru/{slug}`) supaya bisa
 * di-bookmark dan dibuka langsung — di portal lama semuanya modal tanpa URL.
 */
class PengajuanController extends Controller
{
    public function __construct(private readonly JamLayanan $jam) {}

    /** Riwayat permohonan milik pengaju. */
    public function riwayat(Request $request)
    {
        /*
         * 🔴 PENGALIHAN INI MEMBAWA QUERY STRING — dan itu bukan kerapian.
         *
         * Operator OPD pindah ke kerangka dashboard 2 Sep 2026, jadi alamat
         * lamanya harus mengalih. Cara yang "jelas" adalah `Route::redirect`
         * di berkas rute — dan itu MEMBUANG query string diam-diam.
         *
         * Lonceng notifikasi membangun tautannya sebagai `<link>?sorot=<id>`,
         * dan notifikasi yang SUDAH TERSIMPAN di basis data ber-`link =
         * '/user/pengajuan'`. Dengan `Route::redirect`, setiap notifikasi lama
         * mendarat di daftar permohonan tanpa menyorot baris yang justru jadi
         * alasan notifikasinya dikirim. Notifikasinya "berfungsi", cuma
         * kehilangan gunanya — tanpa galat, tanpa apa pun yang terlihat rusak.
         * (Sudah pernah terjadi di project saudara; jangan diulang.)
         *
         * Warga TIDAK ikut pindah — halaman ini tetap miliknya.
         */
        if ($request->user()->isOpd()) {
            $qs = $request->getQueryString();

            return redirect('/dashboard/permohonan'.($qs ? '?'.$qs : ''));
        }

        return Inertia::render('Pengajuan/Riwayat', [
            'baru' => $request->query('baru'),
        ]);
    }

    /** Pemilih layanan. */
    public function pilih(Request $request)
    {
        // Operator OPD memakai pemilih layanan di dashboard; alamat lamanya
        // dialihkan beserta query string-nya (lihat catatan di `riwayat()`).
        if ($request->user()->isOpd()) {
            $qs = $request->getQueryString();

            return redirect('/dashboard/pengajuan-baru'.($qs ? '?'.$qs : ''));
        }

        // Layanan yang disembunyikan petugas (halaman Pengaturan) tidak
        // ditawarkan di sini. Penyaringannya di server, bukan CSS: kartu yang
        // cuma disembunyikan tampilannya tetap bisa dibuka lewat URL-nya.
        $hidden = PengaturanController::hidden();

        return Inertia::render('Pengajuan/Pilih', [
            'daftar' => array_values(array_filter(
                config('layanan.daftar'),
                fn ($l) => ! in_array($l['kunci'], $hidden, true),
            )),
            'kategori' => config('layanan.kategori'),
            // Kotak pencarian & tombol pintasan di beranda mengirim `?q=` ke
            // sini. Tanpa baris ini kata kuncinya hilang diam-diam: warga
            // mengetik "akta kelahiran" di hero lalu mendarat di daftar penuh
            // dengan kotak pencarian kosong.
            'kataKunciAwal' => (string) $request->query('q', ''),
        ]);
    }

    /** Formulir satu layanan. */
    public function form(Request $request, string $slug)
    {
        if ($request->user()->isOpd()) {
            return redirect('/dashboard/pengajuan-baru/'.$slug);
        }

        $form = Layanan::formDariRute($slug);

        if (! $form) {
            abort(404);
        }

        /*
         * 🔴 Kartu layanan yang dimatikan memang tidak ditawarkan di `pilih()`,
         * tapi sampai 2 Sep 2026 halaman formulirnya tetap terbuka bagi siapa
         * pun yang mengetik URL-nya langsung — dan kiriman dari sana diterima.
         * Menyembunyikan kartu bukan pagar.
         */
        if (PengaturanController::tersembunyi($form['slug'])) {
            abort(404);
        }

        $u = $request->user();

        // Data pemohon diisi otomatis dari akun yang login. NIK hanya diisi bila
        // `user_id` memang 16 digit (warga) — akun OPD yang user_id-nya username
        // dibiarkan kosong supaya tidak masuk ke kolom NIK.
        $prefill = [];
        if (preg_match('/^\d{16}$/', (string) $u->user_id)) {
            $prefill['pemohonnik'] = $u->user_id;
        }
        if (filled($u->user_fullname)) {
            $prefill['pemohonnama'] = $u->user_fullname;
        }
        if (filled($u->user_email)) {
            $prefill['pemohonemail'] = $u->user_email;
        }
        if (filled($u->user_nokk)) {
            $prefill['pemohonkk'] = $u->user_nokk;
        }
        if (filled($u->user_hp)) {
            $prefill['pemohonhp'] = $u->user_hp;
        }

        return Inertia::render('Pengajuan/Form', [
            'layanan' => $form,
            'prefill' => $prefill,
            // Status jam dikirim dari server, bukan diambil ulang oleh klien —
            // sumbernya sama dengan yang menggerbang endpoint pengirimannya.
            'jam' => $this->jam->status(),
        ]);
    }
}
