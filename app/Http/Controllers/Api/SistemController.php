<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JenisPermohonan;
use App\Models\StaticContent;
use App\Models\Wilayah;
use App\Services\JamLayanan;
use App\Services\PencacahKunjungan;
use App\Support\Balasan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Endpoint sistem & master data yang dipakai banyak halaman. */
class SistemController extends Controller
{
    public function __construct(
        private readonly JamLayanan $jam,
        private readonly PencacahKunjungan $kunjungan,
    ) {}

    /**
     * Pemeriksaan kesehatan aplikasi + koneksi DB.
     *
     * Di portal Next.js endpoint ini dipanggil cron tiap 2 menit untuk menahan
     * Passenger agar tidak mematikan proses saat idle (cold start →
     * ChunkLoadError). Di Laravel cron itu **tidak diperlukan** — tidak ada
     * proses yang perlu dijaga hidup. Endpointnya tetap ada untuk pemantauan.
     */
    public function health()
    {
        $db = 'ok';
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $db = 'error';
        }

        return Balasan::ok([
            'status' => $db === 'ok' ? 'ok' : 'degraded',
            'db' => $db,
            'waktu' => now()->toIso8601String(),
            'zona' => config('app.timezone'),
        ], [], $db === 'ok' ? 200 : 503);
    }

    /** Sesi aktif — pengganti `GET /api/auth/session` portal Next.js. */
    public function sesi(Request $request)
    {
        $u = $request->user();

        return Balasan::ok([
            'authenticated' => (bool) $u,
            'user' => $u ? [
                'id' => $u->id,
                'user_id' => $u->user_id,
                'name' => $u->user_fullname,
                'email' => $u->user_email,
                'level' => $u->userlevel_id,
            ] : null,
        ]);
    }

    public function jenisPermohonan()
    {
        return Balasan::ok([
            'items' => JenisPermohonan::where('aktif', true)
                ->orderBy('urutan')->orderBy('id')
                ->get(['id', 'kode', 'nama', 'kategori']),
        ]);
    }

    /**
     * Master wilayah. `?jenis=KECAMATAN|KELURAHAN`, `?parent=<id>` untuk
     * dropdown bertingkat.
     */
    public function wilayah(Request $request)
    {
        $q = Wilayah::query()->orderBy('nama');

        if ($jenis = $request->query('jenis')) {
            $q->where('jenis', strtoupper($jenis));
        }
        if ($parent = $request->query('parent')) {
            $q->where('parent_id', (int) $parent);
        }

        return Balasan::ok(['items' => $q->get(['id', 'kode', 'nama', 'jenis', 'parent_id'])]);
    }

    /** Status jam layanan saat ini — dipakai gerbang formulir permohonan. */
    public function jamLayanan()
    {
        $cfg = $this->jam->konfigurasi();

        return Balasan::ok($this->jam->status($cfg) + ['config' => $cfg]);
    }

    /**
     * Konten statis halaman publik.
     * `?keys=profil.visi-misi,profil.motto` (tanpa keys → seluruh blok).
     *
     * Hasilnya = nilai bawaan di-merge dengan override dari DB.
     *
     * ⚠️ Registri nilai bawaan (12 blok, port dari `lib/static-content-registry.ts`
     * 32 KB) **dibangun di Fase 6**. Sampai itu, endpoint ini mengembalikan
     * override DB apa adanya — cukup untuk blok yang memang sudah pernah disunting
     * (produksi punya 7 baris), tapi blok yang belum pernah disunting akan kosong.
     */
    public function kontenStatis(Request $request)
    {
        $keys = collect(explode(',', (string) $request->query('keys')))
            ->map(fn ($k) => trim($k))->filter()->values();

        $rows = StaticContent::when($keys->isNotEmpty(), fn ($q) => $q->whereIn('kunci', $keys))
            ->get(['kunci', 'konten']);

        $items = [];
        foreach ($rows as $r) {
            $items[$r->kunci] = $r->konten;
        }
        // Kunci yang diminta tapi belum ada di DB tetap muncul (sebagai objek
        // kosong) supaya klien tidak perlu membedakan "belum ada" dari "gagal".
        foreach ($keys as $k) {
            $items[$k] ??= (object) [];
        }

        return Balasan::ok(['items' => $items]);
    }

    public function kunjunganStatistik()
    {
        return Balasan::ok($this->kunjungan->statistik());
    }

    /**
     * Ping kunjungan dari halaman publik.
     * `{ pv: false }` hanya menyegarkan status online tanpa menambah hitungan.
     */
    public function kunjunganPing(Request $request)
    {
        $this->kunjungan->catat($request, $request->boolean('pv', true));

        return Balasan::ok(null);
    }
}
