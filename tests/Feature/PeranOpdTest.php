<?php

namespace Tests\Feature;

use App\Models\Permohonan;
use App\Models\User;
use App\Models\UserLevel;
use Tests\TestCase;

/**
 * Batas wewenang Operator OPD sesudah ia pindah ke kerangka dashboard (2 Sep 2026).
 *
 * 🔴 KENAPA UJI INI ADA. Memindahkan OPD ke dashboard berarti ia memakai
 * halaman, rute, dan endpoint yang SAMA dengan petugas. Yang membedakan
 * keduanya bukan lagi halamannya, melainkan beberapa baris penyaringan yang
 * tersebar di middleware dan controller — dan tidak satu pun dari itu
 * menimbulkan galat kalau hilang. Yang terjadi kalau salah: 141 akun instansi
 * melihat 11.919 permohonan seluruh kabupaten, lengkap dengan NIK dan nomor
 * telepon pemohonnya, tanpa apa pun yang tampak rusak.
 *
 * ⚠️ Uji ini MEMBACA data yang ada, tidak membuat dan tidak menulis apa pun.
 * Satu-satunya permintaan yang mengubah data (`PATCH`) memang diharapkan
 * ditolak 403, jadi ia tidak pernah sampai menyentuh basis data.
 */
class PeranOpdTest extends TestCase
{
    private function opd(): User
    {
        $u = User::where('userlevel_id', UserLevel::OPERATOR_OPD)
            ->where('status', User::STATUS_AKTIF)
            ->whereHas('permohonan')
            ->first();

        if (! $u) {
            $this->markTestSkipped('Tidak ada akun Operator OPD beserta permohonannya di basis data ini.');
        }

        return $u;
    }

    public function test_opd_mendarat_di_daftar_permohonannya_sendiri(): void
    {
        // Sebelum 2 Sep 2026 tujuannya `/user/pengajuan` — halaman publik
        // ber-navbar, bukan dashboard.
        $this->actingAs($this->opd())
            ->get('/dashboard')
            ->assertRedirect('/dashboard/permohonan');
    }

    public function test_opd_boleh_membuka_halaman_yang_dipakai_bersama(): void
    {
        $opd = $this->opd();

        $this->actingAs($opd)->get('/dashboard/permohonan')->assertOk();
        $this->actingAs($opd)->get('/dashboard/pengajuan-baru')->assertOk();
    }

    public function test_opd_ditolak_di_halaman_khusus_petugas(): void
    {
        $opd = $this->opd();

        foreach (['/dashboard/users', '/dashboard/pengaduan', '/dashboard/skm', '/dashboard/master'] as $jalur) {
            $this->actingAs($opd)->get($jalur)->assertForbidden();
        }
    }

    public function test_halaman_master_kini_khusus_super_admin(): void
    {
        $operator = User::where('userlevel_id', UserLevel::OPERATOR)
            ->where('status', User::STATUS_AKTIF)->first();

        if (! $operator) {
            $this->markTestSkipped('Tidak ada akun Operator di basis data ini.');
        }

        // Sampai 2 Sep 2026 halaman ini `peran:petugas`, jadi setiap Operator
        // bisa membuka kunci permohonan yang sudah final.
        $this->actingAs($operator)->get('/dashboard/master')->assertForbidden();
        $this->actingAs($operator)->post('/api/admin/master', [])->assertForbidden();
    }

    public function test_daftar_permohonan_opd_hanya_miliknya(): void
    {
        $opd = $this->opd();

        $balasan = $this->actingAs($opd)->getJson('/api/admin/permohonan?limit=100');
        $balasan->assertOk();

        $miliknya = Permohonan::where('user_id', $opd->id)->count();
        $seluruhnya = Permohonan::count();

        // Kalau pagarnya hilang, `total` akan sama dengan jumlah SELURUH
        // permohonan — dan itu justru yang paling mudah lolos dari mata, karena
        // halamannya tetap tampil rapi.
        $this->assertSame($miliknya, $balasan->json('data.total'));
        $this->assertLessThan($seluruhnya, $balasan->json('data.total'));

        foreach ($balasan->json('data.items') as $it) {
            $this->assertSame(
                $opd->id,
                Permohonan::whereKey($it['id'])->value('user_id'),
                "Permohonan {$it['id']} bukan milik OPD ini",
            );
        }
    }

    public function test_hitungan_per_status_ikut_disaring(): void
    {
        $opd = $this->opd();

        // 🔴 Lencana jumlah di tab dihitung lewat query TERPISAH. Menyaring
        // daftar barisnya saja membuat tabelnya benar tapi angka di tab-nya
        // menghitung seluruh kabupaten — bocor tanpa satu baris data pun tampil.
        $balasan = $this->actingAs($opd)->getJson('/api/admin/permohonan');

        $this->assertSame(
            Permohonan::where('user_id', $opd->id)->count(),
            $balasan->json('data.counts.'),
            'Hitungan "semua" tidak ikut disaring ke milik OPD',
        );
    }

    public function test_detail_permohonan_orang_lain_menjawab_404_bukan_403(): void
    {
        $opd = $this->opd();

        $punyaOrangLain = Permohonan::where('user_id', '!=', $opd->id)->value('id');

        // 404, bukan 403: id permohonan berurutan dan mudah ditebak, dan 403
        // mengonfirmasi bahwa nomor itu ada.
        $this->actingAs($opd)
            ->getJson("/api/admin/permohonan/{$punyaOrangLain}")
            ->assertNotFound();
    }

    public function test_opd_tidak_bisa_memproses_permohonan(): void
    {
        $opd = $this->opd();
        $miliknya = Permohonan::where('user_id', $opd->id)->value('id');

        // Miliknya sendiri pun tidak boleh — OPD mengajukan, bukan memproses.
        $this->actingAs($opd)
            ->patchJson("/api/admin/permohonan/{$miliknya}", ['status' => 'SELESAI'])
            ->assertForbidden();
    }

    public function test_alamat_lama_mengalih_beserta_query_string(): void
    {
        // 🔴 Inilah yang hilang kalau memakai `Route::redirect`: 15 notifikasi
        // tersimpan ber-`link = '/user/pengajuan'` + `?sorot=<id>` akan mendarat
        // di daftar tanpa menyorot baris yang jadi alasan notifikasinya dikirim.
        $this->actingAs($this->opd())
            ->get('/user/pengajuan?sorot=123')
            ->assertRedirect('/dashboard/permohonan?sorot=123');
    }

    public function test_warga_tidak_ikut_pindah(): void
    {
        $warga = User::where('userlevel_id', UserLevel::WARGA)
            ->where('status', User::STATUS_AKTIF)->first();

        if (! $warga) {
            $this->markTestSkipped('Tidak ada akun warga aktif di basis data ini.');
        }

        // Halaman lama tetap miliknya, dan dashboard tetap tertutup.
        $this->actingAs($warga)->get('/user/pengajuan')->assertOk();
        $this->actingAs($warga)->get('/dashboard/permohonan')->assertForbidden();
    }
}
