<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Sunting akun & setel ulang sandi dari panel detail (2 Sep 2026).
 *
 * 🔴 Sebelum ini `UserAdminController` hanya punya index/show/store/ubahStatus/
 * destroy. Tidak ada seorang pun — Super Admin sekalipun — yang bisa
 * membetulkan salah ketik pada sebuah akun atau menolong warga yang lupa
 * sandinya. Satu-satunya jalan adalah menonaktifkan lalu membuat akun baru,
 * dan permohonan yang sudah tertaut ikut tertinggal di akun lama.
 *
 * ⚠️ Uji ini MENULIS ke basis data, tapi selalu mengembalikan keadaan semula
 * di `tearDown` — termasuk hash sandi, yang tidak bisa dipulihkan kalau
 * tertimpa tanpa disimpan lebih dulu.
 */
class SuntingAkunTest extends TestCase
{
    private ?User $korban = null;

    private array $semula = [];

    private function warga(): User
    {
        $u = User::where('userlevel_id', UserLevel::WARGA)
            ->where('status', User::STATUS_AKTIF)
            ->orderByDesc('id')
            ->first();

        if (! $u) {
            $this->markTestSkipped('Tidak ada akun warga aktif di basis data ini.');
        }

        // Simpan seluruh kolom yang mungkin disentuh, supaya bisa dikembalikan.
        $this->korban = $u;
        $this->semula = $u->only([
            'user_id', 'userlevel_id', 'user_fullname', 'user_nik', 'user_nokk',
            'user_hp', 'user_email', 'user_kecamatan', 'user_kelurahan',
            'user_kabupaten', 'password', 'forgotten_code', 'forgotten_time',
        ]);

        return $u;
    }

    protected function tearDown(): void
    {
        if ($this->korban) {
            User::whereKey($this->korban->id)->update($this->semula);
        }

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

    private function muatan(User $u, array $ganti = []): array
    {
        return array_merge([
            'nama' => $u->user_fullname ?: 'WARGA UJI',
            'userId' => $u->user_id,
            'nik' => $u->user_nik,
            'kk' => $u->user_nokk,
            'hp' => $u->user_hp,
            'email' => $u->user_email,
            'kecamatan' => $u->user_kecamatan ?: 'KRUI SELATAN',
        ], $ganti);
    }

    public function test_operator_boleh_menyunting_akun_warga(): void
    {
        $warga = $this->warga();

        $this->actingAs($this->akun(UserLevel::OPERATOR))
            ->putJson("/api/admin/users/{$warga->id}", $this->muatan($warga, [
                'hp' => '081200000001',
            ]))
            ->assertOk();

        $this->assertSame('081200000001', $warga->fresh()->user_hp);
    }

    public function test_operator_tidak_boleh_menyunting_akun_petugas(): void
    {
        $admin = $this->akun(UserLevel::SUPER_ADMIN);

        // Termasuk `user_id`-nya — menyuntingnya sama saja dengan mengambil alih.
        $this->actingAs($this->akun(UserLevel::OPERATOR))
            ->putJson("/api/admin/users/{$admin->id}", $this->muatan($admin))
            ->assertForbidden();
    }

    public function test_operator_tidak_boleh_menaikkan_level_akun(): void
    {
        $warga = $this->warga();

        /*
         * 🔴 Ini jalan memutar yang paling mudah terlewat: Operator memang
         * dilarang MEMBUAT akun petugas, tapi kalau ia boleh MENAIKKAN akun
         * warga jadi petugas, larangan itu tidak menahan apa pun.
         */
        $this->actingAs($this->akun(UserLevel::OPERATOR))
            ->putJson("/api/admin/users/{$warga->id}", $this->muatan($warga, [
                'level' => UserLevel::OPERATOR,
                'userId' => 'penyusup.uji',
            ]))
            ->assertForbidden();

        $this->assertSame(UserLevel::WARGA, $warga->fresh()->userlevel_id);
    }

    public function test_level_super_admin_tidak_bisa_diberikan_dari_sini(): void
    {
        $warga = $this->warga();

        $this->actingAs($this->akun(UserLevel::SUPER_ADMIN))
            ->putJson("/api/admin/users/{$warga->id}", $this->muatan($warga, [
                'level' => UserLevel::SUPER_ADMIN,
                'userId' => 'calon.superadmin',
            ]))
            ->assertForbidden();
    }

    public function test_nik_warga_wajib_16_digit(): void
    {
        $warga = $this->warga();

        // Warga login memakai NIK, jadi `userId`-nya harus berbentuk NIK.
        $this->actingAs($this->akun(UserLevel::SUPER_ADMIN))
            ->putJson("/api/admin/users/{$warga->id}", $this->muatan($warga, [
                'userId' => 'bukan-nik',
            ]))
            ->assertStatus(422);
    }

    public function test_menyimpan_tanpa_perubahan_tidak_dianggap_bentrok(): void
    {
        $warga = $this->warga();

        /*
         * ⚠️ Tanpa `whereKeyNot` pada pemeriksaan bentrok, menyimpan TANPA
         * mengubah identitas login pun ditolak — akun ini bentrok dengan
         * dirinya sendiri.
         */
        $this->actingAs($this->akun(UserLevel::SUPER_ADMIN))
            ->putJson("/api/admin/users/{$warga->id}", $this->muatan($warga))
            ->assertOk();
    }

    public function test_akun_lama_tanpa_kecamatan_tetap_bisa_disunting(): void
    {
        $warga = $this->warga();
        User::whereKey($warga->id)->update(['user_kecamatan' => null]);

        /*
         * 🔴 Terukur: `user_kecamatan` terisi pada 2 dari 1.390 akun. Kalau
         * penyuntingan menuntutnya, petugas yang cuma ingin membetulkan satu
         * digit nomor telepon dipaksa MENEBAK kecamatan warga — dan tebakan itu
         * langsung mencemari saringan wilayah.
         */
        $this->actingAs($this->akun(UserLevel::OPERATOR))
            ->putJson("/api/admin/users/{$warga->id}", $this->muatan($warga, [
                'kecamatan' => '',
                'hp' => '081900000002',
            ]))
            ->assertOk();

        $this->assertSame('081900000002', $warga->fresh()->user_hp);
    }

    public function test_kecamatan_yang_sudah_ada_tidak_boleh_dikosongkan(): void
    {
        $warga = $this->warga();
        User::whereKey($warga->id)->update(['user_kecamatan' => 'KRUI SELATAN']);

        // Yang sudah tercatat tidak boleh hilang — itu memundurkan data.
        $this->actingAs($this->akun(UserLevel::OPERATOR))
            ->putJson("/api/admin/users/{$warga->id}", $this->muatan($warga, ['kecamatan' => '']))
            ->assertStatus(422);

        $this->assertSame('KRUI SELATAN', $warga->fresh()->user_kecamatan);
    }

    public function test_operator_boleh_menyetel_sandi_warga(): void
    {
        $warga = $this->warga();

        $this->actingAs($this->akun(UserLevel::OPERATOR))
            ->postJson("/api/admin/users/{$warga->id}/sandi", ['password' => 'bantu2026'])
            ->assertOk();

        $baru = $warga->fresh();
        $this->assertTrue(Hash::check('bantu2026', $baru->password));

        // Kode pemulihan lama dimatikan — tautan "lupa password" yang mungkin
        // masih beredar tidak boleh bisa dipakai mengubahnya kembali.
        $this->assertNull($baru->forgotten_code);
    }

    public function test_sandi_angka_semua_dan_terlalu_pendek_ditolak(): void
    {
        $warga = $this->warga();
        $admin = $this->akun(UserLevel::SUPER_ADMIN);

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$warga->id}/sandi", ['password' => '12345678'])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$warga->id}/sandi", ['password' => 'ab1'])
            ->assertStatus(422);
    }

    public function test_operator_tidak_boleh_menyetel_sandi_petugas(): void
    {
        $this->actingAs($this->akun(UserLevel::OPERATOR))
            ->postJson('/api/admin/users/'.$this->akun(UserLevel::SUPER_ADMIN)->id.'/sandi',
                ['password' => 'ambilalih2026'])
            ->assertForbidden();
    }

    public function test_sandi_sendiri_tidak_bisa_disetel_dari_sini(): void
    {
        $admin = $this->akun(UserLevel::SUPER_ADMIN);

        // Diarahkan ke halaman Profil, yang meminta sandi lama lebih dulu.
        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$admin->id}/sandi", ['password' => 'gantisendiri2026'])
            ->assertForbidden();
    }
}
