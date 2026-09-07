<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemografiWilayah;
use App\Services\CatatanAktivitas;
use App\Support\Balasan;
use App\Http\Controllers\Api\StatistikController;
use App\Models\StaticContent;
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

    /** Balasan seragam saat daftar kategori sedang dikunci. */
    private function terkunci()
    {
        return Balasan::gagal([
            'Daftar kategori dikunci. Kategori tidak dapat ditambah atau dihapus; '
            .'nama tampilannya masih bisa diubah lewat tombol Ganti Nama.',
        ], 409);
    }

    /**
     * Seluruh kategori + DI MANA ia benar-benar tampil + jumlah barisnya.
     *
     * 🔴 Sebelumnya panel ini cuma berbunyi "8 tampil di halaman utama" —
     * kalimat yang tidak menyebut tampil di mana. Petugas membandingkannya
     * dengan enam kartu angka di beranda, menyimpulkan ada dua kategori yang
     * hilang, lalu hendak menghapus dua kategori yang sebetulnya berisi ribuan
     * baris DKB. Padahal keduanya hal yang berbeda: kategori mengisi TAB tabel
     * demografi, sedangkan kartu beranda konfigurasi tersendiri yang boleh
     * menarik beberapa angka dari kategori yang sama.
     */
    public function index()
    {
        ['beranda' => $beranda] = KategoriDemografi::registri();
        $bawaan = KategoriDemografi::slugBawaan();
        $semua = KategoriDemografi::semua();

        $barisPer = DemografiWilayah::selectRaw('kategori, COUNT(*) as jml')
            ->groupBy('kategori')->pluck('jml', 'kategori');

        /*
         * 🔴 Dihitung dari hasil SELARAS, bukan dari konfigurasi mentah.
         *
         * Baris `beranda.statistik` masih boleh memuat sisa susunan lama — tiga
         * kartu berkategori `jenis-kelamin`, misalnya. Yang benar-benar tampil di
         * beranda adalah hasil `selaraskanKartu`, satu per kategori. Kalau panel
         * ini menghitung yang mentah, ia berkata "3 kartu beranda" untuk kategori
         * yang di beranda cuma punya satu — dan seluruh gunanya panel ini adalah
         * mengatakan apa yang SUNGGUH tampil.
         */
        $kartuPer = [];
        $kartuTampil = KategoriDemografi::selaraskanKartu(
            $this->kartuBeranda(),
            KategoriDemografi::tampil(),
        );
        foreach ($kartuTampil as $kartu) {
            $slug = (string) ($kartu['kategori'] ?? '');
            if ($slug !== '') {
                $kartuPer[$slug] = ($kartuPer[$slug] ?? 0) + 1;
            }
        }

        return Balasan::ok([
            'terkunci' => KategoriDemografi::TERKUNCI,
            'kategori' => array_map(fn ($k) => [
                ...$k,
                'bawaan' => in_array($k['slug'], $bawaan, true),
                'beranda' => $beranda === null || in_array($k['slug'], $beranda, true),
                'kartu' => $kartuPer[$k['slug']] ?? 0,
                'baris' => (int) ($barisPer[$k['slug']] ?? 0),
            ], $semua),
        ]);
    }

    /** Tulis ulang kartu beranda: persis satu per kategori yang tampil. */
    private function selaraskanKartuBeranda(): void
    {
        $kartu = KategoriDemografi::selaraskanKartu(
            $this->kartuBeranda(),
            KategoriDemografi::tampil(),
        );

        StaticContent::updateOrCreate(
            ['kunci' => StatistikController::KUNCI_KARTU],
            ['judul' => 'Kartu Statistik Beranda', 'konten' => ['kartu' => $kartu]],
        );
    }

    /**
     * Susunan kartu beranda, dengan cadangan yang SAMA dengan yang dipakai
     * beranda sungguhan — kalau tidak, hitungan di layar ini bisa berkata
     * "tanpa kartu" untuk kategori yang kartunya jelas terpampang di beranda.
     */
    private function kartuBeranda(): array
    {
        $konten = StaticContent::where('kunci', StatistikController::KUNCI_KARTU)->value('konten');
        $kartu = is_array($konten) ? ($konten['kartu'] ?? []) : [];

        if (! is_array($kartu) || $kartu === []) {
            $kartu = config('konten.kartu_beranda', []);
        }

        return array_filter(is_array($kartu) ? $kartu : [], 'is_array');
    }

    /**
     * Ganti NAMA TAMPILAN satu kategori. Slug-nya tidak pernah ikut berubah.
     *
     * 🔴 Inilah satu-satunya cara mengubah daftar kategori sekarang, dan
     * sengaja dibuat begitu. Slug adalah nilai kolom `kategori` pada setiap
     * baris DKB yang sudah diimpor dan potongan URL publik
     * `/media/demografi/<slug>`; mengubahnya berarti seluruh data lama lepas
     * dari kategorinya dalam satu klik. Nama boleh salah ketik dan diperbaiki
     * kapan saja — slug tidak.
     */
    public function gantiNama(Request $request)
    {
        $slug = trim((string) $request->input('slug'));
        $judul = trim((string) $request->input('judul'));

        if ($slug === '') {
            return Balasan::gagal(['Kategori tidak disebut']);
        }
        if (mb_strlen($judul) < 3) {
            return Balasan::gagal(['Nama kategori minimal 3 huruf']);
        }
        if (mb_strlen($judul) > 60) {
            return Balasan::gagal(['Nama kategori maksimal 60 huruf']);
        }
        if (! KategoriDemografi::dikenal($slug)) {
            return Balasan::gagal(['Kategori tidak dikenal']);
        }

        $registri = KategoriDemografi::registri();
        $sekarang = collect(KategoriDemografi::semua())->firstWhere('slug', $slug);

        /*
         * Nama yang dikembalikan ke aslinya MENGHAPUS entrinya, bukan menyimpan
         * salinan yang kebetulan sama. Kalau tidak, label bawaan yang kelak
         * diperbaiki di konfigurasi akan kalah oleh salinan basi di basis data.
         */
        $asli = collect(KategoriDemografi::bawaan())->firstWhere('slug', $slug)['label']
            ?? collect($registri['kustom'])->firstWhere('slug', $slug)['label']
            ?? null;

        $label = $registri['label'];
        if ($judul === $asli) {
            unset($label[$slug]);
        } else {
            $label[$slug] = $judul;
        }
        $registri['label'] = $label;

        KategoriDemografi::simpan($registri, $request->user()->id);
        $this->log->catat(
            $request->user(), 'UBAH', 'Demografi',
            'Mengganti nama kategori "'.($sekarang['label'] ?? $slug).'" menjadi "'.$judul.'" ('.$slug.')',
            $slug, $request,
        );

        return Balasan::ok(['slug' => $slug, 'label' => $judul], ['Nama kategori disimpan: "'.$judul.'"']);
    }

    /** Tambah kategori buatan dinas. */
    public function store(Request $request)
    {
        if (KategoriDemografi::TERKUNCI) {
            return $this->terkunci();
        }

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
        $diminta = array_values(array_unique(array_filter(
            $mentah,
            fn ($s) => is_string($s) && in_array($s, $dikenal, true),
        )));

        /*
         * 🔴 BATAS ENAM DITEGAKKAN DI SINI, bukan cuma di tombolnya — dan
         * MENYUSUT SELALU BOLEH walau masih di atas batas.
         *
         * Portal yang sudah berjalan bisa punya delapan kategori menyala dari
         * sebelum aturan ini ada. Menolak setiap daftar yang panjangnya di atas
         * enam akan menolak juga usaha MEMATIKAN salah satunya — daftar 8 jadi
         * 7 tetap di atas enam — dan petugas terkunci pada keadaan yang justru
         * diminta ia perbaiki. Yang ditolak hanya yang MENAMBAH.
         */
        $sebelumnya = $registri['beranda'] === null ? PHP_INT_MAX : count($registri['beranda']);
        if (count($diminta) > KategoriDemografi::MAKS_KARTU && count($diminta) >= $sebelumnya) {
            return Balasan::gagal([
                'Paling banyak '.KategoriDemografi::MAKS_KARTU
                .' kategori yang boleh tampil di halaman utama. '
                .'Matikan salah satu dulu sebelum menyalakan yang lain.',
            ]);
        }
        $registri['beranda'] = $diminta;

        KategoriDemografi::simpan($registri, $request->user()->id);

        /*
         * 🔴 Kartu beranda IKUT DISELARASKAN, bukan dibiarkan sendiri.
         *
         * Kalau tidak, mematikan sebuah kategori menyisakan kartunya
         * menggantung, dan menyalakan kategori baru tidak memberinya kartu
         * sama sekali. Sesudah ini jumlah kartu SELALU sama dengan jumlah
         * kategori yang tampil.
         */
        $this->selaraskanKartuBeranda();
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
        if (KategoriDemografi::TERKUNCI) {
            return $this->terkunci();
        }

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
