<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master jenis layanan. Produksi berisi 17 baris, tapi hanya 15 yang punya
 * formulir (SAKINAH dan PENCETAKAN_KTP ada di master tanpa form) — jangan
 * mengasumsikan jumlah baris di sini sama dengan jumlah formulir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_jenis_permohonan', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('kode');
            $table->string('nama');
            $table->string('kategori'); // CAPIL | DAFDUK
            $table->boolean('aktif')->default(true);
            $table->integer('urutan')->default(0);

            $table->unique('kode', 'm_jenis_permohonan_kode_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_jenis_permohonan');
    }
};
