<?php

namespace App\Support;

use App\Models\StaticContent;

/**
 * Pembaca blok konten CMS — port `lib/use-static-content.ts` untuk sisi SERVER.
 *
 * Bedanya dengan versi klien (`resources/js/lib/konten-statis.js`): yang ini
 * dipakai halaman publik Blade, sehingga isinya ikut terkirim sebagai HTML dan
 * terbaca mesin pencari. Blok yang dibaca island tetap memakai versi klien.
 *
 * 🔴 Baris di `t_static_contents` hanya MENIMPA bawaan di `config/konten.php`,
 * dan penimpaannya per-KUNCI TERATAS. Petugas yang menyunting sebagian isi blok
 * tidak kehilangan sisanya, tapi kunci yang dihapusnya di dashboard memang
 * hilang — itu memang maksudnya, bukan kebocoran nilai bawaan yang muncul lagi.
 */
final class Konten
{
    /**
     * Ambil beberapa blok sekaligus (satu kueri).
     *
     * @param  array<int,string>  $kunci
     * @return array<string,array>
     */
    public static function ambil(array $kunci): array
    {
        $baris = StaticContent::whereIn('kunci', $kunci)->pluck('konten', 'kunci');

        // 🔴 Seluruh larik bawaan diambil sekali, lalu diindeks manual.
        // `config("konten.bawaan.beranda.hero")` TIDAK bisa dipakai: titik pada
        // nama kunci dibaca Laravel sebagai penelusuran bertingkat, sehingga ia
        // mencari `bawaan → beranda → hero` dan selalu mengembalikan null —
        // padahal kuncinya memang string harfiah "beranda.hero". Kunci itu
        // sudah ada di produksi dan tidak boleh diganti.
        $bawaan = config('konten.bawaan', []);

        $hasil = [];
        foreach ($kunci as $k) {
            $db = $baris[$k] ?? null;

            $hasil[$k] = is_array($db)
                ? array_merge($bawaan[$k] ?? [], $db)
                : ($bawaan[$k] ?? []);
        }

        return $hasil;
    }

    /** Satu blok saja. */
    public static function satu(string $kunci): array
    {
        return self::ambil([$kunci])[$kunci];
    }

    /**
     * Bentuk formulir satu blok untuk MODE EDIT — judul, deskripsi, dan daftar
     * medannya. `null` berarti blok itu memang tidak boleh disunting inline.
     *
     * Tiga sumber, diperiksa berurutan:
     *   1. `config/konten.php` → `medan` (blok tetap: hero, carousel, profil, …)
     *   2. halaman ketentuan (`config/ketentuan.php`) — bagiannya bernomor,
     *      jadi medannya dirakit dari `urutan` halaman itu
     *   3. halaman informasi `info.<grup>.<slug>` — bentuknya seragam untuk
     *      SELURUH halaman informasi, termasuk dua seksi halaman layanan PPID
     *
     * 🔴 Yang dinamis sengaja dirakit, bukan didaftar satu per satu: halaman
     * informasi ditambah dengan menambah satu entri config, dan daftar terpisah
     * pasti tertinggal — halaman baru akan tampil tanpa pensil tanpa ada yang
     * menyadarinya.
     *
     * @return array{kunci:string,judul:string,deskripsi:string,medan:array}|null
     */
    public static function skema(string $kunci): ?array
    {
        if ($tetap = config('konten.medan', [])[$kunci] ?? null) {
            return [
                'kunci' => $kunci,
                'judul' => config('konten.blok', [])[$kunci] ?? $kunci,
                'deskripsi' => $tetap['deskripsi'] ?? '',
                'medan' => $tetap['medan'],
            ];
        }

        if ($ketentuan = self::skemaKetentuan($kunci)) {
            return $ketentuan;
        }

        return self::skemaInfo($kunci);
    }

    /**
     * Kebijakan & Privasi / Syarat & Ketentuan — satu medan `list` per bagian
     * bernomor, dengan label yang sama dengan judul bagiannya di halaman.
     */
    private static function skemaKetentuan(string $kunci): ?array
    {
        foreach (config('ketentuan', []) as $nama => $halaman) {
            if ($nama === 'bawaan' || ($halaman['kunci'] ?? null) !== $kunci) {
                continue;
            }

            $medan = [['nama' => 'intro', 'label' => 'Kalimat Pembuka', 'tipe' => 'textarea']];

            // Hanya halaman yang memang punya label tanggal yang menampilkannya —
            // menambahkannya di halaman lain berarti isian yang tak pernah tampil.
            if (array_key_exists('pembaruan', config('ketentuan.bawaan', [])[$kunci] ?? [])) {
                $medan[] = ['nama' => 'pembaruan', 'label' => 'Label Terakhir Diperbarui', 'tipe' => 'text'];
            }

            foreach ($halaman['urutan'] as $bagian => $judulBagian) {
                $medan[] = ['nama' => $bagian, 'label' => "{$judulBagian} (per poin)", 'tipe' => 'list'];
            }

            return [
                'kunci' => $kunci,
                'judul' => 'Halaman — '.$halaman['title'],
                'deskripsi' => $halaman['description'],
                'medan' => $medan,
            ];
        }

        return null;
    }

    /**
     * Halaman informasi `info.<grup>.<slug>`.
     *
     * `links`, `faq`, dan `formulir` SENGAJA tidak ikut disunting: ketiganya
     * bukan teks melainkan sambungan ke bagian lain portal (tautan navigasi,
     * blok FAQ tersendiri, island formulir), dan menyuntingnya dari sini berarti
     * halaman bisa kehilangan formulirnya karena satu salah ketik.
     */
    private static function skemaInfo(string $kunci): ?array
    {
        if (! str_starts_with($kunci, 'info.') || substr_count($kunci, '.') < 2) {
            return null;
        }

        [, $grup, $slug] = explode('.', $kunci, 3);

        $bawaan = config("info-halaman.{$grup}.{$slug}");

        // Dua seksi halaman layanan PPID memakai kunci `info.ppid.<slug>` yang
        // sama bentuknya, tapi isinya di config lain.
        if (! $bawaan && $grup === 'ppid') {
            foreach (config('ppid-layanan', []) as $halaman) {
                foreach ($halaman['seksi'] as $seksi) {
                    if ($seksi['slug'] === $slug) {
                        $bawaan = $seksi['isi'];
                        break 2;
                    }
                }
            }
        }

        if (! $bawaan) {
            return null;
        }

        return [
            'kunci' => $kunci,
            'judul' => 'Halaman — '.$bawaan['title'],
            'deskripsi' => "Isi halaman /{$grup}/{$slug}.",
            'medan' => [
                ['nama' => 'title', 'label' => 'Judul Halaman', 'tipe' => 'text'],
                ['nama' => 'description', 'label' => 'Deskripsi Singkat', 'tipe' => 'textarea'],
                ['nama' => 'body', 'label' => 'Paragraf Isi', 'tipe' => 'list'],
                ['nama' => 'list', 'label' => 'Daftar Poin', 'tipe' => 'list'],
                [
                    'nama' => 'gambar', 'label' => 'Gambar/Infografis (opsional)', 'tipe' => 'image',
                    'catatan' => 'Bila diisi, gambar tampil di atas isi halaman — mis. infografis alur pelayanan.',
                ],
            ],
        ];
    }

    /**
     * Isi satu blok apa adanya, sudah digabung dengan bawaannya — dipakai
     * dialog Mode Edit sebagai nilai awal.
     *
     * Bedanya dengan `satu()`: halaman ketentuan menyimpan bawaannya di
     * `config/ketentuan.php`, bukan `config/konten.php`, jadi dialog yang cuma
     * membaca `satu()` akan tampil kosong pada dua halaman itu.
     */
    public static function isiUntukEditor(string $kunci): array
    {
        $bawaan = config('konten.bawaan', [])[$kunci]
            ?? config('ketentuan.bawaan', [])[$kunci]
            ?? self::bawaanInfo($kunci);

        return array_merge($bawaan, self::satu($kunci));
    }

    /** Isi bawaan halaman informasi, dari config-nya masing-masing. */
    private static function bawaanInfo(string $kunci): array
    {
        if (! str_starts_with($kunci, 'info.') || substr_count($kunci, '.') < 2) {
            return [];
        }

        [, $grup, $slug] = explode('.', $kunci, 3);

        if ($isi = config("info-halaman.{$grup}.{$slug}")) {
            return $isi;
        }

        foreach (config('ppid-layanan', []) as $halaman) {
            foreach ($halaman['seksi'] as $seksi) {
                if ($seksi['slug'] === $slug) {
                    return $seksi['isi'];
                }
            }
        }

        return [];
    }

    /**
     * Slide carousel siap pakai.
     *
     * Hanya slide yang PUNYA GAMBAR yang dipakai — slide setengah jadi di
     * dashboard tidak boleh muncul sebagai bingkai hitam kosong di beranda.
     * Warna latarnya diputar dari palet gelap yang sama dengan portal lama.
     */
    public static function slideCarousel(array $blok): array
    {
        $gelap = ['#0d1b2a', '#0f1923', '#0a1628', '#111827'];

        $slides = array_values(array_filter(
            is_array($blok['slides'] ?? null) ? $blok['slides'] : [],
            fn ($s) => is_array($s) && filled($s['image'] ?? null),
        ));

        return array_map(fn ($s, $i) => [
            'id' => $i + 1,
            'title' => $s['title'] ?? '',
            'subtitle' => $s['subtitle'] ?? '',
            'image' => $s['image'],
            'color' => $gelap[$i % count($gelap)],
        ], $slides, array_keys($slides));
    }
}
