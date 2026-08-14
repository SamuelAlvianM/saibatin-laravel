<?php

use App\Http\Controllers\Auth\CekStatusController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SandiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PengajuanPetugasController;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rute Web
|--------------------------------------------------------------------------
|
| Pembagiannya mengikuti keputusan arsitektur:
|   - Halaman PUBLIK ber-SEO → Blade (HTML penuh dari server)   [Fase 7]
|   - Dashboard & formulir   → Inertia + React                  [Fase 2,4,5]
| Tidak ada Inertia SSR: itu butuh daemon Node yang tidak tersedia di hosting.
|
*/

// ── Tamu ────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'tampilkan'])->name('login');
    // 5 percobaan/menit. Identitas login warga adalah NIK dan daftar NIK bersifat
    // semi-publik, jadi tanpa pembatas ini tebak-sandi massal terlalu murah.
    Route::post('/login', [LoginController::class, 'masuk'])->middleware('throttle:5,1');

    Route::get('/register', [RegisterController::class, 'tampilkan'])->name('register');
    Route::post('/register', [RegisterController::class, 'daftar'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [SandiController::class, 'formLupa']);
    Route::post('/forgot-password', [SandiController::class, 'kirimTautan'])->middleware('throttle:5,1');

    Route::get('/reset-password', [SandiController::class, 'formReset']);
    Route::post('/reset-password', [SandiController::class, 'reset'])->middleware('throttle:5,1');
});

// ── OTP pendaftaran ─────────────────────────────────────────────────────────
// Terbuka untuk tamu (dipakai saat mendaftar). Pembatas kirim-ulang 60 detik
// per identitas ada di controller; throttle di sini menahan penyalahgunaan IP.
Route::post('/otp/send', [OtpController::class, 'kirim'])->middleware('throttle:10,1');
Route::post('/otp/verify', [OtpController::class, 'verifikasi'])->middleware('throttle:20,1');

// ── Cek status pendaftaran (tanpa login) ────────────────────────────────────
Route::get('/cek-status', [CekStatusController::class, 'tampilkan'])->name('cek-status');
Route::post('/cek-status', [CekStatusController::class, 'periksa'])->middleware('throttle:20,1');
Route::post('/ajukan-ulang', [CekStatusController::class, 'ajukanUlang'])->middleware('throttle:5,1');

// ── Area berizin ────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'keluar'])->name('logout');

    // 🔴 Wajib ada: LoginController mengantar warga ke `/profil?lengkapi=foto`
    // pada login pertama. Selama rute ini belum dibuat, login yang BERHASIL
    // berakhir di 404.
    Route::get('/profil', App\Http\Controllers\ProfilPageController::class)->name('profil');

    // ── Pengajuan permohonan (warga/OPD) ────────────────────────────────────
    Route::get('/user/pengajuan', [App\Http\Controllers\PengajuanController::class, 'riwayat']);
    Route::get('/user/pengajuan/baru', [App\Http\Controllers\PengajuanController::class, 'pilih']);
    Route::get('/user/pengajuan/baru/{slug}', [App\Http\Controllers\PengajuanController::class, 'form']);

    // ── Dashboard petugas (Fase 5) ──────────────────────────────────────────
    // `/dashboard` sendiri hanya ber-middleware `auth`: warga/OPD yang terlanjur
    // mem-bookmark-nya diarahkan ke halaman mereka oleh controller, bukan
    // dilempari 403. Sisanya memang tertutup untuk mereka.
    Route::get('/dashboard', [DashboardController::class, 'beranda'])->name('dashboard');

    Route::middleware('peran:petugas')->prefix('dashboard')->group(function () {
        Route::get('/permohonan', fn () => Inertia::render('Dashboard/Permohonan', [
            'sorot' => request()->query('sorot'),
        ]));

        Route::get('/pengajuan-baru', [PengajuanPetugasController::class, 'tampilkan']);
        Route::get('/pengajuan-baru/{slug}', [PengajuanPetugasController::class, 'form']);

        Route::get('/users', fn () => Inertia::render('Dashboard/Akun', [
            'kecamatan' => Wilayah::where('jenis', Wilayah::KECAMATAN)->orderBy('nama')->pluck('nama'),
        ]));

        Route::get('/pengaduan', fn () => Inertia::render('Dashboard/Pengaduan'));
        Route::get('/kritik-saran', fn () => Inertia::render('Dashboard/KritikSaran'));
        Route::get('/skm', fn () => Inertia::render('Dashboard/Skm'));

        // 🔴 Sengaja TIDAK ditautkan dari sidebar — dibuka lewat URL saja.
        Route::get('/master', fn () => Inertia::render('Dashboard/Master'));

        // ── Konten & Media — seluruhnya khusus Super Admin, sama seperti
        //    `ADMIN_ONLY_HREFS` di sidebar portal Next.js.
        Route::middleware('peran:1')->group(function () {
            Route::get('/berita', fn () => Inertia::render('Dashboard/Berita'));
            Route::get('/media', fn () => Inertia::render('Dashboard/Media'));

            Route::get('/demografi', fn () => Inertia::render('Dashboard/Demografi', [
                'kategori' => config('demografi.kategori'),
                'kartuBawaan' => config('konten.kartu_beranda'),
            ]));

            Route::get('/produk', fn () => Inertia::render('Dashboard/Produk', [
                // Registry dikirim dari server supaya dashboard, validasi
                // penyimpanan, dan halaman publik membaca daftar yang sama.
                'kategori' => config('dokumen.kategori'),
            ]));

            // Seperti Master: halaman galeri memang tidak ditautkan dari sidebar
            // di portal aslinya — dibuka lewat URL.
            Route::get('/galeri', fn () => Inertia::render('Dashboard/Galeri'));
        });

        // Log aktivitas = catatan pengawasan, hanya Super Admin.
        Route::get('/log', fn () => Inertia::render('Dashboard/Log'))->middleware('peran:1');
    });
});

// ── Endpoint JSON ───────────────────────────────────────────────────────────
// Dimuat DI SINI (bukan sebagai grup `api` bawaan Laravel) supaya ikut middleware
// `web` — autentikasi portal ini berbasis sesi cookie, dan grup `api` yang
// stateless akan membuat $request->user() selalu null.
Route::prefix('api')->group(base_path('routes/api.php'));

// ── Pustaka media (publik) ──────────────────────────────────────────────────
// 🔴 HARUS di atas `/uploads/{jalur}` di bawahnya: pola itu `.*` dan akan
// menelan `media/...` juga — lihat MediaPublikController.
Route::get('/uploads/media/{jalur}', App\Http\Controllers\MediaPublikController::class)
    ->where('jalur', '.*');

// ── Berkas unggahan (berkontrol akses) ──────────────────────────────────────
// 🔴 Berkas warga tidak pernah disajikan langsung dari public/ — lihat
// BerkasController untuk aturan kepemilikannya.
Route::get('/uploads/{jalur}', [App\Http\Controllers\BerkasController::class, 'tampilkan'])
    ->where('jalur', '.*');

// ── Situs publik (Blade + React island) ─────────────────────────────────────
// Didaftarkan PALING AKHIR: sebagian nanti berpola catch-all (`/produk/{slug}`,
// `/ppid/{slug}`) yang akan menelan rute di atasnya kalau dinaikkan.
Route::get('/', [App\Http\Controllers\PublikController::class, 'beranda'])->name('beranda');

Route::get('/galeri', [App\Http\Controllers\PublikController::class, 'galeri'])->name('galeri');

// Halaman informasi statis. Slug yang tidak terdaftar di `config/info-halaman.php`
// dijawab 404 oleh controller — bukan halaman kosong.
//
// 🔴 Ditulis sebagai aksi controller, BUKAN closure: closure tidak bisa
// di-`route:cache`, dan cache rute itu justru yang dipakai di cPanel.
// 🔴 HARUS di atas `/produk/{slug}`: halaman ini punya tampilan sendiri
// (akordeon bergambar), dan catch-all di bawah akan menelannya diam-diam —
// halamannya tetap 200, cuma kembali jadi view informasi generik.
Route::get('/produk/produk-disdukcapil', [App\Http\Controllers\PublikController::class, 'produkDisdukcapil']);

Route::get('/produk/{slug}', [App\Http\Controllers\PublikController::class, 'produk'])->name('produk');
Route::get('/ppid/{slug}', [App\Http\Controllers\PublikController::class, 'ppid'])->name('ppid');

Route::get('/media/berita', [App\Http\Controllers\PublikController::class, 'beritaIndeks'])->name('berita.indeks');
Route::get('/media/berita/{slug}', [App\Http\Controllers\PublikController::class, 'beritaDetail'])->name('berita.detail');

// ── Pusat Bantuan & WBS (susunan mengikuti SIDAKO) ──────────────────────────
Route::get('/pusat-bantuan/{slug}', [App\Http\Controllers\PublikController::class, 'pusatBantuan'])
    ->name('pusat-bantuan');
Route::get('/wbs/{slug}', [App\Http\Controllers\PublikController::class, 'wbs'])->name('wbs');

// Kanal Pengaduan Masyarakat & WBS disatukan: isinya sama dan keduanya menyimpan
// ke endpoint yang sama. Tautan `/pengaduan` lama tetap hidup lewat redirect ini
// — footer, hasil pencarian, dan tautan yang sudah dibagikan warga menunjuk ke
// sana. 301, bukan 302: alamatnya memang pindah permanen.
Route::redirect('/pengaduan', '/wbs/tentang-wbs', 301);

// ── Hubungi Kami ────────────────────────────────────────────────────────────
// Tidak lagi di navbar (mengikuti SIDAKO) — ditautkan dari footer.
Route::get('/hubungi-kami', [App\Http\Controllers\PublikController::class, 'hubungiKami'])
    ->name('hubungi-kami');
Route::get('/hubungi-kami/{slug}', [App\Http\Controllers\PublikController::class, 'hubungiSlug']);

// ── Survei Kepuasan Masyarakat ──────────────────────────────────────────────
Route::get('/survei-kepuasan', [App\Http\Controllers\PublikController::class, 'survei'])
    ->name('survei-kepuasan');

// ── Media: GIS, demografi, peta ─────────────────────────────────────────────
// 🔴 Ketiganya HARUS di bawah `/media/berita` di atas — `/media/{slug}` tidak
// dipakai justru supaya tidak menelan alamat berita.
Route::get('/media/gis', [App\Http\Controllers\PublikController::class, 'gis'])->name('gis');
Route::get('/media/demografi', [App\Http\Controllers\PublikController::class, 'demografi'])
    ->name('demografi');
Route::redirect('/media/peta', '/media/gis', 301);
Route::redirect('/media/laporan-demografi', '/media/demografi', 301);
Route::redirect('/media/survey-kepuasan', '/survei-kepuasan', 301);

// ── Halaman ketentuan ───────────────────────────────────────────────────────
// Ditautkan dari footer setiap halaman, jadi keduanya wajib ada sejak awal —
// tautan footer yang 404 muncul di SELURUH situs sekaligus.
Route::get('/kebijakan-privasi', fn () => app(App\Http\Controllers\PublikController::class)
    ->ketentuan('kebijakan-privasi'))->name('kebijakan-privasi');
Route::get('/syarat', fn () => app(App\Http\Controllers\PublikController::class)
    ->ketentuan('syarat'))->name('syarat');
// Alamat lama yang masih beredar.
Route::redirect('/privasi', '/kebijakan-privasi', 301);

// ── Alamat lama portal Next.js ──────────────────────────────────────────────
// Halaman "Pelayanan Online" (grid layanan + 15 form berupa MODAL) sudah
// dipensiunkan: permohonan kini lewat dashboard sebagai halaman penuh. URL
// lamanya dipertahankan sebagai pengalihan supaya tautan & bookmark yang sudah
// beredar tidak mati — termasuk `?q=` dari kotak pencarian beranda.
Route::get('/permohonan-online', fn (Request $r) => redirect(
    filled($r->query('q'))
        ? '/user/pengajuan/baru?q='.urlencode((string) $r->query('q'))
        : '/user/pengajuan/baru'
));
Route::redirect('/riwayat', '/user/pengajuan', 301);

// ── Peta situs ──────────────────────────────────────────────────────────────
// Versi untuk MANUSIA (bukan `sitemap.xml`): daftar seluruh alamat publik dalam
// satu halaman, dipakai warga yang tidak menemukan menunya dan mesin pencari
// sebagai jaring tautan internal.
Route::get('/sitemap', [App\Http\Controllers\PublikController::class, 'petaSitus'])->name('sitemap');
