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
     * SELURUH 9 unsur wajib dinilai pada skala 1–4. Validasinya di sini, bukan
     * hanya di UI — nilai yang lolos setengah terisi akan merusak perhitungan
     * IKM tanpa terlihat rusak.
     */
    public function kirimSkm(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:191'],
            'jenisKel' => ['nullable', 'string', 'max:191'],
            'umur' => ['nullable', 'integer', 'min:1', 'max:120'],
            'pekerjaan' => ['nullable', 'string', 'max:191'],
            'jawaban' => ['required', 'array'],
            'saran' => ['nullable', 'string'],
        ], ['nama.required' => 'Info: Nama wajib diisi']);

        $maks = config('skm.skala_max');
        $belum = [];

        foreach (array_keys(config('skm.aspek')) as $i) {
            $nilai = (int) ($data['jawaban'][(string) $i] ?? 0);
            if ($nilai < 1 || $nilai > $maks) {
                $belum[] = $i + 1;
            }
        }

        if ($belum !== []) {
            return Balasan::gagal(['Belum dinilai: unsur '.implode(',', $belum)], 422);
        }

        $jawaban = SkmJawaban::create([
            'nama' => trim($data['nama']),
            'jenis_kelamin' => $data['jenisKel'] ?? null,
            'umur' => $data['umur'] ?? null,
            'pekerjaan' => $data['pekerjaan'] ?? null,
            'jawaban' => $data['jawaban'],
            'saran' => $data['saran'] ?? null,
        ]);

        $this->notif->aman(fn () => $this->notif->kePetugas(
            tipe: 'SKM_BARU',
            judul: 'Responden SKM baru',
            isi: trim($data['nama']).' mengisi Survei Kepuasan Masyarakat'
                .(filled($data['saran'] ?? null) ? ' — saran: "'.mb_substr(trim($data['saran']), 0, 120).'"' : '').'.',
            link: '/dashboard/skm',
            refType: 'SkmJawaban',
            refId: $jawaban->id,
        ));

        return Balasan::ok(null, ['Info: Terima kasih, survei Anda berhasil dikirim']);
    }

    /** Daftar unsur & skala — dipakai formulir survei. */
    public function unsurSkm()
    {
        return Balasan::ok([
            'aspek' => config('skm.aspek'),
            'skalaMax' => config('skm.skala_max'),
            'skalaLabel' => config('skm.skala_label'),
        ]);
    }
}
