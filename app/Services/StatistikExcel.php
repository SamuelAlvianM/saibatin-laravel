<?php

namespace App\Services;

use App\Models\Gallery;
use App\Models\JenisPermohonan;
use App\Models\KritikSaran;
use App\Models\News;
use App\Models\Pengaduan;
use App\Models\Permohonan;
use App\Models\Produk;
use App\Models\SkmJawaban;
use App\Models\User;
use App\Models\UserLevel;
use App\Support\StatusAkun;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as PenulisXlsx;

/**
 * Ekspor Excel statistik dashboard — port `lib/statistik-export.ts`
 * (exceljs → PhpSpreadsheet).
 *
 * Satu berkas per kartu statistik, lengkap dengan KOP SURAT (logo instansi +
 * nama pemerintah daerah + alamat) dan **sheet kedua berisi data rincian** yang
 * menjadi dasar angkanya.
 *
 * 🔴 Identitas di bawah ini SENGAJA berbeda di tiap project (Tidore/DAGA,
 * SIDAKO, SAIBATIN) — logo dan nama instansi tidak boleh disamakan saat fitur
 * ini disalin antar-project. Yang boleh sama hanya cara menggambarnya.
 */
class StatistikExcel
{
    private const INSTANSI = [
        'pemerintah' => 'PEMERINTAH KABUPATEN PESISIR BARAT',
        'dinas' => 'DINAS KEPENDUDUKAN DAN PENCATATAN SIPIL',
        'alamat' => 'Pasar Mulya Timur 01, Pasar Krui, Kec. Pesisir Tengah  ·  Telp (0728) 21XXX',
        'wilayah' => 'PESISIR BARAT  ·  LAMPUNG',
        'portal' => 'Portal SAIBATIN',
        'logo' => 'logo-saibatin.png',
        /** Warna aksen (biru SAIBATIN #1B4B72). */
        'aksen' => '1B4B72',
        'aksenMuda' => 'EAF0F6',
    ];

    /**
     * Bagian statistik yang bisa diekspor. Kuncinya dipakai apa adanya di URL
     * (`?bagian=…`) dan sebagai nama berkas.
     */
    public const BAGIAN = [
        'ringkasan' => 'Ringkasan Pelayanan',
        'progress' => 'Progress Permohonan',
        'tren' => 'Tren Permohonan 6 Bulan',
        'layanan' => 'Layanan Terpopuler',
        'harian' => 'Permohonan per Tanggal',
        'aspirasi' => 'Aspirasi Warga',
        'akun' => 'Akun Pengguna',
        'pengunjung' => 'Pengunjung Situs',
        'konten' => 'Konten Situs',
    ];

    private const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** Tinggi logo pada kop, dalam piksel. */
    private const TINGGI_LOGO = 70;

    /** Baris tempat kepala tabel duduk — kop mengambil baris 1–9. */
    private const BARIS_KEPALA = 10;

    /** Di atas ini, belang selang-seling dilewati (lihat `tulisSheet`). */
    private const MAKS_BARIS_BELANG = 2000;

    /**
     * Kolom Uraian sengaja lebar: selain enak dibaca, ia memberi ruang agar teks
     * kop yang di-merge mulai kolom B (di-tengah) tidak menabrak logo di kolom A.
     */
    private const KOLOM_URAIAN = [
        ['label' => 'No', 'lebar' => 6],
        ['label' => 'Uraian', 'lebar' => 52],
        ['label' => 'Jumlah', 'lebar' => 16, 'angka' => true],
    ];

    private const STATUS_LABEL_PERMOHONAN = [
        'MENUNGGU' => 'Menunggu Verifikasi',
        'DIPROSES' => 'Sedang Diproses',
        'SELESAI' => 'Selesai',
        'DITOLAK' => 'Ditolak',
    ];

    private const STATUS_LABEL_PENGADUAN = [
        'BARU' => 'Baru',
        'DIPROSES' => 'Diproses',
        'SELESAI' => 'Selesai',
    ];

    private const STATUS_LABEL_AKUN = [
        0 => 'Menunggu verifikasi',
        1 => 'Aktif',
        2 => 'Ditolak',
        3 => 'Nonaktif',
    ];

    public function __construct(private readonly PencacahKunjungan $kunjungan) {}

    public static function bagianValid(string $v): bool
    {
        return array_key_exists($v, self::BAGIAN);
    }

    /**
     * Susun workbook satu bagian (2 sheet: ringkas + rincian), atau SELURUH
     * bagian bila `$bagian` kosong (semua sheet ringkas dulu, lalu rincian).
     */
    public function buat(?string $bagian = null): Spreadsheet
    {
        $buku = new Spreadsheet();
        $buku->removeSheetByIndex(0);
        $buku->getProperties()
            ->setCreator(self::INSTANSI['dinas'].' '.self::INSTANSI['pemerintah'])
            ->setTitle('Statistik '.($bagian ? self::BAGIAN[$bagian] : 'Dashboard'));

        $kini = Carbon::now();

        if ($bagian) {
            $this->tulisSheet($buku, $this->ringkas($bagian, $kini));
            if ($detail = $this->rincianUntuk($bagian, $kini)) {
                $this->tulisSheet($buku, $detail);
            }
        } else {
            // Ekspor lengkap: semua ringkasan dulu, lalu rincian inti (tanpa
            // mengulang daftar permohonan yang sama berkali-kali).
            foreach (array_keys(self::BAGIAN) as $b) {
                $this->tulisSheet($buku, $this->ringkas($b, $kini));
            }
            foreach ([
                $this->rincianPermohonan($kini),
                $this->rincianAspirasi($kini),
                $this->rincianAkun($kini),
                $this->rincianKonten($kini),
            ] as $d) {
                $this->tulisSheet($buku, $d);
            }
        }

        $buku->setActiveSheetIndex(0);

        return $buku;
    }

    public function keUnduhan(Spreadsheet $buku, string $namaFile): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($buku) {
            (new PenulisXlsx($buku))->save('php://output');
        }, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store',
        ]);
    }

    // ── Penulisan sheet ──────────────────────────────────────────────────────

    /**
     * Tulis satu sheet: kop surat → judul laporan → tabel → catatan cetak.
     *
     * KOP: logo mengambang di **kiri**, empat baris teks di-merge mulai kolom B
     * sampai kolom terakhir lalu di-tengah — jadi logo punya ruangnya sendiri di
     * kolom A dan tidak menimpa tulisan.
     *
     * @param  array{sheet:string,judul:string,keterangan?:string,kolom:array,baris:array,total?:bool}  $t
     */
    private function tulisSheet(Spreadsheet $buku, array $t): void
    {
        $lembar = $buku->createSheet()->setTitle(mb_substr($t['sheet'], 0, 31));
        $lembar->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        // Kop + kepala tabel tetap terlihat saat digulir.
        $lembar->freezePane('A'.(self::BARIS_KEPALA + 1));

        $jmlKolom = count($t['kolom']);
        $kolomAkhir = Coordinate::stringFromColumnIndex($jmlKolom);
        // Teks kop mulai kolom B (bila ada ≥2 kolom) supaya kolom A bebas untuk logo.
        $kopKiri = $jmlKolom >= 2 ? 'B' : 'A';

        // ── Kop surat ────────────────────────────────────────────────────────
        $kop = [
            [self::INSTANSI['pemerintah'], 12, true, 19],
            [self::INSTANSI['dinas'], 13, true, 22],
            [self::INSTANSI['alamat'], 9, false, 15],
            [self::INSTANSI['wilayah'], 9, false, 15],
        ];
        foreach ($kop as $i => [$teks, $ukuran, $tebal, $tinggi]) {
            $r = $i + 1;
            $lembar->mergeCells("{$kopKiri}{$r}:{$kolomAkhir}{$r}");
            $sel = $lembar->getCell($kopKiri.$r);
            $sel->setValue($teks);
            $sel->getStyle()->getFont()->setName('Times New Roman')->setSize($ukuran)->setBold($tebal);
            $sel->getStyle()->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $lembar->getRowDimension($r)->setRowHeight($tinggi);
        }

        // Garis tebal khas kop surat resmi (sepenuh lebar, termasuk kolom logo).
        $lembar->getRowDimension(5)->setRowHeight(5);
        $lembar->getStyle("A5:{$kolomAkhir}5")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_MEDIUM)
            ->getColor()->setARGB('FF'.self::INSTANSI['aksen']);

        $this->pasangLogo($lembar);

        // ── Judul laporan ────────────────────────────────────────────────────
        $lembar->getRowDimension(6)->setRowHeight(8);
        $lembar->mergeCells("A7:{$kolomAkhir}7");
        $judul = $lembar->getCell('A7');
        $judul->setValue($t['judul']);
        $judul->getStyle()->getFont()->setName('Times New Roman')->setSize(12)->setBold(true)->setUnderline(true);
        $judul->getStyle()->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $lembar->getRowDimension(7)->setRowHeight(20);

        if (! empty($t['keterangan'])) {
            $lembar->mergeCells("A8:{$kolomAkhir}8");
            $ket = $lembar->getCell('A8');
            $ket->setValue($t['keterangan']);
            $ket->getStyle()->getFont()->setSize(9)->setItalic(true)->getColor()->setARGB('FF6B7280');
            $ket->getStyle()->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }
        $lembar->getRowDimension(9)->setRowHeight(6);

        // ── Kepala tabel ─────────────────────────────────────────────────────
        foreach ($t['kolom'] as $i => $k) {
            $lembar->getCell(Coordinate::stringFromColumnIndex($i + 1).self::BARIS_KEPALA)
                ->setValueExplicit($k['label'], DataType::TYPE_STRING);
        }
        $barisKepala = 'A'.self::BARIS_KEPALA.":{$kolomAkhir}".self::BARIS_KEPALA;
        $lembar->getStyle($barisKepala)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::INSTANSI['aksen']]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => $this->kotak(),
        ]);
        $lembar->getRowDimension(self::BARIS_KEPALA)->setRowHeight(22);

        // ── Isi tabel ────────────────────────────────────────────────────────
        //
        // 🔴 Nilai ditulis per sel, tapi GAYA-nya per RENTANG. Bedanya bukan
        // kosmetik: `getStyle()` pada tiap sel membuat satu objek gaya per sel,
        // dan pada sheet rincian permohonan (11.902 baris × 9 kolom = 107 ribu
        // sel) itu memakan **475 detik** — terukur, bukan dugaan. Dengan gaya
        // per rentang, berkas yang sama selesai dalam hitungan detik.
        //
        // `setValueExplicit(TYPE_STRING)` juga wajib untuk kolom bukan-angka:
        // NIK dan No. KK 16 digit akan ditafsirkan Excel sebagai bilangan lalu
        // kehilangan presisi (`1,80123E+15`) kalau dibiarkan ditebak.
        $barisAwal = self::BARIS_KEPALA + 1;
        foreach ($t['baris'] as $i => $baris) {
            $r = $barisAwal + $i;
            foreach ($t['kolom'] as $c => $k) {
                $nilai = $baris[$c] ?? '';
                $sel = $lembar->getCell(Coordinate::stringFromColumnIndex($c + 1).$r);
                if (! empty($k['angka']) || is_int($nilai) || is_float($nilai)) {
                    $sel->setValue($nilai);
                } else {
                    $sel->setValueExplicit((string) $nilai, DataType::TYPE_STRING);
                }
            }
        }

        $barisAkhirIsi = $barisAwal + max(0, count($t['baris']) - 1);
        if ($t['baris'] !== []) {
            $lembar->getStyle("A{$barisAwal}:{$kolomAkhir}{$barisAkhirIsi}")->applyFromArray([
                'font' => ['size' => 10],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => $this->kotak(),
            ]);

            foreach ($t['kolom'] as $c => $k) {
                $angka = ! empty($k['angka']);
                $huruf = Coordinate::stringFromColumnIndex($c + 1);
                $lembar->getStyle("{$huruf}{$barisAwal}:{$huruf}{$barisAkhirIsi}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => $angka
                            ? Alignment::HORIZONTAL_RIGHT
                            : ($c === 0 && $k['lebar'] <= 8 ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT),
                        'wrapText' => ! $angka,
                    ],
                    'numberFormat' => $angka ? ['formatCode' => '#,##0'] : [],
                ]);
            }

            // Belang selang-seling. Tiap baris genap = satu operasi rentang,
            // jadi biayanya sebanding jumlah baris — murah untuk tabel rekap
            // (≤ 30 baris), mahal untuk daftar 12 ribu baris yang memang tidak
            // dibaca baris-per-baris. Di atas ambang ini belangnya dilewati.
            if (count($t['baris']) <= self::MAKS_BARIS_BELANG) {
                for ($i = 1; $i < count($t['baris']); $i += 2) {
                    $r = $barisAwal + $i;
                    $lembar->getStyle("A{$r}:{$kolomAkhir}{$r}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF7F9F9');
                }
            }
        }

        // ── Baris TOTAL ──────────────────────────────────────────────────────
        if (! empty($t['total']) && $t['baris'] !== []) {
            $r = self::BARIS_KEPALA + 1 + count($t['baris']);
            foreach ($t['kolom'] as $c => $k) {
                $huruf = Coordinate::stringFromColumnIndex($c + 1);
                $sel = $lembar->getCell($huruf.$r);
                if (! empty($k['angka'])) {
                    $sel->setValue(array_sum(array_map(
                        fn ($b) => is_numeric($b[$c] ?? null) ? $b[$c] : 0,
                        $t['baris']
                    )));
                    $lembar->getStyle($huruf.$r)->applyFromArray([
                        'numberFormat' => ['formatCode' => '#,##0'],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    ]);
                } elseif ($c === 0) {
                    $sel->setValueExplicit('TOTAL', DataType::TYPE_STRING);
                    $lembar->getStyle($huruf.$r)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
            $lembar->getStyle("A{$r}:{$kolomAkhir}{$r}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::INSTANSI['aksenMuda']]],
                'borders' => $this->kotak() + [
                    'top' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF'.self::INSTANSI['aksen']],
                    ],
                ],
            ]);
            $lembar->getRowDimension($r)->setRowHeight(18);
        }

        // ── Catatan cetak ────────────────────────────────────────────────────
        $barisCatatan = self::BARIS_KEPALA + count($t['baris']) + (! empty($t['total']) ? 2 : 1) + 2;
        $lembar->mergeCells("A{$barisCatatan}:{$kolomAkhir}{$barisCatatan}");
        $catatan = $lembar->getCell('A'.$barisCatatan);
        $catatan->setValue('Dicetak dari '.self::INSTANSI['portal'].' pada '.$this->waktu(Carbon::now()));
        $catatan->getStyle()->getFont()->setSize(8)->setItalic(true)->getColor()->setARGB('FF9CA3AF');
        $catatan->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        foreach ($t['kolom'] as $i => $k) {
            $lembar->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($k['lebar']);
        }

        // Kolom A harus cukup lebar untuk menampung logo, kalau tidak logo
        // meluber ke kolom B dan menyerempet teks kop yang di-tengah di sana.
        // Lebar kolom Excel dihitung dalam satuan karakter: piksel ≈ lebar × 7 + 5.
        if ($jmlKolom >= 2 && ($lebarLogo = $this->lebarLogo())) {
            $minimal = ($lebarLogo + 10 - 5) / 7;
            $kolomA = $lembar->getColumnDimension('A');
            if ($kolomA->getWidth() < $minimal) {
                $kolomA->setWidth(ceil($minimal * 10) / 10);
            }
        }
    }

    /** @return array<string,mixed> definisi garis kotak untuk `applyFromArray`. */
    private function kotak(): array
    {
        return [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FFD5DBDB'],
            ],
        ];
    }

    private function pasangLogo(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $lembar): void
    {
        $jalur = public_path(self::INSTANSI['logo']);
        if (! is_file($jalur) || ! ($lebar = $this->lebarLogo())) {
            return;
        }

        // Mengambang di kolom A, rentang baris 1–4. Ukurannya absolut (piksel),
        // tidak bergantung lebar kolom, jadi tetap proporsional.
        $gambar = new Drawing();
        $gambar->setName('Logo');
        $gambar->setPath($jalur);
        $gambar->setHeight(self::TINGGI_LOGO);
        $gambar->setWidth($lebar);
        $gambar->setCoordinates('A1');
        $gambar->setOffsetX(4);
        $gambar->setOffsetY(3);
        $gambar->setWorksheet($lembar);
    }

    /**
     * Lebar logo pada tinggi tetap, mengikuti perbandingan ASLINYA.
     *
     * Logo tiap dinas beda rasio (Tidore 462×540, Tana Tidung 861×991, Pesisir
     * Barat 415×601); memaku lebar & tinggi ke angka tetap akan menggepengkan
     * sebagian di antaranya — persis kesalahan yang pernah terjadi pada ikon situs.
     */
    private function lebarLogo(): ?int
    {
        static $lebar = false;

        if ($lebar === false) {
            $ukuran = @getimagesize(public_path(self::INSTANSI['logo']));
            $lebar = $ukuran ? (int) round(($ukuran[0] / $ukuran[1]) * self::TINGGI_LOGO) : null;
        }

        return $lebar;
    }

    // ── Pengumpul data RINGKAS per bagian ────────────────────────────────────

    private function ringkas(string $bagian, Carbon $kini): array
    {
        return match ($bagian) {
            'ringkasan' => $this->dataRingkasan($kini),
            'progress' => $this->dataProgress($kini),
            'tren' => $this->dataTren($kini),
            'layanan' => $this->dataLayanan($kini),
            'harian' => $this->dataHarian($kini),
            'aspirasi' => $this->dataAspirasi($kini),
            'akun' => $this->dataAkun($kini),
            'pengunjung' => $this->dataPengunjung($kini),
            'konten' => $this->dataKonten($kini),
        };
    }

    /** Bentuk baris bernomor dari daftar [label, nilai]. */
    private function bernomor(array $data): array
    {
        return array_values(array_map(
            fn ($i, $d) => [$i + 1, $d[0], $d[1]],
            array_keys($data),
            $data
        ));
    }

    private function dataRingkasan(Carbon $kini): array
    {
        $perStatus = Permohonan::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $total = (int) $perStatus->sum();
        $h = fn (string $k) => (int) ($perStatus[$k] ?? 0);

        return [
            'sheet' => 'Ringkasan',
            'judul' => 'REKAPITULASI PELAYANAN PERMOHONAN ONLINE',
            'keterangan' => 'Keadaan per '.$this->tanggalPanjang($kini),
            'kolom' => self::KOLOM_URAIAN,
            // Sengaja TANPA baris TOTAL: isinya campuran total & bagian dari
            // total, menjumlahkannya menghasilkan angka yang tidak berarti.
            'baris' => $this->bernomor([
                ['Total permohonan (seluruh periode)', $total],
                ['Permohonan selesai', $h('SELESAI')],
                ['Permohonan sedang berjalan (menunggu + diproses)', $h('MENUNGGU') + $h('DIPROSES')],
                ['Permohonan ditolak', $h('DITOLAK')],
                [
                    'Permohonan bulan '.self::BULAN[$kini->month - 1].' '.$kini->year,
                    Permohonan::where('created_at', '>=', $kini->copy()->startOfMonth())->count(),
                ],
            ]),
        ];
    }

    private function dataProgress(Carbon $kini): array
    {
        $perStatus = Permohonan::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $h = fn (string $k) => (int) ($perStatus[$k] ?? 0);

        return [
            'sheet' => 'Progress Permohonan',
            'judul' => 'PROGRESS PERMOHONAN MENURUT STATUS',
            'keterangan' => 'Keadaan per '.$this->tanggalPanjang($kini),
            'kolom' => [
                ['label' => 'No', 'lebar' => 6],
                ['label' => 'Status Permohonan', 'lebar' => 46],
                ['label' => 'Jumlah', 'lebar' => 16, 'angka' => true],
            ],
            'baris' => $this->bernomor([
                ['Menunggu Verifikasi', $h('MENUNGGU')],
                ['Sedang Diproses', $h('DIPROSES')],
                ['Selesai', $h('SELESAI')],
                ['Ditolak', $h('DITOLAK')],
            ]),
            'total' => true,
        ];
    }

    private function dataTren(Carbon $kini): array
    {
        $mulai = $kini->copy()->startOfMonth()->subMonths(5);

        $ember = [];
        $indeks = [];
        for ($i = 0; $i < 6; $i++) {
            $d = $mulai->copy()->addMonths($i);
            $indeks[$d->format('Y-n')] = $i;
            $ember[] = ['label' => self::BULAN[$d->month - 1].' '.$d->year, 'jumlah' => 0];
        }

        // ⚠️ Dikelompokkan di PHP, bukan `GROUP BY MONTH()` — kolomnya
        // `datetime(3)` dan pengelompokan di SQL memakai zona waktu koneksi,
        // sedangkan aplikasi ini memaksa Asia/Jakarta di sisi PHP.
        foreach (Permohonan::where('created_at', '>=', $mulai)->pluck('created_at') as $t) {
            $k = Carbon::parse($t)->format('Y-n');
            if (isset($indeks[$k])) {
                $ember[$indeks[$k]]['jumlah']++;
            }
        }

        return [
            'sheet' => 'Tren 6 Bulan',
            'judul' => 'TREN PERMOHONAN 6 BULAN TERAKHIR',
            'keterangan' => 'Periode '.$ember[0]['label'].' s.d. '.$ember[5]['label'],
            'kolom' => [
                ['label' => 'No', 'lebar' => 6],
                ['label' => 'Bulan', 'lebar' => 40],
                ['label' => 'Jumlah Permohonan', 'lebar' => 22, 'angka' => true],
            ],
            'baris' => $this->bernomor(array_map(fn ($e) => [$e['label'], $e['jumlah']], $ember)),
            'total' => true,
        ];
    }

    private function dataLayanan(Carbon $kini): array
    {
        // Dashboard hanya menampilkan 5 teratas karena grafiknya sempit; ekspor
        // sengaja memuat SELURUH jenis layanan — laporan dinas memerlukan daftar
        // penuh, termasuk layanan yang belum pernah dimohon (jumlah 0).
        $jumlah = Permohonan::selectRaw('jenis_id, COUNT(*) c')->groupBy('jenis_id')->pluck('c', 'jenis_id');

        $baris = JenisPermohonan::get(['id', 'nama', 'kategori'])
            ->map(fn ($j) => [
                'nama' => $j->nama,
                'kategori' => $j->kategori ?? '-',
                'jumlah' => (int) ($jumlah[$j->id] ?? 0),
            ])
            ->sortBy([['jumlah', 'desc'], ['nama', 'asc']])
            ->values()
            ->map(fn ($j, $i) => [$i + 1, $j['nama'], $j['kategori'], $j['jumlah']])
            ->all();

        return [
            'sheet' => 'Layanan',
            'judul' => 'DAFTAR PENERIMA MANFAAT MENURUT JENIS PERMOHONAN',
            'keterangan' => 'Seluruh jenis layanan · keadaan per '.$this->tanggalPanjang($kini),
            'kolom' => [
                ['label' => 'No', 'lebar' => 6],
                ['label' => 'Jenis Permohonan', 'lebar' => 42],
                ['label' => 'Kategori', 'lebar' => 12],
                ['label' => 'Jumlah Pemohon', 'lebar' => 18, 'angka' => true],
            ],
            'baris' => $baris,
            'total' => true,
        ];
    }

    private function dataHarian(Carbon $kini): array
    {
        $hari = 30;
        $mulai = $kini->copy()->startOfDay()->subDays($hari - 1);

        $ember = [];
        $indeks = [];
        for ($i = 0; $i < $hari; $i++) {
            $d = $mulai->copy()->addDays($i);
            $indeks[$d->format('Y-n-j')] = $i;
            $ember[] = ['label' => $d->day.' '.self::BULAN[$d->month - 1].' '.$d->year, 'jumlah' => 0];
        }

        foreach (Permohonan::where('created_at', '>=', $mulai)->pluck('created_at') as $t) {
            $k = Carbon::parse($t)->format('Y-n-j');
            if (isset($indeks[$k])) {
                $ember[$indeks[$k]]['jumlah']++;
            }
        }

        return [
            'sheet' => 'Per Tanggal',
            'judul' => 'PERMOHONAN PER TANGGAL — 30 HARI TERAKHIR',
            'keterangan' => 'Periode '.$ember[0]['label'].' s.d. '.$ember[$hari - 1]['label'],
            'kolom' => [
                ['label' => 'No', 'lebar' => 6],
                ['label' => 'Tanggal', 'lebar' => 40],
                ['label' => 'Jumlah Permohonan', 'lebar' => 22, 'angka' => true],
            ],
            'baris' => $this->bernomor(array_map(fn ($e) => [$e['label'], $e['jumlah']], $ember)),
            'total' => true,
        ];
    }

    private function dataAspirasi(Carbon $kini): array
    {
        $awalBulan = $kini->copy()->startOfMonth();
        $perStatus = Pengaduan::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $h = fn (string $k) => (int) ($perStatus[$k] ?? 0);

        return [
            'sheet' => 'Aspirasi Warga',
            'judul' => 'REKAPITULASI ASPIRASI DAN PENGADUAN WARGA',
            'keterangan' => 'Keadaan per '.$this->tanggalPanjang($kini),
            'kolom' => self::KOLOM_URAIAN,
            'baris' => $this->bernomor([
                ['Pengaduan baru', $h(Pengaduan::STATUS_BARU)],
                ['Pengaduan sedang diproses', $h(Pengaduan::STATUS_DIPROSES)],
                ['Pengaduan selesai', $h(Pengaduan::STATUS_SELESAI)],
                ['Kritik & saran (seluruh periode)', KritikSaran::count()],
                [
                    'Kritik & saran bulan '.self::BULAN[$kini->month - 1].' '.$kini->year,
                    KritikSaran::where('created_at', '>=', $awalBulan)->count(),
                ],
                ['Responden Survei Kepuasan Masyarakat', SkmJawaban::count()],
            ]),
        ];
    }

    private function dataAkun(Carbon $kini): array
    {
        $perLevel = User::selectRaw('userlevel_id, COUNT(*) c')->groupBy('userlevel_id')->pluck('c', 'userlevel_id');
        $level = fn (array $ids) => (int) collect($ids)->sum(fn ($i) => (int) ($perLevel[$i] ?? 0));

        return [
            'sheet' => 'Akun Pengguna',
            'judul' => 'REKAPITULASI AKUN PENGGUNA PORTAL',
            'keterangan' => 'Keadaan per '.$this->tanggalPanjang($kini),
            'kolom' => self::KOLOM_URAIAN,
            'baris' => $this->bernomor([
                ['Warga', $level([UserLevel::WARGA])],
                ['Operator OPD', $level([UserLevel::OPERATOR_OPD])],
                ['Staff Dinas (admin & operator capil)', $level([UserLevel::SUPER_ADMIN, UserLevel::OPERATOR])],
                ['Jumlah seluruh akun', (int) $perLevel->sum()],
                ['— di antaranya berstatus aktif', User::where('status', StatusAkun::AKTIF)->count()],
                ['— di antaranya menunggu verifikasi', User::where('status', StatusAkun::MENUNGGU)->count()],
            ]),
        ];
    }

    private function dataPengunjung(Carbon $kini): array
    {
        $p = $this->kunjungan->statistik();

        return [
            'sheet' => 'Pengunjung',
            'judul' => 'STATISTIK PENGUNJUNG SITUS',
            'keterangan' => 'Keadaan per '.$this->waktu($kini),
            'kolom' => self::KOLOM_URAIAN,
            'baris' => $this->bernomor([
                ['Pengunjung online (aktif 5 menit terakhir)', $p['online']],
                ['Pengunjung hari ini', $p['hariIni']],
                ['Total kunjungan (seluruh periode)', $p['total']],
            ]),
        ];
    }

    private function dataKonten(Carbon $kini): array
    {
        return [
            'sheet' => 'Konten Situs',
            'judul' => 'REKAPITULASI KONTEN SITUS',
            'keterangan' => 'Keadaan per '.$this->tanggalPanjang($kini),
            'kolom' => self::KOLOM_URAIAN,
            'baris' => $this->bernomor([
                ['Berita terbit', News::where('publish', true)->count()],
                ['Draf berita', News::where('publish', false)->count()],
                ['Foto galeri', Gallery::count()],
                ['Dokumen publikasi', Produk::count()],
            ]),
            'total' => true,
        ];
    }

    // ── Sheet RINCIAN (data detail di balik angka ringkas) ───────────────────

    /**
     * Sheet rincian yang menyertai satu kartu statistik. `null` = kartu itu
     * memang tak punya data baris (mis. Pengunjung yang hanya agregat).
     */
    private function rincianUntuk(string $bagian, Carbon $kini): ?array
    {
        return match ($bagian) {
            'ringkasan', 'progress', 'layanan' => $this->rincianPermohonan($kini),
            'tren' => $this->rincianPermohonan(
                $kini,
                $kini->copy()->startOfMonth()->subMonths(5),
                '6 bulan terakhir'
            ),
            'harian' => $this->rincianPermohonan(
                $kini,
                $kini->copy()->startOfDay()->subDays(29),
                '30 hari terakhir'
            ),
            'aspirasi' => $this->rincianAspirasi($kini),
            'akun' => $this->rincianAkun($kini),
            'konten' => $this->rincianKonten($kini),
            'pengunjung' => null,
        };
    }

    /** Rincian permohonan (dasar kartu Ringkasan/Progress/Tren/Harian/Layanan). */
    private function rincianPermohonan(Carbon $kini, ?Carbon $sejak = null, ?string $labelPeriode = null): array
    {
        // Diambil per 1.000 baris, bukan sekaligus: daftar penuh berisi 11.902
        // permohonan dan menghidrasi semuanya bersama relasinya menahan puluhan
        // MB tanpa perlu — yang disimpan cukup baris teksnya.
        $baris = [];
        $no = 0;

        Permohonan::query()
            ->when($sejak, fn ($q) => $q->where('created_at', '>=', $sejak))
            ->orderByDesc('created_at')
            ->with(['user:id,user_fullname,user_nik', 'jenis:id,nama'])
            ->select(['id', 'no_register', 'status', 'created_at', 'proses_by_name', 'proses_at', 'user_id', 'jenis_id'])
            ->chunk(1000, function ($rows) use (&$baris, &$no) {
                foreach ($rows as $r) {
                    $baris[] = [
                        ++$no,
                        $r->no_register,
                        $r->jenis->nama ?? '-',
                        $r->user->user_fullname ?? '-',
                        $r->user->user_nik ?? '-',
                        self::STATUS_LABEL_PERMOHONAN[$r->status] ?? $r->status,
                        $this->waktu($r->created_at),
                        $r->proses_by_name ?? '-',
                        $r->proses_at ? $this->waktu($r->proses_at) : '-',
                    ];
                }
            });

        return [
            'sheet' => 'Rincian Permohonan',
            'judul' => 'RINCIAN PERMOHONAN',
            'keterangan' => number_format(count($baris), 0, ',', '.').' permohonan'
                .($labelPeriode ? ' · '.$labelPeriode : '').' · per '.$this->tanggalPanjang($kini),
            'kolom' => [
                ['label' => 'No', 'lebar' => 6],
                ['label' => 'No. Registrasi', 'lebar' => 24],
                ['label' => 'Jenis Layanan', 'lebar' => 34],
                ['label' => 'Nama Pemohon', 'lebar' => 26],
                ['label' => 'NIK', 'lebar' => 20],
                ['label' => 'Status', 'lebar' => 18],
                ['label' => 'Tgl Pengajuan', 'lebar' => 20],
                ['label' => 'Diproses Oleh', 'lebar' => 22],
                ['label' => 'Tgl Diproses', 'lebar' => 20],
            ],
            'baris' => $baris,
        ];
    }

    /** Rincian pengaduan (dasar kartu Aspirasi Warga). */
    private function rincianAspirasi(Carbon $kini): array
    {
        $rows = Pengaduan::orderByDesc('created_at')
            ->get(['nama', 'nik', 'subjek', 'isi', 'status', 'created_at']);

        return [
            'sheet' => 'Rincian Aspirasi',
            'judul' => 'RINCIAN ASPIRASI & PENGADUAN WARGA',
            'keterangan' => number_format($rows->count(), 0, ',', '.').' pengaduan · per '.$this->tanggalPanjang($kini),
            'kolom' => [
                ['label' => 'No', 'lebar' => 6],
                ['label' => 'Nama', 'lebar' => 24],
                ['label' => 'NIK', 'lebar' => 20],
                ['label' => 'Subjek', 'lebar' => 28],
                ['label' => 'Isi Pengaduan', 'lebar' => 50],
                ['label' => 'Status', 'lebar' => 14],
                ['label' => 'Tanggal', 'lebar' => 20],
            ],
            'baris' => $rows->values()->map(fn ($r, $i) => [
                $i + 1,
                $r->nama,
                $r->nik ?? '-',
                $r->subjek ?? '-',
                $r->isi,
                self::STATUS_LABEL_PENGADUAN[$r->status] ?? $r->status,
                $this->waktu($r->created_at),
            ])->all(),
        ];
    }

    /** Rincian akun pengguna (dasar kartu Akun Pengguna). */
    private function rincianAkun(Carbon $kini): array
    {
        $baris = [];
        $no = 0;

        User::orderByDesc('created_at')->with('level:id,nama')
            ->select(['id', 'user_id', 'user_fullname', 'userlevel_id', 'user_email', 'user_hp', 'user_kecamatan', 'status', 'created_at'])
            ->chunk(1000, function ($rows) use (&$baris, &$no) {
                foreach ($rows as $r) {
                    $baris[] = [
                        ++$no,
                        $r->user_id,
                        $r->user_fullname ?? '-',
                        $r->level->nama ?? 'Level '.$r->userlevel_id,
                        self::STATUS_LABEL_AKUN[$r->status] ?? 'Status '.$r->status,
                        $r->user_email ?? '-',
                        $r->user_hp ?? '-',
                        $r->user_kecamatan ?? '-',
                        $this->waktu($r->created_at),
                    ];
                }
            });

        return [
            'sheet' => 'Rincian Akun',
            'judul' => 'RINCIAN AKUN PENGGUNA PORTAL',
            'keterangan' => number_format(count($baris), 0, ',', '.').' akun · per '.$this->tanggalPanjang($kini),
            'kolom' => [
                ['label' => 'No', 'lebar' => 6],
                ['label' => 'User ID / NIK', 'lebar' => 22],
                ['label' => 'Nama', 'lebar' => 26],
                ['label' => 'Peran', 'lebar' => 18],
                ['label' => 'Status', 'lebar' => 18],
                ['label' => 'Email', 'lebar' => 26],
                ['label' => 'WhatsApp', 'lebar' => 16],
                ['label' => 'Kecamatan', 'lebar' => 20],
                ['label' => 'Terdaftar', 'lebar' => 20],
            ],
            'baris' => $baris,
        ];
    }

    /** Rincian konten (dasar kartu Konten Situs) — daftar berita. */
    private function rincianKonten(Carbon $kini): array
    {
        $rows = News::orderByDesc('created_at')->get(['judul', 'kategori', 'penulis', 'publish', 'created_at']);

        return [
            'sheet' => 'Rincian Konten',
            'judul' => 'RINCIAN KONTEN SITUS (BERITA)',
            'keterangan' => number_format($rows->count(), 0, ',', '.').' berita · per '.$this->tanggalPanjang($kini),
            'kolom' => [
                ['label' => 'No', 'lebar' => 6],
                ['label' => 'Judul', 'lebar' => 50],
                ['label' => 'Kategori', 'lebar' => 18],
                ['label' => 'Penulis', 'lebar' => 22],
                ['label' => 'Status', 'lebar' => 14],
                ['label' => 'Tanggal', 'lebar' => 20],
            ],
            'baris' => $rows->values()->map(fn ($r, $i) => [
                $i + 1,
                $r->judul,
                $r->kategori ?? '-',
                $r->penulis ?? '-',
                $r->publish ? 'Terbit' : 'Draf',
                $this->waktu($r->created_at),
            ])->all(),
        ];
    }

    // ── Format tanggal (id-ID, mengikuti portal Next.js) ─────────────────────

    private const BULAN_PANJANG = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    private function tanggalPanjang(Carbon $d): string
    {
        return $d->day.' '.self::BULAN_PANJANG[$d->month - 1].' '.$d->year;
    }

    private function waktu(Carbon|string|null $d): string
    {
        if (! $d) {
            return '-';
        }
        $d = $d instanceof Carbon ? $d : Carbon::parse($d);

        return $d->format('d').' '.self::BULAN[$d->month - 1].' '.$d->year.'  '.$d->format('H.i');
    }
}
