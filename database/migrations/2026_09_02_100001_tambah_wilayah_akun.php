<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alamat wilayah akun: tambah desa/kelurahan & kabupaten.
 *
 * 🔴 KENAPA. Saringan wilayah pada daftar permohonan membaca wilayah dari AKUN
 * pengajunya — itu satu-satunya tempat wilayah tercatat per baris. Sebelum
 * migrasi ini, `users` hanya punya `user_kecamatan`, dan itu pun cuma terisi
 * pada 2 dari 1.390 akun karena `UserAdminController` sengaja menulis `null`
 * untuk setiap akun non-warga.
 *
 * Akibatnya saringan wilayah akan tampil rapi dengan 11 kecamatan dan menyaring
 * 11.919 permohonan menjadi 16 — tanpa galat, tanpa apa pun yang terlihat rusak.
 *
 * Akun Operator OPD mewakili SEBUAH DESA/KELURAHAN (`bk.tanjungrejo`,
 * `ngm.gedungcahyakuningan`, …), jadi kecamatan saja tidak cukup untuk
 * membedakan 118 desa di Pesisir Barat — dan wilayah itulah yang menentukan
 * cakupan sebuah akun.
 *
 * ⚠️ Yang disimpan **NAMA**, bukan id — sama seperti `user_kecamatan` yang
 * sudah ada, supaya keduanya konsisten. Konsekuensinya juga sama: mengganti
 * ejaan di `m_wilayah` memutus kecocokan. Itu pilihan sadar, bukan kelalaian;
 * mengubahnya jadi id berarti menulis ulang kolom yang sudah dipakai.
 *
 * `user_kabupaten` diberi nilai awal untuk seluruh akun yang sudah ada —
 * portal ini melayani satu kabupaten saja, jadi mengosongkannya hanya membuat
 * kolom yang selalu harus ditebak pembacanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_kelurahan')->nullable()->after('user_kecamatan');
            $table->string('user_kabupaten')->nullable()->after('user_kelurahan');
        });

        DB::table('users')->update(['user_kabupaten' => 'PESISIR BARAT']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_kelurahan', 'user_kabupaten']);
        });
    }
};
