<?php

namespace App\Services;

use App\Models\StaticContent;
use Carbon\CarbonImmutable;

/**
 * Jam pelayanan permohonan online (ala jam buka Google Maps).
 *
 * Per hari Minggu–Sabtu: buka/tutup + rentang `HH:MM`, plus daftar tanggal libur
 * khusus. Master switch `enabled`; bila false, permohonan bisa dibuat kapan pun.
 * Disimpan di `t_static_contents` kunci `pelayanan.jam`.
 *
 * 🔴 Berlaku untuk SEMUA pembuat permohonan — warga maupun petugas. Pemeriksaannya
 * ada di endpoint, bukan hanya di UI: gerbang yang cuma menyembunyikan tombol
 * tidak menghalangi siapa pun yang memanggil endpointnya langsung.
 *
 * Zona acuan **Asia/Jakarta**, dipaku di sini dan tidak mengikuti zona server.
 */
class JamLayanan
{
    public const KUNCI = 'pelayanan.jam';
    public const ZONA = 'Asia/Jakarta';

    public const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    /** Default: Senin–Jumat 08.00–16.00, pembatasan NONAKTIF. */
    public static function default(): array
    {
        return [
            'enabled' => false,
            'days' => array_map(fn ($i) => [
                'buka' => $i >= 1 && $i <= 5,
                'mulai' => '08:00',
                'selesai' => '16:00',
            ], range(0, 6)),
            'holidays' => [],
        ];
    }

    public function konfigurasi(): array
    {
        $baris = StaticContent::where('kunci', self::KUNCI)->first();

        return $this->bersihkan($baris?->konten);
    }

    /** Normalisasi masukan tak tepercaya (datang dari form dashboard). */
    public function bersihkan(mixed $mentah): array
    {
        $def = self::default();

        if (! is_array($mentah)) {
            return $def;
        }

        $days = $def['days'];
        if (is_array($mentah['days'] ?? null)) {
            foreach ($days as $i => $bawaan) {
                $r = $mentah['days'][$i] ?? null;
                if (! is_array($r)) {
                    continue;
                }
                $days[$i] = [
                    'buka' => ($r['buka'] ?? null) === true,
                    'mulai' => $this->jamValid($r['mulai'] ?? null) ?? $bawaan['mulai'],
                    'selesai' => $this->jamValid($r['selesai'] ?? null) ?? $bawaan['selesai'],
                ];
            }
        }

        $holidays = [];
        if (is_array($mentah['holidays'] ?? null)) {
            $holidays = array_values(array_unique(array_filter(
                $mentah['holidays'],
                fn ($t) => is_string($t) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)
            )));
            sort($holidays);
        }

        return [
            'enabled' => ($mentah['enabled'] ?? null) === true,
            'days' => $days,
            'holidays' => $holidays,
        ];
    }

    /**
     * Apakah pembuatan permohonan diizinkan saat ini?
     *
     * @return array{open:bool, message:string}
     */
    public function status(?array $cfg = null, ?CarbonImmutable $saat = null): array
    {
        $cfg ??= $this->konfigurasi();

        if (! $cfg['enabled']) {
            return ['open' => true, 'message' => ''];
        }

        $kini = ($saat ?? CarbonImmutable::now())->setTimezone(self::ZONA);
        $ymd = $kini->format('Y-m-d');
        $hari = (int) $kini->format('w'); // 0 = Minggu
        $menit = (int) $kini->format('G') * 60 + (int) $kini->format('i');

        if (in_array($ymd, $cfg['holidays'], true)) {
            return [
                'open' => false,
                'message' => "Layanan permohonan online libur pada {$ymd}. Silakan kembali di hari kerja berikutnya.",
            ];
        }

        $jam = $cfg['days'][$hari] ?? null;
        if (! ($jam['buka'] ?? false)) {
            return [
                'open' => false,
                'message' => 'Layanan permohonan online tutup pada hari '.self::HARI[$hari].'.',
            ];
        }

        $mulai = $this->keMenit($jam['mulai']);
        $selesai = $this->keMenit($jam['selesai']);

        if ($menit < $mulai || $menit >= $selesai) {
            return [
                'open' => false,
                'message' => sprintf(
                    'Layanan permohonan online hari %s hanya buka pukul %s–%s WIB.',
                    self::HARI[$hari], $jam['mulai'], $jam['selesai']
                ),
            ];
        }

        return [
            'open' => true,
            'message' => sprintf(
                'Buka hari %s pukul %s–%s WIB',
                self::HARI[$hari], $jam['mulai'], $jam['selesai']
            ),
        ];
    }

    private function jamValid(mixed $v): ?string
    {
        return is_string($v) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v) ? $v : null;
    }

    private function keMenit(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return $h * 60 + $m;
    }
}
