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
