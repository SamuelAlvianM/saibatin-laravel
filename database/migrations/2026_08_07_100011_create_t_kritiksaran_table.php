<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kritik & saran. Tanpa `updated_at` — memang sekali kirim, tidak pernah disunting. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_kritiksaran', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('nama');
            $table->string('email')->nullable();
            $table->string('hp')->nullable();
            $table->text('pesan');
            $table->dateTime('created_at', 3)->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_kritiksaran');
    }
};
