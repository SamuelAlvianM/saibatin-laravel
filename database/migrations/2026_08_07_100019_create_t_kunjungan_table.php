<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pencacah kunjungan situs publik — satu baris per pengunjung (cookie anonim)
 * per hari. Kartu "Pengunjung" di dashboard membacanya sebagai:
 *   online    = last_seen dalam 5 menit terakhir
 *   hari ini  = baris bertanggal hari ini
 *   total     = jumlah seluruh hits
 *
 * ⚠️ "Hari ini" harus dihitung pada zona waktu aplikasi (Asia/Jakarta), bukan
 * zona server. Di project saudara, MySQL yang restart tanpa `default-time-zone`
 * terkunci pernah menggeser 17 kolom tanggal sejauh +9 jam.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_kunjungan', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('visitor_id', 64);
            $table->date('tanggal');
            $table->integer('hits')->default(1);
            $table->dateTime('last_seen', 3);

            $table->unique(['visitor_id', 'tanggal'], 't_kunjungan_visitor_id_tanggal_key');
            $table->index('tanggal', 't_kunjungan_tanggal_idx');
            $table->index('last_seen', 't_kunjungan_last_seen_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_kunjungan');
    }
};
