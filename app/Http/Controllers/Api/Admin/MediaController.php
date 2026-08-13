<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\CatatanAktivitas;
use App\Services\PustakaMedia;
use App\Support\Balasan;
use Illuminate\Http\Request;

/**
 * Pustaka media — port `app/api/media/route.ts`, `media/upload`, `media/[id]`.
 *
 * 🔴 KHUSUS Super Admin (level 1), sama seperti aslinya. Media yang diunggah
 * di sini muncul di halaman publik; operator biasa tidak menerbitkan konten.
 */
class MediaController extends Controller
{
    private const PER_HALAMAN = 24;

    public function __construct(
        private readonly PustakaMedia $pustaka,
        private readonly CatatanAktivitas $log,
    ) {}

    /** Daftar media untuk pemilih & halaman pustaka. */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $tipe = $request->query('type');
        $page = max(1, (int) $request->query('page', 1));

        $query = fn () => Media::query()
            ->when(filled($q), fn ($w) => $w->where('nama_asli', 'like', "%{$q}%"))
            ->when($tipe === 'image', fn ($w) => $w->where('mime_type', 'like', 'image/%'));

        $total = $query()->count();

        $items = $query()
            ->orderByDesc('created_at')
            ->skip(($page - 1) * self::PER_HALAMAN)
            ->take(self::PER_HALAMAN)
            ->get()
            ->map(fn (Media $m) => $this->bentuk($m));

        return Balasan::ok([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => self::PER_HALAMAN,
        ]);
    }

    public function unggah(Request $request)
    {
        $berkas = $request->file('file');

        if (! $berkas || ! $berkas->isValid()) {
            return Balasan::gagal(['File tidak ditemukan']);
        }

        try {
            $media = $this->pustaka->simpan($berkas, $request->user());
        } catch (\RuntimeException $e) {
            return Balasan::gagal([$e->getMessage()]);
        } catch (\Throwable) {
            return Balasan::gagal(['Gagal mengunggah file'], 500);
        }

        $this->log->catat(
            $request->user(), 'UNGGAH', 'Media',
            "Mengunggah media \"{$media->nama_asli}\"",
            $media->id, $request,
        );

        return Balasan::ok(['media' => $this->bentuk($media)], ['File berhasil diunggah']);
    }

    public function hapus(Request $request, string $id)
    {
        $media = Media::find($id);

        if (! $media) {
            return Balasan::gagal(['Media tidak ditemukan'], 404);
        }

        $this->pustaka->hapusBerkas($media);
        $nama = $media->nama_asli ?? $media->path;
        $media->delete();

        $this->log->catat(
            $request->user(), 'HAPUS', 'Media',
            "Menghapus media \"{$nama}\"", $id, $request,
        );

        return Balasan::ok(null, ['Media berhasil dihapus']);
    }

    /** Bentuk camelCase — kontrak yang dibaca komponen pemilih media. */
    private function bentuk(Media $m): array
    {
        return [
            'id' => $m->id,
            'namaAsli' => $m->nama_asli,
            'namaFile' => $m->nama_file,
            'mimeType' => $m->mime_type,
            'ukuran' => $m->ukuran,
            'lebar' => $m->lebar,
            'tinggi' => $m->tinggi,
            'path' => $m->path,
            'url' => $m->url,
            'createdAt' => $m->created_at,
        ];
    }
}
