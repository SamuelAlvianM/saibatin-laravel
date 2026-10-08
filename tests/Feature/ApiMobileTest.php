<?php

namespace Tests\Feature;

use App\Models\Permohonan;
use App\Models\User;
use App\Models\UserLevel;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API aplikasi mobile — `/api/mobile/v1/*`.
 *
 * 🔴 KENAPA UJI INI ADA. `routes/api.php` dimuat DUA KALI: sekali untuk website
 * (sesi cookie), sekali di bawah prefix mobile (token). Pagar wewenangnya —
 * `peran:*`, penyaringan milik OPD, kontrol akses berkas — ditulis untuk
 * jalur website, dan berlaku di jalur mobile HANYA karena `PakaiTokenMobile`
 * menukar guard bawaan ke `sanctum`. Kalau middleware itu terlepas atau
 * urutannya bergeser, jalur mobile tidak menjadi "rusak" — ia menolak semua
 * orang (401) atau, lebih buruk, ada pagar yang membaca pengguna yang salah.
 *
 * ⚠️ Seperti `PeranOpdTest`: uji ini hanya MEMBACA. `Sanctum::actingAs` tidak
 * membuat token di basis data.
 */
class ApiMobileTest extends TestCase
{
    private const P = '/api/mobile/v1';

    private function akun(int $level): User
    {
        $u = User::where('userlevel_id', $level)->where('status', User::STATUS_AKTIF)
            ->when($level === UserLevel::OPERATOR_OPD, fn ($q) => $q->whereHas('permohonan'))
            ->first();

        if (! $u) {
            $this->markTestSkipped("Tidak ada akun aktif level {$level} di basis data ini.");
        }

        return $u;
    }

    public function test_tanpa_token_ditolak_dengan_kontrak_balasan(): void
    {
        // Tanpa header Accept pun balasannya JSON — bukan redirect ke /login.
        $this->get(self::P.'/auth/me')
            ->assertUnauthorized()
            ->assertJsonStructure(['error', 'success', 'data', 'html']);
    }

    public function test_galat_validasi_memakai_kontrak_balasan(): void
    {
        $this->post(self::P.'/auth/login')
            ->assertStatus(422)
            ->assertJsonPath('error.0', 'NIK/User ID harus diisi')
            ->assertJsonStructure(['data' => ['kolom' => ['user_id', 'password']]]);
    }

    public function test_endpoint_publik_terbuka_tanpa_token(): void
    {
        $this->get(self::P.'/health')->assertOk()->assertJsonPath('data.status', 'ok');
        $this->get(self::P.'/auth/register')->assertOk()->assertJsonStructure(['data' => ['kecamatan', 'otpWajib']]);
    }

    public function test_token_warga_membaca_pengguna_yang_benar(): void
    {
        $warga = $this->akun(UserLevel::WARGA);
        Sanctum::actingAs($warga);

        $this->get(self::P.'/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $warga->id)
            ->assertJsonPath('data.user.peran', 'warga');
    }

    public function test_warga_ditolak_di_endpoint_petugas(): void
    {
        Sanctum::actingAs($this->akun(UserLevel::WARGA));

        foreach (['/dashboard', '/admin/permohonan', '/admin/users', '/admin/log-aktivitas'] as $jalur) {
            $this->get(self::P.$jalur)->assertForbidden();
        }
    }

    public function test_petugas_mendapat_ringkasan_dashboard(): void
    {
        Sanctum::actingAs($this->akun(UserLevel::SUPER_ADMIN));

        $this->get(self::P.'/dashboard')
            ->assertOk()
            ->assertJsonPath('data.permohonan.total', Permohonan::count());
    }

    public function test_daftar_permohonan_opd_tetap_hanya_miliknya(): void
    {
        // Padanan `PeranOpdTest::test_daftar_permohonan_opd_hanya_miliknya`
        // lewat jalur token — pagar kepemilikan di controller harus ikut berlaku.
        $opd = $this->akun(UserLevel::OPERATOR_OPD);
        Sanctum::actingAs($opd);

        $balasan = $this->get(self::P.'/admin/permohonan?limit=100')->assertOk();

        $this->assertSame(Permohonan::where('user_id', $opd->id)->count(), $balasan->json('data.total'));
        $this->get(self::P.'/admin/users')->assertForbidden();
        $this->get(self::P.'/dashboard')->assertForbidden();
    }

    public function test_formulir_layanan_hanya_prefill_untuk_warga(): void
    {
        $slug = config('layanan.daftar.0.slug');

        Sanctum::actingAs($this->akun(UserLevel::WARGA));
        $this->get(self::P.'/layanan/'.$slug)->assertOk()->assertJsonStructure(['data' => ['layanan', 'prefill', 'jam', 'kirimKe']]);

        Sanctum::actingAs($this->akun(UserLevel::SUPER_ADMIN));
        $this->get(self::P.'/layanan/'.$slug)->assertOk()->assertJsonPath('data.prefill', []);
    }

    public function test_website_tetap_memakai_sesi_bukan_token(): void
    {
        // Token Sanctum tidak boleh membuka endpoint website: di sana guard-nya
        // `web` (sesi cookie). Tanpa sesi → endpoint berizin menolak.
        Sanctum::actingAs($this->akun(UserLevel::SUPER_ADMIN));
        auth()->shouldUse('web');

        $this->getJson('/api/admin/permohonan')->assertUnauthorized();
    }
}
