<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tiket bantuan (chat warga ↔ petugas, dan antar-petugas lewat kategori INTERNAL).
 *
 * `updated_at` = waktu aktivitas terakhir dan menjadi dasar auto-close setelah
 * TIKET_AUTO_CLOSE_DAYS hari. Auto-close dicek MALAS (saat daftar tiket dibuka),
 * bukan lewat worker — target hosting cPanel tidak punya proses hidup terus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_tiket', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('nomor'); // TKT<timestamp>
            $table->integer('user_id'); // pembuat tiket
            $table->string('subjek');
            $table->string('kategori')->default('LAYANAN'); // LAYANAN|TEKNIS|INTERNAL
            $table->string('status')->default('TERBUKA');   // TERBUKA|TERTUTUP
            $table->dateTime('closed_at', 3)->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('updated_at', 3);

            $table->unique('nomor', 't_tiket_nomor_key');
            $table->index('user_id', 't_tiket_user_id_idx');
            $table->index('status', 't_tiket_status_idx');
            $table->foreign('user_id', 't_tiket_user_id_fkey')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_tiket');
    }
};
