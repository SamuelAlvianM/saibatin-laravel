<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Galeri foto kegiatan. `kategori`: BUPATI | PELAYANAN. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_galleries', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('judul');
            $table->string('kategori')->default('PELAYANAN');
            $table->string('gambar');
            $table->text('deskripsi')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_galleries');
    }
};
