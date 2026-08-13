<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Services\CatatanAktivitas;
use App\Support\Balasan;
use Illuminate\Http\Request;

/**
 * Galeri foto — port `app/api/galeri` (POST) & `app/api/admin/galeri/[id]`.
 *
 * Pembacaannya publik dan tinggal di `KontenController::galeri`; yang di sini
 * hanya yang mengubah. Khusus Super Admin, sama seperti aslinya.
 */
class GaleriAdminController extends Controller
{
    /** Kategori yang dikenal halaman publik. */
    private const KATEGORI = ['PELAYANAN', 'BUPATI'];

    public function __construct(private readonly CatatanAktivitas $log) {}

    public function store(Request $request)
    {
        $judul = trim((string) $request->input('judul'));
        $gambar = trim((string) $request->input('gambar'));
        $kategori = strtoupper(trim((string) $request->input('kategori')));

        if ($judul === '' || $gambar === '') {
            return Balasan::gagal(['Judul dan gambar wajib diisi']);
        }

        $item = Gallery::create([
            'judul' => $judul,
            'gambar' => $gambar,
            'kategori' => in_array($kategori, self::KATEGORI, true) ? $kategori : null,
        ]);

        $this->log->catat(
            $request->user(), 'BUAT', 'Galeri',
            "Menambah foto galeri \"{$item->judul}\"", $item->id, $request,
        );

        return Balasan::ok(['item' => $item], ['Foto berhasil ditambahkan']);
    }

    public function destroy(Request $request, int $id)
    {
        $item = Gallery::find($id);

        if (! $item) {
            return Balasan::gagal(['Info: Gagal menghapus foto'], 404);
        }

        // Berkas gambarnya TIDAK ikut dihapus: satu media di pustaka bisa
        // dipakai beberapa tempat (galeri, berita, blok konten). Membersihkan
        // media yang benar-benar tak terpakai urusan halaman Pustaka Media.
        $item->delete();

        $this->log->catat(
            $request->user(), 'HAPUS', 'Galeri',
            "Menghapus foto galeri #{$id}", $id, $request,
        );

        return Balasan::ok(null, ['Info: Foto berhasil dihapus']);
    }
}
