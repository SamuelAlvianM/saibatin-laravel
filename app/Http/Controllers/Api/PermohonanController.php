<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JenisPermohonan;
use App\Models\Permohonan;
use App\Services\JamLayanan;
use App\Services\Pemberitahuan;
use App\Services\Recaptcha;
use App\Support\AlasanTolakPermohonan;
use App\Support\Balasan;
use Illuminate\Http\Request;

/**
 * Permohonan milik warga/OPD yang sedang login.
 *
 * Pembuatan permohonan lewat 15 formulir layanan ada di endpoint catch-all
 * `/api/{layanan}/{action}` (Fase 4). Endpoint di sini adalah jalur umumnya:
 * daftar riwayat + pembuatan berbasis `jenisKode`.
 */
class PermohonanController extends Controller
{
    public function __construct(
        private readonly Recaptcha $recaptcha,
        private readonly JamLayanan $jam,
        private readonly Pemberitahuan $notif,
    ) {}

    /**
     * Riwayat permohonan — paginasi **cursor** untuk load-on-scroll.
     * `?status=<STATUS|semua>&cursor=<id>&limit=<n>`
     *
     * Urut `id desc` (monotonic ≈ created_at desc) supaya cursor-nya stabil:
     * mengurutkan pakai `created_at` membuat baris bergeser halaman saat ada
     * dua permohonan berdetik sama.
     */
    public function index(Request $request)
    {
        $u = $request->user();
        $limit = min(30, max(5, (int) $request->query('limit', 12)));
        $status = $request->query('status');
        $cursor = $request->query('cursor');

        $q = Permohonan::where('user_id', $u->id)
            ->when($status && $status !== 'semua', fn ($w) => $w->where('status', $status))
            ->when($cursor, fn ($w) => $w->where('id', '<', (int) $cursor))
            ->with('jenis:id,nama')
            ->orderByDesc('id');

        // Ambil limit+1 untuk tahu apakah masih ada halaman berikutnya.
        $baris = $q->take($limit + 1)->get();
        $adaLagi = $baris->count() > $limit;
        $halaman = $adaLagi ? $baris->take($limit) : $baris;

        $data = [
            'items' => $halaman->map(fn ($p) => [
                'id' => $p->id,
                'noregister' => $p->no_register,
                'status' => $p->status,
                'createdAt' => $p->created_at,
                'updatedAt' => $p->updated_at,
                'jenisNama' => $p->jenis->nama ?? (string) $p->jenis_id,

                /*
                 * 🔴 ALASAN PENOLAKAN IKUT DIKIRIM — dan sampai 2 Sep 2026 tidak.
                 *
                 * Halaman ini sebelumnya hanya menerima status, sehingga
                 * permohonan yang ditolak tampil sebagai lencana "DITOLAK" tanpa
                 * satu kata pun penjelasan. Alasannya memang dikirim lewat surel
                 * dan WhatsApp, tapi keduanya bisa terlewat, masuk folder spam,
                 * atau nomornya sudah berganti — dan portal, satu-satunya tempat
                 * yang pasti bisa dibuka pemohon, justru diam.
                 *
                 * Warga sungguhan melaporkan ditolak berulang kali "karena data
                 * tidak lengkap" tanpa pernah tahu data mana yang dimaksud.
                 *
                 * Hanya untuk baris yang DITOLAK: pada status lain `catatan`
                 * adalah catatan kerja petugas, bukan pesan untuk pemohon.
                 */
                'tolak' => $p->status === Permohonan::STATUS_DITOLAK
                    ? AlasanTolakPermohonan::uraikan($p->catatan)
                    : null,
            ])->values(),
            'nextCursor' => $adaLagi ? $halaman->last()->id : null,
        ];

        // Jumlah per status hanya dihitung di halaman pertama — hemat query saat
        // pengguna terus menggulir.
        if (! $cursor) {
            $hitung = Permohonan::where('user_id', $u->id)
                ->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

            $counts = ['semua' => 0, 'MENUNGGU' => 0, 'DIPROSES' => 0, 'SELESAI' => 0, 'DITOLAK' => 0];
            foreach ($hitung as $s => $c) {
                $counts[$s] = (int) $c;
                $counts['semua'] += (int) $c;
            }
            $data['counts'] = $counts;
        }

        return Balasan::ok($data);
    }

    public function store(Request $request)
    {
        // 🔴 Jam layanan berlaku untuk SEMUA pembuat permohonan — warga maupun
        // petugas — dan diperiksa di sini, bukan hanya di UI.
        $jam = $this->jam->status();
        if (! $jam['open']) {
            return Balasan::gagal([$jam['message']], 403);
        }

        if (! $this->recaptcha->verifikasi($request->input('recaptchaToken'))) {
            return Balasan::gagal(['Info: Verifikasi reCAPTCHA gagal']);
        }

        $kode = (string) $request->input('jenisKode');
        if (blank($kode)) {
            return Balasan::gagal(['Info: Jenis permohonan wajib dipilih']);
        }

        $jenis = JenisPermohonan::where('kode', $kode)->first();
        if (! $jenis) {
            return Balasan::gagal(['Info: Jenis permohonan tidak valid']);
        }

        $u = $request->user();
        $noregister = 'REG'.now()->getTimestampMs();

        $permohonan = Permohonan::create([
            'no_register' => $noregister,
            'user_id' => $u->id,
            'jenis_id' => $jenis->id,
            'status' => Permohonan::STATUS_MENUNGGU,
            'payload' => $request->input('payload') ?? [],
        ]);

        $this->notif->aman(fn () => $this->notif->kePetugas(
            tipe: 'PERMOHONAN_BARU',
            judul: 'Permohonan baru masuk',
            isi: ($u->user_fullname ?? $u->user_id)." mengajukan {$jenis->nama} ({$noregister}).",
            link: '/dashboard/permohonan',
            refType: 'Permohonan',
            refId: $permohonan->id,
            kecualiUserId: $u->id,
        ));

        return Balasan::ok(
            ['noregister' => $permohonan->no_register, 'id' => $permohonan->id],
            ['Info: Permohonan berhasil diajukan']
        );
    }
}
