<?php

namespace App\Support;

/**
 * Akses skema 15 layanan permohonan (`config/layanan.php`) + validasi payload.
 *
 * 🔴 VALIDASINYA DIGERAKKAN SKEMA, BUKAN NAMA FIELD — dan ini perbedaan
 * disengaja dari portal Next.js.
 *
 * Endpoint catch-all di sana memeriksa tipe kolom dengan menebak dari akhiran
 * namanya:
 *
 *     if (/(nik|nokk|kk)$/i.test(k) && !/^\d{16}$/.test(s)) → tolak
 *
 * Regex itu ikut mencocoki dua field milik layanan `kk-numpang`:
 *   • `alasannumpangkk`  — SELECT berisi "Pekerjaan"/"Pendidikan"/…
 *   • `nikygnumpangkk`   — TEXTAREA berisi banyak NIK, satu per baris
 *
 * Keduanya WAJIB di layanan itu, sehingga setiap pengiriman kk-numpang selalu
 * ditolak 422 dengan pesan "harus berupa 16 digit angka" — layanannya tidak bisa
 * dipakai sama sekali lewat jalur itu. Menebak tipe dari nama juga rapuh untuk
 * `nikygpisah` (textarea multi-NIK) yang kebetulan lolos hanya karena akhirannya
 * berbeda.
 *
 * Di sini tipe field dibaca dari skemanya, jadi masalah itu tidak bisa terjadi.
 */
final class Layanan
{
    /** Skema formulir berdasarkan slug FORM (bukan slug rute). */
    public static function form(string $slugForm): ?array
    {
        return config("layanan.form.{$slugForm}");
    }

    /** Slug rute publik → slug skema formulir. */
    public static function formDariRute(string $slugRute): ?array
    {
        $slugForm = config("layanan.rute_ke_form.{$slugRute}");

        return $slugForm ? self::form($slugForm) : null;
    }

    /** Slug formulir → kode di `m_jenis_permohonan`. */
    public static function kode(string $slugForm): ?string
    {
        return config("layanan.kode.{$slugForm}");
    }

    /** Kode `m_jenis_permohonan` → skema formulirnya (kebalikan `kode()`). */
    public static function formDariKode(?string $kode): ?array
    {
        if (blank($kode)) {
            return null;
        }

        $slug = array_search($kode, config('layanan.kode'), true);

        return $slug === false ? null : self::form($slug);
    }

    /**
     * Terjemahkan nilai berkode jadi label terbaca.
     *
     * Aman dipanggil untuk semua nilai: yang sudah berupa teks (permohonan baru)
     * dikembalikan apa adanya. Hanya nilai yang SELURUHNYA angka pada field yang
     * memang punya kamus yang diterjemahkan — kalau tidak, "12" pada kolom RT
     * bisa berubah jadi nama pekerjaan.
     */
    public static function labelKode(string $field, string $nilai): ?string
    {
        $grup = config('kode-options.field.'.preg_replace('/x$/', '', $field));

        if (! $grup || ! preg_match('/^\d+$/', trim($nilai))) {
            return null;
        }

        return config("kode-options.grup.{$grup}.".trim($nilai));
    }

    /**
     * Payload mentah → daftar {label, nilai} siap tampil, urut mengikuti
     * formulirnya.
     *
     * Labelnya diambil dari SKEMA, bukan kamus terpisah seperti
     * `lib/permohonan-display.ts` di portal Next.js. Satu sumber: kalau label
     * field diubah di `config/layanan.php`, detail permohonan ikut berubah dan
     * tidak ada kamus kedua yang diam-diam tertinggal.
     *
     * Kunci yang tidak dikenal skema tetap ditampilkan (data migrasi memuat
     * field yang formulirnya sudah tidak ada) — dengan nama aslinya, karena
     * menyembunyikannya berarti petugas kehilangan data tanpa tahu.
     *
     * @return array{data: array<int,array{label:string,nilai:string}>, berkas: array<int,array{label:string,path:string}>}
     */
    public static function tampilkanPayload(?array $form, ?array $payload): array
    {
        $payload ??= [];
        $data = [];
        $berkas = [];
        $sudah = [];
        $terpakai = [];

        $tulis = function (array $fd, mixed $nilai) use (&$data, &$berkas, &$sudah) {
            $v = trim((string) $nilai);
            if ($v === '') {
                return;
            }

            if (($fd['type'] ?? '') === 'file') {
                if (! str_starts_with($v, '/uploads/') || in_array($v, $sudah, true)) {
                    return;
                }
                $sudah[] = $v;
                $berkas[] = ['label' => $fd['label'], 'path' => $v];

                return;
            }

            $data[] = [
                'label' => $fd['label'],
                'nilai' => self::labelKode($fd['name'], $v) ?? $v,
            ];
        };

        foreach ($form ? self::semuaField($form) : [] as $fd) {
            $terpakai[] = $fd['name'];
            $tulis($fd, $payload[$fd['name']] ?? null);
        }

        foreach ($payload as $k => $v) {
            if (in_array($k, $terpakai, true) || is_array($v) || is_object($v)) {
                continue;
            }

            $tulis([
                'name' => $k,
                'label' => ucwords(str_replace('_', ' ', $k)),
                'type' => str_starts_with($k, 'file') ? 'file' : 'text',
            ], $v);
        }

        return ['data' => $data, 'berkas' => $berkas];
    }

    /** Seluruh field dari semua seksi, diratakan. */
    public static function semuaField(array $form): array
    {
        return array_merge(...array_column($form['sections'], 'fields'));
    }

    /**
     * Validasi satu nilai terhadap definisi fieldnya.
     * Mengembalikan pesan galat, atau null bila lolos.
     */
    public static function periksaField(array $fd, mixed $nilai): ?string
    {
        $v = trim((string) ($nilai ?? ''));

        if (($fd['required'] ?? false) && $v === '') {
            return "{$fd['label']} wajib diisi";
        }
        if ($v === '') {
            return null;
        }

        return match ($fd['type']) {
            'nik', 'kk' => preg_match('/^\d{16}$/', $v)
                ? null : "{$fd['label']} harus 16 digit angka",
            'phone' => preg_match('/^0\d{9,12}$/', $v)
                ? null : "{$fd['label']} harus 10–13 digit dan diawali 0",
            'email' => filter_var($v, FILTER_VALIDATE_EMAIL)
                ? null : "Format {$fd['label']} tidak valid",
            default => null,
        };
    }

    /**
     * Validasi seluruh payload terhadap skema layanan.
     *
     * Mengumpulkan SEMUA kekurangan lalu mengembalikannya sekaligus — warga
     * tahu persis apa saja yang belum lengkap, bukan diberi tahu satu per satu
     * setiap kali menekan kirim.
     *
     * @return array<int,string>
     */
    public static function periksaPayload(array $form, array $payload): array
    {
        $galat = [];

        foreach (self::semuaField($form) as $fd) {
            if ($pesan = self::periksaField($fd, $payload[$fd['name']] ?? null)) {
                $galat[] = $pesan;
            }
        }

        return $galat;
    }

    /**
     * Berkas yang tersimpan di payload (field bertipe `file` berisi path
     * `/uploads/…`) — didaftarkan ke `t_berkas` agar tampil sebagai lampiran.
     *
     * @return array<int,array{key:string,label:string,path:string}>
     */
    public static function berkasDariPayload(array $form, array $payload): array
    {
        $hasil = [];
        $sudah = [];

        foreach (self::semuaField($form) as $fd) {
            if ($fd['type'] !== 'file') {
                continue;
            }

            $path = trim((string) ($payload[$fd['name']] ?? ''));

            // Hanya path unggahan kita sendiri; nilai lain diabaikan.
            if ($path === '' || ! str_starts_with($path, '/uploads/')) {
                continue;
            }
            // Field ganda yang menunjuk berkas sama → satu entri saja.
            if (in_array($path, $sudah, true)) {
                continue;
            }

            $sudah[] = $path;
            $hasil[] = ['key' => $fd['name'], 'label' => $fd['label'], 'path' => $path];
        }

        return $hasil;
    }
}
