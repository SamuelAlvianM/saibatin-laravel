<?php

namespace App\Services;

use App\Models\DemografiWilayah;
use App\Support\KategoriDemografi;
use App\Support\PeriodeDemografi;
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
    /** Akhiran nama sheet rincian desa yang dibuat oleh ekspor portal ini. */
    private const AKHIRAN_DESA = '— DESA';

    /** Kop surat memakan sembilan baris; header tabel dicari sampai sini. */
    private const BARIS_HEADER_MAKS = 15;

    /** Kepala kolom yang bukan data: penanda wilayah & penomoran laporan. */
    private const BUKAN_KOLOM_NILAI = ['IDEM', 'KODE', 'WILAYAH', 'NO', 'NO.'];

    public function __construct(private readonly StatistikExcel $statistik) {}
    /**
     * @return array{rows:array<int,array<string,mixed>>,kolom:array<int,string>,kecamatan:int,pekon:int}
     */
    public function baca(string $jalur): array
    {
        $buku = IOFactory::load($jalur);
        $lembar = $buku->getSheet(0);
        $utama = $this->bacaLembar($lembar);

        /*
         * 🔴 Sheet KEDUA ikut dibaca bila ia rincian desa dari sheet pertama.
         *
         * Ekspor portal ini memisahkan kecamatan dan desa ke dua sheet, supaya
         * baris TOTAL tidak menjumlahkan angka yang sama dua kali. Membaca
         * sheet pertama saja berarti berkas yang diunduh lalu diunggah balik
         * kehilangan SELURUH rincian desanya — tanpa galat, tanpa peringatan;
         * petugas baru sadar saat tabel desa di halaman publik mendadak kosong.
         *
         * Hanya sheet ke-2, dan hanya bila namanya berakhiran "— Desa".
         * Membaca semua sheet akan menuang delapan kategori dari berkas "Export
         * Semua" ke dalam satu kategori tujuan — angka agama tersimpan sebagai
         * jenis kelamin.
         */
        $rows = $utama['rows'];
        if ($buku->getSheetCount() > 1) {
            $kedua = $buku->getSheet(1);
            $akhiran = mb_strtoupper(self::AKHIRAN_DESA);
            if (str_ends_with(mb_strtoupper(trim($kedua->getTitle())), $akhiran)) {
                // Kode yang sudah ada menang: sheet kecamatan sumber resminya.
                $ada = array_column($rows, 'kode');
                foreach ($this->bacaLembar($kedua)['rows'] as $r) {
                    if (! in_array($r['kode'], $ada, true)) {
                        $rows[] = $r;
                    }
                }
            }
        }

        $kecamatan = count(array_filter($rows, fn ($r) => $r['level'] === 4));

        return [
            'rows' => $rows,
            'kolom' => $utama['kolom'],
            'kecamatan' => $kecamatan,
            'pekon' => count($rows) - $kecamatan,
        ];
    }

    /**
     * Baca satu lembar: baris data + nama kolom nilainya.
     *
     * @return array{rows:array<int,array<string,mixed>>,kolom:array<int,string>}
     */
    private function bacaLembar(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $lembar): array
    {

        /*
         * 🔴 Baris header DICARI, tidak dipakukan ke baris 1.
         *
         * Berkas hasil ekspor portal ini sendiri berkop surat: nama dinas,
         * logo, judul laporan — barulah tabelnya, di baris kesepuluh. Selama
         * pengimpor bersikeras membaca baris 1, berkas yang baru saja diunduh
         * petugas ditolak "Header tidak dikenali", padahal isinya persis benar.
         *
         * IDEM tidak lagi diwajibkan: levelnya toh ditentukan dari STRUKTUR
         * KODE, dan angka IDEM tidak konsisten antar berkas SIAK. Mewajibkan
         * kolom yang tidak pernah dipakai hanya menolak berkas yang sebetulnya
         * terbaca.
         */
        $header = [];
        $barisHeader = 0;
        foreach ($lembar->getRowIterator(1, self::BARIS_HEADER_MAKS) as $baris) {
            $calon = [];
            foreach ($baris->getCellIterator() as $sel) {
                $calon[$sel->getColumn()] = strtoupper(trim((string) $sel->getValue()));
            }
            if (in_array('KODE', $calon, true) && in_array('WILAYAH', $calon, true)) {
                $header = $calon;
                $barisHeader = $baris->getRowIndex();
                break;
            }
        }

        if ($barisHeader === 0) {
            throw new \RuntimeException(
                'Header tidak dikenali (butuh kolom KODE dan WILAYAH pada '
                .self::BARIS_HEADER_MAKS.' baris pertama)'
            );
        }

        $kolomKode = array_search('KODE', $header, true);
        $kolomWilayah = array_search('WILAYAH', $header, true);

        // Kolom nilai = kolom bernama yang bukan penanda/penomoran laporan.
        $kolomNilai = [];
        foreach ($header as $huruf => $nama) {
            if ($nama === '' || in_array($nama, self::BUKAN_KOLOM_NILAI, true)) {
                continue;
            }
            $kolomNilai[$huruf] = $nama;
        }

        $rows = [];
        $kecamatan = 0;
        $pekon = 0;

        foreach ($lembar->getRowIterator($barisHeader + 1) as $baris) {
            $nomor = $baris->getRowIndex();
            $kelas = self::klasifikasiKodeImpor((string) $lembar->getCell($kolomKode.$nomor)->getValue());

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

        return ['rows' => $rows, 'kolom' => array_values($kolomNilai)];
    }

    /**
     * Susun workbook ekspor — BERKOP SURAT, sama dengan ekspor statistik.
     *
     * 🔴 Berkas ini keluar dari portal pemerintah dan beredar sebagai lampiran
     * surat, bahan rapat, dan cetakan. Sebelumnya isinya cuma satu baris header
     * abu-abu tanpa satu pun keterangan: tidak ada nama dinas, tidak ada
     * periode, tidak ada tanggal cetak. Berkas seperti itu tidak bisa
     * dipertanggungjawabkan — dibuka seminggu kemudian, tidak ada yang tahu ini
     * data semester berapa.
     *
     * ⚠️ SATU SHEET UNTUK KECAMATAN, SATU LAGI UNTUK DESA. Baris kecamatan
     * adalah jumlah desa di bawahnya; menaruh keduanya dalam satu tabel membuat
     * baris TOTAL menjumlahkan angka yang sama dua kali. Pemisahan ini bukan
     * soal rapi, melainkan soal angkanya benar.
     *
     * @param  array{tahun:int, semester:int}|null  $periode  null = semua periode
     */
    public function tulis(?string $kategori = null, ?array $periode = null): ?Spreadsheet
    {
        /*
         * 🔴 Daftar kategori dari REGISTRI, bukan config.
         *
         * Tanpa ini "Export Semua" diam-diam melewatkan seluruh kategori
         * buatan dinas — berkasnya terlihat lengkap padahal tidak.
         */
        $semua = KategoriDemografi::semua();
        $daftar = $kategori
            ? array_values(array_filter($semua, fn ($k) => $k['slug'] === $kategori))
            : $semua;

        if ($kategori && $daftar === []) {
            return null;
        }

        $buku = new Spreadsheet();
        $buku->removeSheetByIndex(0);
        $buku->getProperties()->setTitle('Data Kependudukan');

        $keterangan = $periode
            ? PeriodeDemografi::labelPanjang($periode['tahun'], $periode['semester'])
            : 'Seluruh periode';
        $adaIsi = false;

        foreach ($daftar as $kat) {
            $baris = DemografiWilayah::where('kategori', $kat['slug'])
                ->when($periode, fn ($w) => $w->periode($periode['tahun'], $periode['semester']))
                ->orderBy('kode')
                ->get();
            if ($baris->isEmpty()) {
                continue;
            }

            $adaIsi = true;
            $label = $kat['label'];
            $kecamatan = $baris->where('level', 4)->values();
            $desa = $baris->where('level', 5)->values();

            // Kecamatan: tabel utama, lengkap dengan baris TOTAL.
            if ($kecamatan->isNotEmpty()) {
                $nilai = array_keys($kecamatan->first()->data ?? []);
                $this->statistik->tulisSheet($buku, [
                    'sheet' => $label,
                    'judul' => 'DATA KEPENDUDUKAN — '.mb_strtoupper($label),
                    'keterangan' => $keterangan.' · per kecamatan',
                    'kolom' => $this->susunKolom($nilai),
                    'baris' => $this->susunBaris($kecamatan, $nilai),
                    'total' => true,
                ]);
            }

            /*
             * Desa: sheet terpisah, TANPA baris TOTAL. Totalnya sudah ada di
             * sheet kecamatan; mengulangnya di sini hanya mengundang orang
             * menjumlahkan keduanya.
             */
            if ($desa->isNotEmpty()) {
                $nilai = array_keys($desa->first()->data ?? []);
                $this->statistik->tulisSheet($buku, [
                    'sheet' => mb_substr($label.' — Desa', 0, 31),
                    'judul' => 'DATA KEPENDUDUKAN — '.mb_strtoupper($label).' (RINCIAN DESA)',
                    'keterangan' => $keterangan.' · per desa/kelurahan',
                    'kolom' => $this->susunKolom($nilai),
                    'baris' => $this->susunBaris($desa, $nilai),
                ]);
            }
        }

        if (! $adaIsi) {
            return null;
        }

        $buku->setActiveSheetIndex(0);

        return $buku;
    }

    /**
     * 🔴 Kepala kolom memakai istilah SIAK apa adanya: KODE dan WILAYAH.
     *
     * Berkas ekspor ini bukan cuma bacaan — ia juga dipakai untuk diunggah
     * balik, mis. setelah satu angka dikoreksi di Excel. Menamainya "Kecamatan"
     * atau "Desa / Kelurahan" membuatnya lebih enak dibaca tapi tidak lagi
     * dikenali pengimpor, dan petugas baru tahu setelah unggahannya ditolak.
     * Baris mana yang dimuat sudah disebut pada keterangan di bawah judul.
     *
     * @param  array<int,string>  $nilai
     * @return array<int,array{label:string,lebar:int,angka?:bool}>
     */
    private function susunKolom(array $nilai): array
    {
        $kolom = [
            ['label' => 'No', 'lebar' => 6],
            ['label' => 'KODE', 'lebar' => 14],
            ['label' => 'WILAYAH', 'lebar' => 30],
        ];
        foreach ($nilai as $n) {
            $kolom[] = ['label' => $n, 'lebar' => max(12, mb_strlen($n) + 4), 'angka' => true];
        }

        return $kolom;
    }

    /**
     * @param  \Illuminate\Support\Collection<int,DemografiWilayah>  $baris
     * @param  array<int,string>  $nilai
     * @return array<int,array<int,string|int>>
     */
    private function susunBaris($baris, array $nilai): array
    {
        $hasil = [];
        foreach ($baris as $i => $b) {
            $satu = [$i + 1, $b->kode, $b->wilayah];
            foreach ($nilai as $nama) {
                $satu[] = (int) ($b->data[$nama] ?? 0);
            }
            $hasil[] = $satu;
        }

        return $hasil;
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
     * Versi untuk IMPOR Excel: kabupaten/kota sengaja dilewati.
     *
     * Berkas SIAK memuat baris kabupaten sebagai ringkasan, dan angkanya sudah
     * terkandung di baris kecamatan di bawahnya. Menyimpannya berarti menaruh
     * jebakan penjumlahan ganda di tabel yang sama.
     *
     * @return array{kode:string,level:int}|null
     */
    private static function klasifikasiKodeImpor(string $mentah): ?array
    {
        $hasil = self::klasifikasiKode($mentah);

        return $hasil && $hasil['level'] >= 4 ? $hasil : null;
    }

    /**
     * KODE mentah → kode ternormalisasi + level, menurut standar Kemendagri.
     * 4 digit = kabupaten/kota (3) · 6 = kecamatan (4) · 10 = desa (5).
     *
     * 🔴 SATU ATURAN UNTUK SEMUA JALUR MASUK. Penyimpanan manual dari editor
     * dulu punya aturannya sendiri — `strlen($kode) === 10 ? 5 : 4` — sehingga
     * SEMUA yang bukan 10 digit jadi kecamatan, termasuk baris kabupaten/kota
     * berkode 4 digit. Sekali saja petugas membuka editor lalu menekan Simpan,
     * baris "KOTA TIDORE KEPULAUAN" naik pangkat jadi kecamatan ke-9, dan
     * setiap penjumlahan tingkat kecamatan menghitung seluruh kota DUA KALI.
     *
     * Terukur di TIDORE (7 Sep 2026): tujuh kategori punya 8 kecamatan, tapi
     * `jenis-kelamin` — yang paling sering disunting karena memasok tiga kartu
     * beranda — punya 9, dan yang ke-9 berkode 8272 sepanjang 4 digit.
     *
     * @return array{kode:string,level:int}|null
     */
    public static function klasifikasiKode(string $mentah): ?array
    {
        $teks = trim($mentah);

        /*
         * 🔴 Mengandung HURUF → ditolak, bukan dikupas hurufnya.
         *
         * Dulu baris ini langsung membuang semua yang bukan angka. Untuk
         * ".DUSUN" hasilnya kebetulan benar (tak ada angka tersisa), tapi untuk
         * teks yang memuat angka hasilnya bencana: catatan kaki ekspor
         * "Dicetak dari Portal ... pada 07 Sep 2026, 10.33" menyusut jadi
         * "0720261033" — sepuluh digit, jadi terbaca sebagai KODE DESA yang
         * sah, dan angka-angkanya ikut tersimpan sebagai jumlah penduduk.
         * Terukur saat berkas hasil ekspor diunggah balik: satu desa hantu
         * berpenduduk 720.261.033 jiwa, tanpa satu pun galat.
         *
         * Kode wilayah Kemendagri tidak pernah berhuruf; yang berhuruf memang
         * bukan baris data.
         */
        if (preg_match('/[a-z]/i', $teks)) {
            return null;
        }

        $digit = preg_replace('/\D/', '', $teks) ?? '';

        if ($digit === '') {
            return null;
        }

        return match (strlen($digit)) {
            4 => ['kode' => $digit, 'level' => 3],
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
