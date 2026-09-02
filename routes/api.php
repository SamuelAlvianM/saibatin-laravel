<?php

use App\Http\Controllers\Api\Admin\BeritaAdminController;
use App\Http\Controllers\Api\Admin\DemografiAdminController;
use App\Http\Controllers\Api\Admin\GaleriAdminController;
use App\Http\Controllers\Api\Admin\KontenStatisController;
use App\Http\Controllers\Api\Admin\LogAktivitasController;
use App\Http\Controllers\Api\Admin\MasterController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\PengaduanAdminController;
use App\Http\Controllers\Api\Admin\PengaturanController;
use App\Http\Controllers\Api\Admin\PermohonanAdminController;
use App\Http\Controllers\Api\Admin\ProdukAdminController;
use App\Http\Controllers\Api\Admin\SkmAdminController;
use App\Http\Controllers\Api\Admin\StatistikEksporController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\AspirasiController;
use App\Http\Controllers\Api\KontenController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\PendudukController;
use App\Http\Controllers\Api\PermohonanController;
use App\Http\Controllers\Api\ProfilController;
use App\Http\Controllers\Api\SistemController;
use App\Http\Controllers\Api\StatistikController;
use App\Http\Controllers\Api\UnggahController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Endpoint JSON — prefix /api
|--------------------------------------------------------------------------
|
| 🔴 Berkas ini dimuat di dalam grup middleware **web**, bukan `api`. Bukan
| kelalaian: autentikasi portal ini memakai **sesi cookie**, sama seperti portal
| Next.js. Grup `api` bawaan Laravel bersifat stateless — `$request->user()`
| akan selalu null di sana, dan seluruh endpoint berizin di bawah ini akan
| menolak pengguna yang jelas-jelas sudah login.
|
| Konsekuensinya: setiap POST/PUT/PATCH/DELETE **wajib membawa token CSRF**.
| Sisi klien memakai `resources/js/lib/api.js` yang sudah mengurusnya.
|
| Seluruh balasan memakai kontrak warisan `{ error[], success[], data, html[] }`
| lewat App\Support\Balasan — dipertahankan supaya klien lama tetap kompatibel.
|
*/

// ── Publik ──────────────────────────────────────────────────────────────────
Route::get('/health', [SistemController::class, 'health']);
Route::get('/auth/session', [SistemController::class, 'sesi']);
Route::get('/jenis-permohonan', [SistemController::class, 'jenisPermohonan']);
Route::get('/wilayah', [SistemController::class, 'wilayah']);
Route::get('/jam-layanan', [SistemController::class, 'jamLayanan']);
Route::get('/static-content', [SistemController::class, 'kontenStatis']);

Route::get('/kunjungan', [SistemController::class, 'kunjunganStatistik']);
Route::post('/kunjungan', [SistemController::class, 'kunjunganPing']);

Route::get('/berita', [KontenController::class, 'berita']);
Route::get('/berita/{slug}', [KontenController::class, 'beritaDetail']);
Route::get('/galeri', [KontenController::class, 'galeri']);
Route::get('/demografi', App\Http\Controllers\Api\DemografiController::class);
// Menambah foto memakai jalur yang sama dengan pembacaannya (seperti aslinya),
// jadi izinnya dipasang per-rute, bukan lewat grup.
Route::middleware(['auth', 'peran:1'])->post('/galeri', [GaleriAdminController::class, 'store']);

Route::get('/stats', StatistikController::class);

Route::get('/skm/unsur', [AspirasiController::class, 'unsurSkm']);

// Endpoint publik yang MENULIS — semuanya lewat reCAPTCHA di controller,
// plus pembatas laju di sini karena tidak ada sesi yang bisa dijadikan pegangan.
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/pengaduan', [AspirasiController::class, 'kirimPengaduan']);
    // 🔴 HARUS di atas catch-all `{layanan}/{aksi}` di bawah — polanya cocok
    // dengan `pengaduan/upload` dan akan menelannya kalau urutannya terbalik.
    // Publik (tanpa sesi) karena pelapor WBS boleh anonim; berkasnya tetap
    // masuk storage privat dan hanya petugas yang bisa membukanya.
    Route::post('/pengaduan/upload', App\Http\Controllers\Api\BuktiPengaduanController::class);
    Route::post('/kritik-saran', [AspirasiController::class, 'kirimKritik']);
    Route::post('/skm', [AspirasiController::class, 'kirimSkm']);
    Route::post('/auth/check-nik', [PendudukController::class, 'cekNikKk']);
});

// ── Berizin (butuh sesi) ────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfilController::class, 'tampilkan']);
    Route::put('/profil', [ProfilController::class, 'perbarui']);
    Route::post('/profil/change-password', [ProfilController::class, 'gantiSandi']);
    Route::put('/profil/foto', [ProfilController::class, 'simpanFoto']);
    Route::delete('/profil/foto', [ProfilController::class, 'hapusFoto']);

    Route::get('/notifikasi', [NotifikasiController::class, 'index']);
    Route::patch('/notifikasi', [NotifikasiController::class, 'tandaiSemua']);
    Route::patch('/notifikasi/{id}', [NotifikasiController::class, 'tandaiSatu'])->whereNumber('id');

    Route::get('/permohonan', [PermohonanController::class, 'index']);
    Route::post('/permohonan', [PermohonanController::class, 'store']);

    // Tanda terima PDF. Aman dari catch-all `{layanan}/{aksi}` di bawah karena
    // itu hanya menangkap POST dua segmen; ini GET dan tiga segmen. Tetap
    // didaftarkan di sini, bukan di bawah, supaya urutannya tidak jadi jebakan
    // saat rute lain ditambahkan.
    Route::get('/permohonan/{id}/pdf', App\Http\Controllers\Api\PermohonanPdfController::class)
        ->whereNumber('id');

    Route::post('/penduduk/check', [PendudukController::class, 'cek']);
    Route::post('/upload', UnggahController::class);

    // Daftar aspirasi hanya untuk petugas — pemeriksaan levelnya di controller
    // (balasannya 403 dengan kontrak yang sama, bukan halaman error Laravel).
    Route::get('/pengaduan', [AspirasiController::class, 'daftarPengaduan']);
    Route::get('/kritik-saran', [AspirasiController::class, 'daftarKritik']);
});

/*
|--------------------------------------------------------------------------
| Dashboard petugas — /api/admin/** (Fase 5)
|--------------------------------------------------------------------------
|
| Gerbangnya `peran:petugas` (level 1 & 2) di satu tempat, bukan `if` yang
| diulang di tiap metode controller. Yang lebih ketat dari itu — log aktivitas
| dan pengaturan pelayanan — dipersempit lagi jadi `peran:1`.
|
| ⚠️ Blok ini HARUS tetap berada di ATAS catch-all `{layanan}/{aksi}` di bawah.
|
*/
/*
 * Membaca daftar & detail permohonan — petugas DAN Operator OPD.
 *
 * 🔴 Grup terpisah, dan `PATCH` sengaja TIDAK ikut: Operator OPD mengajukan
 * permohonan, bukan memprosesnya. Ia tetap di grup `peran:petugas` di bawah.
 *
 * ⚠️ Pagar KEPEMILIKAN ada di `PermohonanAdminController`, bukan di sini —
 * middleware cuma tahu peran, tidak tahu baris mana milik siapa. Controller
 * itulah yang mempersempit daftar OPD ke permohonannya sendiri dan menjawab
 * 404 (bukan 403) untuk nomor milik orang lain.
 */
Route::middleware(['auth', 'peran:petugas,opd'])->prefix('admin')->group(function () {
    Route::get('/permohonan', [PermohonanAdminController::class, 'index']);
    Route::get('/permohonan/{id}', [PermohonanAdminController::class, 'show'])->whereNumber('id');
});

Route::middleware(['auth', 'peran:petugas'])->prefix('admin')->group(function () {
    Route::patch('/permohonan/{id}', [PermohonanAdminController::class, 'update'])->whereNumber('id');

    Route::get('/users', [UserAdminController::class, 'index']);
    Route::post('/users', [UserAdminController::class, 'store']);
    Route::patch('/users', [UserAdminController::class, 'ubahStatus']);
    Route::get('/users/{id}', [UserAdminController::class, 'show'])->whereNumber('id');
    // PUT, bukan PATCH:  PATCH sudah dipakai `ubahStatus` (aksi, bukan
    // sunting data). Memisahkannya membuat dua hal yang berbeda tetap terbaca
    // berbeda di daftar rute.
    Route::put('/users/{id}', [UserAdminController::class, 'update'])->whereNumber('id');
    // Setel ulang sandi — endpoint sendiri, bukan kolom di `update()`: ini
    // tindakan sekali jalan yang tidak punya "nilai sebelumnya", dan tidak boleh
    // ikut terbawa saat petugas cuma membetulkan email.
    Route::post('/users/{id}/sandi', [UserAdminController::class, 'setelSandi'])->whereNumber('id');
    Route::delete('/users/{id}', [UserAdminController::class, 'destroy'])->whereNumber('id');

    Route::get('/pengaduan', [PengaduanAdminController::class, 'index']);
    Route::patch('/pengaduan/{id}', [PengaduanAdminController::class, 'update'])->whereNumber('id');

    Route::get('/skm', SkmAdminController::class);

    // Unduhan .xlsx, bukan JSON — tombol Excel di tiap kartu dashboard.
    Route::get('/statistik/export', StatistikEksporController::class);

    // ── Khusus Super Admin ──────────────────────────────────────────────────
    Route::middleware('peran:1')->group(function () {
        /*
         * 🔴 Buka kunci permohonan final — dipersempit ke Super Admin 2 Sep 2026
         * bersama halamannya di `web.php`. Keduanya HARUS sepakat: menyempitkan
         * halaman saja membuat menunya hilang dari layar sementara endpoint ini
         * tetap menerima kiriman dari siapa pun yang tahu alamatnya.
         */
        Route::post('/master', [MasterController::class, 'bukaKunci']);

        Route::get('/log-aktivitas', [LogAktivitasController::class, 'index']);

        Route::delete('/galeri/{id}', [GaleriAdminController::class, 'destroy'])->whereNumber('id');

        // 🔴 `demografi/export`, `import`, `parse` didaftarkan SEBELUM
        // `demografi` polos supaya tidak tertukar, dan seluruhnya sebelum
        // catch-all layanan di bawah.
        Route::get('/demografi/export', [DemografiAdminController::class, 'ekspor']);
        Route::post('/demografi/import', [DemografiAdminController::class, 'impor']);
        Route::post('/demografi/parse', [DemografiAdminController::class, 'pratinjau']);
        Route::get('/demografi', [DemografiAdminController::class, 'index']);
        Route::put('/demografi', [DemografiAdminController::class, 'simpan']);
        Route::delete('/demografi', [DemografiAdminController::class, 'hapus']);

        // Bentuk formulir + isi satu blok (dialog Mode Edit di halaman publik).
        Route::get('/static-content', [KontenStatisController::class, 'skema']);
        Route::put('/static-content', [KontenStatisController::class, 'simpan']);

        Route::get('/produk', [ProdukAdminController::class, 'index']);
        Route::post('/produk', [ProdukAdminController::class, 'store']);
        Route::delete('/produk/{id}', [ProdukAdminController::class, 'destroy'])->whereNumber('id');

        Route::get('/berita', [BeritaAdminController::class, 'index']);
        Route::post('/berita', [BeritaAdminController::class, 'store']);
        Route::put('/berita/{id}', [BeritaAdminController::class, 'update'])->whereNumber('id');
        Route::delete('/berita/{id}', [BeritaAdminController::class, 'destroy'])->whereNumber('id');

        Route::get('/jam-layanan', [PengaturanController::class, 'jamLayanan']);
        Route::put('/jam-layanan', [PengaturanController::class, 'simpanJamLayanan']);

        Route::get('/pelayanan-visibilitas', [PengaturanController::class, 'visibilitas']);
        Route::put('/pelayanan-visibilitas', [PengaturanController::class, 'simpanVisibilitas']);
    });
});

/*
|--------------------------------------------------------------------------
| Pustaka media — /api/media (BUKAN /api/admin/media)
|--------------------------------------------------------------------------
|
| Jalurnya mengikuti portal Next.js apa adanya supaya komponen pemilih media
| tidak perlu diubah. Khusus Super Admin.
|
| 🔴 Harus di ATAS catch-all layanan di bawah: `POST /api/media/upload` cocok
| dengan pola `{layanan}/{aksi}` dan akan ditelan kalau urutannya terbalik.
|
*/
Route::middleware(['auth', 'peran:1'])->prefix('media')->group(function () {
    Route::get('/', [MediaController::class, 'index']);
    Route::post('/upload', [MediaController::class, 'unggah']);
    Route::delete('/{id}', [MediaController::class, 'hapus']);
});

/*
|--------------------------------------------------------------------------
| Catch-all 15 layanan permohonan — POST /api/{layanan}/{aksi}
|--------------------------------------------------------------------------
|
| 🔴 HARUS DIDAFTARKAN PALING AKHIR. Polanya `{layanan}/{aksi}` akan menelan
| rute dua-segmen mana pun yang didaftarkan sesudahnya (mis. `auth/session`,
| `profil/foto`, `skm/unsur`). Laravel memakai rute pertama yang cocok, jadi
| urutan di berkas ini menentukan.
|
| Slug yang tak dikenal ditolak controller dengan 404 — bukan diteruskan.
|
*/
Route::middleware('auth')->post('/{layanan}/{aksi}', App\Http\Controllers\Api\LayananController::class)
    ->where('layanan', '[a-z0-9-]+')
    ->where('aksi', '[a-zA-Z]+');

/*
|--------------------------------------------------------------------------
| Belum dibangun di fase ini — dan alasannya
|--------------------------------------------------------------------------
|
| /api/permohonan/{id}/pdf → Fase 8 (dompdf)
| /api/ocr/ktp             → DIHAPUS — OCR pindah ke browser (keputusan user)
| /api/tiket/**            → DIHAPUS — fitur tiket & chat dibatalkan user, 13 Agu
| /api/otp/*, /api/auth/*  → sudah ada di routes/web.php (Fase 2)
|
*/
