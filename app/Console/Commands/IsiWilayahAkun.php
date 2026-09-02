<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserLevel;
use App\Models\Wilayah;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Isi `user_kecamatan` & `user_kelurahan` untuk akun instansi yang sudah ada.
 *
 * 🔴 KENAPA PERLU. Wilayah sebuah permohonan dibaca dari akun pengajunya —
 * tidak ada tempat lain yang mencatatnya per baris. Sejak 2 Sep 2026 kolom itu
 * WAJIB untuk akun instansi (`UserLevel::WAJIB_WILAYAH`), tapi 141 akun yang
 * SUDAH ADA dibuat di bawah aturan lama yang justru menulis `null`. Tanpa
 * pengisian ini, saringan wilayah menyaring 11.919 permohonan jadi 16.
 *
 * 🔴 SUMBERNYA DATA, BUKAN TEBAKAN. Nama akun instansi berbentuk
 * `<prefix>.<desa>` (`bk.tanjungrejo`), dan bagian desanya dicocokkan dengan
 * `m_wilayah` — 118 desa sungguhan milik Pesisir Barat. Kecamatannya diambil
 * dari `parent` desa itu, bukan dari tafsiran prefix.
 *
 * Prefix hanya dipakai untuk MEMBEDAKAN nama desa yang kembar, dan artinya pun
 * disimpulkan dari data: prefix yang seluruh kecocokan tunggalnya mendarat di
 * satu kecamatan dianggap menunjuk kecamatan itu. Prefix yang tidak konsisten
 * tidak dipakai sama sekali.
 *
 * ⚠️ Yang tidak bisa dipastikan TIDAK ditulis — akun dinas (`opd_dinkes`),
 * rumah sakit, akun uji, dan akun KUA yang menyebut kecamatan alih-alih desa.
 * Semuanya dilaporkan agar dinas memutuskan, bukan ditebak. Salah satu wilayah
 * berarti permohonan sebuah desa masuk ke rekap kecamatan yang keliru, dan itu
 * angka yang dipakai sebagai laporan resmi.
 *
 * Idempoten: menjalankan ulang tidak mengubah apa pun yang sudah benar.
 *
 *     php artisan wilayah:isi-akun              # laporan saja, TIDAK menulis
 *     php artisan wilayah:isi-akun --tulis      # baru menulis
 */
class IsiWilayahAkun extends Command
{
    protected $signature = 'wilayah:isi-akun
                            {--tulis : Benar-benar menyimpan; tanpa ini hanya laporan}
                            {--timpa : Ikut menimpa akun yang wilayahnya sudah terisi}';

    protected $description = 'Isi kecamatan & desa akun instansi dari m_wilayah, berdasarkan nama akunnya';

    /** Samakan bentuk supaya "TANJUNG REJO" cocok dengan "tanjungrejo". */
    private function normal(string $s): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', $s));
    }

    public function handle(): int
    {
        $desa = Wilayah::with('parent:id,nama')
            ->where('jenis', Wilayah::KELURAHAN)
            ->get(['id', 'nama', 'parent_id']);

        if ($desa->isEmpty()) {
            $this->error('m_wilayah belum berisi desa/kelurahan — jalankan seeder wilayah lebih dulu.');

            return self::FAILURE;
        }

        /** @var Collection<string, Collection<int, Wilayah>> */
        $petaDesa = $desa->groupBy(fn ($d) => $this->normal($d->nama));

        /*
         * Sebagian akun instansi menyebut KECAMATAN, bukan desa — akun KUA
         * seluruhnya begitu (`kua.ngambur`, `kua.waykrui`). Mereka melayani satu
         * kecamatan penuh, jadi wilayahnya memang berhenti di situ; desanya
         * dibiarkan kosong, bukan diisi salah satu desa di dalamnya.
         */
        $petaKecamatan = Wilayah::where('jenis', Wilayah::KECAMATAN)
            ->get(['id', 'nama'])
            ->keyBy(fn ($k) => $this->normal($k->nama));

        $akun = User::where('userlevel_id', UserLevel::OPERATOR_OPD)
            ->get(['id', 'user_id', 'user_fullname', 'user_kecamatan', 'user_kelurahan']);

        // Tahap 1 — kecocokan yang TIDAK MERAGUKAN. Dari sinilah arti tiap
        // prefix disimpulkan; ia tidak pernah menjadi sumber tebakan.
        $artiPrefix = [];
        foreach ($akun as $u) {
            [$prefix, $sufiks] = $this->pecah($u->user_id);
            $calon = $petaDesa->get($this->normal($sufiks));

            if ($prefix === '' || ! $calon || $calon->count() !== 1) {
                continue;
            }

            $kec = $calon->first()->parent->nama ?? null;
            if ($kec) {
                $artiPrefix[$prefix][$kec] = true;
            }
        }

        // Prefix yang menunjuk lebih dari satu kecamatan dibuang — ia tidak
        // membedakan apa pun, dan memakainya justru menambah kesalahan.
        $prefixKecamatan = [];
        foreach ($artiPrefix as $p => $kecSet) {
            if (count($kecSet) === 1) {
                $prefixKecamatan[$p] = array_key_first($kecSet);
            }
        }

        $siap = [];
        $ragu = [];

        foreach ($akun as $u) {
            if (! $this->option('timpa') && filled($u->user_kecamatan)) {
                continue;   // sudah terisi — idempoten
            }

            [$prefix, $sufiks] = $this->pecah($u->user_id);
            $calon = $petaDesa->get($this->normal($sufiks));

            if (! $calon) {
                // Menyebut kecamatan, bukan desa? Itu sah untuk akun sekecamatan.
                $kecLangsung = $petaKecamatan->get($this->normal($sufiks));

                if ($kecLangsung) {
                    $siap[] = [$u, null, $kecLangsung->nama];

                    continue;
                }

                $ragu[] = [$u->user_id, $u->user_fullname, 'bukan nama desa maupun kecamatan di m_wilayah'];

                continue;
            }

            if ($calon->count() === 1) {
                $siap[] = [$u, $calon->first(), $calon->first()->parent->nama ?? null];

                continue;
            }

            // Nama desa kembar — dipilah lewat arti prefix, kalau ada.
            $kec = $prefixKecamatan[$prefix] ?? null;
            $tersaring = $kec
                ? $calon->filter(fn ($d) => ($d->parent->nama ?? null) === $kec)
                : collect();

            if ($tersaring->count() === 1) {
                $siap[] = [$u, $tersaring->first(), $kec];
            } else {
                $ragu[] = [
                    $u->user_id,
                    $u->user_fullname,
                    'nama desa kembar di '.$calon->count().' kecamatan, prefix tidak memastikan',
                ];
            }
        }

        $this->info(sprintf('Akun instansi: %d · siap diisi: %d · perlu diputuskan dinas: %d',
            $akun->count(), count($siap), count($ragu)));
        $this->newLine();

        if ($siap !== []) {
            $this->line('<comment>Akan diisi:</comment>');
            $this->table(
                ['Akun', 'Desa/Kelurahan', 'Kecamatan'],
                collect($siap)->take(200)->map(fn ($r) => [
                    $r[0]->user_id, $r[1]->nama ?? '— (sekecamatan)', $r[2] ?? '-',
                ])->all(),
            );
        }

        if ($ragu !== []) {
            $this->newLine();
            $this->line('<comment>TIDAK diisi — perlu keputusan dinas:</comment>');
            $this->table(['Akun', 'Nama', 'Sebab'], $ragu);
        }

        if (! $this->option('tulis')) {
            $this->newLine();
            $this->warn('Ini laporan saja — belum ada yang disimpan. Tambahkan --tulis untuk menyimpan.');

            return self::SUCCESS;
        }

        foreach ($siap as [$u, $d, $kec]) {
            $u->forceFill([
                'user_kelurahan' => $d->nama ?? null,
                'user_kecamatan' => $kec,
                'user_kabupaten' => 'PESISIR BARAT',
            ])->save();
        }

        $this->newLine();
        $this->info(count($siap).' akun diperbarui.');

        return self::SUCCESS;
    }

    /** `bk.tanjungrejo` → ['bk', 'tanjungrejo']; tanpa titik → ['', seluruhnya]. */
    private function pecah(string $userId): array
    {
        $pos = strpos($userId, '.');

        return $pos === false
            ? ['', $userId]
            : [substr($userId, 0, $pos), substr($userId, $pos + 1)];
    }
}
