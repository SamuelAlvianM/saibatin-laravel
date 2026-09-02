<?php

namespace App\Support;

/**
 * Penolakan permohonan: alasan baku, rincian data yang perlu dilengkapi, dan
 * keterangan petugas — beserta penyandian & penguraiannya ke `t_permohonan.catatan`.
 *
 * 🔴 KENAPA INI ADA. Sampai fitur ini dibuat, penolakan permohonan hanya punya
 * SATU kolom teks, dan formulirnya hanya menyediakan tempat menulis ketika
 * petugas memilih "Lainnya". Petugas yang memilih "Berkas tidak lengkap"
 * karena itu mengirim tiga kata itu apa adanya — tidak ada tempat untuk
 * menyebutkan berkas MANA yang kurang. Ditambah server yang belum mewajibkan
 * alasan sama sekali, warga bisa menerima penolakan berkali-kali tanpa pernah
 * tahu apa yang harus diperbaiki. Itu keluhan nyata dari warga, bukan dugaan.
 *
 * ⚠️ Disimpan sebagai satu teks di `catatan`, TANPA kolom atau tabel baru —
 * mengikuti pola `AlasanTolak` yang sudah dipakai untuk penolakan pendaftaran
 * akun. Alasannya bukan kemalasan: `catatan` sudah dibaca EMPAT konsumen
 * (halaman riwayat warga, surel `permohonan-ditolak`, notifikasi lonceng, dan
 * PDF tanda terima). Menambah kolom berarti menyentuh keempatnya sekaligus,
 * sementara teks yang tersusun rapi sudah terbaca benar di semuanya.
 *
 * Bentuknya sengaja dijaga TERBACA MANUSIA, bukan JSON — kalau suatu saat ada
 * yang membacanya langsung dari basis data, isinya harus tetap masuk akal.
 *
 *     Berkas tidak lengkap
 *     Data yang perlu dilengkapi: Kartu Keluarga, Akta Kelahiran.
 *     Kartu Keluarga yang diunggah terpotong pada bagian bawah.
 *
 * 🔴 PENOLAKAN LAMA TETAP TERBACA. Baris pertama yang tidak dikenali sebagai
 * alasan baku diperlakukan sebagai keterangan bebas — persis nasib ribuan
 * catatan yang sudah tersimpan sebelum fitur ini ada. Jangan menambah aturan
 * yang membuat catatan lama gagal diurai; yang hilang bukan kerapian data,
 * melainkan satu-satunya penjelasan yang pernah diterima warga.
 */
final class AlasanTolakPermohonan
{
    private const PREFIX_RINCIAN = 'Data yang perlu dilengkapi: ';

    /**
     * Alasan baku penolakan.
     *
     * ⚠️ Teksnya SAMA PERSIS dengan daftar lama di `Pages/Dashboard/Permohonan.jsx`.
     * Itu disengaja: catatan penolakan yang sudah tersimpan berisi kalimat ini
     * apa adanya, dan `uraikan()` mengenalinya dengan mencocokkan teks. Mengubah
     * satu huruf pun membuat penolakan lama berhenti terbaca sebagai alasan.
     */
    public const ALASAN = [
        'Berkas tidak lengkap',
        'Berkas tidak jelas / buram',
        'Data tidak sesuai dengan dokumen',
        'NIK / dokumen tidak valid',
        'Persyaratan belum terpenuhi',
        'Lainnya',
    ];

    /**
     * Alasan yang WAJIB disertai rincian data yang kurang.
     *
     * Keputusan dinas: keempatnya menunjuk pada berkas atau isian tertentu,
     * jadi menyebutkannya bukan tambahan — itu inti pesannya. Dua sisanya
     * (`NIK / dokumen tidak valid` dan `Lainnya`) cukup keterangan, karena
     * masalahnya tidak selalu bisa ditunjuk ke satu isian.
     */
    public const WAJIB_RINCIAN = [
        'Berkas tidak lengkap',
        'Berkas tidak jelas / buram',
        'Data tidak sesuai dengan dokumen',
        'Persyaratan belum terpenuhi',
    ];

    /** Apakah alasan ini menuntut rincian? */
    public static function perluRincian(string $alasan): bool
    {
        return in_array($alasan, self::WAJIB_RINCIAN, true);
    }

    /**
     * Pilihan rincian untuk sebuah permohonan — SELURUH isian formulirnya,
     * dikelompokkan jadi "Isian" dan "Lampiran".
     *
     * 🔴 Diambil dari skema layanan permohonan itu sendiri, bukan daftar tetap.
     * Tiap jenis permohonan punya isian dan lampiran yang berbeda, dan daftar
     * tetap akan menawarkan berkas yang tidak diminta layanan itu — persis
     * kebingungan yang mau dihilangkan.
     *
     * `type === 'file'` yang membedakan lampiran dari isian; itu satu-satunya
     * penanda yang ada di skema, dan sudah dipakai `Layanan::berkasDariPayload()`.
     *
     * @param  array|null  $form  skema dari `Layanan::formDariKode()`
     * @return array<int, array{judul: string, item: array<int, string>}>
     */
    public static function pilihan(?array $form): array
    {
        if (! $form) {
            return [];
        }

        $isian = [];
        $lampiran = [];

        foreach (Layanan::semuaField($form) as $fd) {
            $label = trim((string) ($fd['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            if (($fd['type'] ?? '') === 'file') {
                $lampiran[] = $label;
            } else {
                $isian[] = $label;
            }
        }

        // Label kembar dibuang: beberapa layanan memakai label yang sama di dua
        // seksi (mis. "Nama Lengkap" pemohon dan subjek). Petugas memilih LABEL,
        // jadi dua entri identik hanya jadi pilihan yang mustahil dibedakan.
        return array_values(array_filter([
            $isian === [] ? null : ['judul' => 'Isian', 'item' => array_values(array_unique($isian))],
            $lampiran === [] ? null : ['judul' => 'Lampiran', 'item' => array_values(array_unique($lampiran))],
        ]));
    }

    /** Seluruh label yang sah untuk permohonan ini, tanpa pengelompokan. */
    public static function labelSah(?array $form): array
    {
        return array_merge(...array_column(self::pilihan($form), 'item')) ?: [];
    }

    /**
     * Gabungkan alasan + rincian + keterangan jadi satu teks `catatan`.
     *
     * Rincian yang kosong tidak menyisakan baris kosong — catatan ini dibaca
     * warga apa adanya di halaman riwayat dan di surel.
     */
    public static function susun(string $alasan, array $rincian, string $keterangan): string
    {
        $baris = [];

        $alasan = trim($alasan);
        $keterangan = trim($keterangan);

        // "Lainnya" bukan penjelasan apa pun bagi warga — yang berarti justru
        // keterangannya. Menuliskannya hanya menambah satu baris tanpa isi.
        if ($alasan !== '' && $alasan !== 'Lainnya') {
            $baris[] = $alasan;
        }

        $rincian = array_values(array_filter(array_map('trim', $rincian), fn ($s) => $s !== ''));

        if ($rincian !== []) {
            $baris[] = self::PREFIX_RINCIAN.implode(', ', $rincian).'.';
        }

        if ($keterangan !== '') {
            $baris[] = $keterangan;
        }

        return implode("\n", $baris);
    }

    /**
     * Pisahkan `catatan` kembali jadi alasan, rincian, dan keterangan.
     *
     * Dipakai halaman detail supaya bisa menampilkannya sebagai daftar, bukan
     * satu blok teks — dan supaya formulir petugas bisa memuat ulang penolakan
     * yang sudah ada tanpa mengetik ulang.
     *
     * @return array{alasan: string, rincian: array<int, string>, keterangan: string}
     */
    public static function uraikan(?string $catatan): array
    {
        $teks = trim(str_replace("\r\n", "\n", $catatan ?? ''));

        if ($teks === '') {
            return ['alasan' => '', 'rincian' => [], 'keterangan' => ''];
        }

        $baris = explode("\n", $teks);
        $alasan = '';
        $rincian = [];
        $sisa = [];

        foreach ($baris as $i => $b) {
            $b = trim($b);

            // Baris PERTAMA saja yang boleh jadi alasan. Kalimat yang sama di
            // tengah keterangan adalah bagian dari kalimat petugas, bukan label.
            if ($i === 0 && in_array($b, self::ALASAN, true)) {
                $alasan = $b;

                continue;
            }

            if (str_starts_with($b, self::PREFIX_RINCIAN)) {
                $rincian = array_values(array_filter(array_map(
                    'trim',
                    explode(',', rtrim(substr($b, strlen(self::PREFIX_RINCIAN)), '.')),
                ), fn ($s) => $s !== ''));

                continue;
            }

            $sisa[] = $b;
        }

        return [
            'alasan' => $alasan,
            'rincian' => $rincian,
            'keterangan' => trim(implode("\n", $sisa)),
        ];
    }
}
