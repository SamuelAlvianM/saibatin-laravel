<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SkmJawaban;
use App\Support\Balasan;

/**
 * Rekap Survei Kepuasan Masyarakat — port `app/api/admin/skm/route.ts`.
 *
 * Nilai IKM mengikuti Permenpan RB 14/2017: rata-rata seluruh unsur pada skala
 * 1–4 dikonversi ke 0–100. Jawaban di luar rentang TIDAK dihitung (bukan
 * dianggap 0) — satu baris rusak tidak boleh menyeret turun nilai seluruh unsur.
 */
class SkmAdminController extends Controller
{
    public function __invoke()
    {
        $aspek = config('skm.aspek');
        $maks = (int) config('skm.skala_max');

        $rows = SkmJawaban::orderByDesc('created_at')->get();

        $jumlah = array_fill(0, count($aspek), 0);
        $cacah = array_fill(0, count($aspek), 0);

        foreach ($rows as $r) {
            $jawaban = is_array($r->jawaban) ? $r->jawaban : [];
            foreach (array_keys($aspek) as $i) {
                $nilai = (int) ($jawaban[(string) $i] ?? 0);
                if ($nilai >= 1 && $nilai <= $maks) {
                    $jumlah[$i] += $nilai;
                    $cacah[$i]++;
                }
            }
        }

        $rataPerAspek = [];
        foreach ($aspek as $i => $nama) {
            $rataPerAspek[] = [
                'aspek' => $nama,
                'rata' => $cacah[$i] ? round($jumlah[$i] / $cacah[$i], 2) : 0,
            ];
        }

        $rataKeseluruhan = $rataPerAspek === []
            ? 0
            : array_sum(array_column($rataPerAspek, 'rata')) / count($rataPerAspek);

        return Balasan::ok([
            'totalResponden' => $rows->count(),
            'rataPerAspek' => $rataPerAspek,
            'rataKeseluruhan' => round($rataKeseluruhan, 2),
            'nilaiIKM' => $maks > 0 ? round(($rataKeseluruhan / $maks) * 100, 2) : 0,
            'skalaMax' => $maks,
            // Ikut dikirim supaya halaman rekap tidak menyalin ulang label skala
            // yang sudah ada di `config/skm.php`.
            'skalaLabel' => config('skm.skala_label'),
            'respondenTerbaru' => $rows->take(15)->map(function ($r) use ($aspek) {
                $jawaban = is_array($r->jawaban) ? $r->jawaban : [];
                $nilai = array_map(fn ($i) => (int) ($jawaban[(string) $i] ?? 0), array_keys($aspek));

                return [
                    'id' => $r->id,
                    'nama' => $r->nama ?: 'Anonim',
                    'rataSkor' => $nilai === [] ? 0 : round(array_sum($nilai) / count($nilai), 2),
                    'saran' => $r->saran,
                    'createdAt' => $r->created_at,
                ];
            })->values(),
        ]);
    }
}
