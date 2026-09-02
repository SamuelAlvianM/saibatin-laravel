import { useCallback, useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { ClipboardList, Eye, Lock, Search } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import {
  FilterBanyak, FilterPeriode, Kartu, Kosong, LencanaStatus, Memuat, Paginasi, Pesan,
  STATUS_FINAL, STATUS_PERMOHONAN, Tombol, tglSingkat, tulisAcuan, useTunda,
} from '@/Components/Dasbor';
import { ambilJson } from '@/lib/api';
import { kelasSorot, useSorot } from '@/lib/sorot';
import { Input } from '@/Components/ui/input';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';

const STATUS_URUT = ['MENUNGGU', 'DIPROSES', 'SELESAI', 'DITOLAK'];
const PER_HALAMAN = 20;

/**
 * Daftar & pemrosesan permohonan — port `app/dashboard/permohonan/AdminPermohonan.tsx`.
 *
 * Alur yang sengaja dipertahankan: petugas TIDAK bisa mengubah status dari
 * baris tabel. Tombolnya hanya ada di HALAMAN DETAIL
 * (`Dashboard/PermohonanDetail`), setelah data dan berkasnya terbaca. Menaruh
 * tombol "Selesai" di tabel membuat permohonan bisa difinalkan tanpa seorang
 * pun membuka lampirannya.
 */
export default function Permohonan({ sorot }) {
  /*
   * Halaman ini dipakai DUA peran: petugas (melihat & memproses seluruh
   * permohonan) dan Operator OPD (hanya miliknya, tanpa tombol proses).
   *
   * ⚠️ Ini murni soal TAMPILAN. Yang benar-benar mempersempit datanya adalah
   * `PermohonanAdminController`, dan `PATCH` tetap `peran:petugas` di berkas
   * rute. Jangan pernah memakai nilai ini sebagai pagar akses.
   */
  const level = usePage().props.auth?.user?.level;
  const bisaProses = level === 1 || level === 2;

  const [items, setItems] = useState([]);
  const [counts, setCounts] = useState({});
  const [daftarPetugas, setDaftarPetugas] = useState([]);
  const [daftarJenis, setDaftarJenis] = useState([]);
  const [daftarWilayah, setDaftarWilayah] = useState([]);
  const [total, setTotal] = useState(0);
  const [totalHalaman, setTotalHalaman] = useState(1);
  const [page, setPage] = useState(1);
  const [memuat, setMemuat] = useState(true);

  const [status, setStatus] = useState('');
  const [petugas, setPetugas] = useState('');
  const [jenis, setJenis] = useState([]);
  const [wilayah, setWilayah] = useState([]);
  const [periode, setPeriode] = useState('');
  const [acuan, setAcuan] = useState(() => new Date());
  const [cari, setCari] = useState('');
  const cariTertunda = useTunda(cari);

  const [pesan, setPesan] = useState(null);

  // Token anti-balapan: balasan filter LAMA yang datang belakangan harus
  // dibuang, kalau tidak tabel menampilkan hasil filter yang sudah diganti.
  const permintaan = useRef(0);
  // Datang dari notifikasi (?sorot=<id>): server yang menghitung halamannya.
  const sorotAwal = useRef(sorot ? Number(sorot) : null);

  const muat = useCallback(async (halaman) => {
    const milik = ++permintaan.current;
    setMemuat(true);

    const q = new URLSearchParams({ limit: String(PER_HALAMAN), page: String(halaman) });
    if (status) q.set('status', status);
    if (cariTertunda.trim()) q.set('q', cariTertunda.trim());
    if (petugas) q.set('petugas', petugas);
    if (jenis.length) q.set('jenis', jenis.join(','));
    if (wilayah.length) q.set('wilayah', wilayah.join(','));
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
    if (j.data.daftarJenis) setDaftarJenis(j.data.daftarJenis);
    if (j.data.daftarWilayah) setDaftarWilayah(j.data.daftarWilayah);
  }, [status, cariTertunda, petugas, jenis, wilayah, periode, acuan]);

  // Ganti filter → selalu kembali ke halaman 1; kalau tidak, petugas bisa
  // terdampar di halaman yang pada filter baru sudah tidak ada isinya.
  useEffect(() => {
    setPage(1);
    muat(1);
  }, [muat]);

  // Sorotan cukup sebagai penunjuk arah — hilang sendiri setelah terlihat.
  const sorotId = useSorot(sorot, 'permohonan', !memuat, items);

  const gantiHalaman = (p) => {
    setPage(p);
    muat(p);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  /*
   * Detail dibuka sebagai HALAMAN, bukan panel di dalam daftar ini.
   *
   * ⚠️ `router.visit`, bukan `window.location` — kunjungan Inertia menjaga
   * sesi komponen dan tombol Kembali peramban tetap mengembalikan petugas ke
   * daftar beserta filter yang sedang dipakainya.
   */
  const bukaDetail = (it) => router.visit(`/dashboard/permohonan/${it.id}`);

  return (
    <LayoutDashboard judul="Permohonan">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <Kartu>
          <div className="mb-4 flex items-center gap-2">
            <ClipboardList className="h-5 w-5 text-slate-700" />
            <h1 className="font-semibold text-slate-900">
              {bisaProses ? 'Daftar Permohonan' : 'Permohonan Saya'}
            </h1>
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

            {/* Bersebelahan dengan saringan periode, dan BOLEH LEBIH DARI SATU —
                petugas kerap membandingkan beberapa layanan sekaligus
                (mis. semua turunan Kartu Keluarga). Dipakai kedua peran:
                Operator OPD pun perlu memilah pengajuannya sendiri. */}
            <FilterBanyak
              nilai={jenis}
              onUbah={setJenis}
              pilihan={daftarJenis}
              label="Jenis permohonan"
              labelSemua="Semua jenis"
              nonaktif={memuat}
            />

            {/* Saringan wilayah tak berarti bagi Operator OPD: seluruh baris
                yang ia lihat memang permohonannya sendiri, dari satu wilayah. */}
            {bisaProses && (
              <FilterBanyak
                nilai={wilayah}
                onUbah={setWilayah}
                pilihan={daftarWilayah}
                label="Wilayah (kecamatan)"
                labelSemua="Semua wilayah"
                nonaktif={memuat}
              />
            )}

            {/* Saringan "petugas mana yang memproses" tak berarti bagi pengaju:
                permohonannya ditangani siapa pun yang kebetulan bertugas. */}
            {bisaProses && daftarPetugas.length > 0 && (
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
                      {/* Kolom Wilayah hanya untuk petugas — bagi OPD seluruh
                          barisnya memang dari wilayahnya sendiri, jadi kolom itu
                          cuma mengulang hal yang sama 20 kali. */}
                      {['No. Register', 'Pemohon', ...(bisaProses ? ['Wilayah'] : []), 'Jenis', 'Dibuat', 'Perubahan Status', 'Status', 'Aksi'].map((h) => (
                        <th key={h} className="py-2 pr-4 font-medium">{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {items.map((it) => (
                      <tr key={it.id} id={`permohonan-${it.id}`}
                          className={`border-b border-slate-100 align-top transition-colors ${
                            kelasSorot(sorotId === it.id)
                          }`}>
                        <td className="py-2.5 pr-4 font-mono text-xs">{it.noregister}</td>
                        <td className="py-2.5 pr-4">
                          <div>{it.pemohon}</div>
                          <div className="font-mono text-xs text-slate-400">{it.pemohonId}</div>
                        </td>
                        {bisaProses && (
                          <td className="py-2.5 pr-4 text-xs">
                            {it.wilayah?.desa || it.wilayah?.kecamatan ? (
                              <>
                                <div className="text-slate-600">{it.wilayah.desa ?? it.wilayah.kecamatan}</div>
                                {it.wilayah.desa && it.wilayah.kecamatan && (
                                  <div className="text-slate-400">Kec. {it.wilayah.kecamatan}</div>
                                )}
                              </>
                            ) : <span className="text-slate-300">—</span>}
                          </td>
                        )}
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
    </LayoutDashboard>
  );
}
