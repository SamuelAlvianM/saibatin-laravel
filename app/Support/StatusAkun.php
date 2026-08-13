<?php

namespace App\Support;

/**
 * Status akun pengguna — kolom `users.status` (int).
 *
 * Alur:
 *   MENUNGGU → (petugas Aktifkan)        → AKTIF
 *   MENUNGGU → (petugas Tolak + alasan)  → DITOLAK → (warga Ajukan Ulang) → MENUNGGU  (loop)
 *   AKTIF    → (petugas Nonaktifkan)     → NONAKTIF  (hanya petugas yang bisa membuka lagi)
 *
 * 🔴 Login HANYA mengizinkan AKTIF. Status lain memakai pesannya masing-masing —
 * gagal login di sini bukan "sandi salah", melainkan keadaan akun, dan warga
 * harus tahu langkah berikutnya. Karena itu setiap status membawa pesan sendiri
 * dan penanda apakah warga perlu diarahkan ke halaman Cek Status.
 */
final class StatusAkun
{
    public const MENUNGGU = 0;
    public const AKTIF = 1;
    public const DITOLAK = 2;
    public const NONAKTIF = 3;

    /**
     * @return array{label:string, warna:string, pesan:string}
     */
    public static function info(int $status): array
    {
        return self::semua()[$status] ?? self::semua()[self::MENUNGGU];
    }

    /** Pesan yang ditampilkan saat login ditolak karena keadaan akun. */
    public static function pesanLogin(int $status): string
    {
        return self::info($status)['pesan'];
    }

    public static function label(int $status): string
    {
        return self::info($status)['label'];
    }

    /**
     * @return array<int, array{label:string, warna:string, pesan:string}>
     */
    public static function semua(): array
    {
        return [
            self::MENUNGGU => [
                'label' => 'Menunggu',
                'warna' => 'amber',
                'pesan' => 'Pendaftaran akun Anda sedang diproses dan menunggu verifikasi petugas. Silakan cek berkala.',
            ],
            self::AKTIF => [
                'label' => 'Aktif',
                'warna' => 'emerald',
                'pesan' => 'Akun Anda aktif. Silakan login untuk mengajukan permohonan.',
            ],
            self::DITOLAK => [
                'label' => 'Ditolak',
                'warna' => 'rose',
                'pesan' => 'Pendaftaran akun Anda ditolak. Perbaiki sesuai alasan di bawah lalu ajukan ulang.',
            ],
            self::NONAKTIF => [
                'label' => 'Nonaktif',
                'warna' => 'slate',
                'pesan' => 'Akun Anda tidak dapat digunakan beberapa saat karena alasan keamanan. Silakan hubungi Staff Disdukcapil untuk mengaktifkan akun kembali.',
            ],
        ];
    }
}
