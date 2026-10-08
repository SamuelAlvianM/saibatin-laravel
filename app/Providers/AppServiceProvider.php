<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Panjang string bawaan 191, bukan 255 seperti default Laravel.
        //
        // Seluruh tabel portal ini dibuat Prisma, yang memakai varchar(191).
        // Tanpa baris ini, `$table->string()` menghasilkan varchar(255) dan skema
        // hasil migration MELESET dari 20 tabel produksi yang sudah berisi data —
        // satu-satunya perbedaan yang tersisa saat kedua skema dibandingkan.
        // Kolom yang memang butuh lain (t_produk.judul 255, t_kunjungan.visitor_id 64)
        // menuliskan panjangnya secara eksplisit, jadi tidak terpengaruh.
        Schema::defaultStringLength(191);

        // Relasi harus dimuat eksplisit. Portal ini banyak menampilkan tabel
        // ratusan baris (permohonan, akun); lazy loading yang lolos ke produksi
        // berubah jadi N+1 yang tak terlihat saat data dev masih sedikit.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Navbar publik perlu tahu siapa yang sedang login (tombol "Login/Daftar"
        // vs nama pengguna + menu akun). Ditaruh di composer, bukan di tiap
        // controller: setiap halaman publik memakai layout yang sama, dan
        // menyalin baris ini ke belasan controller cuma menunggu ada yang lupa.
        //
        // Yang dikirim SENGAJA cuma tiga kolom. Prop island tercetak sebagai
        // atribut HTML di sumber halaman — apa pun yang ditaruh di sini bisa
        // dibaca siapa saja yang membuka "view source".
        /*
         * Pembatas laju endpoint tamu di API mobile (`routes/mobile.php`).
         *
         * 🔴 Bukan `throttle:5,1` seperti di website, dan alasannya CGNAT.
         * `throttle:X,Y` polos memberi tamu kunci `domain|IP` saja — tanpa nama
         * rute — jadi login, daftar, lupa sandi, dan ajukan ulang berbagi SATU
         * penghitung per IP. Operator seluler menaruh ribuan pelanggan di
         * belakang satu IP publik; dengan kunci itu, lima warga yang login dari
         * jaringan yang sama dalam semenit membuat warga keenam terkunci.
         * Terbukti saat uji ujung-ke-ujung 8 Okt 2026.
         *
         * Di sini batas ketatnya per RUTE per AKUN (NIK/username/email/HP yang
         * dikirim) — tebak-sandi terhadap satu akun tetap tertahan — ditambah
         * batas per IP yang longgar sebagai rem serangan massal.
         */
        $identitas = fn (Request $r) => strtolower(trim((string) (
            $r->input('user_id') ?? $r->input('nik') ?? $r->input('email') ?? $r->input('hp') ?? ''
        ))) ?: 'ip:'.$r->ip();

        RateLimiter::for('mobile-ketat', fn (Request $r) => [
            Limit::perMinute(5)->by('akun|'.$r->path().'|'.$identitas($r)),
            Limit::perMinute(60)->by('ip|'.$r->path().'|'.$r->ip()),
        ]);

        RateLimiter::for('mobile-longgar', fn (Request $r) => [
            Limit::perMinute(20)->by('akun|'.$r->path().'|'.$identitas($r)),
            Limit::perMinute(120)->by('ip|'.$r->path().'|'.$r->ip()),
        ]);

        View::composer('publik.layout', function ($view) {
            $u = Auth::user();

            $view->with('navbar', [
                'user' => $u ? [
                    'nama' => $u->user_fullname,
                    'user_id' => $u->user_id,
                    'level' => (int) $u->userlevel_id,
                ] : null,
            ]);
        });
    }
}
