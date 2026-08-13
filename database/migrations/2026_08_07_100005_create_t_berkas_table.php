<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lampiran permohonan (scan KTP/KK/akta).
 *
 * 🔴 `path` menyimpan URL publik berpola `/uploads/<layanan>/<uid>_<ts>.<ext>`,
 * TAPI berkas fisiknya TIDAK boleh berada di `public/`. Isinya dokumen identitas
 * warga; penyajiannya wajib lewat controller berkontrol akses (staff = semua,
 * warga = miliknya sendiri, sisanya 404). Kepemilikan dibaca dari prefix nama
 * berkas (`<uid>_`), jadi tidak perlu query tambahan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_berkas', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('permohonan_id');
            $table->string('nama_file'); // label dokumen, mis. "Kartu Keluarga"
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->integer('ukuran')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();

            $table->index('permohonan_id', 't_berkas_permohonan_id_fkey');
            $table->foreign('permohonan_id', 't_berkas_permohonan_id_fkey')
                ->references('id')->on('t_permohonan')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_berkas');
    }
};
