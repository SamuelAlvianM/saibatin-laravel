<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel sesi Laravel.
 *
 * Migration bawaan `create_users_table` sengaja DIBUANG: tabel `users` portal ini
 * bukan bentuk bawaan Laravel (lihat migration `create_users_table` kita sendiri —
 * kolomnya user_id/userlevel_id/user_fullname/status, warisan Laravel 9 asli).
 * `password_reset_tokens` juga tidak dipakai: alur lupa-sandi portal memakai kolom
 * `forgotten_code` + `forgotten_time` di tabel `users`.
 *
 * Driver sesi `database` dipilih karena target hosting cPanel tidak punya Redis dan
 * driver `file` boros inode.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // Sengaja integer biasa (bukan foreignId/bigint) agar cocok dengan
            // `users.id` yang bertipe `int` signed. Tanpa constraint FK — Laravel
            // memang hanya mengindeksnya.
            $table->integer('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
