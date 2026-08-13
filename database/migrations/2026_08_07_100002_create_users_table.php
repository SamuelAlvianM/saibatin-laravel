<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengguna portal (warga & petugas). Bentuknya BUKAN `users` bawaan Laravel —
 * ini rekonstruksi tabel Laravel 9 asli (app.pesbar.002) yang dipertahankan utuh
 * lewat Prisma, dan sekarang dipertahankan lagi di sini.
 *
 * Yang tidak boleh diubah tanpa migrasi data:
 * - `user_id` = identitas login: NIK 16 digit untuk warga, USERNAME untuk OPD/petugas.
 * - `password` = hash bcrypt cost 10 (`$2a$`/`$2y$`) — Hash::check() Laravel membacanya,
 *   jadi 1.386 akun lama tetap bisa login tanpa reset.
 * - `status` = 0 Menunggu · 1 Aktif · 2 Ditolak · 3 Nonaktif. Hanya 1 boleh login.
 * - `ket` = alasan penolakan/nonaktif (diurai lagi jadi kolom + kalimat, lihat akun-tolak).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('user_id');
            $table->string('password');
            $table->integer('userlevel_id')->default(3);
            $table->string('user_fullname')->nullable();
            $table->string('user_nik')->nullable();
            $table->string('user_nokk')->nullable();
            $table->string('user_hp')->nullable();
            $table->string('user_email')->nullable();
            $table->string('activation_code')->nullable();
            $table->text('activation_code_url')->nullable();
            $table->dateTime('activation_time', 3)->nullable();
            $table->string('forgotten_code')->nullable();
            $table->dateTime('forgotten_time', 3)->nullable();
            $table->dateTime('login_last', 3)->nullable();
            $table->string('ip_address')->nullable();
            $table->integer('status')->default(0);
            $table->text('ket')->nullable();
            $table->dateTime('email_verified_at', 3)->nullable();
            $table->string('remember_token')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->integer('updated_by')->nullable();
            $table->dateTime('updated_at', 3);
            // Ditambahkan 29 Juli 2026 (lihat deploy/sql/2026-07-29_user-foto-kecamatan.sql).
            $table->string('user_foto')->nullable();
            $table->string('user_kecamatan')->nullable();

            // Nama index dipertahankan persis seperti yang dibuat Prisma, supaya
            // skema hasil migration ini dapat dipertukarkan dengan DB yang sudah ada.
            $table->index('user_id', 'users_user_id_idx');
            $table->index('user_nik', 'users_user_nik_idx');
            $table->index('status', 'users_status_idx');
            $table->index('userlevel_id', 'users_userlevel_id_fkey');
            $table->foreign('userlevel_id', 'users_userlevel_id_fkey')
                ->references('id')->on('m_userlevels')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
