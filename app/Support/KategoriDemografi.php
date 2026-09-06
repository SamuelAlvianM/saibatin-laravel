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

        return ['kustom' => $kustom, 'beranda' => $beranda];
    }

    /** Seluruh kategori: bawaan dulu, lalu buatan dinas. */
    public static function semua(): array
    {
        return array_merge(self::bawaan(), self::registri()['kustom']);
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
        ['kustom' => $kustom, 'beranda' => $beranda] = self::registri();
        $semua = array_merge(self::bawaan(), $kustom);

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
                ],
                'updated_by' => $olehUid,
            ],
        );
    }
}
