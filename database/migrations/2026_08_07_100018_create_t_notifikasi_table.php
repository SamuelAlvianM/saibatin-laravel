<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifikasi in-app (lonceng navbar/dashboard).
 *
 * Sengaja BUKAN tabel `notifications` bawaan Laravel: bentuknya sudah dipakai
 * 94 baris produksi dan UI membaca kolomnya langsung (tipe/judul/isi/link/dibaca).
 * `link` = tujuan saat diklik; halaman tujuan menyorot baris terkait lewat `?sorot=<id>`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_notifikasi', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('user_id'); // penerima
            $table->string('tipe');     // PERMOHONAN_STATUS|PERMOHONAN_BARU|PENGADUAN_BARU|KRITIK_BARU|AKUN_BARU
            $table->string('judul');
            $table->text('isi');
            $table->string('link')->nullable();
            $table->string('ref_type')->nullable();
            $table->integer('ref_id')->nullable();
            $table->boolean('dibaca')->default(false);
            $table->dateTime('created_at', 3)->useCurrent();

            $table->index(['user_id', 'dibaca'], 't_notifikasi_user_id_dibaca_idx');
            $table->index(['user_id', 'created_at'], 't_notifikasi_user_id_created_at_idx');
            $table->foreign('user_id', 't_notifikasi_user_id_fkey')
                ->references('id')->on('users')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_notifikasi');
    }
};
