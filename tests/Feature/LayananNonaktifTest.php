<?php

namespace Tests\Feature;

use App\Models\StaticContent;
use App\Models\User;
use App\Models\UserLevel;
use Tests\TestCase;

/**
 * "Layanan dinonaktifkan" harus benar-benar menonaktifkan.
 *
 * 🔴 KENAPA UJI INI ADA. Sampai 2 Sep 2026 penyaringannya hanya ada di pemilih
 * layanan warga: halaman formulir dan endpoint pengiriman tidak memeriksa apa
 * pun. Siapa pun yang mengetik URL-nya tetap bisa membuka formulir DAN
 * mengirim permohonan untuk layanan yang petugas kira sudah mati — lalu
 * permohonan itu masuk ke antrean seperti biasa.
 *
 * ⚠️ Layanan yang dipakai menguji sengaja `kk-numpang`, yang slug rutenya
 * (`kk-numpang-kk`) BERBEDA dari slug formulirnya. Sembilan dari 17 layanan
 * begitu, dan pemeriksaan yang mencampur keduanya lolos tanpa galat justru
 * pada layanan-layanan itu.
 */
class LayananNonaktifTest extends TestCase
{
    private const KUNCI = 'kartuKeluargaNumpang';

    private const SLUG_RUTE = 'kk-numpang-kk';

    private ?array $kontenAwal = null;

    protected function setUp(): void
    {
        parent::setUp();

        $baris = StaticContent::where('kunci', StaticContent::KUNCI_VISIBILITAS)->first();
        $this->kontenAwal = $baris?->konten;

        StaticContent::updateOrCreate(
            ['kunci' => StaticContent::KUNCI_VISIBILITAS],
            ['konten' => ['hidden' => [self::KUNCI]]],
        );
    }

    protected function tearDown(): void
    {
        // Kembalikan persis seperti semula — uji ini menyentuh pengaturan yang
        // dipakai seluruh aplikasi, bukan data miliknya sendiri.
        StaticContent::updateOrCreate(
            ['kunci' => StaticContent::KUNCI_VISIBILITAS],
            ['konten' => $this->kontenAwal ?? ['hidden' => []]],
        );

        parent::tearDown();
    }

    private function akun(int $level): User
    {
        $u = User::where('userlevel_id', $level)->where('status', User::STATUS_AKTIF)->first();

        if (! $u) {
            $this->markTestSkipped("Tidak ada akun aktif ber-level {$level}.");
        }

        return $u;
    }

    public function test_super_admin_masih_bisa_membuka_layanan_yang_ia_matikan(): void
    {
        // Dialah yang mematikannya dan perlu mengujinya.
        $this->actingAs($this->akun(UserLevel::SUPER_ADMIN))
            ->get('/dashboard/pengajuan-baru/'.self::SLUG_RUTE)
            ->assertOk();
    }

    public function test_operator_biasa_ditolak_404(): void
    {
        // Sebelumnya 200 — pintu belakang yang tidak terlihat dari layar mana pun.
        $this->actingAs($this->akun(UserLevel::OPERATOR))
            ->get('/dashboard/pengajuan-baru/'.self::SLUG_RUTE)
            ->assertNotFound();
    }

    public function test_opd_ditolak_404(): void
    {
        $this->actingAs($this->akun(UserLevel::OPERATOR_OPD))
            ->get('/dashboard/pengajuan-baru/'.self::SLUG_RUTE)
            ->assertNotFound();
    }

    public function test_warga_ditolak_404_di_jalur_publiknya(): void
    {
        $this->actingAs($this->akun(UserLevel::WARGA))
            ->get('/user/pengajuan/baru/'.self::SLUG_RUTE)
            ->assertNotFound();
    }

    public function test_pengiriman_permohonan_ditolak_403(): void
    {
        /*
         * 🔴 Ini yang paling penting. Halaman boleh saja tertutup, tapi selama
         * endpoint-nya menerima, layanan itu belum mati — dan permohonannya
         * masuk ke antrean petugas tanpa ada yang tahu dari mana asalnya.
         */
        $this->actingAs($this->akun(UserLevel::OPERATOR))
            ->postJson('/api/kk-numpang/store', [])
            ->assertForbidden();
    }

    public function test_layanan_lain_tidak_ikut_terkunci(): void
    {
        $operator = $this->akun(UserLevel::OPERATOR);

        // `kk-pisah-kk` tidak dimatikan — penjaga baru tidak boleh menyeretnya.
        $this->actingAs($operator)->get('/dashboard/pengajuan-baru/kk-pisah-kk')->assertOk();
    }

    public function test_daftar_menyembunyikan_bagi_operator_dan_menandai_bagi_admin(): void
    {
        $admin = $this->actingAs($this->akun(UserLevel::SUPER_ADMIN))
            ->get('/dashboard/pengajuan-baru');
        $admin->assertOk();

        $operator = $this->actingAs($this->akun(UserLevel::OPERATOR))
            ->get('/dashboard/pengajuan-baru');
        $operator->assertOk();

        $propAdmin = $admin->viewData('page')['props'];
        $propOperator = $operator->viewData('page')['props'];

        // Admin: tetap 17 kartu, satu DITANDAI. Operator: 16, tanpa tanda.
        $this->assertContains(self::KUNCI, $propAdmin['tersembunyi']);
        $this->assertSame([], $propOperator['tersembunyi']);

        $this->assertContains(
            self::KUNCI,
            array_column($propAdmin['daftar'], 'kunci'),
            'Super Admin harus tetap melihat kartunya, dengan tanda',
        );
        $this->assertNotContains(
            self::KUNCI,
            array_column($propOperator['daftar'], 'kunci'),
            'Operator tidak boleh menerima kartu yang tautannya berakhir 404',
        );
    }
}
