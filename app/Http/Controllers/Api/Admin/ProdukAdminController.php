<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Services\CatatanAktivitas;
use App\Support\Balasan;
use Illuminate\Http\Request;

/**
 * Dokumen publikasi — port `app/api/admin/produk` & `produk/[id]`.
 *
 * Kategorinya dibaca dari `config/dokumen.php` (port `lib/dokumen-registry.ts`),
 * bukan daftar terpisah: dropdown dashboard, validasi di sini, dan halaman
 * publik nanti harus melihat daftar yang sama.
 */
class ProdukAdminController extends Controller
{
    public function __construct(private readonly CatatanAktivitas $log) {}

    public function index(Request $request)
    {
        $jenis = $request->query('jenis') ?? $request->query('kategori');

        $items = Produk::query()
            ->when(filled($jenis), fn ($w) => $w->where('jenis', $jenis))
            ->latest('created_at')
            ->take(300)
            ->get();

        return Balasan::ok(['items' => $items]);
    }

    public function store(Request $request)
    {
        $jenis = (string) $request->input('jenis');
        $judul = trim((string) $request->input('judul'));
        $file = $request->input('file');
        $konten = $request->input('konten');

        if (! in_array($jenis, $this->jenisSah(), true)) {
            return Balasan::gagal(['Info: Jenis tidak valid']);
        }
        if ($judul === '') {
            return Balasan::gagal(['Info: Judul wajib diisi']);
        }
        if (blank($file) && blank($konten)) {
            return Balasan::gagal(['Info: Lampirkan file atau isi konten']);
        }

        $u = $request->user();

        $item = Produk::create([
            'jenis' => $jenis,
            'judul' => $judul,
            'file' => filled($file) ? (string) $file : null,
            'konten' => filled($konten) ? (string) $konten : null,
            'uploaded_by' => $u->id,
            'uploaded_by_name' => $u->user_fullname ?? $u->user_id,
        ]);

        $this->log->catat(
            $u, 'BUAT', 'Dokumen',
            "Menambah dokumen \"{$item->judul}\" ({$item->jenis})",
            $item->id, $request,
        );

        return Balasan::ok(['item' => $item], ['Info: Dokumen berhasil ditambahkan']);
    }

    public function destroy(Request $request, int $id)
    {
        $item = Produk::find($id);

        if (! $item) {
            return Balasan::gagal(['Info: Gagal menghapus dokumen'], 404);
        }

        $judul = $item->judul;
        $item->delete();

        $this->log->catat(
            $request->user(), 'HAPUS', 'Dokumen',
            "Menghapus dokumen \"{$judul}\"", $id, $request,
        );

        return Balasan::ok(null, ['Info: Dokumen berhasil dihapus']);
    }

    /** @return array<int,string> */
    private function jenisSah(): array
    {
        return [
            ...array_column(config('dokumen.kategori'), 'key'),
            ...config('dokumen.legacy'),
        ];
    }
}
