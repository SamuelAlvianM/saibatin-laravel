<?php

namespace App\Services;

use App\Services\StatistikKependudukan as S;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * PDF "Laporan Statistik Kependudukan" — templat mengikuti unduhan portal
 * Disdukcapil Kota Tual (A4 potret: kop, empat kartu, peta + peringkat +
 * tabel per kecamatan, komposisi, perkembangan antar-periode). Datanya dari
 * StatistikKependudukan::ambil() — sama persis dengan halaman `/media/gis`.
 *
 * Peta: SVG dirakit di sini dari `resources/peta/peta-wilayah.json` (geometri
 * yang sama dengan island Highcharts), lalu disematkan sebagai data URI —
 * dompdf menggambarnya lewat php-svg-lib. Tiga lapis seperti di web: laut,
 * daratan tetangga, SELURUH kecamatan (yang tanpa data berwarna pucat).
 */
class StatistikPdf
{
    /**
     * Posisi label (satuan viewBox). 🔴 Nilai yang sama ada di
     * `resources/js/lib/peta-label.js` — ubah keduanya bersamaan.
     */
    private const LABEL = [
        'LEMONG' => [16, 150, true],
        'PESISIR UTARA' => [16, 220, true],
        'PULAU PISANG' => [16, 290, true],
        'WAY KRUI' => [16, 360, true],
        'PESISIR TENGAH' => [16, 430, true],
        'KRUI SELATAN' => [16, 500, true],
        'KARYA PENGGAWA' => [330, 205, false],
        'PESISIR SELATAN' => [455, 395, false],
        'NGAMBUR' => [545, 480, false],
        'NGARAS' => [612, 545, false],
        'BANGKUNAT' => [770, 725, false],
    ];

    private const LABEL_TETANGGA = [
        [70, 40, 'Kaur'], [650, 170, 'Lampung Barat'], [915, 700, 'Tanggamus'],
    ];

    private const SOROT = '#f5b301';

    public function unduh(array $s)
    {
        // dompdf memegang seluruh halaman (dan SVG peta) di memori.
        @ini_set('memory_limit', '256M');

        $q = http_build_query(['periode' => S::kunciPeriode($s['periode']), 'wilayah' => $s['wilayah']['kode']]);

        $pdf = Pdf::loadView('pdf.statistik', [
            's' => $s,
            'peta' => $this->petaSvg($s),
            'logo' => $this->logo(),
            'url' => rtrim((string) config('app.url'), '/').'/media/gis?'.$q,
            'diunduh' => now('Asia/Jakarta')->translatedFormat('j F Y, H.i').' WIB',
        ])->setPaper('a4');

        /*
         * "Halaman x dari n" lewat canvas dompdf. CSS `counter(pages)` tidak
         * didukung dompdf — menulis "dari 0".
         */
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $kanvas = $dompdf->getCanvas();
        $kanvas->page_text($kanvas->get_width() - 110, $kanvas->get_height() - 28, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 7, [0.39, 0.45, 0.55]);

        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower(
            str_replace('Kec. ', 'kecamatan ', S::judulWilayah($s['wilayah']))
        )), '-');

        return $pdf->download("statistik-kependudukan-{$slug}-{$s['periode']['tahun']}-s{$s['periode']['semester']}.pdf");
    }

    /** SVG peta choropleth sebagai data URI, atau null bila berkas geometri tak ada. */
    private function petaSvg(array $s): ?string
    {
        $berkas = resource_path('peta/peta-wilayah.json');
        if (! is_file($berkas)) {
            return null;
        }
        $g = json_decode(file_get_contents($berkas), true);
        $W = (int) $g['lebar'];
        $H = (int) $g['tinggi'];

        $perNama = collect($s['kecamatan'])->keyBy(fn ($k) => mb_strtoupper(trim($k['nama'])));
        $nilai = collect($s['kecamatan'])->pluck('penduduk')->filter(fn ($n) => $n !== null);
        $min = (float) ($nilai->min() ?? 0);
        $maks = (float) ($nilai->max() ?? 0);
        $w = $s['wilayah'];
        $dipilih = $w['tingkat'] === 'kecamatan' ? $w['kode'] : ($w['tingkat'] === 'desa' ? ($w['induk'] ?? null) : null);
        $e = fn ($t) => htmlspecialchars((string) $t, ENT_XML1);

        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$W}\" height=\"{$H}\" viewBox=\"0 0 {$W} {$H}\">";
        $svg .= "<rect x=\"0\" y=\"0\" width=\"{$W}\" height=\"{$H}\" fill=\"#eaf4f8\"/>";
        foreach ($g['tetangga'] as $t) {
            $svg .= "<path d=\"{$t['d']}\" fill=\"#e2e5e3\" stroke=\"#cdd3d0\" stroke-width=\"1\"/>";
        }
        $sorot = '';
        foreach ($g['kecamatan'] as $k) {
            $data = $perNama->get($k['nama']);
            $n = $data['penduduk'] ?? null;
            $warna = S::warnaUntuk($n === null ? null : (float) $n, $min, $maks);
            $svg .= "<path d=\"{$k['d']}\" fill=\"{$warna}\" stroke=\"#ffffff\" stroke-width=\"1.5\"/>";
            if ($data && $data['kode'] === $dipilih) {
                $sorot .= "<path d=\"{$k['d']}\" fill=\"none\" stroke=\"".self::SOROT.'" stroke-width="5"/>';
            }
        }
        $svg .= $sorot;

        foreach (self::LABEL_TETANGGA as [$x, $y, $teks]) {
            $svg .= "<text x=\"{$x}\" y=\"{$y}\" font-family=\"DejaVu Sans\" font-size=\"20\" font-style=\"italic\" fill=\"#8a9590\" text-anchor=\"middle\">{$e($teks)}</text>";
        }
        foreach ($g['kecamatan'] as $k) {
            $data = $perNama->get($k['nama']);
            $n = $data['penduduk'] ?? null;
            [$lx, $ly, $luar] = self::LABEL[$k['nama']] ?? [$k['pusat'][0], $k['pusat'][1], false];
            $nama = $e(S::namaCantik($k['nama']));
            $teksNilai = $n === null ? 'belum ada data' : S::angka($n).' jiwa';
            if ($luar) {
                $ujung = $lx + max(mb_strlen($nama) * 13, mb_strlen($teksNilai) * 11) + 6;
                $svg .= "<line x1=\"{$ujung}\" y1=\"".($ly - 6)."\" x2=\"{$k['pusat'][0]}\" y2=\"{$k['pusat'][1]}\" stroke=\"#334155\" stroke-width=\"1.5\"/>";
                $svg .= "<circle cx=\"{$k['pusat'][0]}\" cy=\"{$k['pusat'][1]}\" r=\"5\" fill=\"#1e293b\" stroke=\"#ffffff\" stroke-width=\"2\"/>";
                $svg .= "<text x=\"{$lx}\" y=\"{$ly}\" font-family=\"DejaVu Sans\" font-size=\"22\" font-weight=\"bold\" fill=\"#1e293b\">{$nama}</text>";
                $svg .= "<text x=\"{$lx}\" y=\"".($ly + 24)."\" font-family=\"DejaVu Sans\" font-size=\"18\" fill=\"#475569\">{$e($teksNilai)}</text>";
            } else {
                $gelap = $n !== null && array_search(S::warnaUntuk((float) $n, $min, $maks), S::SKALA_WARNA, true) >= 3;
                $warna = $gelap ? '#ffffff' : '#1e293b';
                $svg .= "<text x=\"{$lx}\" y=\"{$ly}\" font-family=\"DejaVu Sans\" font-size=\"24\" font-weight=\"bold\" fill=\"{$warna}\" text-anchor=\"middle\">{$nama}</text>";
                $svg .= "<text x=\"{$lx}\" y=\"".($ly + 26)."\" font-family=\"DejaVu Sans\" font-size=\"19\" fill=\"{$warna}\" text-anchor=\"middle\">{$e($teksNilai)}</text>";
            }
        }
        $svg .= '</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /** Logo kop (data URI, diperkecil bila GD tersedia) — pola PermohonanPdfController. */
    private function logo(): ?string
    {
        $sumber = public_path('logo-saibatin.png');
        if (! is_file($sumber)) {
            return null;
        }

        return cache()->remember('pdf.logo-statistik.'.filemtime($sumber), now()->addDays(30), function () use ($sumber) {
            $mentah = 'data:image/png;base64,'.base64_encode(file_get_contents($sumber));
            if (! function_exists('imagecreatefrompng') || ! ($asli = @imagecreatefrompng($sumber))) {
                return $mentah;
            }
            $tinggi = 104;
            $lebar = (int) round(imagesx($asli) * ($tinggi / imagesy($asli)));
            $kecil = imagecreatetruecolor($lebar, $tinggi);
            imagealphablending($kecil, false);
            imagesavealpha($kecil, true);
            imagecopyresampled($kecil, $asli, 0, 0, 0, 0, $lebar, $tinggi, imagesx($asli), imagesy($asli));
            ob_start();
            imagepng($kecil, null, 9);

            return 'data:image/png;base64,'.base64_encode(ob_get_clean());
        });
    }
}
