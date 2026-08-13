<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit ringan tindakan petugas: siapa, aksi apa, objek apa, kapan.
 *
 * Hanya diisi untuk pelaku level 1/2 (petugas) — warga tidak dicatat. `ringkasan`
 * sudah berupa kalimat siap tampil ("Mengubah status REG… → SELESAI"), bukan
 * potongan data yang harus dirakit ulang di UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_log_aktivitas', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id'); // pelaku
            $table->string('aksi');     // BUAT|UBAH|HAPUS|UNGGAH|IMPOR|LAINNYA
            $table->string('entitas');  // Permohonan|Berita|Konten|Akun|Media|…
            $table->string('entitas_id')->nullable();
            $table->text('ringkasan');
            $table->string('ip_address')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();

            $table->index(['user_id', 'created_at'], 't_log_aktivitas_user_id_created_at_idx');
            $table->index('created_at', 't_log_aktivitas_created_at_idx');
            $table->foreign('user_id', 't_log_aktivitas_user_id_fkey')
                ->references('id')->on('users')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_log_aktivitas');
    }
};
