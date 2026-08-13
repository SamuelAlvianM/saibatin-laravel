<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Rentang waktu untuk filter dashboard — port `lib/periode.ts`.
 *
 * Periode bisa DIGESER lewat `acuan` (mis. `?periode=minggu&acuan=2026-08-03`),
 * jadi petugas bisa melihat minggu lalu / bulan lalu, bukan hanya yang berjalan.
 * Rentangnya selalu **[awal, akhir)** — akhir eksklusif, supaya seluruh hari
 * terakhir ikut terhitung tanpa harus bermain-main dengan 23:59:59.999.
 *
 * 🔴 Semua perhitungan memakai zona **Asia/Jakarta** (lihat `config/app.php`).
 * Kolom waktu di DB warisan adalah waktu lokal Jakarta; menghitungnya sebagai
 * UTC menggeser batas "hari ini" sejauh 7 jam — filter Harian akan memungut
 * permohonan kemarin sore dan melewatkan yang pagi tadi.
 */
final class Periode
{
    /** Minggu dimulai SENIN (konvensi Indonesia), bukan Minggu. */
    private const AWAL_PEKAN = CarbonImmutable::MONDAY;

    /**
     * @return array{awal: CarbonImmutable, akhir: CarbonImmutable}|null
     *         null = tanpa batas waktu (periode kosong / tidak dikenal)
     */
    public static function rentang(?string $kode, ?CarbonImmutable $acuan = null): ?array
    {
        $acuan ??= CarbonImmutable::now();

        return match ($kode) {
            'hari' => ['awal' => $acuan->startOfDay(), 'akhir' => $acuan->startOfDay()->addDay()],
            'minggu' => [
                'awal' => $acuan->startOfWeek(self::AWAL_PEKAN),
                'akhir' => $acuan->startOfWeek(self::AWAL_PEKAN)->addWeek(),
            ],
            'bulan' => ['awal' => $acuan->startOfMonth(), 'akhir' => $acuan->startOfMonth()->addMonth()],
            'tahun' => ['awal' => $acuan->startOfYear(), 'akhir' => $acuan->startOfYear()->addYear()],
            default => null,
        };
    }

    /** Baca `?acuan=YYYY-MM-DD`; kosong/tidak sah → hari ini. */
    public static function bacaAcuan(mixed $nilai): CarbonImmutable
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return CarbonImmutable::now();
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $nilai)->startOfDay();
        } catch (\Throwable) {
            return CarbonImmutable::now();
        }
    }

    /**
     * Terapkan filter periode ke query mana pun.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public static function saring($query, ?string $kode, mixed $acuan, string $kolom = 'created_at')
    {
        $rentang = self::rentang($kode, self::bacaAcuan($acuan));

        return $rentang
            ? $query->where($kolom, '>=', $rentang['awal'])->where($kolom, '<', $rentang['akhir'])
            : $query;
    }
}
