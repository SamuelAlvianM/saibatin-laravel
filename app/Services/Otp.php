<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

/**
 * OTP stateless — TANPA tabel di database.
 *
 * Kodenya tidak pernah disimpan server. Yang beredar hanyalah `challenge`:
 * `<kedaluwarsa>.<hmac(identitas, kode, kedaluwarsa)>`. Kode tidak bisa dibaca
 * balik dari challenge, dan server memverifikasi dengan menghitung ulang HMAC.
 *
 * Alurnya tiga langkah:
 *   1. kirim()      → kode dikirim via email/WA, klien menerima `challenge`
 *   2. verifikasi() → kode + challenge cocok → server menerbitkan `bukti` (30 menit)
 *   3. register     → cekBukti() sebelum akun dibuat
 *
 * Kenapa stateless: hosting target tidak punya Redis, dan menyimpan OTP di MySQL
 * berarti satu tabel + pembersihan berkala untuk data yang umurnya 5 menit.
 */
class Otp
{
    private const TTL_KODE = 300;   // kode berlaku 5 menit
    private const TTL_BUKTI = 1800; // bukti verifikasi berlaku 30 menit

    public function __construct(private readonly Fonnte $fonnte) {}

    /** Normalisasi nomor Indonesia ke format 62…; null bila tidak valid. */
    public function normalisasiHp(?string $raw): ?string
    {
        $d = preg_replace('/\D/', '', (string) $raw);

        $inti = str_starts_with($d, '62') ? $d
            : (str_starts_with($d, '0') ? '62'.substr($d, 1) : null);

        return ($inti && strlen($inti) >= 10 && strlen($inti) <= 15) ? $inti : null;
    }

    public function normalisasiEmail(?string $raw): ?string
    {
        $e = strtolower(trim((string) $raw));

        return (filter_var($e, FILTER_VALIDATE_EMAIL) && strlen($e) <= 254) ? $e : null;
    }

    /**
     * Kanal pengiriman yang aktif.
     * `OTP_CHANNEL` memaksa; tanpa itu: email bila SMTP terkonfigurasi, WA bila
     * token Fonnte ada, null bila keduanya kosong.
     */
    public function kanal(): ?string
    {
        $paksa = strtolower((string) env('OTP_CHANNEL', ''));
        if (in_array($paksa, ['email', 'wa'], true)) {
            return $paksa;
        }

        if ($this->emailSiap()) {
            return 'email';
        }

        return $this->fonnte->aktif() ? 'wa' : null;
    }

    /**
     * 🔴 SAKELAR MATI OTP — dimatikan atas permintaan user (8 Agu 2026),
     * mengikuti portal Next.js (`lib/otp.ts`, commit d8ea801).
     *
     * Selama `false`: OTP tidak diminta di server DAN tidak ditampilkan di
     * formulir pendaftaran (klien punya sakelar kembarannya, `OTP_AKTIF` di
     * `resources/js/Pages/Auth/Register.jsx` — ubah keduanya bersamaan).
     *
     * Mesin OTP-nya sengaja TIDAK dihapus: rute `/otp/send` & `/otp/verify`,
     * `buat()`/`verifikasi()`/`cekBukti()`, dan blok UI-nya semua masih utuh.
     * Menyalakan lagi cukup mengembalikan konstanta ini ke `true`.
     */
    private const AKTIF = false;

    /**
     * Apakah OTP diwajibkan saat pendaftaran?
     *
     * Ada kanal aktif → wajib. Dev tanpa kanal → tetap wajib, tapi kodenya
     * dikembalikan ke layar supaya alurnya bisa diuji. Production tanpa kanal →
     * DILEWATI, supaya pendaftaran warga tidak terkunci hanya karena layanan
     * OTP belum dikonfigurasi.
     */
    public function wajib(): bool
    {
        if (! self::AKTIF) {
            return false;
        }

        return $this->kanal() !== null || ! app()->isProduction();
    }

    /** @return array{kode:string, challenge:string} */
    public function buat(string $identitas): array
    {
        $kode = (string) random_int(100000, 999999);
        $exp = time() + self::TTL_KODE;

        return [
            'kode' => $kode,
            'challenge' => $exp.'.'.$this->hmac("otp.{$identitas}.{$kode}.{$exp}"),
        ];
    }

    public function verifikasi(string $identitas, string $kode, string $challenge): bool
    {
        [$exp, $sig] = array_pad(explode('.', $challenge, 2), 2, null);

        if (! $exp || ! $sig || time() > (int) $exp) {
            return false;
        }

        return hash_equals($this->hmac("otp.{$identitas}.{$kode}.{$exp}"), $sig);
    }

    /** Tanda "identitas ini sudah lolos OTP" — dibawa klien ke endpoint register. */
    public function buatBukti(string $identitas): string
    {
        $exp = time() + self::TTL_BUKTI;

        return $exp.'.'.$this->hmac("bukti.{$identitas}.{$exp}");
    }

    public function cekBukti(string $identitas, ?string $bukti): bool
    {
        [$exp, $sig] = array_pad(explode('.', (string) $bukti, 2), 2, null);

        if (! $exp || ! $sig || time() > (int) $exp) {
            return false;
        }

        return hash_equals($this->hmac("bukti.{$identitas}.{$exp}"), $sig);
    }

    public function emailSiap(): bool
    {
        return config('mail.default') !== 'log'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.mailers.smtp.username'));
    }

    /** @return bool true bila benar-benar terkirim (false = mode dev tanpa kanal) */
    public function kirim(string $kanal, string $identitas, string $kode): bool
    {
        if ($kanal === 'email' && $this->emailSiap()) {
            Mail::send('emails.otp', ['kode' => $kode], function ($m) use ($identitas) {
                $m->to($identitas)->subject('Kode Verifikasi Pendaftaran — SAIBATIN');
            });

            return true;
        }

        if ($kanal === 'wa' && $this->fonnte->aktif()) {
            return $this->fonnte->kirim($identitas, sprintf(
                '*%s* adalah kode verifikasi pendaftaran akun SAIBATIN Disdukcapil '.
                'Pesisir Barat Anda. Kode berlaku 5 menit. JANGAN berikan kode ini kepada siapa pun.',
                $kode
            ));
        }

        return false;
    }

    /** HMAC memakai APP_KEY — tidak ada rahasia tambahan yang perlu dikelola. */
    private function hmac(string $data): string
    {
        return hash_hmac('sha256', $data, config('app.key'));
    }
}
