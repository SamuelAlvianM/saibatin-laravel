<?php

use App\Support\PeriodeDemografi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Beri dimensi waktu pada `m_demografi_wilayah`: tahun + semester.
 *
 * 🔴 MASALAH YANG DIPERBAIKI. Kunci unik lama `(kategori, kode)` memaksa satu
 * baris per wilayah per kategori. Impor mengganti total isi kategorinya, jadi
 * mengunggah DKB semester berikutnya MENGHAPUS semester sebelumnya. Dinas
 * kehilangan datanya, dan tidak ada satu pun cara membandingkan dua semester.
 *
 * ⚠️ BARIS LAMA DITANDAI Semester II 2024, bukan periode kosong. Badge beranda
 * keempat portal selama ini memang tertulis "DKB Semester II 2024"; menandainya
 * demikian hanya menuliskan apa yang sudah diakui halaman depan. Membiarkannya
 * NULL akan membuat data yang sah tidak muncul di pemilih periode mana pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('m_demografi_wilayah', function (Blueprint $table) {
            // Sementara nullable — diisi dulu, baru dijadikan wajib. Kalau
            // langsung NOT NULL, MySQL mengisinya 0 dan periode "tahun 0"
            // itu ikut muncul di pemilih.
            $table->smallInteger('tahun')->nullable()->after('kategori');
            $table->tinyInteger('semester')->nullable()->after('tahun');
        });

        DB::table('m_demografi_wilayah')->whereNull('tahun')->update([
            'tahun' => PeriodeDemografi::TAHUN_BAWAAN,
            'semester' => PeriodeDemografi::SEMESTER_BAWAAN,
        ]);

        Schema::table('m_demografi_wilayah', function (Blueprint $table) {
            $table->smallInteger('tahun')->nullable(false)->change();
            $table->tinyInteger('semester')->nullable(false)->change();
        });

        Schema::table('m_demografi_wilayah', function (Blueprint $table) {
            $table->dropUnique('m_demografi_wilayah_kategori_kode_key');
            $table->unique(
                ['kategori', 'tahun', 'semester', 'kode'],
                'm_demografi_wilayah_periode_kode_key',
            );
            $table->index(
                ['kategori', 'tahun', 'semester', 'level'],
                'm_demografi_wilayah_periode_level_idx',
            );
        });
    }

    public function down(): void
    {
        /*
         * ⚠️ Turun berarti MEMBUANG seluruh periode selain yang bawaan — kunci
         * unik lama tidak sanggup menampung dua periode sekaligus. Baris yang
         * dibuang tidak bisa dikembalikan; ekspor dulu sebelum menjalankannya.
         */
        DB::table('m_demografi_wilayah')
            ->where(fn ($w) => $w->where('tahun', '!=', PeriodeDemografi::TAHUN_BAWAAN)
                ->orWhere('semester', '!=', PeriodeDemografi::SEMESTER_BAWAAN))
            ->delete();

        Schema::table('m_demografi_wilayah', function (Blueprint $table) {
            $table->dropUnique('m_demografi_wilayah_periode_kode_key');
            $table->dropIndex('m_demografi_wilayah_periode_level_idx');
            $table->unique(['kategori', 'kode'], 'm_demografi_wilayah_kategori_kode_key');
            $table->dropColumn(['tahun', 'semester']);
        });
    }
};
