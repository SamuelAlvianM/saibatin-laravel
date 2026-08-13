<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Level/peran pengguna. Isi nyata di produksi ada EMPAT baris — bukan tiga
 * seperti yang tertulis di komentar schema.prisma:
 *   1 Super Admin · 2 Operator · 3 Warga · 4 Operator OPD
 * Level 4 memegang 140 akun, jadi jangan diperlakukan sebagai anomali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_userlevels', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('nama');
            $table->dateTime('created_at', 3)->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_userlevels');
    }
};
