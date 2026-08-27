<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KritikSaran;
use App\Models\Pengaduan;
use App\Models\SkmJawaban;
use App\Services\Pemberitahuan;
use App\Services\Recaptcha;
use App\Support\Balasan;
use Illuminate\Http\Request;

/**
 * Aspirasi warga: pengaduan masyarakat, kritik & saran, survei kepuasan.
 *
 * Ketiganya endpoint **publik** (tanpa login) — karena itu semuanya lewat
 * reCAPTCHA dan pembatas laju. Daftar isinya hanya boleh dibaca petugas.
 */
class AspirasiController extends Controller
{
    public function __construct(
        private readonly Recaptcha $recaptcha,
        private readonly Pemberitahuan $notif,
    ) {}

    // ── Pengaduan masyarakat ────────────────────────────────────────────────

    public function kirimPengaduan(Request $request)
    {
        if (! $this->recaptcha->verifikasi($request->input('recaptchaToken'))) {
            return Balasan::gagal(['Info: Verifikasi reCAPTCHA gagal']);
        }

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:191'],
            'isi' => ['required', 'string'],
            'nik' => ['nullable', 'string', 'max:191'],
            'email' => ['nullable', 'string', 'max:191'],
            'hp' => ['nullable', 'string', 'max:191'],
            'subjek' => ['nullable', 'string', 'max:191'],
        ], [
            'nama.required' => 'Info: Nama dan isi pengaduan wajib diisi',
            'isi.required' => 'Info: Nama dan isi pengaduan wajib diisi',
        ]);

        $pengaduan = Pengaduan::create($data);

        $this->notif->aman(fn () => $this->notif->kePetugas(
            tipe: 'PENGADUAN_BARU',
            judul: 'Pengaduan masyarakat baru',
            isi: $data['nama'].(filled($data['subjek'] ?? null) ? " — {$data['subjek']}" : ''),
            link: '/dashboard/pengaduan',
            refType: 'Pengaduan',
            refId: $pengaduan->id,
        ));

        return Balasan::ok(null, ['Info: Pengaduan berhasil dikirim']);
    }

    /**
     * 🔴 Hanya petugas. Isinya memuat identitas pelapor — termasuk jalur WBS
     * yang kerahasiaannya dijanjikan di halaman publik.
     */
    public function daftarPengaduan(Request $request)
    {
        if (! $request->user()?->isPetugas()) {
            return Balasan::gagal(['Tidak diizinkan'], 403);
        }

        return Balasan::ok(['items' => Pengaduan::latest('created_at')->get()]);
    }

    // ── Kritik & saran ──────────────────────────────────────────────────────

    public function kirimKritik(Request $request)
    {
        if (! $this->recaptcha->verifikasi($request->input('recaptchaToken'))) {
            return Balasan::gagal(['Info: Verifikasi reCAPTCHA gagal']);
        }

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:191'],
            'pesan' => ['required', 'string'],
            'email' => ['nullable', 'email', 'max:191'],
            'hp' => ['nullable', 'string', 'max:191'],
        ], [
            'nama.required' => 'Info: Nama wajib diisi',
            'pesan.required' => 'Info: Pesan wajib diisi',
        ]);

        $kritik = KritikSaran::create($data);

        $this->notif->aman(fn () => $this->notif->kePetugas(
            tipe: 'KRITIK_BARU',
            judul: 'Kritik & saran baru',
            isi: $data['nama'].' mengirim kritik & saran.',
            link: '/dashboard/kritik-saran',
            refType: 'KritikSaran',
            refId: $kritik->id,
        ));

        return Balasan::ok(null, ['Info: Terima kasih, masukan Anda sudah kami terima']);
    }

    public function daftarKritik(Request $request)
    {
        if (! $request->user()?->isPetugas()) {
            return Balasan::gagal(['Tidak diizinkan'], 403);
        }

        return Balasan::ok(['items' => KritikSaran::latest('created_at')->get()]);
    }

    // ── Survei Kepuasan Masyarakat ──────────────────────────────────────────

    /**
     * Simpan jawaban SKM (publik).
     *
     * SELURUH 16 pertanyaan wajib dinilai pada skala 1–4; identitas responden
     * seluruhnya OPSIONAL — mengikuti berkas kuesioner dinas, yang menyatakan
     * nama "boleh inisial atau tidak diisi".
     *
     * 🔴 Validasi kelengkapan ada DI SINI, bukan hanya di UI: jawaban setengah
     * terisi merusak NRR (dan karenanya nilai IKM) tanpa terlihat rusak.
     */
    public function kirimSkm(Request $request)
    {
        // 🔴 Penjaga sesungguhnya sakelar `skm.terbuka` — halaman yang sekadar
        // menyembunyikan formulir masih bisa dilewati dengan mengirim permintaan
        // langsung ke endpoint ini. Petugas tetap boleh mengirim supaya dinas
        // bisa mencoba kuesionernya sampai tuntas sebelum dibuka untuk warga.
        if (! config('skm.terbuka') && ! $request->user()?->isPetugas()) {
            return Balasan::gagal(['Info: Survei belum dibuka untuk umum'], 403);
        }

        $data = $request->validate([
            'nama' => ['nullable', 'string', 'max:191'],
            'instansi' => ['nullable', 'string', 'max:191'],
            'jenisKel' => ['nullable', 'string', 'max:191'],
            'umur' => ['nullable', 'integer', 'min:1', 'max:120'],
            'pendidikan' => ['nullable', 'string', 'max:191'],
            'pekerjaan' => ['nullable', 'string', 'max:191'],
            'produkLayanan' => ['nullable', 'string', 'max:191'],
            'disabilitas' => ['nullable', 'boolean'],
            'jenisDisabilitas' => ['nullable', 'string', 'max:191'],
            'jawaban' => ['required', 'array'],
            'saran' => ['nullable', 'string'],
        ]);

        $maks = (int) config('skm.skala_max');
        $belum = [];
        $bersih = [];

        foreach (config('skm.pertanyaan') as $urutan => $p) {
            $nilai = (int) ($data['jawaban'][$p['kunci']] ?? 0);

            if ($nilai < 1 || $nilai > $maks) {
                $belum[] = $urutan + 1;

                continue;
            }

            // Hanya kunci yang dikenal yang ikut tersimpan — badan permintaan
            // tidak boleh menyelundupkan kunci lain ke kolom JSON.
            $bersih[$p['kunci']] = $nilai;
        }

        if ($belum !== []) {
            return Balasan::gagal(['Belum dinilai: pertanyaan '.implode(', ', $belum)], 422);
        }

        $nama = trim((string) ($data['nama'] ?? ''));

        $jawaban = SkmJawaban::create([
            'nama' => $nama !== '' ? $nama : null,
            'instansi' => $data['instansi'] ?? null,
            'jenis_kelamin' => $data['jenisKel'] ?? null,
            'umur' => $data['umur'] ?? null,
            'pendidikan' => $data['pendidikan'] ?? null,
            'pekerjaan' => $data['pekerjaan'] ?? null,
            'produk_layanan' => $data['produkLayanan'] ?? null,
            'disabilitas' => $data['disabilitas'] ?? null,
            // Jenis disabilitas hanya bermakna kalau jawabannya "ya"; menyimpan
            // sisa isian dari orang yang berpindah pilihan akan muncul di rekap
            // sebagai penyandang disabilitas yang tidak pernah menyatakannya.
            'jenis_disabilitas' => ($data['disabilitas'] ?? false) ? ($data['jenisDisabilitas'] ?? null) : null,
            'jawaban' => $bersih,
            'saran' => $data['saran'] ?? null,
        ]);

        $this->notif->aman(fn () => $this->notif->kePetugas(
            tipe: 'SKM_BARU',
            judul: 'Responden SKM baru',
            isi: ($nama !== '' ? $nama : 'Responden anonim').' mengisi Survei Kepuasan Masyarakat'
                .(filled($data['saran'] ?? null) ? ' — saran: "'.mb_substr(trim($data['saran']), 0, 120).'"' : '').'.',
            link: '/dashboard/skm',
            refType: 'SkmJawaban',
            refId: $jawaban->id,
        ));

        return Balasan::ok(null, ['Info: Terima kasih, survei Anda berhasil dikirim']);
    }

    /** Daftar pertanyaan, skala, & pilihan identitas — dipakai formulir survei. */
    public function unsurSkm()
    {
        return Balasan::ok([
            'pertanyaan' => config('skm.pertanyaan'),
            'skalaMax' => config('skm.skala_max'),
            'skalaLabel' => config('skm.skala_label'),
            'pendidikan' => config('skm.pendidikan'),
            'pekerjaan' => config('skm.pekerjaan'),
            'jenisDisabilitas' => config('skm.jenis_disabilitas'),
        ]);
    }
}
