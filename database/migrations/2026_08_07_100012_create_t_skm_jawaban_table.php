<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Survei Kepuasan Masyarakat. `jawaban` (JSON) = {kunciPertanyaan: nilai}.
 *
 * ⚠️ Data warisan memakai kunci `u0`–`u8` sementara formulir baru memakai kunci
 * lain. Di project saudara (TIDORE) hal ini pernah membuat nilai IKM tampil
 * 0,00 / mutu D karena 107 responden lama tak ikut terhitung. Perhitungan IKM
 * harus menerima KEDUA bentuk kunci, dan rumusnya NRR tertimbang × 25 —
 * bukan sekadar rata-rata dibagi 5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_skm_jawaban', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('nama')->nullable();
            $table->integer('umur')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->json('jawaban');
            $table->text('saran')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_skm_jawaban');
    }
};
