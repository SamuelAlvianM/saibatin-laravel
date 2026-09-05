<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserLevel;
use App\Models\Wilayah;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Akun uji LOKAL untuk keempat peran — dipakai saat pengujian manual.
 *
 * 🔴 SENGAJA MEMBUAT AKUN BARU, BUKAN MENYETEL ULANG AKUN DINAS. Memakai akun
 * sungguhan untuk menguji berarti mengganti sandinya, dan itu mengunci orang
 * yang memakainya keluar dari portalnya sendiri. Id 9999xx dan nama ber-"(uji
 * lokal)" dipilih supaya mencolok bila tak sengaja terbawa ke produksi.
 *
 * ⚠️ JANGAN PERNAH DIJALANKAN DI PRODUKSI. Akun bersandi tetap yang diketahui
 * publik adalah pintu masuk terbuka; perintah ini menolak jalan bila
 * `APP_ENV=production`.
 *
 *     php artisan akun:uji
 */
class BuatAkunUji extends Command
{
    protected $signature = 'akun:uji';

    protected $description = 'Buat/segarkan akun uji lokal untuk peran admin, staf, OPD, dan warga';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('DITOLAK: perintah ini tidak boleh dijalankan di produksi.');

            return self::FAILURE;
        }

        $kecamatan = Wilayah::query()
            ->where('jenis', 'KECAMATAN')
            ->orderBy('nama')
            ->value('nama');

        $akun = [
            [
                'id' => 999901, 'user_id' => 'admin.uji.lokal', 'sandi' => 'adm12345',
                'level' => UserLevel::SUPER_ADMIN, 'nama' => 'Super Admin (uji lokal)', 'kec' => null,
            ],
            [
                'id' => 999902, 'user_id' => 'staf.uji.lokal', 'sandi' => 'staf12345',
                'level' => UserLevel::OPERATOR, 'nama' => 'Operator Capil (uji lokal)', 'kec' => null,
            ],
            [
                'id' => 999903, 'user_id' => 'opd.uji.lokal', 'sandi' => 'opd12345',
                'level' => UserLevel::OPERATOR_OPD, 'nama' => 'Operator OPD (uji lokal)', 'kec' => $kecamatan,
            ],
            /*
             * ⚠️ Warga masuk memakai NIK, bukan username — itulah sebabnya
             * `user_id`-nya 16 digit. NIK ini sengaja diawali angka yang
             * mustahil dipakai wilayah mana pun.
             */
            [
                'id' => 999904, 'user_id' => '9999000000000001', 'sandi' => 'warga12345',
                'level' => UserLevel::WARGA, 'nama' => 'Warga Uji (uji lokal)', 'kec' => $kecamatan,
            ],
        ];

        foreach ($akun as $a) {
            User::updateOrCreate(
                ['id' => $a['id']],
                [
                    'user_id' => $a['user_id'],
                    'password' => Hash::make($a['sandi']),
                    'userlevel_id' => $a['level'],
                    'user_fullname' => $a['nama'],
                    'user_kecamatan' => $a['kec'],
                    'status' => 1,
                    'activation_time' => now(),
                ],
            );

            $this->line(sprintf(
                '  ✓ %-18s / %-11s  level %d  %s',
                $a['user_id'], $a['sandi'], $a['level'], UserLevel::NAMA[$a['level']] ?? '',
            ));
        }

        $this->newLine();
        $this->warn('Akun ini hanya untuk laptop. Jangan pernah ada di produksi.');

        return self::SUCCESS;
    }
}
