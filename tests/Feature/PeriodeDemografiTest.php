<?php

namespace Tests\Feature;

use App\Models\DemografiWilayah;
use App\Support\PeriodeDemografi;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Periode (tahun + semester) pada data demografi (4 Sep 2026).
 *
 * 🔴 SIFAT YANG DIJAGA. Sebelum ada periode, `m_demografi_wilayah` berkunci
 * `(kategori, kode)` — satu baris per wilayah — dan impor MENGGANTI TOTAL isi
 * kategorinya. Mengunggah DKB semester berikutnya karena itu MENGHAPUS
 * semester sebelumnya, tanpa peringatan apa pun. Uji di berkas ini ada untuk
 * memastikan kerusakan itu tidak bisa kembali.
 *
 * ⚠️ Uji ini MENULIS ke basis data. Seluruh barisnya memakai kategori dan
 * tahun khusus uji (lihat konstanta di bawah) supaya tidak mungkin bertabrakan
 * dengan data dinas, dan dibersihkan di `tearDown` bagaimanapun hasilnya.
 */
class PeriodeDemografiTest extends TestCase
{
    /** Tahun yang mustahil dipakai data sungguhan. */
    private const TAHUN_UJI = 2001;

    private const KATEGORI = 'jenis-kelamin';

    private const KODE = '9999999';

    protected function tearDown(): void
    {
        DB::table('m_demografi_wilayah')
            ->where('tahun', self::TAHUN_UJI)
            ->delete();

        parent::tearDown();
    }

    private function tulis(int $semester, int $nilai): void
    {
        DB::table('m_demografi_wilayah')->insert([
            'kategori' => self::KATEGORI,
            'tahun' => self::TAHUN_UJI,
            'semester' => $semester,
            'kode' => self::KODE,
            'wilayah' => 'WILAYAH UJI',
            'level' => DemografiWilayah::LEVEL_KECAMATAN,
            'parent_kode' => null,
            'data' => json_encode(['L' => $nilai, 'P' => $nilai, 'JML' => $nilai * 2]),
            'updated_at' => now(),
        ]);
    }

    public function test_dua_semester_boleh_berdampingan(): void
    {
        $this->tulis(1, 100);
        $this->tulis(2, 200);

        $this->assertSame(2, DemografiWilayah::where('tahun', self::TAHUN_UJI)->count());
    }

    public function test_wilayah_yang_sama_tidak_boleh_kembar_dalam_satu_periode(): void
    {
        $this->tulis(1, 100);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->tulis(1, 999);
    }

    public function test_scope_periode_hanya_mengambil_periodenya(): void
    {
        $this->tulis(1, 100);
        $this->tulis(2, 200);

        $sem1 = DemografiWilayah::where('tahun', self::TAHUN_UJI)
            ->periode(self::TAHUN_UJI, 1)->get();

        $this->assertCount(1, $sem1);
        $this->assertSame(100, $sem1->first()->data['L']);
    }

    public function test_periode_tersedia_terurut_terbaru_dulu(): void
    {
        $this->tulis(1, 100);
        $this->tulis(2, 200);

        $milikUji = collect(DemografiWilayah::periodeTersedia())
            ->where('tahun', self::TAHUN_UJI)
            ->values();

        $this->assertCount(2, $milikUji);
        $this->assertSame(2, $milikUji[0]['semester'], 'Semester II harus mendahului Semester I');
        $this->assertSame(1, $milikUji[1]['semester']);
    }

    /**
     * 🔴 Inti dari seluruh perubahan ini: menulis periode baru TIDAK BOLEH
     * menyentuh periode lama. Kalau uji ini gagal, dinas kehilangan data.
     */
    public function test_menulis_periode_baru_tidak_menghapus_yang_lama(): void
    {
        $this->tulis(1, 100);

        // Tiru `gantiTotal`: hapus periode sasaran saja, lalu tulis ulang.
        DemografiWilayah::where('kategori', self::KATEGORI)
            ->periode(self::TAHUN_UJI, 2)
            ->delete();
        $this->tulis(2, 555);

        $lama = DemografiWilayah::where('tahun', self::TAHUN_UJI)
            ->periode(self::TAHUN_UJI, 1)->first();

        $this->assertNotNull($lama, 'Semester I hilang saat Semester II ditulis');
        $this->assertSame(100, $lama->data['L'], 'Angka Semester I ikut berubah');
    }

    public function test_label_periode_memakai_angka_romawi(): void
    {
        $this->assertSame('Semester I 2024', PeriodeDemografi::label(2024, 1));
        $this->assertSame('Semester II 2025', PeriodeDemografi::label(2025, 2));
        $this->assertSame('DKB Semester II 2024', PeriodeDemografi::labelPanjang(2024, 2));
    }

    public function test_semester_selain_1_dan_2_ditolak(): void
    {
        $this->assertTrue(PeriodeDemografi::semesterSah(1));
        $this->assertTrue(PeriodeDemografi::semesterSah(2));
        $this->assertFalse(PeriodeDemografi::semesterSah(0));
        $this->assertFalse(PeriodeDemografi::semesterSah(3));
        $this->assertFalse(PeriodeDemografi::semesterSah('dua'));
    }

    public function test_tahun_di_luar_akal_ditolak(): void
    {
        $this->assertFalse(PeriodeDemografi::tahunSah(1899));
        $this->assertTrue(PeriodeDemografi::tahunSah(2024));
        // Batas atasnya tahun depan: DKB semester II kadang baru diterima
        // dinas di awal tahun berikutnya.
        $this->assertTrue(PeriodeDemografi::tahunSah((int) date('Y') + 1));
        $this->assertFalse(PeriodeDemografi::tahunSah((int) date('Y') + 2));
    }

    /** Semester I 2025 lebih baru daripada Semester II 2024. */
    public function test_urutan_periode_menyeberang_tahun(): void
    {
        $this->assertGreaterThan(
            PeriodeDemografi::kunciUrut(2024, 2),
            PeriodeDemografi::kunciUrut(2025, 1),
        );
    }
}
