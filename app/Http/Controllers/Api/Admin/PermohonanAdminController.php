<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisPermohonan;
use App\Models\Permohonan;
use App\Models\Wilayah;
use App\Services\CatatanAktivitas;
use App\Services\Pemberitahuan;
use App\Services\Surel;
use App\Support\AlasanTolakPermohonan;
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

        /*
         * Jenis permohonan — BOLEH LEBIH DARI SATU, dipisah koma (`?jenis=3,7`).
         *
         * Petugas kerap membandingkan beberapa layanan sekaligus (mis. semua
         * turunan Kartu Keluarga), dan saringan satu-nilai memaksanya membuka
         * halaman ini berkali-kali untuk pertanyaan yang sama.
         */
        $jenis = array_values(array_unique(array_filter(
            array_map('trim', explode(',', (string) $request->query('jenis'))),
            fn ($v) => $v !== '' && ctype_digit($v),
        )));

        // Wilayah = id KECAMATAN di `m_wilayah`, juga boleh lebih dari satu.
        $wilayah = array_values(array_unique(array_filter(
            array_map('trim', explode(',', (string) $request->query('wilayah'))),
            fn ($v) => $v !== '' && ctype_digit($v),
        )));

        $limit = min(100, max(10, (int) $request->query('limit', 20)));
        $page = max(1, (int) $request->query('page', 1));

        /*
         * 🔴 PAGAR KEPEMILIKAN — Operator OPD hanya melihat permohonannya sendiri.
         *
         * Sejak 2 Sep 2026 endpoint ini juga melayani Operator OPD, yang memakai
         * halaman daftar yang sama persis dengan petugas. Yang membedakan bukan
         * halamannya, melainkan baris ini: tanpa penyaringan ini, 140 akun
         * instansi langsung melihat 11.919 permohonan seluruh kabupaten —
         * lengkap dengan nama, NIK, dan nomor telepon pemohonnya.
         *
         * ⚠️ Ditaruh di dalam `$dasar` supaya ikut ke KETIGA pemakaiannya:
         * daftar barisnya, hitungan total, DAN hitungan per status. Menaruhnya
         * hanya di query baris membuat tabelnya benar tapi lencana jumlah di
         * tab-nya menghitung permohonan seluruh kabupaten — bocor tanpa satu
         * baris data pun tampil.
         */
        $milikSendiri = ! $request->user()->isPetugas();

        // Klausa dasar dipakai tiga kali (hitung total, ambil baris, hitung per
        // status), jadi disusun sebagai closure sekali pakai-ulang.
        $dasar = function ($w) use ($q, $petugas, $jenis, $wilayah, $request, $milikSendiri) {
            $w->when($milikSendiri, fn ($x) => $x->where('user_id', $request->user()->id));

            /*
             * ⚠️ Di dalam `$dasar`, jadi hitungan per status ikut menyesuaikan.
             * Kalau ditaruh di luar, angka di lencana tab tetap menghitung
             * seluruh jenis sementara tabelnya sudah tersaring — dua angka yang
             * saling bertentangan di satu layar, tanpa satu pun galat.
             */
            $w->when($jenis !== [], fn ($x) => $x->whereIn('jenis_id', $jenis));

            $w->when(filled($petugas), fn ($x) => $x->where('proses_by', (int) $petugas));

            /*
             * 🔴 WILAYAH DIBACA DARI AKUN PENGAJU, bukan dari permohonannya.
             *
             * `t_permohonan` tidak punya kolom wilayah dan payload-nya tidak
             * memuat kecamatan — sudah diperiksa pada 4.000 baris. Satu-satunya
             * tempat wilayah tercatat adalah `users.user_kecamatan`, dan itulah
             * sebabnya kolom tersebut diwajibkan untuk peran yang mewakili
             * tempat (`UserLevel::WAJIB_WILAYAH`).
             *
             * ⚠️ Yang dicocokkan NAMA kecamatan, bukan id — sama seperti cara
             * kolomnya disimpan. Mengganti ejaan di `m_wilayah` memutus
             * kecocokan tanpa satu pun galat; kalau ejaannya diubah, akun lama
             * harus ikut diperbarui.
             */
            $w->when($wilayah !== [], function ($x) use ($wilayah) {
                $nama = Wilayah::whereIn('id', $wilayah)
                    ->where('jenis', Wilayah::KECAMATAN)
                    ->pluck('nama');

                $x->whereHas('user', fn ($u) => $u->whereIn('user_kecamatan', $nama));
            });

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
            ->with(['jenis:id,nama,kategori', 'user:id,user_id,user_fullname,user_hp,user_kecamatan,user_kelurahan'])
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
                // Desa hanya disebut kalau memang tercatat — jangan mengaku
                // tahu desanya hanya karena kecamatannya diketahui.
                'wilayah' => [
                    'desa' => $p->user->user_kelurahan ?: null,
                    'kecamatan' => $p->user->user_kecamatan ?: null,
                ],
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

            /*
             * SELURUH jenis dari master, termasuk yang belum punya satu
             * permohonan pun.
             *
             * 🔴 Menyusunnya dari jenis yang BENAR-BENAR TERPAKAI terdengar
             * lebih rapi dan justru salah: layanan yang baru diaktifkan tidak
             * akan pernah bisa disaring sampai ada orang mengajukannya, dan
             * petugas yang ingin memastikan "belum ada yang masuk" tidak punya
             * cara memeriksanya. Terukur di basis data ini: 17 jenis di master,
             * 15 terpakai — Kartu Keluarga Sakinah dan Pencetakan KTP akan
             * hilang dari saringan padahal keduanya aktif.
             */
            $data['daftarJenis'] = JenisPermohonan::orderBy('nama')
                ->get(['id', 'nama'])
                ->map(fn ($j) => ['id' => $j->id, 'nama' => $j->nama])
                ->values();

            /*
             * SELURUH 11 kecamatan Pesisir Barat, bukan hanya yang punya
             * permohonan — alasannya sama dengan daftar jenis di atas.
             *
             * 🔴 Kalau daftarnya mengikuti data, kecamatan yang belum pernah
             * mengajukan HILANG dari layar, dan pertanyaan "kecamatan ini
             * belum pernah mengajukan, atau saya yang salah lihat?" tidak bisa
             * dijawab dari sini sama sekali. Daftarnya juga jadi tetap: urutan
             * yang sama setiap kali, tidak bergeser mengikuti data.
             */
            $data['daftarWilayah'] = Wilayah::where('jenis', Wilayah::KECAMATAN)
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->map(fn ($w) => ['id' => (int) $w->id, 'nama' => $w->nama])
                ->values();

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
    public function show(Request $request, int $id)
    {
        $p = Permohonan::with([
            'jenis',
            'user:id,user_id,user_fullname,user_hp,user_email',
            'berkas',
        ])->find($id);

        if (! $p) {
            return Balasan::gagal(['Permohonan tidak ditemukan'], 404);
        }

        /*
         * 🔴 404, BUKAN 403, untuk permohonan milik orang lain.
         *
         * Id permohonan berurutan dan mudah ditebak. 403 mengonfirmasi bahwa
         * nomor itu ADA — cukup untuk memetakan berapa banyak permohonan yang
         * masuk dan kapan, hanya dengan mencoba nomor berurutan. 404 tidak
         * membedakan "tidak ada" dari "bukan milikmu", dan itu memang tujuannya.
         */
        if (! $request->user()->isPetugas() && $p->user_id !== $request->user()->id) {
            return Balasan::gagal(['Permohonan tidak ditemukan'], 404);
        }

        $form = Layanan::formDariKode($p->jenis->kode ?? null);
        $tampil = Layanan::tampilkanPayload($form, $p->payload);

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

            /*
             * Penolakan diurai jadi bagian-bagiannya supaya halaman detail bisa
             * menampilkannya sebagai DAFTAR, bukan satu blok teks — dan supaya
             * formulir petugas bisa memuat ulang penolakan yang sudah ada tanpa
             * mengetik ulang. `catatan` mentah tetap dikirim: itu yang dibaca
             * surel, notifikasi, dan PDF, jadi keduanya harus tetap sepakat.
             */
            'tolak' => AlasanTolakPermohonan::uraikan($p->catatan),

            /*
             * Pilihan "data yang perlu dilengkapi" untuk permohonan INI —
             * diambil dari skema layanannya sendiri, bukan daftar tetap. Jenis
             * permohonan yang berbeda punya isian dan lampiran yang berbeda.
             */
            'rincianPilihan' => AlasanTolakPermohonan::pilihan($form),
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

        // ⚠️ `kode` ikut dimuat: pemeriksaan rincian penolakan di bawah butuh
        // skema layanannya, dan `Layanan::formDariKode()` dicari lewat kolom itu.
        // Tanpa ini `$p->jenis->kode` bernilai null, skemanya tidak ketemu, dan
        // SELURUH rincian yang sah ditolak sebagai "tidak dikenali".
        $p = Permohonan::with(['jenis:id,nama,kode', 'user:id,user_id,user_fullname,user_email'])->find($id);
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

        /*
         * 🔴 PENOLAKAN WAJIB LENGKAP — DIPERIKSA DI SERVER, BUKAN DI FORMULIR.
         *
         * Sebelumnya aturan ini tidak ada sama sekali di sini: `PATCH` dengan
         * `{"status":"DITOLAK"}` saja dijawab 200, dan permohonan warga ditolak
         * TANPA satu pun alasan tercatat. Yang hilang bukan kerapian data —
         * halaman riwayat warga menampilkan "Silakan hubungi petugas Disdukcapil
         * untuk informasi alasan penolakan" ketika catatannya kosong, dan itulah
         * yang dibaca warga berkali-kali tanpa pernah tahu apa yang salah.
         *
         * Catatan disusun DI SINI, bukan diterima jadi dari klien. Teks ini
         * dibaca warga langsung di halaman riwayat, surel, notifikasi, dan PDF;
         * kalau klien yang merangkainya, satu permintaan buatan bisa menuliskan
         * kalimat apa pun atas nama dinas.
         */
        if ($status === Permohonan::STATUS_DITOLAK) {
            $alasan = trim((string) $request->input('alasan'));
            $keterangan = trim((string) $request->input('keterangan'));
            $rincian = array_values(array_filter(
                array_map(fn ($s) => trim((string) $s), (array) $request->input('rincian', [])),
                fn ($s) => $s !== '',
            ));

            if (! in_array($alasan, AlasanTolakPermohonan::ALASAN, true)) {
                return Balasan::gagal(['Info: Alasan penolakan wajib dipilih'], 422);
            }

            if ($keterangan === '') {
                return Balasan::gagal(['Info: Keterangan penolakan wajib diisi'], 422);
            }

            if (AlasanTolakPermohonan::perluRincian($alasan)) {
                if ($rincian === []) {
                    return Balasan::gagal([
                        'Info: Pilih minimal satu data yang perlu dilengkapi',
                    ], 422);
                }

                /*
                 * Rincian dicocokkan dengan skema layanan permohonan ini.
                 * Tanpa pemeriksaan ini, label apa pun bisa diselipkan ke
                 * kalimat yang dibaca warga sebagai pernyataan resmi dinas.
                 */
                $sah = AlasanTolakPermohonan::labelSah(
                    Layanan::formDariKode($p->jenis->kode ?? null)
                );

                if (array_diff($rincian, $sah) !== []) {
                    return Balasan::gagal([
                        'Info: Ada data yang dipilih tidak dikenali pada jenis permohonan ini',
                    ], 422);
                }
            } else {
                // Alasan yang tidak menuntut rincian tidak boleh diam-diam
                // membawanya — kalimatnya jadi tidak nyambung bagi warga.
                $rincian = [];
            }

            $catatan = AlasanTolakPermohonan::susun($alasan, $rincian, $keterangan);
            $p->catatan = $catatan;
        } elseif ($request->has('catatan')) {
            $p->catatan = $catatan;
        }

        if (filled($status)) {
            $p->status = $status;
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
