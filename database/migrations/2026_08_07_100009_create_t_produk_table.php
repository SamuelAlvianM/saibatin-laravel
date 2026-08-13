<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen publikasi: HUKUM | PERSYARATAN | SOP | STANDAR_PELAYANAN | DAFDUK.
 *
 * `judul` sengaja varchar(255), bukan 191 bawaan: nama resmi produk hukum
 * (Permendagri dengan klausul "perubahan atas …") menembus 200 karakter.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_produk', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('jenis');
            $table->string('judul', 255);
            $table->longText('konten')->nullable();
            $table->string('file')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('updated_at', 3)->useCurrent();
            $table->integer('uploaded_by')->nullable();
            $table->string('uploaded_by_name')->nullable();

            $table->index('jenis', 't_produk_jenis_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_produk');
    }
};
