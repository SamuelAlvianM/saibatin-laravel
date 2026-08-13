<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\CatatanAktivitas;
use App\Support\Balasan;
use Illuminate\Http\Request;

/**
 * Kelola berita — port `app/api/admin/berita` & `berita/[id]`.
 *
 * Khusus Super Admin: isinya terbit ke halaman publik.
 */
class BeritaAdminController extends Controller
{
    public function __construct(private readonly CatatanAktivitas $log) {}

    /** SEMUA berita termasuk draf — panel admin, bukan halaman publik. */
    public function index()
    {
        return Balasan::ok([
            'items' => News::query()->latest('created_at')->take(200)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->periksa($request);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        $item = News::create([
            ...$data,
            'slug' => $this->slugUnik($data['judul']),
            'penulis' => $request->user()->user_fullname ?? 'Admin',
        ]);

        $this->log->catat(
            $request->user(), 'BUAT', 'Berita',
            "Membuat berita \"{$item->judul}\"".($item->publish ? ' (terbit)' : ' (draf)'),
            $item->id, $request,
        );

        return Balasan::ok(['item' => $item], ['Info: Berita berhasil dibuat']);
    }

    public function update(Request $request, int $id)
    {
        $item = News::find($id);

        if (! $item) {
            return Balasan::gagal(['Berita tidak ditemukan'], 404);
        }

        $data = $this->periksa($request);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        // Slug hanya dibuat ulang bila judulnya berubah — tautan berita yang
        // sudah tersebar (WhatsApp, Facebook) tidak boleh mati gara-gara
        // perbaikan salah ketik di badan berita.
        $item->fill($data);
        if ($data['judul'] !== $item->getOriginal('judul')) {
            $item->slug = $this->slugUnik($data['judul'], $id);
        }
        $item->save();

        $this->log->catat(
            $request->user(), 'UBAH', 'Berita',
            "Memperbarui berita \"{$item->judul}\"", $item->id, $request,
        );

        return Balasan::ok(['item' => $item], ['Info: Berita berhasil diperbarui']);
    }

    public function destroy(Request $request, int $id)
    {
        $item = News::find($id);

        if (! $item) {
            return Balasan::gagal(['Info: Gagal menghapus berita'], 404);
        }

        $judul = $item->judul;
        $item->delete();

        $this->log->catat(
            $request->user(), 'HAPUS', 'Berita',
            "Menghapus berita \"{$judul}\"", $id, $request,
        );

        return Balasan::ok(null, ['Info: Berita berhasil dihapus']);
    }

    /** @return array<string,mixed>|\Illuminate\Http\JsonResponse */
    private function periksa(Request $request)
    {
        $judul = trim((string) $request->input('judul'));
        $konten = trim((string) $request->input('konten'));

        if ($judul === '') {
            return Balasan::gagal(['Info: Judul wajib diisi']);
        }
        if ($konten === '') {
            return Balasan::gagal(['Info: Isi berita wajib diisi']);
        }

        return [
            'judul' => $judul,
            'kategori' => filled($request->input('kategori')) ? (string) $request->input('kategori') : null,
            'ringkasan' => filled($request->input('ringkasan')) ? (string) $request->input('ringkasan') : null,
            'konten' => $konten,
            'gambar' => filled($request->input('gambar')) ? (string) $request->input('gambar') : null,
            'publish' => (bool) $request->boolean('publish'),
        ];
    }

    /**
     * Slug unik — beri akhiran angka bila sudah terpakai.
     *
     * Aturannya disalin dari `lib/slug.ts` supaya slug yang dihasilkan port ini
     * identik dengan yang sudah ada di produksi: huruf kecil, hanya a-z0-9 dan
     * tanda hubung, maksimal 120 karakter.
     */
    private function slugUnik(string $dasar, ?int $abaikanId = null): string
    {
        $akar = mb_substr(
            preg_replace(['/[^a-z0-9\s-]/', '/\s+/', '/-+/'], ['', '-', '-'], mb_strtolower(trim($dasar))),
            0, 120,
        );

        if ($akar === '') {
            $akar = 'berita-'.now()->getTimestampMs();
        }

        $slug = $akar;
        $n = 1;

        while (News::where('slug', $slug)->when($abaikanId, fn ($w) => $w->where('id', '!=', $abaikanId))->exists()) {
            $n++;
            $slug = "{$akar}-{$n}";
        }

        return $slug;
    }
}
