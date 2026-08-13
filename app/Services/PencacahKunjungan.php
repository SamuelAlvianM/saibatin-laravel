<?php

namespace App\Services;

use App\Models\Kunjungan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Pencacah kunjungan situs publik — satu baris per pengunjung anonim per hari.
 *
 * Pengunjung dikenali lewat cookie UUID (`saibatin_vid`), bukan IP: IP berbagi
 * di jaringan kantor/warnet akan menyatukan banyak orang jadi satu.
 *
 * ⚠️ "Hari ini" dihitung pada **zona aplikasi** (Asia/Jakarta), bukan zona server
 * maupun UTC. Portal Next.js sempat harus membungkus tanggalnya di `Date.UTC`
 * agar tidak bergeser sehari saat Prisma mengonversi ke UTC; di sini kolomnya
 * `DATE` dan Carbon menuliskan tanggal lokal apa adanya, jadi masalah itu hilang
 * — asalkan `config('app.timezone')` benar (lihat komentar di config/app.php).
 */
class PencacahKunjungan
{
    public const COOKIE = 'saibatin_vid';

    /** Ambang "sedang online". */
    private const JENDELA_ONLINE_MENIT = 5;

    public function tanggalHariIni(): string
    {
        return Carbon::now()->toDateString();
    }

    /** @return array{online:int, hariIni:int, total:int} */
    public function statistik(): array
    {
        return [
            'online' => Kunjungan::where('last_seen', '>=', Carbon::now()->subMinutes(self::JENDELA_ONLINE_MENIT))->count(),
            'hariIni' => Kunjungan::whereDate('tanggal', $this->tanggalHariIni())->count(),
            'total' => (int) Kunjungan::sum('hits'),
        ];
    }

    /**
     * Catat ping dari halaman publik.
     *
     * $tambahHits=false hanya menyegarkan status online (dipakai saat tab kembali
     * aktif) — tanpa itu, satu orang yang membiarkan tab terbuka akan menggelembungkan
     * angka "total tampilan".
     *
     * Kegagalan mencatat TIDAK boleh mengganggu halaman: ini fitur hitung-hitungan,
     * bukan bagian dari layanan.
     */
    public function catat(Request $request, bool $tambahHits = true): void
    {
        $vid = (string) $request->cookie(self::COOKIE);

        if (! preg_match('/^[0-9a-f-]{36}$/i', $vid)) {
            $vid = (string) Str::uuid();
            Cookie::queue(cookie(
                name: self::COOKIE,
                value: $vid,
                minutes: 60 * 24 * 365,
                path: '/',
                secure: null,
                httpOnly: true,
                sameSite: 'lax',
            ));
        }

        try {
            $baris = Kunjungan::firstOrNew([
                'visitor_id' => $vid,
                'tanggal' => $this->tanggalHariIni(),
            ]);

            $baris->hits = $baris->exists
                ? $baris->hits + ($tambahHits ? 1 : 0)
                : 1;
            $baris->last_seen = Carbon::now();
            $baris->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
