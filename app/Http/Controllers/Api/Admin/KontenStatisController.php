<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaticContent;
use App\Services\CatatanAktivitas;
use App\Support\Balasan;
use App\Support\Konten;
use Illuminate\Http\Request;

/**
 * Blok konten statis — port `app/api/admin/static-content/route.ts`.
 *
 * Upsert per kunci, dan pembaca bentuk formulirnya untuk MODE EDIT. Kunci yang
 * dikenal ditentukan `App\Support\Konten::skema()` — yang tetap dari
 * `config/konten.php`, yang dinamis (halaman informasi & ketentuan) dirakit
 * dari config halamannya. Kunci asing ditolak supaya `t_static_contents` tidak
 * jadi tempat sampah yang tak pernah dibersihkan siapa pun.
 *
 * 🔴 Dulu penjaganya `config('konten.blok')` saja, dan itu MENOLAK seluruh
 * kunci halaman informasi (`info.produk.sop`, `info.kebijakan-privasi`, …) —
 * blok yang justru paling sering disunting petugas.
 */
class KontenStatisController extends Controller
{
    public function __construct(private readonly CatatanAktivitas $log) {}

    /**
     * Bentuk formulir + isi satu blok — dibaca dialog Mode Edit saat pensilnya
     * ditekan. Isinya sudah digabung dengan bawaan, jadi dialog tidak pernah
     * tampil kosong pada blok yang belum pernah disimpan.
     */
    public function skema(Request $request)
    {
        $kunci = (string) $request->query('kunci');
        $skema = Konten::skema($kunci);

        if (! $skema) {
            return Balasan::gagal(['Kunci konten tidak dikenal'], 404);
        }

        return Balasan::ok([
            'blok' => $skema,
            'konten' => Konten::isiUntukEditor($kunci),
        ]);
    }

    public function simpan(Request $request)
    {
        $kunci = (string) $request->input('kunci');
        $konten = $request->input('konten');
        $skema = Konten::skema($kunci);

        if (! $skema) {
            return Balasan::gagal(['Kunci konten tidak dikenal']);
        }
        if (! is_array($konten)) {
            return Balasan::gagal(['Konten tidak valid']);
        }

        StaticContent::updateOrCreate(
            ['kunci' => $kunci],
            ['judul' => $skema['judul'], 'konten' => $konten, 'updated_by' => $request->user()->id],
        );

        $this->log->catat(
            $request->user(), 'UBAH', 'Konten',
            "Menyimpan blok konten \"{$skema['judul']}\"", $kunci, $request,
        );

        return Balasan::ok(null, ['Konten berhasil disimpan']);
    }
}
