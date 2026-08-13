<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pesan di dalam sebuah tiket.
 *
 * 🔴 Di project saudara pernah terjadi kebocoran: daftar tiket tidak menyaring
 * kepemilikan sehingga warga bisa membaca tiket warga lain. Penyaringan
 * (`user_id` = pemilik, atau pembacanya petugas) wajib di query, bukan di UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_tiket_pesan', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('tiket_id');
            $table->integer('user_id'); // pengirim
            $table->text('isi');
            $table->dateTime('created_at', 3)->useCurrent();

            $table->index('tiket_id', 't_tiket_pesan_tiket_id_idx');
            $table->index('user_id', 't_tiket_pesan_user_id_fkey');
            $table->foreign('tiket_id', 't_tiket_pesan_tiket_id_fkey')
                ->references('id')->on('t_tiket')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id', 't_tiket_pesan_user_id_fkey')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_tiket_pesan');
    }
};
