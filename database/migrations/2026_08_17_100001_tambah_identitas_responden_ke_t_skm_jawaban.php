<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identitas responden tambahan pada kuesioner SKM resmi dinas (Word 17 Agu 2026):
 * instansi, pendidikan terakhir, produk layanan yang diurus, dan pertanyaan
 * disabilitas.
 *
 * 🔴 ADITIF & NULLABLE — sama seperti `users.user_ktp` (12 Agu). Tabel ini sudah
 * berisi 204 responden kuesioner lama; kolom baru mereka tetap NULL dan rekap
 * tetap menghitung jawabannya (lihat `config/skm.php` → `warisan`).
 *
 * Disimpan sebagai KOLOM, bukan dijejalkan ke JSON `jawaban`, karena justru
 * angka inilah yang disaring dinas saat menyusun laporan: berapa responden
 * penyandang disabilitas, sebaran pendidikan, produk layanan mana yang paling
 * banyak dinilai. Di dalam JSON semuanya hanya bisa dihitung dengan membaca
 * seluruh baris di PHP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_skm_jawaban', function (Blueprint $table) {
            $table->string('instansi')->nullable()->after('nama');
            $table->string('pendidikan')->nullable()->after('jenis_kelamin');
            $table->string('produk_layanan')->nullable()->after('pekerjaan');
            // Tiga keadaan yang berbeda maknanya: ya, tidak, dan BELUM DITANYA
            // (204 responden kuesioner lama) — karena itu boolean nullable,
            // bukan default false yang diam-diam melaporkan mereka "bukan
            // penyandang disabilitas".
            $table->boolean('disabilitas')->nullable()->after('produk_layanan');
            $table->string('jenis_disabilitas')->nullable()->after('disabilitas');
        });
    }

    public function down(): void
    {
        Schema::table('t_skm_jawaban', function (Blueprint $table) {
            $table->dropColumn([
                'instansi', 'pendidikan', 'produk_layanan', 'disabilitas', 'jenis_disabilitas',
            ]);
        });
    }
};
