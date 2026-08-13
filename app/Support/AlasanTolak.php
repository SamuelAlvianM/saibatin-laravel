<?php

namespace App\Support;

/**
 * Penolakan pendaftaran: daftar bagian data yang bisa ditandai petugas sebagai
 * "tidak sesuai", beserta penyandian & penguraiannya ke kolom `users.ket`.
 *
 * Tujuannya agar warga tahu BAGIAN MANA yang harus diperbaiki, bukan sekadar
 * "ditolak" — lalu saat mendaftar ulang, field itu yang disorot.
 *
 * ⚠️ Disimpan sebagai satu teks di `ket`, TANPA kolom/tabel baru. `ket` juga
 * dipakai fitur lain, jadi bentuknya sengaja dijaga tetap terbaca manusia —
 * bukan JSON. Baris pertama memuat daftar kolom, sisanya alasan bebas.
 */
final class AlasanTolak
{
    private const PREFIX = 'Data yang perlu diperbaiki: ';

    /** Urutannya mengikuti formulir pendaftaran. */
    public const KOLOM = [
        'nama' => 'Nama',
        'nik' => 'NIK',
        'kk' => 'Nomor KK',
        'hp' => 'WhatsApp',
        'email' => 'Email',
        'kecamatan' => 'Kecamatan',
        'foto' => 'Foto selfie',
        'ktp' => 'Foto KTP',
    ];

    /** Label siap-tampil untuk sekumpulan key (key tak dikenal diabaikan). */
    public static function label(array $keys): array
    {
        return array_values(array_filter(
            array_map(fn ($k) => self::KOLOM[$k] ?? null, $keys)
        ));
    }

    /** Gabungkan daftar kolom + alasan jadi satu teks `ket`. */
    public static function susun(array $kolom, string $alasan): string
    {
        $label = self::label($kolom);
        $alasan = trim($alasan);

        return $label === []
            ? $alasan
            : self::PREFIX.implode(', ', $label).".\n".$alasan;
    }

    /**
     * Pisahkan `ket` kembali menjadi kolom + alasan.
     *
     * Penolakan lama (sebelum fitur penandaan kolom ada) tidak berawalan PREFIX —
     * itu tetap sah dan dibaca sebagai alasan bebas tanpa kolom.
     *
     * @return array{kolom: array<int,string>, alasan: string}
     */
    public static function uraikan(?string $ket): array
    {
        $teks = str_replace("\r\n", "\n", $ket ?? '');

        if (! str_starts_with($teks, self::PREFIX)) {
            return ['kolom' => [], 'alasan' => trim($teks)];
        }

        $nl = strpos($teks, "\n");
        $baris = $nl === false ? $teks : substr($teks, 0, $nl);
        $sisa = $nl === false ? '' : substr($teks, $nl + 1);

        $keyByLabel = array_change_key_case(array_flip(self::KOLOM), CASE_LOWER);
        $kolom = array_values(array_filter(array_map(
            fn ($s) => $keyByLabel[strtolower(trim($s))] ?? null,
            explode(',', preg_replace('/\.\s*$/', '', substr($baris, strlen(self::PREFIX))))
        )));

        return ['kolom' => $kolom, 'alasan' => trim($sisa)];
    }
}
