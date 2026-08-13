<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom `users.user_ktp` — foto/scan KTP pendaftar.
 *
 * Padanan `deploy/sql/2026-08-08_tambah-kolom-user-ktp.sql` di portal Next.js.
 *
 * ⚠️ Kolom ini TIDAK ikut terbaca saat Fase 1 memotret skema: DB kerja
 * `saibatin_lv` diklon dari DB dev Next.js SEBELUM SQL itu dijalankan, jadi
 * perbandingan "0 baris beda" waktu itu memang benar untuk keadaan saat itu.
 * Sumber kebenarannya sekarang `prisma/schema.prisma`, yang sudah memuatnya.
 * Petugas memakainya untuk menyandingkan selfie dengan KTP saat verifikasi akun.
 *
 * Aditif & nullable — tidak menyentuh satu baris data pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_ktp')->nullable()->after('user_foto');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_ktp');
        });
    }
};
