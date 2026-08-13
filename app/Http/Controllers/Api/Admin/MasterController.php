<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Services\CatatanAktivitas;
use App\Support\Balasan;
use Illuminate\Http\Request;

/**
 * Halaman Master — port `app/api/admin/master/route.ts`.
 *
 * Satu-satunya jalan membuka kunci permohonan yang sudah final (SELESAI/DITOLAK)
 * agar bisa diproses ulang. Butuh sesi petugas **dan** sandi master.
 *
 * Kenapa dipisah begini: status final itulah yang membuat angka laporan bisa
 * dipercaya. Kalau petugas bisa membalik status kapan saja dari halaman biasa,
 * "8.696 selesai" tidak berarti apa-apa. Membukanya harus terasa berat, tercatat
 * di log, dan meninggalkan jejak di catatan permohonannya.
 */
class MasterController extends Controller
{
    public function __construct(private readonly CatatanAktivitas $log) {}

    public function bukaKunci(Request $request)
    {
        $sandi = (string) config('master.password');

        if ($sandi === '') {
            return Balasan::gagal(['Info: Fitur master belum dikonfigurasi (set MASTER_PASSWORD)'], 503);
        }

        $noregister = trim((string) $request->input('noregister'));
        $masukan = (string) $request->input('password');

        if ($masukan === '' || $noregister === '') {
            return Balasan::gagal(['Info: Password master dan No. Register wajib diisi']);
        }

        // Dibandingkan lewat HMAC lebih dulu supaya `hash_equals` selalu
        // membandingkan dua string sepanjang sama — panjang sandi yang benar
        // tidak bocor lewat lama waktu pemeriksaan.
        $kunci = 'master-cek';
        if (! hash_equals(hash_hmac('sha256', $sandi, $kunci), hash_hmac('sha256', $masukan, $kunci))) {
            return Balasan::gagal(['Info: Password master salah'], 403);
        }

        $p = Permohonan::where('no_register', $noregister)->first();
        if (! $p) {
            return Balasan::gagal(['Permohonan tidak ditemukan'], 404);
        }
        if (! in_array($p->status, [Permohonan::STATUS_SELESAI, Permohonan::STATUS_DITOLAK], true)) {
            return Balasan::gagal(['Info: Permohonan ini tidak terkunci (belum final)']);
        }

        $petugas = $request->user();
        $nama = $petugas->user_fullname ?: $petugas->user_id;

        $p->status = Permohonan::STATUS_DIPROSES;
        $p->catatan = (filled($p->catatan) ? $p->catatan."\n" : '')
            ."[Master] Kunci dibuka oleh {$nama} — status dikembalikan ke Diproses.";
        $p->save();

        $this->log->catat(
            $petugas, 'UBAH', 'Permohonan',
            "Membuka kunci permohonan {$noregister} (master) — status kembali ke Diproses",
            $p->id, $request,
        );

        return Balasan::ok(null, ["Info: Kunci permohonan {$noregister} dibuka — status kembali ke Diproses"]);
    }
}
