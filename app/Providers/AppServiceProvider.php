<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
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
    }
}
