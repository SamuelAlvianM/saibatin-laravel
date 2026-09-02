<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Admin\PengaturanController;
use App\Http\Controllers\Controller;
use App\Models\Berkas;
use App\Models\JenisPermohonan;
use App\Models\Permohonan;
use App\Services\CatatanAktivitas;
use App\Services\JamLayanan;
use App\Services\Pemberitahuan;
use App\Support\Balasan;
use App\Support\Layanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Endpoint catch-all 15 layanan permohonan: `POST /api/{layanan}/{aksi}`.
 *
 * Bentuk URL ini warisan portal Laravel 9 dan dipertahankan supaya kontraknya
 * tidak berubah. Aksi yang dikenal:
 *
 *   upload                                    → simpan berkas, balas { url }
 *   fetch|fetchdatas|fetchdata|jenis          → prefill (kosong, non-blocking)
 *   create|update|postdata|insertdata|store   → buat permohonan
 *
 * Alias `postdata`/`insertdata` sengaja tetap diterima: itulah nama aksi di
 * portal Laravel 9, dan tautan/skrip lama mungkin masih memakainya.
 */
class LayananController extends Controller
{
    private const AKSI_KIRIM = ['create', 'update', 'postdata', 'insertdata', 'store'];
    private const AKSI_AMBIL = ['fetch', 'fetchdatas', 'fetchdata', 'jenis'];

    /** Berkas permohonan HANYA gambar (scan/foto dokumen) — PDF tidak diterima. */
    private const EKSTENSI = ['jpg', 'jpeg', 'png', 'webp'];
    private const MAKS = 5 * 1024 * 1024;

    public function __construct(
        private readonly JamLayanan $jam,
        private readonly Pemberitahuan $notif,
        private readonly CatatanAktivitas $log,
    ) {}

    public function __invoke(Request $request, string $layanan, string $aksi)
    {
        $aksi = strtolower($aksi);

        // Slug tak dikenal → 404 alami, bukan pesan yang membocorkan daftar rute.
        $form = Layanan::form($layanan);
        if (! $form) {
            return Balasan::gagal(['Layanan tidak ditemukan'], 404);
        }

        if (in_array($aksi, self::AKSI_AMBIL, true)) {
            return Balasan::ok([]);
        }
        if ($aksi === 'upload') {
            return $this->unggah($request, $layanan);
        }
        if (in_array($aksi, self::AKSI_KIRIM, true)) {
            return $this->buat($request, $form);
        }

        return Balasan::gagal(['Aksi tidak dikenal'], 404);
    }

    private function unggah(Request $request, string $layanan)
    {
        $berkas = $request->file('file') ?? collect($request->allFiles())->flatten()->first();

        if (! $berkas || ! $berkas->isValid()) {
            return Balasan::gagal(['File tidak ditemukan']);
        }
        if ($berkas->getSize() > self::MAKS) {
            return Balasan::gagal(['Ukuran file maksimal 5 MB']);
        }

        $ext = strtolower($berkas->getClientOriginalExtension());
        if (! in_array($ext, self::EKSTENSI, true)) {
            return Balasan::gagal(['Berkas permohonan harus berupa gambar (JPG, PNG, atau WebP)']);
        }

        // 🔴 DI LUAR public/. Berkas ini memuat KTP/KK warga; apa pun di public/
        // disajikan web server tanpa cek sesi. Nama diawali id pengunggah —
        // itulah dasar kontrol akses saat berkasnya disajikan kembali.
        $nama = $request->user()->id.'_'.now()->getTimestampMs().'.'.$ext;
        Storage::disk('local')->putFileAs("permohonan/{$layanan}", $berkas, $nama);

        $url = "/uploads/{$layanan}/{$nama}";

        // success[0] = url — bentuk balasan yang dibaca komponen form.
        return Balasan::ok(['url' => $url], [$url]);
    }

    private function buat(Request $request, array $form)
    {
        /*
         * 🔴 LAYANAN YANG DIMATIKAN MENOLAK KIRIMAN — bukan hanya hilang dari
         * layar.
         *
         * Sampai 2 Sep 2026 endpoint ini tidak memeriksanya sama sekali, jadi
         * permohonan untuk layanan yang sudah dimatikan tetap DITERIMA dan
         * masuk ke antrean petugas. Penyaringannya cuma ada di pemilih layanan,
         * dan pemilih layanan bukan pagar.
         *
         * Super Admin dikecualikan karena dialah yang mematikannya dan perlu
         * mengujinya — sama seperti di `PengajuanPetugasController`.
         */
        if (! $request->user()->isSuperAdmin()
            && PengaturanController::tersembunyi($form['slug'])) {
            return Balasan::gagal(['Info: Layanan ini sedang tidak tersedia'], 403);
        }

        // 🔴 Jam layanan berlaku untuk SEMUA pembuat permohonan, warga maupun
        // petugas, dan diperiksa di sini — bukan hanya disembunyikan di UI.
        $jam = $this->jam->status();
        if (! $jam['open']) {
            return Balasan::gagal([$jam['message']], 403);
        }

        $payload = $request->all();

        $galat = Layanan::periksaPayload($form, $payload);
        if ($galat !== []) {
            return Balasan::gagal($galat, 422);
        }

        $kode = Layanan::kode($form['slug']);
        $jenis = JenisPermohonan::where('kode', $kode)->first();
        if (! $jenis) {
            return Balasan::gagal(['Jenis permohonan tidak valid']);
        }

        $u = $request->user();
        $noregister = 'REG'.now()->getTimestampMs();

        $permohonan = Permohonan::create([
            'no_register' => $noregister,
            'user_id' => $u->id,
            'jenis_id' => $jenis->id,
            'status' => Permohonan::STATUS_MENUNGGU,
            'payload' => $payload,
        ]);

        // Daftarkan berkas ke t_berkas supaya tampil sebagai lampiran di detail
        // warga maupun dashboard petugas.
        $berkas = Layanan::berkasDariPayload($form, $payload);
        if ($berkas !== []) {
            Berkas::insert(array_map(fn ($b) => [
                'permohonan_id' => $permohonan->id,
                'nama_file' => $b['label'],
                'path' => $b['path'],
                'created_at' => now(),
            ], $berkas));
        }

        $namaPemohon = trim((string) ($payload['pemohonnama'] ?? ''))
            ?: ($u->user_fullname ?? $u->user_id);

        $this->notif->aman(fn () => $this->notif->kePetugas(
            tipe: 'PERMOHONAN_BARU',
            judul: 'Permohonan baru masuk',
            isi: "{$namaPemohon} mengajukan {$jenis->nama} ({$noregister}).",
            link: '/dashboard/permohonan',
            refType: 'Permohonan',
            refId: $permohonan->id,
            // Petugas yang mengisi atas nama warga tidak perlu diberi tahu
            // soal permohonan yang baru saja dibuatnya sendiri.
            kecualiUserId: $u->id,
        ));

        // Konfirmasi in-app untuk warga/OPD pengaju (petugas tidak perlu).
        if (! $u->isPetugas()) {
            $this->notif->aman(fn () => $this->notif->buat(
                userId: $u->id,
                tipe: 'PERMOHONAN_STATUS',
                judul: 'Permohonan terkirim',
                isi: "Permohonan {$jenis->nama} Anda terkirim dengan No. Registrasi {$noregister} dan menunggu diproses petugas.",
                link: '/user/pengajuan',
                refType: 'Permohonan',
                refId: $permohonan->id,
            ));
        }

        // Hanya tercatat bila pembuatnya petugas (helper mengabaikan warga).
        $this->log->catat(
            $u, 'BUAT', 'Permohonan',
            "Membuat permohonan {$jenis->nama} ({$noregister}) atas nama warga",
            $permohonan->id, $request,
        );

        return Balasan::ok(
            ['noregister' => $permohonan->no_register, 'id' => $permohonan->id],
            ["Permohonan berhasil diajukan — No. Registrasi {$permohonan->no_register}"]
        );
    }
}
