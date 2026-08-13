<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatistikExcel;
use App\Support\Balasan;
use Illuminate\Http\Request;

/**
 * Ekspor statistik dashboard ke Excel berkop surat — port
 * `app/api/admin/statistik/export/route.ts`.
 *
 * GET /api/admin/statistik/export            → semua bagian (1 sheet per kartu)
 * GET /api/admin/statistik/export?bagian=…   → satu kartu statistik
 *
 * Izinnya disamakan dengan yang boleh MELIHAT dashboard (level 1 & 2, dijaga
 * middleware `peran:petugas` di rutenya) — angka yang diekspor persis angka di
 * layar, jadi memasang syarat yang lebih ketat hanya akan membuat operator
 * memfoto layarnya.
 */
class StatistikEksporController extends Controller
{
    public function __construct(private readonly StatistikExcel $excel) {}

    public function __invoke(Request $request)
    {
        $diminta = trim((string) $request->query('bagian'));

        if ($diminta !== '' && ! StatistikExcel::bagianValid($diminta)) {
            return Balasan::gagal(['Bagian statistik tidak dikenal']);
        }

        $bagian = $diminta === '' ? null : $diminta;

        // 🔴 Terukur di data asli (11.902 permohonan · 1.386 akun): merakit
        // seluruh bagian memakan ± 12 detik dan **± 170 MB** memori — sheet
        // rincian permohonan sendirian 107 ribu sel. Batas bawaan PHP 128 MB
        // membuatnya mati di tengah jalan tanpa pesan yang berguna.
        // Kalau cPanel tidak mengizinkan sebesar ini, yang harus dipangkas
        // adalah sheet rincian, bukan angkanya.
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $buku = $this->excel->buat($bagian);

        return $this->excel->keUnduhan(
            $buku,
            'statistik-'.($bagian ?? 'semua').'-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
