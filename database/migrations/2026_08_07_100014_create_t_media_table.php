<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pustaka media terpusat untuk dashboard (gambar & PDF).
 *
 * `id` = UUID v4 (string), bukan auto-increment — sengaja, supaya nama berkas
 * fisik tidak bisa ditebak. Berkas disimpan di storage (di luar public) dengan
 * path relatif `yyyy/mm/uuid.ext` dan disajikan lewat route `/uploads/media/…`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_media', function (Blueprint $table) {
            $table->string('id')->primary(); // uuid v4
            $table->string('nama_asli');
            $table->string('nama_file'); // uuid.ext
            $table->string('mime_type');
            $table->integer('ukuran');
            $table->integer('lebar')->nullable();
            $table->integer('tinggi')->nullable();
            $table->string('path'); // relatif: yyyy/mm/uuid.ext
            $table->string('url');  // publik: /uploads/media/yyyy/mm/uuid.ext
            $table->integer('uploaded_by')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();

            $table->index('mime_type', 't_media_mime_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_media');
    }
};
