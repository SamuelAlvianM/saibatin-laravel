<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengajuan layanan oleh warga — SATU tabel untuk seluruh jenis permohonan.
 *
 * Kunci desainnya ada di `payload` (JSON): field yang berbeda-beda per jenis
 * layanan disimpan di sana, bukan sebagai kolom. Portal Laravel 9 asli memakai
 * satu tabel per layanan (t_kelahiran_1, t_kematian, t_kk_pisahkk, …); bentuk
 * satu-tabel ini hasil ETL migrasi dan HARUS dipertahankan — 11.902 baris
 * produksi sudah berbentuk begini.
 *
 * `proses_by_name` sengaja denormalized: nama petugas tetap terbaca walau
 * akunnya kelak berubah atau dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_permohonan', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('no_register');
            $table->integer('user_id');
            $table->integer('jenis_id');
            $table->string('status')->default('MENUNGGU'); // MENUNGGU|DIPROSES|SELESAI|DITOLAK
            $table->json('payload')->nullable();
            $table->text('catatan')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('updated_at', 3);
            $table->dateTime('proses_at', 3)->nullable();
            $table->integer('proses_by')->nullable();
            $table->string('proses_by_name')->nullable();

            $table->unique('no_register', 't_permohonan_no_register_key');
            $table->index('user_id', 't_permohonan_user_id_idx');
            $table->index('status', 't_permohonan_status_idx');
            $table->index('jenis_id', 't_permohonan_jenis_id_fkey');
            $table->foreign('jenis_id', 't_permohonan_jenis_id_fkey')
                ->references('id')->on('m_jenis_permohonan')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id', 't_permohonan_user_id_fkey')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_permohonan');
    }
};
