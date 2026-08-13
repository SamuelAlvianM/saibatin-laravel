<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaticContent;
use App\Services\CatatanAktivitas;
use App\Services\JamLayanan;
use App\Support\Balasan;
use Illuminate\Http\Request;

/**
 * Pengaturan pelayanan — port `app/api/admin/jam-layanan` &
 * `app/api/admin/pelayanan-visibilitas`.
 *
 * Keduanya menumpang di `t_static_contents` (kunci `pelayanan.jam` dan
 * `pelayanan.visibilitas`) — tabel itu memang menampung KONFIGURASI, bukan cuma
 * blok konten. Keduanya khusus Super Admin: ini sakelar yang mematikan layanan
 * bagi seluruh warga, bukan pengaturan sehari-hari operator.
 */
class PengaturanController extends Controller
{
    public function __construct(
        private readonly JamLayanan $jam,
        private readonly CatatanAktivitas $log,
    ) {}

    // ── Jam layanan ─────────────────────────────────────────────────────────

    public function jamLayanan()
    {
        return Balasan::ok($this->jam->konfigurasi());
    }

    public function simpanJamLayanan(Request $request)
    {
        // Masukan dari form dashboard tidak dipercaya apa adanya: `bersihkan()`
        // yang menentukan bentuk akhirnya, bukan apa yang dikirim peramban.
        $cfg = $this->jam->bersihkan($request->all());

        StaticContent::updateOrCreate(
            ['kunci' => JamLayanan::KUNCI],
            [
                'judul' => 'Jam Layanan Permohonan Online',
                'konten' => $cfg,
                'updated_by' => $request->user()->id,
            ],
        );

        $this->log->catat(
            $request->user(), 'UBAH', 'Pengaturan',
            'Memperbarui jam layanan permohonan online',
            JamLayanan::KUNCI, $request,
        );

        return Balasan::ok($cfg, ['Jam layanan disimpan']);
    }

    // ── Visibilitas layanan ─────────────────────────────────────────────────

    /** Daftar layanan + mana saja yang sedang disembunyikan. */
    public function visibilitas()
    {
        return Balasan::ok([
            'hidden' => self::hidden(),
            // Dikirim sekalian supaya halaman pengaturan tidak perlu menyalin
            // ulang daftar 15 layanan — satu sumber, yaitu config/layanan.php.
            'layanan' => array_map(fn ($l) => [
                'kunci' => $l['kunci'],
                'title' => $l['title'],
                'category' => $l['category'],
            ], config('layanan.daftar')),
            'kategori' => config('layanan.kategori'),
        ]);
    }

    public function simpanVisibilitas(Request $request)
    {
        $mentah = $request->input('hidden');

        if (! is_array($mentah)) {
            return Balasan::gagal(['Data tidak valid']);
        }

        // Hanya kunci yang dikenal yang disimpan → cegah data sampah menumpuk
        // di kolom konfigurasi yang tidak pernah dibersihkan siapa pun.
        $sah = array_column(config('layanan.daftar'), 'kunci');
        $hidden = array_values(array_unique(array_filter(
            $mentah,
            fn ($h) => is_string($h) && in_array($h, $sah, true),
        )));

        StaticContent::updateOrCreate(
            ['kunci' => StaticContent::KUNCI_VISIBILITAS],
            [
                'judul' => 'Visibilitas Layanan Permohonan Online',
                'konten' => ['hidden' => $hidden],
                'updated_by' => $request->user()->id,
            ],
        );

        $this->log->catat(
            $request->user(), 'UBAH', 'Pengaturan',
            'Memperbarui visibilitas layanan ('.count($hidden).' disembunyikan)',
            StaticContent::KUNCI_VISIBILITAS, $request,
        );

        return Balasan::ok(['hidden' => $hidden], ['Pengaturan pelayanan disimpan']);
    }

    /**
     * Kunci layanan yang sedang disembunyikan.
     *
     * Statis karena pemakainya bukan cuma halaman pengaturan — pemilih layanan
     * warga membacanya juga, dan keduanya harus melihat daftar yang sama.
     *
     * @return array<int,string>
     */
    public static function hidden(): array
    {
        $konten = StaticContent::where('kunci', StaticContent::KUNCI_VISIBILITAS)->value('konten');
        $hidden = is_array($konten) ? ($konten['hidden'] ?? []) : [];

        return is_array($hidden) ? array_values(array_filter($hidden, 'is_string')) : [];
    }
}
