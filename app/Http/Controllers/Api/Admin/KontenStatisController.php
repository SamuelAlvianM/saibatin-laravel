<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaticContent;
use App\Services\CatatanAktivitas;
use App\Support\Balasan;
use Illuminate\Http\Request;

/**
 * Blok konten statis — port `app/api/admin/static-content/route.ts`.
 *
 * Upsert per kunci. Kunci yang dikenal dibaca dari `config/konten.php`; kunci
 * asing ditolak supaya `t_static_contents` tidak jadi tempat sampah yang tak
 * pernah dibersihkan siapa pun.
 *
 * Registry CMS-nya masih sebagian — blok halaman publik menyusul di Fase 7,
 * karena blok itu memang disunting dari halaman publiknya sendiri.
 */
class KontenStatisController extends Controller
{
    public function __construct(private readonly CatatanAktivitas $log) {}

    public function simpan(Request $request)
    {
        $kunci = (string) $request->input('kunci');
        $konten = $request->input('konten');
        $blok = config('konten.blok');

        if (! array_key_exists($kunci, $blok)) {
            return Balasan::gagal(['Kunci konten tidak dikenal']);
        }
        if (! is_array($konten)) {
            return Balasan::gagal(['Konten tidak valid']);
        }

        StaticContent::updateOrCreate(
            ['kunci' => $kunci],
            ['judul' => $blok[$kunci], 'konten' => $konten, 'updated_by' => $request->user()->id],
        );

        $this->log->catat(
            $request->user(), 'UBAH', 'Konten',
            "Menyimpan blok konten \"{$blok[$kunci]}\"", $kunci, $request,
        );

        return Balasan::ok(null, ['Konten berhasil disimpan']);
    }
}
