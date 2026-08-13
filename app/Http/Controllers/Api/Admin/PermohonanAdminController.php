<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Services\CatatanAktivitas;
use App\Services\Pemberitahuan;
use App\Services\Surel;
use App\Support\Balasan;
use App\Support\Layanan;
use App\Support\Periode;
use Illuminate\Http\Request;

/**
 * Permohonan dari sisi PETUGAS — port `app/api/admin/permohonan/**`.
 *
 * Bedanya dengan `Api\PermohonanController` (milik warga): di sini seluruh
 * 11.902 baris terlihat, dan statusnya bisa diubah.
 */
class PermohonanAdminController extends Controller
{
    public function __construct(
        private readonly CatatanAktivitas $log,
        private readonly Pemberitahuan $notif,
        private readonly Surel $surel,
    ) {}

    /**
     * Daftar seluruh permohonan.
     *
     * Paginasi **bernomor** (bukan cursor seperti riwayat warga): petugas perlu
     * melompat ke halaman tertentu dan melihat totalnya. Seluruh pencarian dan
     * filter dijalankan di DATABASE, jadi hasilnya mencakup semua data — bukan
     * cuma baris yang kebetulan sedang tampil.
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));
        $petugas = trim((string) $request->query('petugas'));
        $sorot = $request->query('sorot');

        $limit = min(100, max(10, (int) $request->query('limit', 20)));
        $page = max(1, (int) $request->query('page', 1));

        // Klausa dasar dipakai tiga kali (hitung total, ambil baris, hitung per
        // status), jadi disusun sebagai closure sekali pakai-ulang.
        $dasar = function ($w) use ($q, $petugas, $request) {
            $w->when(filled($petugas), fn ($x) => $x->where('proses_by', (int) $petugas));

            Periode::saring($w, $request->query('periode'), $request->query('acuan'));

            $w->when(filled($q), fn ($x) => $x->where(function ($o) use ($q) {
                $o->where('no_register', 'like', "%{$q}%")
                    ->orWhere('catatan', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u
                        ->where('user_fullname', 'like', "%{$q}%")
                        ->orWhere('user_id', 'like', "%{$q}%")
                        ->orWhere('user_hp', 'like', "%{$q}%"))
                    ->orWhereHas('jenis', fn ($j) => $j->where('nama', 'like', "%{$q}%"));
            }));

            return $w;
        };

        $query = fn () => $dasar(Permohonan::query())
            ->when(filled($status), fn ($w) => $w->where('status', $status));

        // Datang dari notifikasi: hitung permohonan itu ada di halaman berapa,
        // lalu langsung buka halaman tersebut. Tanpa ini petugas mendarat di
        // halaman 1 dan harus mencari sendiri di antara ribuan baris.
        if (filled($sorot) && ctype_digit((string) $sorot)) {
            // Urutannya id menurun → posisinya = jumlah baris ber-id LEBIH BESAR.
            $sebelum = $query()->where('id', '>', (int) $sorot)->count();
            $page = intdiv($sebelum, $limit) + 1;
        }

        $total = $query()->count();

        $baris = $query()
            ->with(['jenis:id,nama,kategori', 'user:id,user_id,user_fullname,user_hp'])
            ->withCount('berkas')
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get();

        $data = [
            'items' => $baris->map(fn ($p) => [
                'id' => $p->id,
                'noregister' => $p->no_register,
                'status' => $p->status,
                'catatan' => $p->catatan,
                'createdAt' => $p->created_at,
                'updatedAt' => $p->updated_at,
                // Jejak perubahan status — dipakai kolom "Diperbarui" & PDF.
                'prosesAt' => $p->proses_at,
                'prosesByName' => $p->proses_by_name,
                'jenisNama' => $p->jenis->nama ?? '-',
                'kategori' => $p->jenis->kategori ?? '-',
                'pemohon' => $p->user->user_fullname ?? $p->user->user_id ?? '-',
                'pemohonId' => $p->user->user_id ?? '-',
                'hp' => $p->user->user_hp ?? '-',
                'jumlahBerkas' => $p->berkas_count,
            ])->values(),
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalHalaman' => max(1, (int) ceil($total / $limit)),
        ];

        // Hitungan per status & daftar petugas hanya dikirim di halaman pertama —
        // isinya sama untuk tiap halaman, jadi tak perlu dihitung ulang.
        if ($page === 1) {
            // Hitungan status mengikuti filter LAIN (petugas/periode/pencarian)
            // tapi bukan filter status itu sendiri, supaya angka di tiap chip
            // menunjukkan "berapa yang muncul kalau chip ini diklik".
            $hitung = $dasar(Permohonan::query())
                ->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

            $counts = ['' => 0];
            foreach ($hitung as $s => $c) {
                $counts[$s] = (int) $c;
                $counts[''] += (int) $c;
            }
            $data['counts'] = $counts;

            $data['daftarPetugas'] = Permohonan::whereNotNull('proses_by')
                ->select('proses_by', 'proses_by_name')
                ->distinct()
                ->orderBy('proses_by_name')
                ->get()
                ->map(fn ($r) => [
                    'id' => (int) $r->proses_by,
                    'nama' => $r->proses_by_name ?: "Petugas #{$r->proses_by}",
                ])->values();
        }

        return Balasan::ok($data);
    }

    /**
     * Detail satu permohonan.
     *
     * Payload-nya sudah DIRANGKAI SIAP TAMPIL di sini (label dari skema, kode
     * lama diterjemahkan) alih-alih dikirim mentah lalu diterjemahkan React.
     * Alasannya: skema formulir dan kamus kode hidup di config PHP; menyalinnya
     * ke sisi klien berarti dua kamus yang harus dijaga sama.
     */
    public function show(int $id)
    {
        $p = Permohonan::with([
            'jenis',
            'user:id,user_id,user_fullname,user_hp,user_email',
            'berkas',
        ])->find($id);

        if (! $p) {
            return Balasan::gagal(['Permohonan tidak ditemukan'], 404);
        }

        $tampil = Layanan::tampilkanPayload(Layanan::formDariKode($p->jenis->kode ?? null), $p->payload);

        // Berkas dari `t_berkas` (sumber utama) digabung dengan yang hanya
        // tercatat di payload — permohonan hasil migrasi kerap punya yang kedua
        // saja, dan tanpa penggabungan ini lampirannya tampak kosong.
        $path = $p->berkas->pluck('path')->all();
        $berkas = $p->berkas->map(fn ($b) => ['label' => $b->nama_file ?: 'Berkas', 'path' => $b->path])
            ->concat(array_filter($tampil['berkas'], fn ($b) => ! in_array($b['path'], $path, true)))
            ->values();

        return Balasan::ok(['permohonan' => [
            'id' => $p->id,
            'noregister' => $p->no_register,
            'status' => $p->status,
            'catatan' => $p->catatan,
            'createdAt' => $p->created_at,
            'updatedAt' => $p->updated_at,
            'prosesAt' => $p->proses_at,
            'prosesByName' => $p->proses_by_name,
            'jenis' => ['nama' => $p->jenis->nama ?? '-', 'kategori' => $p->jenis->kategori ?? '-'],
            'user' => [
                'userId' => $p->user->user_id ?? '-',
                'userFullname' => $p->user->user_fullname,
                'userHp' => $p->user->user_hp,
                'userEmail' => $p->user->user_email,
            ],
            'data' => $tampil['data'],
            'berkas' => $berkas,
        ]]);
    }

    /**
     * Ubah status &/atau catatan petugas.
     *
     * 🔴 SELESAI dan DITOLAK bersifat FINAL — barisnya terkunci dan hanya bisa
     * dibuka lewat halaman Master. Itu yang membuat angka laporan tidak berubah
     * diam-diam setelah pelayanan dinyatakan tuntas.
     */
    public function update(Request $request, int $id)
    {
        $status = $request->input('status');
        $catatan = $request->input('catatan');

        $valid = [
            Permohonan::STATUS_MENUNGGU, Permohonan::STATUS_DIPROSES,
            Permohonan::STATUS_SELESAI, Permohonan::STATUS_DITOLAK,
        ];

        if (filled($status) && ! in_array($status, $valid, true)) {
            return Balasan::gagal(['Info: Status tidak valid']);
        }
        if (blank($status) && ! $request->has('catatan')) {
            return Balasan::gagal(['Info: Tidak ada perubahan']);
        }

        $p = Permohonan::with(['jenis:id,nama', 'user:id,user_id,user_fullname,user_email'])->find($id);
        if (! $p) {
            return Balasan::gagal(['Permohonan tidak ditemukan'], 404);
        }

        $sebelum = $p->status;
        if (in_array($sebelum, [Permohonan::STATUS_SELESAI, Permohonan::STATUS_DITOLAK], true)) {
            return Balasan::gagal([
                'Info: Permohonan sudah final (Selesai/Ditolak) dan terkunci — buka kunci lewat halaman Master',
            ], 423);
        }

        $petugas = $request->user();
        $gantiStatus = filled($status) && $status !== $sebelum;

        if (filled($status)) {
            $p->status = $status;
        }
        if ($request->has('catatan')) {
            $p->catatan = $catatan;
        }
        if ($gantiStatus) {
            $p->proses_by = $petugas->id;
            $p->proses_by_name = $petugas->user_fullname ?: $petugas->user_id;
            $p->proses_at = now();
        }
        $p->save();

        $nama = $p->user->user_fullname ?: $p->user->user_id;

        if ($gantiStatus && in_array($status, [Permohonan::STATUS_SELESAI, Permohonan::STATUS_DITOLAK], true)) {
            $selesai = $status === Permohonan::STATUS_SELESAI;
            $this->surel->kirim(
                $p->user->user_email,
                $selesai
                    ? "Permohonan {$p->no_register} Telah Disetujui"
                    : "Permohonan {$p->no_register} Ditolak — Anda Dapat Mengajukan Revisi",
                $selesai ? 'emails.permohonan-selesai' : 'emails.permohonan-ditolak',
                [
                    'nama' => $nama,
                    'noregister' => $p->no_register,
                    'jenis' => $p->jenis->nama ?? '-',
                    'catatan' => $p->catatan,
                ],
            );
        }

        if ($gantiStatus) {
            $jenisNama = $p->jenis->nama ?? 'Permohonan';
            $pesan = match ($status) {
                Permohonan::STATUS_DIPROSES => [
                    'Permohonan sedang diproses',
                    "Permohonan {$jenisNama} ({$p->no_register}) Anda sedang diproses petugas.",
                ],
                Permohonan::STATUS_SELESAI => [
                    'Permohonan selesai',
                    "Permohonan {$jenisNama} ({$p->no_register}) Anda telah SELESAI.",
                ],
                Permohonan::STATUS_DITOLAK => [
                    'Permohonan ditolak',
                    "Permohonan {$jenisNama} ({$p->no_register}) Anda ditolak."
                        .(filled($p->catatan) ? " Catatan: {$p->catatan}" : ''),
                ],
                default => null,
            };

            if ($pesan) {
                $this->notif->aman(fn () => $this->notif->buat(
                    userId: $p->user_id,
                    tipe: 'PERMOHONAN_STATUS',
                    judul: $pesan[0],
                    isi: $pesan[1],
                    link: '/user/pengajuan',
                    refType: 'Permohonan',
                    refId: $p->id,
                ));
            }
        }

        $this->log->catat(
            $petugas,
            'UBAH',
            'Permohonan',
            $gantiStatus
                ? "Mengubah status permohonan {$p->no_register} menjadi {$status}"
                : "Memperbarui catatan permohonan {$p->no_register}",
            $p->id,
            $request,
        );

        return Balasan::ok(null, ['Info: Permohonan berhasil diperbarui']);
    }
}
