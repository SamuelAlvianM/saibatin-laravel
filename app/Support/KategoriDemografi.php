<?php

namespace App\Support;

use App\Models\StaticContent;

/**
 * Registri kategori demografi — yang BAWAAN ditambah yang dibuat dinas.
 *
 * 🔴 KENAPA ADA. Delapan kategori di `config/demografi.php` adalah berkas DKB
 * baku dari SIAK. Tapi dinas juga menyusun agregatnya sendiri, dan isinya
 * berganti tiap tahun mengikuti apa yang diminta pimpinan. Selama daftarnya
 * hanya ada di berkas konfigurasi, menambah satu kategori berarti menunggu
 * pengembang mengubah kode dan menerbitkan ulang portalnya — untuk pekerjaan
 * yang seharusnya milik dinas.
 *
 * ⚠️ SEMUA pemeriksaan "kategori dikenal" harus lewat `dikenal()`. Kolom
 * `kategori` di `m_demografi_wilayah` cuma teks bebas; kalau satu jalur saja
 * masih membaca `config('demografi.kategori')` langsung, kategori buatan dinas
 * ditolak di jalur itu dan petugas melihat "Kategori tidak dikenal" untuk
 * kategori yang ia buat sendiri.
 *
 * ⚠️ Registri ini HANYA memuat kategori tambahan. Yang delapan tetap di
 * konfigurasi: halaman beranda, kartu statistik, dan berkas ekspor menyebut
 * slug-nya secara langsung, dan slug yang bisa dihapus dinas akan membuat
 * semuanya menunjuk ke ruang kosong.
 */
class KategoriDemografi
{
    /** Kunci baris `t_static_contents` tempat registri disimpan. */
    public const KUNCI = 'demografi.kategori';

    /** Batas jumlah kategori kustom — daftar tak terbatas jadi tak terpakai. */
    public const MAKS_KUSTOM = 24;

    /**
     * Daftar kategori DIKUNCI: tidak bisa ditambah atau dihapus dari dasbor.
     *
     * 🔴 Kenapa dikunci. Kolom `kategori` di `m_demografi_wilayah` cuma
     * teks, dan setiap baris DKB yang sudah diimpor menempel pada slug-nya.
     * Menghapus satu kategori meninggalkan ribuan baris yang tidak dikenal
     * siapa pun — tidak muncul di layar, tidak bisa diekspor, tidak bisa
     * dihapus lewat antarmuka.
     *
     * Yang tersisa untuk dinas adalah MENGGANTI NAMANYA, dan itu aman: yang
     * berubah cuma label di layar, slug-nya tidak tersentuh sedikit pun.
     *
     * ⚠️ Satu tetapan ini mengunci ANTARMUKA SEKALIGUS ENDPOINT-nya.
     * Mengunci tombolnya saja meninggalkan store/destroy yang masih menerima
     * permintaan — terkunci di layar, terbuka bagi yang tahu alamatnya.
     */
    public const TERKUNCI = true;

    /**
     * Jatah kartu beranda: SATU per kategori, dan paling banyak enam kategori.
     *
     * 🔴 ATURANNYA 1:1, BUKAN SEKADAR BATAS ATAS. Sebelum ini kartu bebas
     * menunjuk kategori mana pun, dan enam kartu bawaan ternyata cuma menarik
     * dari TIGA kategori: `jenis-kelamin` memasok tiga sekaligus (Jumlah
     * Penduduk, Laki-laki, Perempuan) dan `wajib-ktp` dua. Beranda terlihat
     * penuh padahal yang diwakili sedikit, dan lima kategori lain yang datanya
     * sudah diimpor tidak pernah muncul sebagai angka.
     *
     * Sekarang kartu adalah WAJAH satu kategori: menyalakan kategori
     * memberinya kartu, mematikannya mencabut kartunya.
     */
    public const MAKS_KARTU = 6;

    /** Warna dipilih bergiliran supaya kartu baru tidak lahir kembar warnanya. */
    private const URUTAN_WARNA = ['biru', 'teal', 'amber', 'sky', 'emerald', 'violet'];

    /**
     * Susun kartu supaya persis satu per kategori yang tampil, seurut daftarnya.
     *
     * Setelan yang sudah ada dipertahankan; yang kembar dibuang (yang pertama
     * menang); kategori yang belum punya kartu diberi satu dengan setelan awal.
     *
     * 🔴 Saat yang menyala LEBIH dari jatahnya, yang sudah tersetel menang.
     * Portal lama bisa punya delapan kategori menyala sementara petaknya cuma
     * enam; memotong menurut urutan daftar akan membuang justru kartu yang
     * sudah punya kolom angka, dan beranda terlihat rusak karena urutan
     * penyimpanan, bukan karena keputusan siapa pun.
     */
    public static function selaraskanKartu(array $kartu, array $tampil): array
    {
        $perKategori = [];
        foreach ($kartu as $k) {
            $slug = (string) ($k['kategori'] ?? '');
            if ($slug === '' || isset($perKategori[$slug])) {
                continue;
            }
            $perKategori[$slug] = $k;
        }

        if (count($tampil) > self::MAKS_KARTU) {
            $tersetel = array_filter($tampil, fn ($k) => ! empty($perKategori[$k['slug']]['kolom']));
            $belum = array_filter($tampil, fn ($k) => empty($perKategori[$k['slug']]['kolom']));
            $tampil = array_merge(array_values($tersetel), array_values($belum));
        }

        $hasil = [];
        foreach (array_slice($tampil, 0, self::MAKS_KARTU) as $i => $kat) {
            $ada = $perKategori[$kat['slug']] ?? null;
            if ($ada) {
                $hasil[] = [...$ada, 'kategori' => $kat['slug']];

                continue;
            }
            $hasil[] = [
                'title' => $kat['label'],
                'icon' => 'Users',
                'kategori' => $kat['slug'],
                /* Kolomnya sengaja KOSONG bila tidak jelas: kartu tanpa kolom
                   tampil sebagai "belum ada data", dan itu jujur. Menebak
                   kolom sembarangan membuat beranda mengumumkan angka yang
                   tidak dimaksudkan siapa pun. */
                'kolom' => '',
                'warna' => self::URUTAN_WARNA[$i % count(self::URUTAN_WARNA)],
                'badgeKolom' => '',
            ];
        }

        return $hasil;
    }

    /** Kategori bawaan dari `config/demografi.php`. */
    public static function bawaan(): array
    {
        return config('demografi.kategori', []);
    }

    public static function slugBawaan(): array
    {
        return array_column(self::bawaan(), 'slug');
    }

    /**
     * Isi registri apa adanya.
     *
     * `beranda` bernilai `null` berarti BELUM PERNAH DIATUR — dan itu bukan hal
     * yang sama dengan daftar kosong. Belum diatur = tampilkan semua (perilaku
     * selama ini); daftar kosong = petugas memang mematikan semuanya.
     *
     * @return array{kustom: array<int, array{slug:string,label:string,fileHint:string}>, beranda: array<int,string>|null}
     */
    public static function registri(): array
    {
        $baris = StaticContent::where('kunci', self::KUNCI)->first();
        $konten = is_array($baris?->konten) ? $baris->konten : [];

        $bawaan = self::slugBawaan();
        $kustom = [];

        foreach ($konten['kustom'] ?? [] as $k) {
            if (! is_array($k) || ! isset($k['slug'], $k['label'])) {
                continue;
            }
            // Slug bawaan tidak boleh dibayangi kategori kustom bernama sama —
            // yang menang jadi tak tentu, dan datanya bercampur di kolom sama.
            if (in_array($k['slug'], $bawaan, true)) {
                continue;
            }

            $kustom[] = [
                'slug' => (string) $k['slug'],
                'label' => (string) $k['label'],
                'fileHint' => (string) ($k['fileHint'] ?? 'dibuat dinas'),
            ];
        }

        $beranda = isset($konten['beranda']) && is_array($konten['beranda'])
            ? array_values(array_filter($konten['beranda'], 'is_string'))
            : null;

        $label = [];
        foreach ((array) ($konten['label'] ?? []) as $slug => $nama) {
            if (is_string($slug) && is_string($nama) && trim($nama) !== '') {
                $label[$slug] = trim($nama);
            }
        }

        return ['kustom' => $kustom, 'beranda' => $beranda, 'label' => $label];
    }

    /**
     * Pasang nama pengganti pada daftar kategori.
     *
     * 🔴 Dipakai SEMUA jalur baca. Kalau satu jalur saja melewatkannya —
     * tab halaman utama, judul sheet ekspor, nama di editor kartu — portal
     * yang sama menyebut satu kategori dengan dua nama berbeda, dan yang
     * melihatnya tidak punya cara menebak mana yang benar.
     */
    private static function pasangLabel(array $daftar, array $label): array
    {
        return array_map(
            fn ($k) => isset($label[$k['slug']]) ? [...$k, 'label' => $label[$k['slug']]] : $k,
            $daftar,
        );
    }

    /** Seluruh kategori: bawaan dulu, lalu buatan dinas — dengan nama terkini. */
    public static function semua(): array
    {
        ['kustom' => $kustom, 'label' => $label] = self::registri();

        return self::pasangLabel(array_merge(self::bawaan(), $kustom), $label);
    }

    /** Kategori ini dikenal? Pengganti pemeriksaan langsung ke config. */
    public static function dikenal(string $slug): bool
    {
        return in_array($slug, array_column(self::semua(), 'slug'), true);
    }

    /**
     * Kategori yang tampil di halaman publik.
     *
     * Portal yang sudah berjalan tidak boleh mendadak kehilangan seluruh tabel
     * demografinya hanya karena pengaturan barunya belum pernah disentuh.
     */
    public static function tampil(): array
    {
        ['kustom' => $kustom, 'beranda' => $beranda, 'label' => $label] = self::registri();
        $semua = self::pasangLabel(array_merge(self::bawaan(), $kustom), $label);

        if ($beranda === null) {
            return $semua;
        }

        return array_values(array_filter(
            $semua,
            fn ($k) => in_array($k['slug'], $beranda, true),
        ));
    }

    /**
     * Judul → slug: huruf kecil, hanya huruf/angka, dipisah tanda hubung.
     *
     * ⚠️ Slug ini masuk ke kolom `kategori` di basis data dan ke URL publik
     * `/media/demografi/<slug>`, jadi ia tidak boleh mengandung spasi, tanda
     * baca, atau huruf non-ASCII.
     */
    public static function slug(string $judul): string
    {
        $s = strtolower(trim($judul));
        $s = (string) preg_replace('/[^a-z0-9]+/', '-', $s);
        $s = trim($s, '-');

        return substr($s, 0, 60);
    }

    /** Tulis registri kembali ke `t_static_contents`. */
    public static function simpan(array $registri, int $olehUid): void
    {
        StaticContent::updateOrCreate(
            ['kunci' => self::KUNCI],
            [
                'judul' => 'Kategori Data Demografi',
                'konten' => [
                    'kustom' => array_values($registri['kustom']),
                    'beranda' => $registri['beranda'],
                    'label' => (object) ($registri['label'] ?? []),
                ],
                'updated_by' => $olehUid,
            ],
        );
    }
}
