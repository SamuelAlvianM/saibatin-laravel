<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemografiWilayah;
use App\Services\CatatanAktivitas;
use App\Support\Balasan;
use App\Support\KategoriDemografi;
use Illuminate\Http\Request;

/**
 * Kategori data demografi — port `app/api/admin/demografi/kategori`.
 *
 * Mendaftar, menambah kategori buatan dinas, mengatur mana yang tampil di
 * halaman utama, dan menghapus kategori kustom yang sudah kosong.
 */
class KategoriDemografiController extends Controller
{
    public function __construct(private readonly CatatanAktivitas $log) {}

    /** Seluruh kategori + penanda bawaan/kustom + tampil-di-beranda. */
    public function index()
    {
        ['kustom' => $kustom, 'beranda' => $beranda] = KategoriDemografi::registri();
        $bawaan = KategoriDemografi::slugBawaan();
        $semua = array_merge(KategoriDemografi::bawaan(), $kustom);

        return Balasan::ok([
            'kategori' => array_map(fn ($k) => [
                ...$k,
                'bawaan' => in_array($k['slug'], $bawaan, true),
                'beranda' => $beranda === null || in_array($k['slug'], $beranda, true),
            ], $semua),
        ]);
    }

    /** Tambah kategori buatan dinas. */
    public function store(Request $request)
    {
        $judul = trim((string) $request->input('judul'));

        if (mb_strlen($judul) < 3) {
            return Balasan::gagal(['Nama kategori minimal 3 huruf']);
        }
        if (mb_strlen($judul) > 60) {
            return Balasan::gagal(['Nama kategori maksimal 60 huruf']);
        }

        $slug = KategoriDemografi::slug($judul);
        if ($slug === '') {
            return Balasan::gagal(['Nama kategori harus mengandung huruf atau angka']);
        }

        $registri = KategoriDemografi::registri();

        if (count($registri['kustom']) >= KategoriDemografi::MAKS_KUSTOM) {
            return Balasan::gagal([
                'Kategori kustom sudah mencapai batas '.KategoriDemografi::MAKS_KUSTOM,
            ]);
        }

        /*
         * ⚠️ Slug kembar DITOLAK, tidak diberi akhiran angka diam-diam.
         *
         * Kolom `kategori` cuma teks; dua kategori berslug sama akan berbagi
         * baris yang sama tanpa ada yang menyadarinya — impor yang satu
         * menghapus data yang lain. Dan "Pekerjaan-2" yang muncul sendiri di
         * layar hanya membuat petugas mengira ia salah pencet.
         */
        if (KategoriDemografi::dikenal($slug)) {
            return Balasan::gagal(["Kategori \"{$judul}\" sudah ada"]);
        }

        $baru = ['slug' => $slug, 'label' => $judul, 'fileHint' => 'dibuat dinas'];
        $registri['kustom'][] = $baru;

        /* Kategori baru ikut tampil di halaman utama bila daftar tampilnya
           sudah pernah diatur — kalau tidak, ia lahir tersembunyi tanpa ada
           yang meminta. */
        if ($registri['beranda'] !== null) {
            $registri['beranda'][] = $slug;
        }

        KategoriDemografi::simpan($registri, $request->user()->id);
        $this->log->catat(
            $request->user(), 'BUAT', 'Demografi',
            "Menambah kategori demografi \"{$judul}\" ({$slug})",
            $slug, $request,
        );

        return Balasan::ok(['kategori' => $baru], ["Kategori \"{$judul}\" dibuat"]);
    }

    /** Atur kategori mana yang tampil di halaman utama. */
    public function update(Request $request)
    {
        $mentah = $request->input('beranda');
        if (! is_array($mentah)) {
            return Balasan::gagal(['Data tidak valid']);
        }

        $registri = KategoriDemografi::registri();
        $dikenal = array_merge(
            KategoriDemografi::slugBawaan(),
            array_column($registri['kustom'], 'slug'),
        );

        // Hanya slug dikenal yang disimpan → cegah data sampah di registri.
        $registri['beranda'] = array_values(array_unique(array_filter(
            $mentah,
            fn ($s) => is_string($s) && in_array($s, $dikenal, true),
        )));

        KategoriDemografi::simpan($registri, $request->user()->id);
        $this->log->catat(
            $request->user(), 'UBAH', 'Demografi',
            'Mengatur kategori tampil di halaman utama ('
                .count($registri['beranda']).' dari '.count($dikenal).')',
            KategoriDemografi::KUNCI, $request,
        );

        return Balasan::ok(
            ['beranda' => $registri['beranda']],
            ['Tampilan halaman utama disimpan'],
        );
    }

    /** Hapus kategori kustom — ditolak selama masih ada datanya. */
    public function destroy(Request $request)
    {
        $slug = trim((string) $request->query('slug'));
        if ($slug === '') {
            return Balasan::gagal(['Kategori tidak disebut']);
        }
        if (in_array($slug, KategoriDemografi::slugBawaan(), true)) {
            return Balasan::gagal(['Kategori bawaan DKB tidak dapat dihapus']);
        }

        $registri = KategoriDemografi::registri();
        $ada = collect($registri['kustom'])->firstWhere('slug', $slug);
        if (! $ada) {
            return Balasan::gagal(['Kategori tidak dikenal']);
        }

        /*
         * 🔴 DITOLAK selama masih ada barisnya.
         *
         * Menghapus kategori dari registri tidak menghapus datanya — barisnya
         * tetap duduk di `m_demografi_wilayah` dengan slug yang tidak lagi
         * dikenal siapa pun: tidak muncul di layar, tidak bisa diekspor, tidak
         * bisa dihapus lewat antarmuka. Petugas diminta menghapus datanya lebih
         * dulu, lewat tombol Hapus pada periodenya, supaya keputusan itu sadar
         * dan terlihat.
         */
        $baris = DemografiWilayah::where('kategori', $slug)->count();
        if ($baris > 0) {
            return Balasan::gagal([
                "Kategori \"{$ada['label']}\" masih berisi {$baris} baris. ".
                'Hapus datanya dulu lewat tombol Hapus pada periodenya.',
            ]);
        }

        $registri['kustom'] = array_values(array_filter(
            $registri['kustom'],
            fn ($k) => $k['slug'] !== $slug,
        ));
        if ($registri['beranda'] !== null) {
            $registri['beranda'] = array_values(array_filter(
                $registri['beranda'],
                fn ($s) => $s !== $slug,
            ));
        }

        KategoriDemografi::simpan($registri, $request->user()->id);
        $this->log->catat(
            $request->user(), 'HAPUS', 'Demografi',
            "Menghapus kategori demografi \"{$ada['label']}\" ({$slug})",
            $slug, $request,
        );

        return Balasan::ok(['slug' => $slug], ["Kategori \"{$ada['label']}\" dihapus"]);
    }
}
