<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pengaduan masyarakat (termasuk jalur WBS). `status`: BARU | DIPROSES | SELESAI. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_pengaduanmasyarakat', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('nama');
            $table->string('nik')->nullable();
            $table->string('email')->nullable();
            $table->string('hp')->nullable();
            $table->string('subjek')->nullable();
            $table->text('isi');
            $table->string('status')->default('BARU');
            $table->text('balasan')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('updated_at', 3);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_pengaduanmasyarakat');
    }
};
