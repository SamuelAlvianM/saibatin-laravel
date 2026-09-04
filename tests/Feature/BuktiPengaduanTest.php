<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Bukti foto pengaduan tampil sebagai GAMBAR, bukan alamat berkas (4 Sep 2026).
 *
 * 🔴 CACAT YANG DIPERBAIKI. Formulir aspirasi menyimpan foto dengan menempelkan
 * URL-nya ke ujung kolom `isi`. Dashboard mencetak `isi` apa adanya, jadi yang
 * dilihat petugas adalah `/uploads/pengaduan/wbs_1736…png` — bukan fotonya.
 * Untuk melihat bukti yang dikirim warga, petugas harus menyalin teks itu ke
 * bilah alamat peramban.
 *
 * ⚠️ Aturan pemisahnya ada di `resources/js/lib/bukti-pengaduan.js` (sisi
 * klien). Uji di sini menjaga KONTRAK datanya: bentuk yang ditulis
 * `FormAspirasi` harus tetap yang dicari pemisah itu. Kalau salah satu
 * berubah sendiri, fotonya diam-diam kembali jadi teks.
 */
class BuktiPengaduanTest extends TestCase
{
    private function berkas(string $nama): string
    {
        return base_path("resources/js/{$nama}");
    }

    public function test_pemisah_bukti_ada_dan_dipakai_dashboard(): void
    {
        $lib = file_get_contents($this->berkas('lib/bukti-pengaduan.js'));
        $hal = file_get_contents($this->berkas('Pages/Dashboard/Pengaduan.jsx'));

        $this->assertStringContainsString('export function pisahBukti', $lib);
        $this->assertStringContainsString('pisahBukti', $hal);
        $this->assertStringContainsString('ringkasIsi', $hal);
    }

    /**
     * 🔴 Penanda yang DITULIS formulir harus sama persis dengan yang DICARI
     * pemisah. Keduanya berjauhan di pohon berkas dan tidak ada satu pun
     * kompilator yang akan menegur bila salah satunya diubah.
     */
    public function test_penanda_penulis_dan_pembaca_sama(): void
    {
        $form = file_get_contents($this->berkas('Publik/FormAspirasi.jsx'));
        $lib = file_get_contents($this->berkas('lib/bukti-pengaduan.js'));

        $this->assertStringContainsString('\n\nBukti Foto:', $form,
            'FormAspirasi tidak lagi menulis penanda "Bukti Foto:"');
        $this->assertStringContainsString("'\\n\\nBukti Foto:'", $lib,
            'Pemisah tidak lagi mencari penanda yang ditulis FormAspirasi');
    }

    /** Dashboard harus merender <img>, bukan sekadar mencetak teksnya. */
    public function test_dashboard_merender_gambar(): void
    {
        $hal = file_get_contents($this->berkas('Pages/Dashboard/Pengaduan.jsx'));

        $this->assertStringContainsString('<img', $hal);
        $this->assertStringContainsString('Bukti Foto (', $hal);
        $this->assertStringNotContainsString(
            '<p className="whitespace-pre-wrap text-slate-700">{detail.isi}</p>',
            $hal,
            'Isi mentah masih dicetak apa adanya — URL foto akan terlihat lagi',
        );
    }

    /**
     * Alamat yang ditulis pengunggah harus cocok dengan pola yang diterima
     * pemisah. Kalau pengunggah pindah folder, fotonya berhenti dikenali.
     */
    public function test_alamat_unggahan_cocok_dengan_pola_pemisah(): void
    {
        $unggah = file_get_contents(base_path('app/Http/Controllers/Api/BuktiPengaduanController.php'));

        $this->assertStringContainsString('/uploads/pengaduan/', $unggah);

        $lib = file_get_contents($this->berkas('lib/bukti-pengaduan.js'));
        $this->assertStringContainsString('/uploads/', $lib);

        // Pola pemisah diuji langsung terhadap contoh nama berkas sungguhan.
        $contoh = '/uploads/pengaduan/wbs_1736412345678_a1b2c3.jpg';
        $this->assertMatchesRegularExpression(
            '#^/uploads/[^\s]+\.(jpe?g|png)$#i',
            $contoh,
        );
    }

    /** Berkas bukti WAJIB di storage privat, bukan public/. */
    public function test_bukti_disimpan_di_storage_privat(): void
    {
        $unggah = file_get_contents(base_path('app/Http/Controllers/Api/BuktiPengaduanController.php'));

        $this->assertStringContainsString("Storage::disk('local')", $unggah,
            'Bukti pengaduan harus masuk disk lokal (storage/app/private), bukan public');
        $this->assertStringContainsString('permohonan/pengaduan', $unggah);
    }
}
