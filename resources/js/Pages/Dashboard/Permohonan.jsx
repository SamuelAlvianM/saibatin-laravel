import { useCallback, useEffect, useRef, useState } from 'react';
import {
  AlertTriangle, ArrowLeft, ClipboardList, Eye, Lock, Mail, Phone, Search, User,
} from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import {
  FilterPeriode, Kartu, Kosong, LencanaStatus, Memuat, Modal, Paginasi, Pesan,
  STATUS_FINAL, STATUS_PERMOHONAN, Tombol, tglJam, tglSingkat, tulisAcuan, useTunda,
} from '@/Components/Dasbor';
import { ambilJson, kirimJson } from '@/lib/api';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';

const STATUS_URUT = ['MENUNGGU', 'DIPROSES', 'SELESAI', 'DITOLAK'];
const PER_HALAMAN = 20;

/** Alasan penolakan yang lazim; "Lainnya" → alasan diketik bebas. */
const ALASAN_TOLAK = [
  'Berkas tidak lengkap',
  'Berkas tidak jelas / buram',
  'Data tidak sesuai dengan dokumen',
  'NIK / dokumen tidak valid',
  'Persyaratan belum terpenuhi',
  'Lainnya',
];

/**
 * Daftar & pemrosesan permohonan — port `app/dashboard/permohonan/AdminPermohonan.tsx`.
 *
 * Alur yang sengaja dipertahankan: petugas TIDAK bisa mengubah status dari
 * baris tabel. Tombolnya hanya ada di dalam panel detail, setelah data dan
 * berkasnya terbaca. Menaruh tombol "Selesai" di tabel membuat permohonan bisa
 * difinalkan tanpa seorang pun membuka lampirannya.
 */
export default function Permohonan({ sorot }) {
  const [items, setItems] = useState([]);
  const [counts, setCounts] = useState({});
  const [daftarPetugas, setDaftarPetugas] = useState([]);
  const [total, setTotal] = useState(0);
  const [totalHalaman, setTotalHalaman] = useState(1);
  const [page, setPage] = useState(1);
  const [memuat, setMemuat] = useState(true);

  const [status, setStatus] = useState('');
  const [petugas, setPetugas] = useState('');
  const [periode, setPeriode] = useState('');
  const [acuan, setAcuan] = useState(() => new Date());
  const [cari, setCari] = useState('');
  const cariTertunda = useTunda(cari);

  const [detail, setDetail] = useState(null);
  const [memuatDetail, setMemuatDetail] = useState(false);
  const [sunting, setSunting] = useState(null);
  const [statusBaru, setStatusBaru] = useState('');
  const [catatan, setCatatan] = useState('');
  const [alasanPreset, setAlasanPreset] = useState('');
  const [konfirmFinal, setKonfirmFinal] = useState(false);
  const [menyimpan, setMenyimpan] = useState(false);
  const [pesan, setPesan] = useState(null);

  // Token anti-balapan: balasan filter LAMA yang datang belakangan harus
  // dibuang, kalau tidak tabel menampilkan hasil filter yang sudah diganti.
  const permintaan = useRef(0);
  // Datang dari notifikasi (?sorot=<id>): server yang menghitung halamannya.
  const sorotAwal = useRef(sorot ? Number(sorot) : null);
  const [sorotId, setSorotId] = useState(sorot ? Number(sorot) : null);

  const muat = useCallback(async (halaman) => {
    const milik = ++permintaan.current;
    setMemuat(true);

    const q = new URLSearchParams({ limit: String(PER_HALAMAN), page: String(halaman) });
    if (status) q.set('status', status);
    if (cariTertunda.trim()) q.set('q', cariTertunda.trim());
    if (petugas) q.set('petugas', petugas);
    if (periode) {
      q.set('periode', periode);
      q.set('acuan', tulisAcuan(acuan));
    }
    if (sorotAwal.current) {
      q.set('sorot', String(sorotAwal.current));
      q.delete('page');
    }

    const j = await ambilJson(`/api/admin/permohonan?${q}`);
    if (milik !== permintaan.current) return;   // filter sudah berganti

    setMemuat(false);

    if (j.error?.length) {
      setPesan({ tipe: 'galat', teks: j.error[0] });
      return;
    }

    if (sorotAwal.current) {
      setPage(j.data.page ?? 1);
      sorotAwal.current = null;
    }

    setItems(j.data.items ?? []);
    setTotal(j.data.total ?? 0);
    setTotalHalaman(j.data.totalHalaman ?? 1);
    if (j.data.counts) setCounts(j.data.counts);
    if (j.data.daftarPetugas) setDaftarPetugas(j.data.daftarPetugas);
  }, [status, cariTertunda, petugas, periode, acuan]);

  // Ganti filter → selalu kembali ke halaman 1; kalau tidak, petugas bisa
  // terdampar di halaman yang pada filter baru sudah tidak ada isinya.
  useEffect(() => {
    setPage(1);
    muat(1);
  }, [muat]);

  // Sorotan cukup sebagai penunjuk arah — hilang sendiri setelah terlihat.
  useEffect(() => {
    if (sorotId == null || memuat) return undefined;
    document.getElementById(`permohonan-${sorotId}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    const t = setTimeout(() => setSorotId(null), 2600);
    return () => clearTimeout(t);
  }, [sorotId, memuat, items]);

  const gantiHalaman = (p) => {
    setPage(p);
    muat(p);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const bukaDetail = async (it) => {
    setMemuatDetail(true);
    setDetail(null);

    const j = await ambilJson(`/api/admin/permohonan/${it.id}`);
    setMemuatDetail(false);

    if (j.data?.permohonan) setDetail(j.data.permohonan);
    else setPesan({ tipe: 'galat', teks: j.error?.[0] ?? 'Gagal memuat detail' });
  };

  const bukaSunting = (d) => {
    setSunting(d);
    setStatusBaru(d.status);
    setCatatan(d.catatan ?? '');
    setAlasanPreset('');
    setKonfirmFinal(false);
  };

  const simpan = async () => {
    if (statusBaru === 'DITOLAK' && !catatan.trim()) {
      setPesan({ tipe: 'galat', teks: alasanPreset === 'Lainnya' ? 'Tulis alasan penolakan' : 'Pilih alasan penolakan' });
      return;
    }
    // Status final mengunci datanya — konfirmasi kedua sebelum benar-benar kirim.
    if (STATUS_FINAL.includes(statusBaru) && !konfirmFinal) {
      setKonfirmFinal(true);
      return;
    }

    setKonfirmFinal(false);
    setMenyimpan(true);

    const j = await kirimJson(`/api/admin/permohonan/${sunting.id}`, { status: statusBaru, catatan }, 'PATCH');
    setMenyimpan(false);

    if (j.error?.length) {
      setPesan({ tipe: 'galat', teks: j.error[0] });
      return;
    }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Tersimpan' });
    setItems((p) => p.map((x) => (x.id === sunting.id ? { ...x, status: statusBaru, catatan } : x)));
    setDetail((p) => (p && p.id === sunting.id ? { ...p, status: statusBaru, catatan } : p));
    setSunting(null);
  };

  return (
    <LayoutDashboard judul="Permohonan">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      {detail || memuatDetail ? (
        <PanelDetail
          detail={detail}
          memuat={memuatDetail}
          onTutup={() => setDetail(null)}
          onProses={bukaSunting}
        />
      ) : (
        <Kartu>
          <div className="mb-4 flex items-center gap-2">
            <ClipboardList className="h-5 w-5 text-slate-700" />
            <h1 className="font-semibold text-slate-900">Daftar Permohonan</h1>
            {!memuat && (
              <span className="ml-auto rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">
                {total} data
              </span>
            )}
          </div>

          <div className="mb-4 flex flex-col gap-3 sm:flex-row">
            <div className="flex flex-wrap gap-1">
              {[['', 'Semua'], ...STATUS_URUT.map((k) => [k, STATUS_PERMOHONAN[k].label])].map(([nilai, label]) => (
                <button key={nilai} onClick={() => setStatus(nilai)}
                        className={`rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors ${
                          status === nilai ? 'border-transparent bg-brand text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-brand'
                        }`}>
                  {label}
                  {counts[nilai] !== undefined && (
                    <span className={`ml-1.5 rounded-full px-1.5 text-xs font-semibold ${
                      status === nilai ? 'bg-white/25' : 'bg-slate-100 text-slate-500'
                    }`}>{counts[nilai]}</span>
                  )}
                </button>
              ))}
            </div>

            <div className="relative flex-1">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <Input value={cari} onChange={(e) => setCari(e.target.value)}
                     placeholder="Cari no. register / nama / NIK / HP / jenis…" className="pl-9" />
            </div>
          </div>

          <div className="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center">
            <FilterPeriode periode={periode} acuan={acuan} onPeriode={setPeriode} onAcuan={setAcuan} nonaktif={memuat} />

            {daftarPetugas.length > 0 && (
              <div className="flex items-center gap-2 lg:ml-auto">
                <span className="text-xs font-medium text-slate-400">Petugas</span>
                {/* Radix menolak SelectItem bernilai "" — "semua" jadi penandanya. */}
                <Select value={petugas || 'semua'} onValueChange={(v) => setPetugas(v === 'semua' ? '' : v)}>
                  <SelectTrigger className="w-52">
                    <SelectValue placeholder="Semua petugas" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="semua">Semua petugas</SelectItem>
                    {daftarPetugas.map((p) => <SelectItem key={p.id} value={String(p.id)}>{p.nama}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
            )}
          </div>

          {memuat ? <Memuat /> : items.length === 0 ? <Kosong>Tidak ada permohonan.</Kosong> : (
            <>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-slate-200 text-left text-slate-500">
                      {['No. Register', 'Pemohon', 'Jenis', 'Dibuat', 'Perubahan Status', 'Status', 'Aksi'].map((h) => (
                        <th key={h} className="py-2 pr-4 font-medium">{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {items.map((it) => (
                      <tr key={it.id} id={`permohonan-${it.id}`}
                          className={`border-b border-slate-100 align-top transition-colors ${
                            sorotId === it.id ? 'bg-amber-50' : ''
                          }`}>
                        <td className="py-2.5 pr-4 font-mono text-xs">{it.noregister}</td>
                        <td className="py-2.5 pr-4">
                          <div>{it.pemohon}</div>
                          <div className="font-mono text-xs text-slate-400">{it.pemohonId}</div>
                        </td>
                        <td className="py-2.5 pr-4">
                          <div>{it.jenisNama}</div>
                          <div className="text-xs text-slate-400">{it.kategori} · {it.jumlahBerkas} berkas</div>
                        </td>
                        <td className="py-2.5 pr-4 text-xs text-slate-500">{tglSingkat(it.createdAt)}</td>
                        <td className="py-2.5 pr-4 text-xs">
                          {it.prosesAt ? (
                            <>
                              <div className="text-slate-600">{tglSingkat(it.prosesAt)}</div>
                              {it.prosesByName && <div className="text-slate-400">oleh {it.prosesByName}</div>}
                            </>
                          ) : <span className="text-slate-300">—</span>}
                        </td>
                        <td className="py-2.5 pr-4"><LencanaStatus status={it.status} /></td>
                        <td className="py-2.5 pr-4">
                          <div className="flex items-center gap-2">
                            <Tombol varian="garis" onClick={() => bukaDetail(it)} title="Lihat detail, berkas & proses">
                              <Eye className="h-3.5 w-3.5" />Detail
                            </Tombol>
                            {STATUS_FINAL.includes(it.status) && (
                              <span className="text-slate-300" title="Permohonan final — buka kunci lewat halaman Master">
                                <Lock className="h-3.5 w-3.5" />
                              </span>
                            )}
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <Paginasi page={page} totalHalaman={totalHalaman} total={total} limit={PER_HALAMAN}
                        onGanti={gantiHalaman} nonaktif={memuat} />
            </>
          )}
        </Kartu>
      )}

      {sunting && (
        <Modal judul="Proses Permohonan" sub={sunting.noregister} onTutup={() => setSunting(null)}>
          <div className="space-y-4">
            <div className="rounded-lg bg-slate-50 p-3 text-sm">
              <p><span className="text-slate-500">Pemohon:</span> {sunting.user?.userFullname ?? sunting.user?.userId ?? '-'}</p>
              <p><span className="text-slate-500">Jenis:</span> {sunting.jenis?.nama ?? '-'}</p>
            </div>

            <div>
              <label className="text-sm font-medium text-slate-700">Status</label>
              <div className="mt-1.5 grid grid-cols-2 gap-2">
                {STATUS_URUT.map((k) => (
                  <button key={k} type="button"
                          onClick={() => {
                            setStatusBaru(k);
                            if (k !== 'DITOLAK') setAlasanPreset('');
                          }}
                          className={`rounded-lg border px-3 py-2 text-sm font-medium transition-colors ${
                            statusBaru === k ? STATUS_PERMOHONAN[k].warna : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                          }`}>
                    {STATUS_PERMOHONAN[k].label}
                  </button>
                ))}
              </div>
            </div>

            {statusBaru === 'DITOLAK' ? (
              <div>
                <Label className="text-slate-700">Alasan Penolakan <span className="text-rose-600">*</span></Label>
                <Select
                  value={alasanPreset}
                  onValueChange={(v) => {
                    setAlasanPreset(v);
                    // Preset langsung jadi catatan; "Lainnya" dikosongkan untuk diketik.
                    setCatatan(v === 'Lainnya' ? '' : v);
                  }}
                >
                  <SelectTrigger className="mt-1.5 w-full">
                    <SelectValue placeholder="Pilih alasan penolakan…" />
                  </SelectTrigger>
                  <SelectContent>
                    {ALASAN_TOLAK.map((a) => <SelectItem key={a} value={a}>{a}</SelectItem>)}
                  </SelectContent>
                </Select>

                {alasanPreset === 'Lainnya' && (
                  <Textarea rows={3} value={catatan} onChange={(e) => setCatatan(e.target.value)} autoFocus
                            placeholder="Tulis alasan penolakan…" className="mt-2" />
                )}
                <p className="mt-1.5 text-xs text-slate-400">Alasan ini dikirim ke pemohon sebagai catatan.</p>
              </div>
            ) : (
              <div>
                <Label htmlFor="catatan" className="text-slate-700">Catatan Petugas</Label>
                <Textarea id="catatan" rows={3} value={catatan} onChange={(e) => setCatatan(e.target.value)}
                          placeholder="Catatan untuk pemohon (opsional)…" className="mt-1.5" />
              </div>
            )}

            <div className="flex justify-end gap-2 pt-2">
              <Tombol varian="garis" onClick={() => setSunting(null)}>Batal</Tombol>
              <Tombol onClick={simpan} disabled={menyimpan}>Simpan</Tombol>
            </div>
          </div>

          {konfirmFinal && (
            <div className="absolute inset-0 z-10 flex items-center justify-center rounded-2xl bg-slate-900/50 p-4"
                 onClick={() => setKonfirmFinal(false)}>
              <div className="w-full max-w-sm rounded-xl bg-white p-5 shadow-2xl" onClick={(e) => e.stopPropagation()}>
                <div className="mb-3 flex items-center gap-2 text-amber-600">
                  <AlertTriangle className="h-5 w-5" />
                  <h4 className="font-semibold text-slate-900">Jadikan {STATUS_PERMOHONAN[statusBaru]?.label}?</h4>
                </div>
                <p className="text-sm leading-relaxed text-slate-600">
                  Setelah status menjadi <b>{STATUS_PERMOHONAN[statusBaru]?.label}</b>, permohonan ini
                  <b> terkunci dan tidak dapat diubah lagi</b>. Membuka kunci hanya bisa lewat <b>halaman Master</b>.
                </p>
                <div className="mt-4 flex justify-end gap-2">
                  <Tombol varian="garis" onClick={() => setKonfirmFinal(false)}>Batal</Tombol>
                  <Tombol varian={statusBaru === 'DITOLAK' ? 'bahaya' : 'sukses'} onClick={simpan} disabled={menyimpan}>
                    Ya, saya mengerti
                  </Tombol>
                </div>
              </div>
            </div>
          )}
        </Modal>
      )}
    </LayoutDashboard>
  );
}

// ── Panel detail ───────────────────────────────────────────────────────────

function PanelDetail({ detail, memuat, onTutup, onProses }) {
  const [lihat, setLihat] = useState(null);

  if (memuat || !detail) {
    return (
      <Kartu>
        <Tombol varian="garis" onClick={onTutup}><ArrowLeft className="h-4 w-4" />Kembali ke tabel</Tombol>
        <Memuat kelas="py-16" />
      </Kartu>
    );
  }

  const final = STATUS_FINAL.includes(detail.status);

  return (
    <Kartu>
      <div className="mb-4 flex items-center justify-between gap-2">
        <Tombol varian="garis" onClick={onTutup}><ArrowLeft className="h-4 w-4" />Kembali ke tabel</Tombol>
        <LencanaStatus status={detail.status} />
      </div>

      <div className="space-y-4">
        <div className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
          <h3 className="font-semibold text-slate-900">{detail.jenis?.nama ?? 'Permohonan'}</h3>
          <p className="mt-0.5 text-xs text-slate-500">
            <span className="font-mono font-semibold">{detail.noregister}</span> · {detail.jenis?.kategori ?? '-'} ·
            diajukan {tglJam(detail.createdAt)}
          </p>
        </div>

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
                <button key={i} onClick={() => setLihat(b)} className="group text-left">
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

        {(detail.catatan || detail.prosesByName) && (
          <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
            {detail.catatan && (
              <>
                <h4 className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Catatan Petugas</h4>
                <p className="whitespace-pre-line text-sm text-slate-700">{detail.catatan}</p>
              </>
            )}
            {detail.prosesByName && (
              <p className={`flex items-center gap-1.5 text-xs text-slate-500 ${detail.catatan ? 'mt-2 border-t border-slate-200 pt-2' : ''}`}>
                <User className="h-3.5 w-3.5 text-slate-400" />
                Diproses oleh <b className="text-slate-700">{detail.prosesByName}</b>
                {detail.prosesAt && <> · {tglJam(detail.prosesAt)}</>}
              </p>
            )}
          </div>
        )}

        <div className="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-4">
          {final ? (
            <span className="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-500"
                  title="Permohonan final — buka kunci lewat halaman Master">
              <Lock className="h-3.5 w-3.5" />Permohonan final &amp; terkunci
            </span>
          ) : (
            <Tombol onClick={() => onProses(detail)}>Proses Permohonan</Tombol>
          )}
        </div>
      </div>

      {/* Penampil berkas layar penuh */}
      {lihat && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 p-4" onClick={() => setLihat(null)}>
          <div className="max-h-full max-w-4xl overflow-auto" onClick={(e) => e.stopPropagation()}>
            <img src={lihat.path} alt={lihat.label} className="max-h-[85dvh] rounded-lg" />
            <p className="mt-2 text-center text-sm text-white/80">{lihat.label}</p>
          </div>
        </div>
      )}
    </Kartu>
  );
}
