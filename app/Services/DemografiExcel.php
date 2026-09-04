<?php

namespace App\Services;

use App\Models\DemografiWilayah;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as PenulisXlsx;

/**
 * Baca & tulis Excel agregat Dukcapil (format SIAK).
 * Port `lib/demografi-import.ts` + `lib/demografi-export.ts` (exceljs →
 * PhpSpreadsheet).
 *
 * 🔴 Setiap berkas punya baris header: IDEM | KODE | WILAYAH | <kolom nilai…>
 *
 * Nomor IDEM **tidak konsisten antar berkas** (kecamatan = IDEM 4 di berkas
 * jenis kelamin, tapi IDEM 3 di berkas KK/WKTP), dan format KODE berbeda
 * (bertitik "82.72.01" vs polos "827201"). Karena itu levelnya ditentukan dari
 * STRUKTUR KODE (standar Kemendagri): 6 digit = kecamatan, 10 digit = pekon.
 * Baris kab/kota (≤ 4 digit) dan dusun (mengandung huruf) diabaikan.
 */
class DemografiExcel
{
    /**
     * @return array{rows:array<int,array<string,mixed>>,kolom:array<int,string>,kecamatan:int,pekon:int}
     */
    public function baca(string $jalur): array
    {
        $lembar = IOFactory::load($jalur)->getSheet(0);

        // Baris 1 = header. Cari IDEM/KODE/WILAYAH; sisanya kolom nilai.
        $header = [];
        foreach ($lembar->getRowIterator(1, 1) as $baris) {
            foreach ($baris->getCellIterator() as $sel) {
                $header[$sel->getColumn()] = strtoupper(trim((string) $sel->getValue()));
            }
        }

        $kolomIdem = array_search('IDEM', $header, true);
        $kolomKode = array_search('KODE', $header, true);
        $kolomWilayah = array_search('WILAYAH', $header, true);

        if ($kolomIdem === false || $kolomKode === false || $kolomWilayah === false) {
            throw new \RuntimeException('Header tidak dikenali (butuh kolom IDEM, KODE, WILAYAH)');
        }

        $kolomNilai = [];
        foreach ($header as $huruf => $nama) {
            if (in_array($huruf, [$kolomIdem, $kolomKode, $kolomWilayah], true) || $nama === '') {
                continue;
            }
            $kolomNilai[$huruf] = $nama;
        }

        $rows = [];
        $kecamatan = 0;
        $pekon = 0;

        foreach ($lembar->getRowIterator(2) as $baris) {
            $nomor = $baris->getRowIndex();
            $kelas = $this->klasifikasiKode((string) $lembar->getCell($kolomKode.$nomor)->getValue());

            if (! $kelas) {
                continue;
            }

            $wilayah = trim((string) $lembar->getCell($kolomWilayah.$nomor)->getValue());
            if ($wilayah === '') {
                continue;
            }

            $data = [];
            foreach ($kolomNilai as $huruf => $nama) {
                $data[$nama] = $this->keAngka($lembar->getCell($huruf.$nomor)->getValue());
            }

            $rows[] = [
                'kode' => $kelas['kode'],
                'wilayah' => $wilayah,
                'level' => $kelas['level'],
                'parent_kode' => $kelas['level'] === 5 ? substr($kelas['kode'], 0, 6) : null,
                'data' => $data,
            ];

            $kelas['level'] === 4 ? $kecamatan++ : $pekon++;
        }

        return [
            'rows' => $rows,
            'kolom' => array_values($kolomNilai),
            'kecamatan' => $kecamatan,
            'pekon' => $pekon,
        ];
    }

    /** Susun workbook ekspor: satu sheet per kategori. */
    /**
     * @param  array{tahun:int, semester:int}|null  $periode  null = semua periode
     */
    public function tulis(?string $kategori = null, ?array $periode = null): ?Spreadsheet
    {
        $slug = array_column(config('demografi.kategori'), 'slug');
        $daftar = $kategori ? [$kategori] : $slug;

        $buku = new Spreadsheet();
        $buku->removeSheetByIndex(0);
        $adaIsi = false;

        foreach ($daftar as $k) {
            $baris = DemografiWilayah::where('kategori', $k)
                ->when($periode, fn ($w) => $w->periode($periode['tahun'], $periode['semester']))
                ->orderBy('kode')
                ->get();
            if ($baris->isEmpty()) {
                continue;
            }

            $adaIsi = true;
            // Nama sheet Excel maksimal 31 karakter.
            $lembar = $buku->createSheet()->setTitle(substr($k, 0, 31));

            $kolomNilai = array_keys($baris->first()->data ?? []);
            $lembar->fromArray(['IDEM', 'KODE', 'WILAYAH', ...$kolomNilai], null, 'A1');

            $nomor = 2;
            foreach ($baris as $b) {
                $nilai = [];
                foreach ($kolomNilai as $nama) {
                    $nilai[] = $b->data[$nama] ?? 0;
                }
                // IDEM diisi level supaya berkas hasil ekspor bisa dibaca ulang
                // oleh pengimpor ini (yang hanya bergantung pada KODE).
                $lembar->fromArray([$b->level, $b->kode, $b->wilayah, ...$nilai], null, 'A'.$nomor);
                $nomor++;
            }
        }

        return $adaIsi ? $buku : null;
    }

    public function keUnduhan(Spreadsheet $buku, string $namaFile): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($buku) {
            (new PenulisXlsx($buku))->save('php://output');
        }, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * KODE mentah → kode ternormalisasi + level.
     * 6 digit = kecamatan (4) · 10 digit = pekon (5) · sisanya diabaikan.
     *
     * @return array{kode:string,level:int}|null
     */
    private function klasifikasiKode(string $mentah): ?array
    {
        $digit = str_replace('.', '', trim($mentah));

        if ($digit === '' || ! ctype_digit($digit)) {
            return null; // ada huruf (mis. DUSUN) → lewati
        }

        return match (strlen($digit)) {
            6 => ['kode' => $digit, 'level' => 4],
            10 => ['kode' => $digit, 'level' => 5],
            default => null,
        };
    }

    private function keAngka(mixed $nilai): int
    {
        if (is_numeric($nilai)) {
            return (int) $nilai;
        }

        $bersih = preg_replace('/[^\d-]/', '', (string) $nilai);

        return $bersih === '' ? 0 : (int) $bersih;
    }
}
