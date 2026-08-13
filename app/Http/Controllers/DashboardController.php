<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\KritikSaran;
use App\Models\LogAktivitas;
use App\Models\News;
use App\Models\Pengaduan;
use App\Models\Permohonan;
use App\Models\Produk;
use App\Models\SkmJawaban;
use App\Models\User;
use App\Models\UserLevel;
use App\Services\PencacahKunjungan;
use App\Support\StatusAkun;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

/**
 * Beranda dashboard petugas — port `app/dashboard/page.tsx`.
 *
 * Seluruh agregasinya dihitung di SERVER lalu dikirim sebagai props, persis
 * seperti aslinya yang server component. Tidak ada endpoint `/api/dashboard`:
 * angka-angka ini hanya dipakai satu halaman, dan menyalurkannya lewat API
 * berarti dua tempat yang harus dijaga sinkron tanpa alasan.
 *
 * ⚠️ Tren dihitung dengan MENGAMBIL created_at 6 bulan terakhir lalu
 * mengelompokkannya di PHP — bukan `GROUP BY MONTH()`. Alasannya: kolomnya
 * `datetime(3)` dan pengelompokan di SQL akan memakai zona waktu koneksi,
 * sedangkan seluruh aplikasi ini memaksa Asia/Jakarta di sisi PHP. Bulan
 * pertama/terakhir akan meleset kalau keduanya tidak sepakat.
 */
class DashboardController extends Controller
{
    private const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const HARI_GRAFIK = 30;

    public function __construct(private readonly PencacahKunjungan $kunjungan) {}

    public function beranda()
    {
        $u = request()->user();

        // Warga & OPD tidak punya dashboard petugas — arahkan ke halaman mereka.
        if (! $u->isPetugas()) {
            return redirect('/user/pengajuan');
        }

        $kini = Carbon::now();
        $awalBulan = $kini->copy()->startOfMonth();
        $awal6Bulan = $kini->copy()->startOfMonth()->subMonths(5);

        $perStatus = Permohonan::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $totalPermohonan = (int) $perStatus->sum();

        $waktu = Permohonan::where('created_at', '>=', $awal6Bulan)->pluck('created_at');

        // ── Tren 6 bulan ────────────────────────────────────────────────────
        $tren = [];
        $indeksTren = [];
        for ($i = 0; $i < 6; $i++) {
            $d = $awal6Bulan->copy()->addMonths($i);
            $indeksTren[$d->format('Y-n')] = $i;
            $tren[] = ['label' => self::BULAN[$d->month - 1], 'count' => 0];
        }

        // ── 30 hari terakhir ────────────────────────────────────────────────
        $harian = [];
        $indeksHarian = [];
        for ($i = 0; $i < self::HARI_GRAFIK; $i++) {
            $d = $kini->copy()->startOfDay()->subDays(self::HARI_GRAFIK - 1 - $i);
            $indeksHarian[$d->format('Y-n-j')] = $i;
            $harian[] = ['label' => $d->day.' '.self::BULAN[$d->month - 1], 'count' => 0];
        }

        foreach ($waktu as $t) {
            $t = Carbon::parse($t);
            if (isset($indeksTren[$t->format('Y-n')])) {
                $tren[$indeksTren[$t->format('Y-n')]]['count']++;
            }
            if (isset($indeksHarian[$t->format('Y-n-j')])) {
                $harian[$indeksHarian[$t->format('Y-n-j')]]['count']++;
            }
        }

        // ── Layanan terpopuler ──────────────────────────────────────────────
        $top = Permohonan::selectRaw('jenis_id, COUNT(*) c')
            ->groupBy('jenis_id')->orderByDesc('c')->take(5)
            ->with('jenis:id,nama')->get()
            ->map(fn ($r) => ['nama' => $r->jenis->nama ?? 'Lainnya', 'count' => (int) $r->c]);

        $perLevel = User::selectRaw('userlevel_id, COUNT(*) c')->groupBy('userlevel_id')->pluck('c', 'userlevel_id');
        $level = fn (array $ids) => (int) collect($ids)->sum(fn ($i) => (int) ($perLevel[$i] ?? 0));

        return Inertia::render('Dashboard/Beranda', [
            'permohonan' => [
                'total' => $totalPermohonan,
                'perStatus' => [
                    'MENUNGGU' => (int) ($perStatus['MENUNGGU'] ?? 0),
                    'DIPROSES' => (int) ($perStatus['DIPROSES'] ?? 0),
                    'SELESAI' => (int) ($perStatus['SELESAI'] ?? 0),
                    'DITOLAK' => (int) ($perStatus['DITOLAK'] ?? 0),
                ],
                'bulanIni' => Permohonan::where('created_at', '>=', $awalBulan)->count(),
                'tren' => $tren,
                'harian' => $harian,
                'terpopuler' => $top,
            ],
            'aspirasi' => [
                'pengaduan' => [
                    'BARU' => Pengaduan::where('status', Pengaduan::STATUS_BARU)->count(),
                    'DIPROSES' => Pengaduan::where('status', Pengaduan::STATUS_DIPROSES)->count(),
                    'SELESAI' => Pengaduan::where('status', Pengaduan::STATUS_SELESAI)->count(),
                ],
                'kritikTotal' => KritikSaran::count(),
                'kritikBulanIni' => KritikSaran::where('created_at', '>=', $awalBulan)->count(),
                'skmTotal' => SkmJawaban::count(),
            ],
            'akun' => [
                'total' => (int) $perLevel->sum(),
                'aktif' => User::where('status', StatusAkun::AKTIF)->count(),
                'menunggu' => User::where('status', StatusAkun::MENUNGGU)->count(),
                'warga' => $level([UserLevel::WARGA]),
                'opd' => $level([UserLevel::OPERATOR_OPD]),
                'staff' => $level([UserLevel::SUPER_ADMIN, UserLevel::OPERATOR]),
            ],
            'kunjungan' => $this->kunjungan->statistik(),
            'konten' => [
                'beritaTerbit' => News::where('publish', true)->count(),
                'beritaDraf' => News::where('publish', false)->count(),
                'galeri' => Gallery::count(),
                'produk' => Produk::count(),
            ],
            // Log hanya untuk Super Admin — sama seperti menu Log Aktivitas.
            'logTerbaru' => $u->isSuperAdmin()
                ? LogAktivitas::with('user:id,user_id,user_fullname')
                    ->orderByDesc('created_at')->take(6)->get()
                    ->map(fn ($l) => [
                        'id' => $l->id,
                        'aksi' => $l->aksi,
                        'ringkasan' => $l->ringkasan,
                        'createdAt' => $l->created_at,
                        'pelaku' => $l->user->user_fullname ?? $l->user->user_id ?? 'Petugas',
                    ])
                : [],
        ]);
    }
}
