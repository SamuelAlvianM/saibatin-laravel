<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Models\User;
use App\Services\CatatanAktivitas;
use App\Services\Pemberitahuan;
use App\Support\Balasan;
use App\Support\StatusAkun;
use Illuminate\Http\Request;

/**
 * Pengaduan masyarakat dari sisi petugas — port `app/api/admin/pengaduan/**`.
 *
 * 🔴 Isinya memuat identitas pelapor, termasuk jalur WBS yang kerahasiaannya
 * dijanjikan di halaman publik. Tidak boleh bocor ke luar dashboard petugas.
 */
class PengaduanAdminController extends Controller
{
    public function __construct(
        private readonly CatatanAktivitas $log,
        private readonly Pemberitahuan $notif,
    ) {}

    /** `?filter=belum|selesai` — 300 terbaru. */
    public function index(Request $request)
    {
        $filter = $request->query('filter');

        $items = Pengaduan::query()
            ->when($filter === 'selesai', fn ($w) => $w->where('status', Pengaduan::STATUS_SELESAI))
            ->when($filter === 'belum', fn ($w) => $w->where('status', '!=', Pengaduan::STATUS_SELESAI))
            ->orderByDesc('created_at')
            ->take(300)
            ->get()
            // Dipetakan, bukan dikirim mentah: kontrak lamanya camelCase (Prisma),
            // dan kolom DB-nya snake_case.
            ->map(fn ($p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'nik' => $p->nik,
                'email' => $p->email,
                'hp' => $p->hp,
                'subjek' => $p->subjek,
                'isi' => $p->isi,
                'status' => $p->status,
                'balasan' => $p->balasan,
                'createdAt' => $p->created_at,
            ]);

        return Balasan::ok(['items' => $items]);
    }

    /** Ubah status &/atau tulis balasan. */
    public function update(Request $request, int $id)
    {
        $status = $request->input('status');
        $balasan = $request->input('balasan');

        $valid = [Pengaduan::STATUS_BARU, Pengaduan::STATUS_DIPROSES, Pengaduan::STATUS_SELESAI];

        if (filled($status) && ! in_array($status, $valid, true)) {
            return Balasan::gagal(['Info: Status tidak valid']);
        }
        if (blank($status) && ! $request->has('balasan')) {
            return Balasan::gagal(['Info: Tidak ada perubahan']);
        }

        $p = Pengaduan::find($id);
        if (! $p) {
            return Balasan::gagal(['Info: Pengaduan tidak ditemukan'], 404);
        }

        $statusLama = $p->status;
        $balasanLama = trim((string) $p->balasan);

        if (filled($status)) {
            $p->status = $status;
        }
        if ($request->has('balasan')) {
            $p->balasan = $balasan;
        }
        $p->save();

        $statusBerubah = filled($status) && $status !== $statusLama;
        $balasanBaru = $request->has('balasan')
            && filled(trim((string) $balasan))
            && trim((string) $balasan) !== $balasanLama;

        // Notifikasi in-app ke pelapor BILA ia punya akun. Pengaduan publik hanya
        // menyimpan nama/NIK/email, jadi pencocokannya best-effort — tidak ada
        // relasi tetap ke tabel users.
        if ($statusBerubah || $balasanBaru) {
            $this->notif->aman(function () use ($p, $balasan, $balasanBaru, $status) {
                $cocok = array_filter([
                    filled($p->nik) ? ['user_nik', $p->nik] : null,
                    filled($p->nik) ? ['user_id', $p->nik] : null,
                    filled($p->email) ? ['user_email', $p->email] : null,
                ]);

                if ($cocok === []) {
                    return;
                }

                $pelapor = User::where('status', StatusAkun::AKTIF)
                    ->where(function ($w) use ($cocok) {
                        foreach ($cocok as [$kolom, $nilai]) {
                            $w->orWhere($kolom, $nilai);
                        }
                    })
                    ->orderByDesc('id')->first();

                if (! $pelapor) {
                    return;
                }

                $subjek = trim((string) $p->subjek) ?: 'Pengaduan Anda';

                $this->notif->buat(
                    userId: $pelapor->id,
                    tipe: 'PENGADUAN_BALASAN',
                    judul: $balasanBaru
                        ? 'Pengaduan Anda dibalas petugas'
                        : ($status === Pengaduan::STATUS_SELESAI
                            ? 'Pengaduan Anda selesai ditangani'
                            : 'Pengaduan Anda sedang diproses'),
                    isi: $balasanBaru
                        ? $subjek.': "'.mb_substr(trim((string) $balasan), 0, 160).'"'
                        : "{$subjek} kini berstatus {$status}.",
                    refType: 'Pengaduan',
                    refId: $p->id,
                );
            });
        }

        $this->log->catat(
            $request->user(), 'UBAH', 'Pengaduan',
            $balasanBaru
                ? "Membalas pengaduan #{$p->id}"
                : "Mengubah status pengaduan #{$p->id}".(filled($status) ? " menjadi {$status}" : ''),
            $p->id, $request,
        );

        return Balasan::ok(null, ['Info: Pengaduan berhasil diperbarui']);
    }
}
