import { useCallback, useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import {
  AlertTriangle, ArrowLeft, Download, Lock, Mail, Phone, User,
} from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import PenampilGambar from '@/Components/PenampilGambar';
import PilihRincian from '@/Components/PilihRincian';
import {
  Kartu, LencanaStatus, Memuat, Pesan, STATUS_FINAL, STATUS_PERMOHONAN,
  Tombol, tglJam,
} from '@/Components/Dasbor';
import { ambilJson, kirimJson } from '@/lib/api';
import { Label } from '@/Components/ui/label';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';

const STATUS_URUT = ['MENUNGGU', 'DIPROSES', 'SELESAI', 'DITOLAK'];

/**
 * Alasan baku penolakan.
 *
 * 🔴 CERMIN `App\Support\AlasanTolakPermohonan::ALASAN` — dan seperti setiap
 * cermin di aplikasi ini, keduanya tidak saling memeriksa. Menambah atau
 * mengganti satu kalimat di sini tanpa mengubah sisi PHP-nya tidak membuat apa
 * pun gagal saat build: petugas hanya akan memilih alasan yang lalu ditolak
 * server dengan "Alasan penolakan wajib dipilih", tanpa penjelasan kenapa
 * padahal ia jelas-jelas sudah memilih. Ubah BERPASANGAN.
 */
const ALASAN_TOLAK = [
  'Berkas tidak lengkap',
  'Berkas tidak jelas / buram',
  'Data tidak sesuai dengan dokumen',
  'NIK / dokumen tidak valid',
  'Persyaratan belum terpenuhi',
  'Lainnya',
];

/** Cermin `AlasanTolakPermohonan::WAJIB_RINCIAN`. Ubah berpasangan. */
const WAJIB_RINCIAN = [
  'Berkas tidak lengkap',
  'Berkas tidak jelas / buram',
  'Data tidak sesuai dengan dokumen',
  'Persyaratan belum terpenuhi',
];

const perluRincian = (alasan) => WAJIB_RINCIAN.includes(alasan);

/**
 * Detail satu permohonan — HALAMAN SENDIRI, bukan panel di dalam daftar.
 *
 * Alasannya bukan selera: alamatnya bisa dikirim ke rekan kerja, tombol Kembali
 * peramban bekerja sebagaimana mestinya, dan formulir penolakan punya ruang
 * untuk daftar "data yang perlu dilengkapi" tanpa dijejalkan ke dalam modal.
 *
 * 🔴 Formulir prosesnya INLINE, bukan modal. Permintaan dinas, dan kebetulan
 * juga menghindari satu jebakan nyata: pesan galat (`Pesan`) dan `Modal` pernah
 * sama-sama `z-50`, sehingga setiap penolakan server dari dalam modal muncul
 * di baliknya dan tidak pernah terbaca siapa pun.
 */
export default function PermohonanDetail({ id }) {
  /*
   * Operator OPD membuka halaman ini untuk permohonan MILIKNYA — hanya baca.
   *
   * ⚠️ Tampilan, bukan pagar. `PATCH /api/admin/permohonan/{id}` tetap
   * `peran:petugas` di berkas rute, dan `show()` menjawab 404 untuk nomor milik
   * orang lain. Yang dilakukan di sini cuma menyembunyikan tombol yang pasti
   * ditolak — tombol yang menjanjikan aksi lalu dijawab 403 lebih buruk
   * daripada tombol yang tidak ada.
   */
  const level = usePage().props.auth?.user?.level;
  const bisaProses = level === 1 || level === 2;

  const [detail, setDetail] = useState(null);
  const [memuat, setMemuat] = useState(true);
  const [pesan, setPesan] = useState(null);
  const [lihat, setLihat] = useState(null);

  // ── Formulir proses ───────────────────────────────────────────────────────
  const [proses, setProses] = useState(false);
  const [statusBaru, setStatusBaru] = useState('');
  const [alasan, setAlasan] = useState('');
  const [rincian, setRincian] = useState([]);
  const [keterangan, setKeterangan] = useState('');
  const [catatan, setCatatan] = useState('');
  const [medan, setMedan] = useState({});
  const [konfirmFinal, setKonfirmFinal] = useState(false);
  const [menyimpan, setMenyimpan] = useState(false);

  const muat = useCallback(async () => {
    setMemuat(true);

    const j = await ambilJson(`/api/admin/permohonan/${id}`);

    setMemuat(false);

    if (j.error?.length) {
      setPesan({ tipe: 'galat', teks: j.error[0] });

      return;
    }

    const p = j.data?.permohonan ?? null;

    setDetail(p);
    setStatusBaru(p?.status ?? '');
    setCatatan(p?.catatan ?? '');
    setAlasan(p?.tolak?.alasan ?? '');
    setRincian(p?.tolak?.rincian ?? []);
    setKeterangan(p?.tolak?.keterangan ?? '');
  }, [id]);

  useEffect(() => { muat(); }, [muat]);

  const final = detail ? STATUS_FINAL.includes(detail.status) : false;
  const menolak = statusBaru === 'DITOLAK';

  const periksa = () => {
    const m = {};

    if (menolak) {
      if (! alasan) m.alasan = 'Pilih alasan penolakan';
      if (perluRincian(alasan) && rincian.length === 0) {
        m.rincian = 'Pilih minimal satu data yang perlu dilengkapi';
      }
      if (! keterangan.trim()) m.keterangan = 'Keterangan wajib diisi';
    }

    setMedan(m);

    return Object.keys(m).length === 0;
  };

  const simpan = async () => {
    if (! periksa()) {
      setKonfirmFinal(false);

      return;
    }

    if (STATUS_FINAL.includes(statusBaru) && ! konfirmFinal) {
      setKonfirmFinal(true);

      return;
    }

    setMenyimpan(true);

    // Penolakan dikirim TERURAI. Server yang merangkainya jadi kalimat —
    // teks itu dibaca warga sebagai pernyataan resmi dinas, jadi bentuk
    // akhirnya tidak boleh ditentukan klien.
    const muatan = menolak
      ? { status: statusBaru, alasan, rincian: perluRincian(alasan) ? rincian : [], keterangan }
      : { status: statusBaru, catatan };

    const j = await kirimJson(`/api/admin/permohonan/${id}`, muatan, 'PATCH');

    setMenyimpan(false);
    setKonfirmFinal(false);

    if (j.error?.length) {
      setPesan({ tipe: 'galat', teks: j.error[0] });
      if (j.medan) setMedan(j.medan);

      return;
    }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Tersimpan' });
    setProses(false);
    muat();
  };

  return (
    <LayoutDashboard judul="Detail Permohonan">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      {memuat || ! detail ? (
        <Kartu>
          <Tombol varian="garis" onClick={() => router.visit('/dashboard/permohonan')}>
            <ArrowLeft className="h-4 w-4" />Kembali ke daftar
          </Tombol>
          {memuat ? <Memuat kelas="py-16" /> : (
            <p className="py-16 text-center text-sm text-slate-500">
              Permohonan tidak ditemukan.
            </p>
          )}
        </Kartu>
      ) : (
        <Kartu>
          <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
            <Tombol varian="garis" onClick={() => router.visit('/dashboard/permohonan')}>
              <ArrowLeft className="h-4 w-4" />Kembali ke daftar
            </Tombol>
            <div className="flex items-center gap-2">
              {/* Tautan biasa, bukan router.visit — balasannya berkas PDF dan
                  kunjungan Inertia akan menelannya tanpa pesan apa pun. */}
              <a href={`/api/permohonan/${detail.id}/pdf`} download
                 title={`Unduh tanda terima ${detail.noregister}`}
                 className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:border-brand hover:text-brand">
                <Download className="h-4 w-4" />PDF
              </a>
              <LencanaStatus status={detail.status} />
            </div>
          </div>

          <div className="space-y-4">
            <div className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
              <h3 className="font-semibold text-slate-900">{detail.jenis?.nama ?? 'Permohonan'}</h3>
              <p className="mt-0.5 text-xs text-slate-500">
                <span className="font-mono font-semibold">{detail.noregister}</span> · {detail.jenis?.kategori ?? '-'} ·
                diajukan {tglJam(detail.createdAt)}
              </p>
            </div>

            {/* 🔴 HASIL PENOLAKAN DI ATAS, sebelum data dan lampiran.
                Pada permohonan yang ditolak, inilah satu-satunya informasi yang
                dicari pembacanya. Menaruhnya di bawah berarti alasannya harus
                digulir melewati seluruh isian formulir lebih dulu. */}
            {detail.status === 'DITOLAK' && <BlokPenolakan tolak={detail.tolak} catatan={detail.catatan} />}

            <div className="rounded-xl border border-slate-200 p-4">
              <h4 className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Pemohon</h4>
              <div className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                <p className="flex items-start gap-2 text-slate-700">
                  <User className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                  <span>
                    {detail.user?.userFullname ?? '-'}
                    <span className="block font-mono text-xs text-slate-400">{detail.user?.userId ?? '-'}</span>
                  </span>
                </p>
                <p className="flex items-center gap-2 text-slate-700">
                  <Phone className="h-4 w-4 shrink-0 text-slate-400" />{detail.user?.userHp || '-'}
                </p>
                <p className="flex items-center gap-2 break-all text-slate-700">
                  <Mail className="h-4 w-4 shrink-0 text-slate-400" />{detail.user?.userEmail || '-'}
                </p>
              </div>
            </div>

            {detail.data?.length > 0 && (
              <div className="rounded-xl border border-slate-200 p-4">
                <h4 className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Data Permohonan</h4>
                <dl className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                  {detail.data.map((d, i) => (
                    <div key={i} className="rounded-lg bg-slate-50/80 px-3 py-2">
                      <dt className="text-xs text-slate-400">{d.label}</dt>
                      <dd className="mt-0.5 break-words text-sm font-medium text-slate-800">{d.nilai}</dd>
                    </div>
                  ))}
                </dl>
              </div>
            )}

            <div className="rounded-xl border border-slate-200 p-4">
              <h4 className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
                Berkas Lampiran ({detail.berkas?.length ?? 0})
              </h4>
              {detail.berkas?.length ? (
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                  {detail.berkas.map((b, i) => (
                    <button key={i} onClick={() => setLihat(i)} className="group text-left"
                            title={`Klik untuk memperbesar — ${b.label}`}>
                      <div className="overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                        {/* Berkasnya disajikan BerkasController, bukan dari public/ —
                            petugas boleh melihat semua, warga hanya miliknya. */}
                        <img src={b.path} alt={b.label} loading="lazy"
                             className="h-28 w-full object-cover transition-transform group-hover:scale-105" />
                      </div>
                      <p className="mt-1 truncate text-xs text-slate-500">{b.label}</p>
                    </button>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-slate-400">Tidak ada berkas terlampir.</p>
              )}
            </div>

            {detail.prosesByName && (
              <p className="flex items-center gap-1.5 text-xs text-slate-500">
                <User className="h-3.5 w-3.5 text-slate-400" />
                Diproses oleh <b className="text-slate-700">{detail.prosesByName}</b>
                {detail.prosesAt && <> · {tglJam(detail.prosesAt)}</>}
              </p>
            )}

            {/* ── Aksi & formulir proses ───────────────────────────────────── */}
            <div className="border-t border-slate-100 pt-4">
              {! bisaProses ? (
                <p className="text-xs text-slate-400">
                  Permohonan ini hanya dapat dilihat. Pemrosesan dilakukan petugas Disdukcapil.
                </p>
              ) : final ? (
                <div className="flex justify-end">
                  <span className="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-500"
                        title="Permohonan final — buka kunci lewat halaman Master">
                    <Lock className="h-3.5 w-3.5" />Permohonan final &amp; terkunci
                  </span>
                </div>
              ) : ! proses ? (
                <div className="flex justify-end">
                  <Tombol onClick={() => setProses(true)}>Proses Permohonan</Tombol>
                </div>
              ) : (
                <div className="space-y-4 rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                  <h4 className="text-sm font-semibold text-slate-900">Proses Permohonan</h4>

                  <div>
                    <Label className="text-slate-700">Status</Label>
                    <div className="mt-1.5 grid grid-cols-2 gap-2 sm:grid-cols-4">
                      {STATUS_URUT.map((k) => (
                        <button key={k} type="button"
                                onClick={() => { setStatusBaru(k); setKonfirmFinal(false); setMedan({}); }}
                                className={`rounded-lg border px-3 py-2 text-sm font-medium transition-colors ${
                                  statusBaru === k
                                    ? STATUS_PERMOHONAN[k].warna
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                }`}>
                          {STATUS_PERMOHONAN[k].label}
                        </button>
                      ))}
                    </div>
                  </div>

                  {menolak ? (
                    <>
                      <div>
                        <Label className="text-slate-700">
                          Alasan Penolakan <span className="text-rose-600">*</span>
                        </Label>
                        <Select
                          value={alasan}
                          onValueChange={(v) => {
                            setAlasan(v);
                            // Rincian milik alasan sebelumnya tidak boleh ikut
                            // terbawa — daftarnya jadi tidak nyambung.
                            if (! perluRincian(v)) setRincian([]);
                            setMedan((m) => ({ ...m, alasan: undefined, rincian: undefined }));
                          }}
                        >
                          <SelectTrigger className={`mt-1.5 w-full ${medan.alasan ? 'border-rose-400' : ''}`}>
                            <SelectValue placeholder="Pilih alasan penolakan…" />
                          </SelectTrigger>
                          <SelectContent>
                            {ALASAN_TOLAK.map((a) => <SelectItem key={a} value={a}>{a}</SelectItem>)}
                          </SelectContent>
                        </Select>
                        {medan.alasan && <p className="mt-1 text-xs text-rose-600">{medan.alasan}</p>}
                      </div>

                      {perluRincian(alasan) && (
                        <div>
                          <Label className="text-slate-700">
                            Data yang Perlu Dilengkapi <span className="text-rose-600">*</span>
                          </Label>
                          <div className="mt-1.5">
                            <PilihRincian
                              nilai={rincian}
                              onUbah={(v) => {
                                setRincian(v);
                                setMedan((m) => ({ ...m, rincian: undefined }));
                              }}
                              grup={detail.rincianPilihan ?? []}
                              galat={Boolean(medan.rincian)}
                            />
                          </div>
                          {medan.rincian
                            ? <p className="mt-1 text-xs text-rose-600">{medan.rincian}</p>
                            : (
                              <p className="mt-1.5 text-xs text-slate-400">
                                Pilihannya mengikuti formulir jenis permohonan ini.
                              </p>
                            )}
                        </div>
                      )}

                      <div>
                        <Label htmlFor="keterangan" className="text-slate-700">
                          Keterangan <span className="text-rose-600">*</span>
                        </Label>
                        <Textarea
                          id="keterangan" rows={3} value={keterangan}
                          onChange={(e) => {
                            setKeterangan(e.target.value);
                            setMedan((m) => ({ ...m, keterangan: undefined }));
                          }}
                          placeholder="Jelaskan secara ringkas apa yang harus diperbaiki pemohon."
                          className={`mt-1.5 ${medan.keterangan ? 'border-rose-400' : ''}`}
                        />
                        {medan.keterangan
                          ? <p className="mt-1 text-xs text-rose-600">{medan.keterangan}</p>
                          : (
                            <p className="mt-1.5 text-xs text-slate-400">
                              Keterangan ini dibaca pemohon di halaman riwayat, surel, dan notifikasi.
                            </p>
                          )}
                      </div>
                    </>
                  ) : (
                    <div>
                      <Label htmlFor="catatan" className="text-slate-700">Catatan Petugas</Label>
                      <Textarea id="catatan" rows={3} value={catatan}
                                onChange={(e) => setCatatan(e.target.value)}
                                placeholder="Catatan untuk pemohon (opsional)…" className="mt-1.5" />
                    </div>
                  )}

                  {/* Konfirmasi status final — sebagai bilah di tempatnya, bukan
                      lapisan melayang di atas formulir. */}
                  {konfirmFinal && (
                    <div className="rounded-lg border border-amber-300 bg-amber-50 p-3">
                      <p className="flex items-start gap-2 text-sm leading-relaxed text-amber-900">
                        <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                        <span>
                          Setelah status menjadi <b>{STATUS_PERMOHONAN[statusBaru]?.label}</b>, permohonan ini
                          <b> terkunci dan tidak dapat diubah lagi</b>. Membuka kunci hanya bisa lewat
                          halaman Master.
                        </span>
                      </p>
                    </div>
                  )}

                  <div className="flex flex-wrap justify-end gap-2">
                    <Tombol varian="garis"
                            onClick={() => { setProses(false); setKonfirmFinal(false); setMedan({}); }}>
                      Batal
                    </Tombol>
                    <Tombol
                      varian={konfirmFinal ? (menolak ? 'bahaya' : 'sukses') : undefined}
                      onClick={simpan}
                      disabled={menyimpan}
                    >
                      {konfirmFinal ? 'Ya, saya mengerti' : 'Simpan'}
                    </Tombol>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Seluruh lampiran diserahkan sekaligus, bukan hanya yang diklik —
              dengan begitu petugas bisa berpindah antar-berkas pakai ←/→ tanpa
              menutup dan membuka penampilnya berkali-kali. */}
          {lihat !== null && (
            <PenampilGambar
              daftar={(detail.berkas ?? []).map((b) => ({ src: b.path, judul: b.label }))}
              indeksAwal={lihat}
              onTutup={() => setLihat(null)}
            />
          )}
        </Kartu>
      )}
    </LayoutDashboard>
  );
}

/**
 * Ringkasan penolakan untuk dibaca — bukan formulir.
 *
 * Bahasanya sengaja formal dan ringkas: ini halaman layanan pemerintah, dan
 * kalimatnya ikut terbaca pemohon.
 *
 * `catatan` mentah dipakai sebagai cadangan untuk penolakan LAMA yang tidak
 * mengenal struktur ini — dan itu bukan kasus langka, melainkan seluruh
 * penolakan yang sudah tersimpan sebelum fitur ini ada.
 */
function BlokPenolakan({ tolak, catatan }) {
  const alasan = tolak?.alasan ?? '';
  const rincian = tolak?.rincian ?? [];
  const keterangan = tolak?.keterangan ?? '';

  if (! alasan && rincian.length === 0 && ! keterangan) {
    return (
      <div className="rounded-xl border border-rose-200 bg-rose-50/70 p-4">
        <h4 className="text-sm font-semibold text-rose-900">Permohonan Ditolak</h4>
        <p className="mt-1 whitespace-pre-line text-sm text-rose-800">
          {catatan?.trim() || 'Alasan penolakan tidak tercatat.'}
        </p>
      </div>
    );
  }

  return (
    <div className="rounded-xl border border-rose-200 bg-rose-50/70 p-4">
      <h4 className="text-sm font-semibold text-rose-900">Permohonan Ditolak</h4>

      {alasan && (
        <p className="mt-1 text-sm font-medium text-rose-800">{alasan}</p>
      )}

      {rincian.length > 0 && (
        <div className="mt-3">
          <p className="text-xs font-semibold uppercase tracking-wide text-rose-700/70">
            Data yang perlu dilengkapi
          </p>
          <ul className="mt-1.5 grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
            {rincian.map((r) => (
              <li key={r} className="flex items-start gap-2 text-sm leading-snug text-rose-900">
                <span aria-hidden className="mt-2 h-1 w-1 shrink-0 rounded-full bg-rose-400" />
                {r}
              </li>
            ))}
          </ul>
        </div>
      )}

      {keterangan && (
        <div className="mt-3 border-t border-rose-200 pt-3">
          <p className="text-xs font-semibold uppercase tracking-wide text-rose-700/70">Keterangan</p>
          <p className="mt-1 whitespace-pre-line text-sm text-rose-900">{keterangan}</p>
        </div>
      )}
    </div>
  );
}
