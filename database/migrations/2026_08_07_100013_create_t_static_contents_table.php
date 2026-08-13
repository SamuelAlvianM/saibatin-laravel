<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konten statis yang bisa disunting dari dashboard (CMS ringan).
 *
 * Baris di tabel ini hanya meng-OVERRIDE nilai default; daftar blok beserta
 * nilai bawaannya ada di config (port dari lib/static-content-registry.ts).
 * Jadi tabel kosong = situs tetap tampil lengkap.
 *
 * ⚠️ Tabel ini juga menampung KONFIGURASI, bukan cuma konten:
 *   - `pelayanan.jam`         → jam layanan permohonan online
 *   - `pelayanan.visibilitas` → layanan mana yang ditampilkan
 * Jangan menyaring isinya hanya berdasarkan daftar blok konten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_static_contents', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('kunci'); // mis. "profil.visi-misi"
            $table->string('judul');
            $table->json('konten');
            $table->integer('updated_by')->nullable();
            $table->dateTime('updated_at', 3);
            $table->dateTime('created_at', 3)->useCurrent();

            $table->unique('kunci', 't_static_contents_kunci_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_static_contents');
    }
};
