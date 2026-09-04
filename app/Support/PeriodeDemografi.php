<?php

namespace App\Support;

/**
 * Periode data kependudukan: satu TAHUN dan satu SEMESTER (1 atau 2).
 *
 * 🔴 KENAPA ADA. Sebelum ini `m_demografi_wilayah` sama sekali tak punya
 * dimensi waktu — kuncinya `(kategori, kode)`, satu baris per wilayah, titik.
 * Padahal Dukcapil mengirim DKB dua kali setahun, dan setiap impor MENGGANTI
 * TOTAL isi kategorinya. Artinya begitu berkas semester berikutnya diunggah,
 * semester sebelumnya lenyap: tidak bisa dibandingkan, tidak bisa ditarik lagi,
 * tidak ada jejaknya.
 *
 * ⚠️ SEMESTER, BUKAN BULAN. Dinas boleh mengunggah kapan saja — berkas semester
 * I bisa datang Juni, bisa Agustus. Yang disebut dalam laporan resmi tetap
 * "semester I" dan "semester II", jadi itu pula yang disimpan. Menyimpan bulan
 * unggahnya hanya akan melahirkan pertanyaan "ini semester berapa?" tiap kali.
 */
final class PeriodeDemografi
{
    public const SEMESTER_SATU = 1;
    public const SEMESTER_DUA = 2;

    /**
     * Periode bawaan untuk baris yang sudah terlanjur ada tanpa keterangan
     * periode. Sesuai badge yang selama ini tampil di beranda keempat portal —
     * "DKB Semester II 2024" — jadi menandainya begitu bukan tebakan,
     * melainkan menuliskan apa yang memang sudah diakui halaman depan.
     */
    public const TAHUN_BAWAAN = 2024;
    public const SEMESTER_BAWAAN = self::SEMESTER_DUA;

    /** Tahun paling awal yang masuk akal untuk data DKB. */
    public const TAHUN_MIN = 2000;

    private const ROMAWI = [1 => 'I', 2 => 'II'];

    /** Semester valid? Hanya 1 dan 2 — tidak ada semester 0 atau 3. */
    public static function semesterSah(mixed $semester): bool
    {
        return in_array((int) $semester, [self::SEMESTER_SATU, self::SEMESTER_DUA], true);
    }

    /**
     * Tahun valid? Batas atasnya tahun depan, bukan tahun ini: DKB semester II
     * kadang baru diterima dinas di awal tahun berikutnya, dan petugas berhak
     * memberinya label tahun yang benar.
     */
    public static function tahunSah(mixed $tahun): bool
    {
        $t = (int) $tahun;

        return $t >= self::TAHUN_MIN && $t <= ((int) date('Y')) + 1;
    }

    /** "Semester II 2024" — dipakai di badge beranda dan pemilih dashboard. */
    public static function label(int $tahun, int $semester): string
    {
        return 'Semester '.(self::ROMAWI[$semester] ?? $semester).' '.$tahun;
    }

    /** "DKB Semester II 2024" — badge beranda, dengan sumber datanya disebut. */
    public static function labelPanjang(int $tahun, int $semester): string
    {
        return 'DKB '.self::label($tahun, $semester);
    }

    /**
     * Urutan periode, terbaru dulu. Dipakai untuk memilih periode aktif.
     * Tahun lebih menentukan daripada semester: Semester I 2025 lebih baru
     * daripada Semester II 2024.
     */
    public static function kunciUrut(int $tahun, int $semester): int
    {
        return $tahun * 10 + $semester;
    }
}
