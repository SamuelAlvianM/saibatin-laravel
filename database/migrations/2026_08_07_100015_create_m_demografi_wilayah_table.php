<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agregat demografi per wilayah, hasil impor Excel Dukcapil (SIAK).
 *
 * `kategori` = jenis-kelamin | agama | gol-darah | pekerjaan | kk | pendidikan |
 *              status-kawin | wajib-ktp
 * `level`    = 4 kecamatan · 5 pekon/kelurahan (hierarki lewat `parent_kode`)
 * `data`     = kolom nilai per kategori, mis. { L, P, JML } untuk jenis kelamin.
 *
 * Tanpa `created_at` — barisnya di-upsert per impor, hanya `updated_at` yang berarti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_demografi_wilayah', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('kategori');
            $table->string('kode');
            $table->string('wilayah');
            $table->integer('level');
            $table->string('parent_kode')->nullable();
            $table->json('data');
            $table->dateTime('updated_at', 3);

            $table->unique(['kategori', 'kode'], 'm_demografi_wilayah_kategori_kode_key');
            $table->index(['kategori', 'level'], 'm_demografi_wilayah_kategori_level_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_demografi_wilayah');
    }
};
