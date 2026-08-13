<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master wilayah Kabupaten Pesisir Barat — hierarki kecamatan → pekon/kelurahan
 * lewat `parent_id` yang menunjuk ke tabel ini sendiri.
 *
 * Ejaan kecamatan pernah diselaraskan ke nama resmi (BENGKUNAT BELIMBING → NGARAS,
 * PULAUPISANG → PULAU PISANG). Jangan mengembalikan ejaan lama: kolom
 * `users.user_kecamatan` menyimpan NAMA, bukan relasi, jadi perubahan ejaan
 * memutus kecocokan data warga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_wilayah', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('kode');
            $table->string('nama');
            $table->string('jenis'); // KECAMATAN | KELURAHAN
            $table->integer('parent_id')->nullable();

            $table->unique('kode', 'm_wilayah_kode_key');
            $table->index('jenis', 'm_wilayah_jenis_idx');
            $table->index('parent_id', 'm_wilayah_parent_id_fkey');
            $table->foreign('parent_id', 'm_wilayah_parent_id_fkey')
                ->references('id')->on('m_wilayah')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_wilayah');
    }
};
