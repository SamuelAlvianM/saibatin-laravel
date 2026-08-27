<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SkmJawaban;
use App\Support\Balasan;

/**
 * Rekap Survei Kepuasan Masyarakat — port `app/api/admin/skm/route.ts`.
 *
 * Nilai IKM mengikuti Permenpan RB 14/2017: NRR (rata-rata skala 1–4) × 25.
 * Jawaban di luar rentang TIDAK dihitung (bukan dianggap 0) — satu baris rusak
 * tidak boleh menyeret turun nilai seluruh unsur.
 *
 * 🔴 DUA GENERASI KUESIONER HIDUP BERSAMAAN, dan keduanya wajib ikut:
 *   - kuesioner dinas 2026 : 16 pertanyaan, kunci `p1`–`p16`
 *   - warisan              : 9 unsur, kunci `0`–`8` (produksi: `u0`–`u8`),
 *                            **204 responden**
 * Menghitung hanya generasi baru membuat nilai IKM tampil seolah portalnya baru
 * dipakai segelintir orang; menghitungnya dalam satu daftar unsur yang sama
 * lebih parah lagi — jawaban lama akan dibaca sebagai jawaban 9 pertanyaan
 * pertama kuesioner baru, yang isinya berbeda. Karena itu rata-rata PER
 * PERTANYAAN dipisah per generasi, sedangkan **IKM dihitung dari rata-rata
 * tiap responden** sehingga keduanya tetap masuk pada skala yang sama (1–4).
 */
class SkmAdminController extends Controller
{
    public function __invoke()
    {
        $maks = (int) config('skm.skala_max');
        $pertanyaan = config('skm.pertanyaan');
        $warisan = config('skm.warisan');

        $rows = SkmJawaban::orderByDesc('created_at')->get();

        $jumlahBaru = [];
        $cacahBaru = [];
        $jumlahLama = array_fill(0, count($warisan), 0);
        $cacahLama = array_fill(0, count($warisan), 0);

        $rataResponden = [];
        $perGenerasi = ['baru' => 0, 'warisan' => 0, 'kosong' => 0];

        foreach ($rows as $r) {
            ['generasi' => $generasi, 'nilai' => $nilai] = $r->nilaiTerbaca();
            $perGenerasi[$generasi]++;

            if ($nilai === []) {
                continue;
            }

            // Rata-rata per RESPONDEN, bukan per jawaban: responden kuesioner 16
            // pertanyaan tidak boleh berbobot 16/9 kali lipat responden lama
            // hanya karena pertanyaannya lebih banyak.
            $rataResponden[] = array_sum($nilai) / count($nilai);

            if ($generasi === 'baru') {
                foreach ($nilai as $kunci => $v) {
                    $jumlahBaru[$kunci] = ($jumlahBaru[$kunci] ?? 0) + $v;
                    $cacahBaru[$kunci] = ($cacahBaru[$kunci] ?? 0) + 1;
                }
            } else {
                foreach ($nilai as $i => $v) {
                    $jumlahLama[(int) $i] += $v;
                    $cacahLama[(int) $i]++;
                }
            }
        }

        $rataPerPertanyaan = array_map(fn ($p, $urutan) => [
            'kunci' => $p['kunci'],
            'nomor' => $urutan + 1,
            'aspek' => $p['teks'],
            'ringkas' => $p['ringkas'],
            'rata' => ($cacahBaru[$p['kunci']] ?? 0)
                ? round($jumlahBaru[$p['kunci']] / $cacahBaru[$p['kunci']], 2)
                : 0,
            'responden' => $cacahBaru[$p['kunci']] ?? 0,
        ], $pertanyaan, array_keys($pertanyaan));

        $rataWarisan = array_map(fn ($teks, $i) => [
            'aspek' => $teks,
            'rata' => $cacahLama[$i] ? round($jumlahLama[$i] / $cacahLama[$i], 2) : 0,
            'responden' => $cacahLama[$i],
        ], $warisan, array_keys($warisan));

        $rataKeseluruhan = $rataResponden === []
            ? 0
            : array_sum($rataResponden) / count($rataResponden);

        return Balasan::ok([
            'totalResponden' => $rows->count(),
            'respondenDinilai' => count($rataResponden),
            'generasi' => [
                'baru' => $perGenerasi['baru'],
                'warisan' => $perGenerasi['warisan'],
                'kosong' => $perGenerasi['kosong'],
            ],
            // Nama `rataPerAspek` dipertahankan supaya halaman rekap & klien lama
            // tidak perlu diubah namanya; isinya kini 16 pertanyaan dinas.
            'rataPerAspek' => $rataPerPertanyaan,
            'rataWarisan' => $rataWarisan,
            'rataKeseluruhan' => round($rataKeseluruhan, 2),
            'nilaiIKM' => $maks > 0 ? round(($rataKeseluruhan / $maks) * 100, 2) : 0,
            'skalaMax' => $maks,
            'skalaLabel' => config('skm.skala_label_umum'),
            'demografi' => $this->demografi($rows),
            'respondenTerbaru' => $rows->take(15)->map(fn ($r) => [
                'id' => $r->id,
                'nama' => $r->nama ?: 'Anonim',
                'produkLayanan' => $r->produk_layanan,
                'disabilitas' => $r->disabilitas,
                'kuesioner' => $r->nilaiTerbaca()['generasi'],
                'rataSkor' => $r->rataSkor(),
                'saran' => $r->saran,
                'createdAt' => $r->created_at,
            ])->values(),
        ]);
    }

    /**
     * Sebaran identitas responden — hanya bermakna untuk kuesioner 2026, karena
     * kolomnya memang baru ada di sana. Responden lama tampil sebagai "tidak
     * diisi", bukan dipaksa masuk salah satu kategori.
     *
     * @param  \Illuminate\Support\Collection<int,SkmJawaban>  $rows
     */
    private function demografi($rows): array
    {
        $hitung = function (string $kolom) use ($rows) {
            return $rows->groupBy(fn ($r) => filled($r->{$kolom}) ? $r->{$kolom} : 'Tidak diisi')
                ->map->count()
                ->sortDesc()
                ->map(fn ($jumlah, $label) => ['label' => $label, 'jumlah' => $jumlah])
                ->values();
        };

        return [
            'pendidikan' => $hitung('pendidikan'),
            'pekerjaan' => $hitung('pekerjaan'),
            'produkLayanan' => $hitung('produk_layanan'),
            'jenisKelamin' => $hitung('jenis_kelamin'),
            // 🔴 Perbandingan KETAT wajib di sini. `where('disabilitas', false)`
            // memakai `==`, dan `null == false` bernilai true di PHP — 204
            // responden kuesioner lama (yang tidak pernah ditanya) ikut
            // terhitung sebagai "bukan penyandang disabilitas", sekaligus masuk
            // hitungan "tidak ditanya". Angkanya jadi dobel dan laporannya salah.
            'disabilitas' => [
                'ya' => $rows->where('disabilitas', '===', true)->count(),
                'tidak' => $rows->where('disabilitas', '===', false)->count(),
                'tidakDitanya' => $rows->whereNull('disabilitas')->count(),
                'jenis' => $rows->where('disabilitas', '===', true)
                    ->groupBy(fn ($r) => $r->jenis_disabilitas ?: 'Tidak disebutkan')
                    ->map->count()
                    ->map(fn ($jumlah, $label) => ['label' => $label, 'jumlah' => $jumlah])
                    ->values(),
            ],
        ];
    }
}
