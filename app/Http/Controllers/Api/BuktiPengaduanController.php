<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Balasan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Unggah bukti foto pengaduan / WBS — port `app/api/pengaduan/upload/route.ts`.
 *
 * 🔴 PUBLIK, tanpa sesi. Itu disengaja dan bukan kelalaian: pelapor WBS boleh
 * anonim, dan memaksanya login lebih dulu meniadakan inti kanalnya. Karena itu
 * pembatas lajunya ketat dan formatnya dibatasi ke JPG/PNG saja.
 *
 * 🔴 Berkasnya masuk `storage/app/private/permohonan/pengaduan/`, BUKAN
 * `public/`. Bukti pengaduan memuat identitas pihak ketiga dan sering memuat
 * dokumen — kalau ditaruh di `public/` seluruh isinya bisa dibaca siapa pun
 * yang menebak namanya (aturan HANDOFF §6 no. 1, sudah dilanggar dua kali di
 * portal Next.js).
 *
 * Namanya diawali `wbs_`, BUKAN id pengunggah. `BerkasController` membaca
 * kepemilikan dari prefix itu: `wbs` bukan bilangan, jadi pemeriksaannya gagal
 * untuk SEMUA warga dan hanya petugas yang bisa membukanya. Persis perilaku
 * yang dipakai SIDAKO.
 */
class BuktiPengaduanController extends Controller
{
    private const MAKS = 5 * 1024 * 1024;

    private const EXT = ['jpg', 'jpeg', 'png'];

    public function __invoke(Request $request)
    {
        $berkas = $request->file('file');

        if (! $berkas || ! $berkas->isValid()) {
            return Balasan::gagal(['File tidak ditemukan']);
        }

        $ext = strtolower($berkas->getClientOriginalExtension());
        if (! in_array($ext, self::EXT, true)) {
            return Balasan::gagal(['Format foto harus JPG atau PNG']);
        }
        if ($berkas->getSize() > self::MAKS) {
            return Balasan::gagal(['Ukuran foto maksimal 5 MB']);
        }

        $nama = 'wbs_'.now()->getTimestampMs().'_'.Str::lower(Str::random(6)).'.'.$ext;

        // putFileAs mengalirkan berkas dari lokasi sementara — tidak memuatnya
        // utuh ke memori.
        Storage::disk('local')->putFileAs('permohonan/pengaduan', $berkas, $nama);

        return Balasan::ok(['url' => "/uploads/pengaduan/{$nama}"], ['Foto berhasil diunggah']);
    }
}
