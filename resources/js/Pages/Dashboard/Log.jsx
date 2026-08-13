import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Eye, Filter, ScrollText, Search, User as UserIcon } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import {
  FilterPeriode, Kartu, Kosong, Memuat, Paginasi, Pesan, tglJam, tulisAcuan, useTunda,
} from '@/Components/Dasbor';
import { ambilJson } from '@/lib/api';
import { Input } from '@/Components/ui/input';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';

/**
 * Log aktivitas petugas — port `app/dashboard/log/LogAktivitasClient.tsx`.
 *
 * 🔴 Khusus Super Admin. Ini catatan pengawasan, bukan riwayat kerja bersama;
 * operator tidak melihat jejak rekannya. Gerbangnya di rute (`peran:1`) dan
 * diulang di endpointnya.
 */

const PER_HALAMAN = 25;

const WARNA_AKSI = {
  BUAT: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
  UBAH: 'bg-sky-50 text-sky-700 ring-sky-100',
  HAPUS: 'bg-rose-50 text-rose-700 ring-rose-100',
  UNGGAH: 'bg-violet-50 text-violet-700 ring-violet-100',
  IMPOR: 'bg-indigo-50 text-indigo-700 ring-indigo-100',
  LAINNYA: 'bg-slate-100 text-slate-600 ring-slate-200',
};

/**
 * Tautan ke data yang dicatat sebuah baris log.
 *
 * `?sorot=<id>` membuat halaman tujuan melompat ke halaman yang memuat data itu
 * lalu menyorot barisnya — admin tidak perlu mencarinya manual di antara ribuan
 * baris. Entitas yang halamannya belum dibangun (Fase 6) sengaja tidak
 * ditautkan: tautan ke 404 lebih membingungkan daripada tidak ada tautan.
 */
function tautanData(it) {
  if (!it.entitasId) return null;

  const id = encodeURIComponent(it.entitasId);

  return {
    Permohonan: `/dashboard/permohonan?sorot=${id}`,
    Akun: '/dashboard/users',
    Pengaduan: '/dashboard/pengaduan',
  }[it.entitas] ?? null;
}

export default function Log() {
  const [items, setItems] = useState([]);
  const [petugas, setPetugas] = useState([]);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [total, setTotal] = useState(0);
  const [userId, setUserId] = useState('');
  const [cari, setCari] = useState('');
  const cariTertunda = useTunda(cari, 300);
  const [periode, setPeriode] = useState('');
  const [acuan, setAcuan] = useState(() => new Date());
  const [memuat, setMemuat] = useState(true);
  const [pesan, setPesan] = useState(null);

  // Ganti filter → kembali ke halaman 1 supaya tidak terdampar di halaman kosong.
  useEffect(() => { setPage(1); }, [userId, cariTertunda, periode, acuan]);

  useEffect(() => {
    let batal = false;
    setMemuat(true);

    const q = new URLSearchParams({ page: String(page) });
    if (userId) q.set('userId', userId);
    if (cariTertunda.trim()) q.set('q', cariTertunda.trim());
    if (periode) {
      q.set('periode', periode);
      q.set('acuan', tulisAcuan(acuan));
    }

    ambilJson(`/api/admin/log-aktivitas?${q}`).then((j) => {
      if (batal) return;
      setMemuat(false);

      if (j.error?.length) {
        setPesan({ tipe: 'galat', teks: j.error[0] });
        return;
      }

      setItems(j.data.items ?? []);
      setTotal(j.data.total ?? 0);
      setTotalPages(j.data.totalPages ?? 1);
      // Daftar petugas tidak berubah karena filter — cukup diisi sekali.
      if (j.data.petugas?.length) setPetugas(j.data.petugas);
    });

    return () => { batal = true; };
  }, [page, userId, cariTertunda, periode, acuan]);

  const adaFilter = userId || cariTertunda.trim() || periode;

  return (
    <LayoutDashboard judul="Log Aktivitas">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <div className="mb-4">
        <h1 className="text-2xl font-semibold text-slate-900">Log Aktivitas</h1>
        <p className="text-sm text-slate-500">
          Jejak tindakan petugas: siapa mengubah apa dan kapan. Hanya Super Admin yang dapat membukanya.
        </p>
      </div>

      <Kartu>
        <div className="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center">
          <div className="flex items-center gap-2">
            <ScrollText className="h-5 w-5 text-slate-700" />
            <span className="text-sm font-semibold text-slate-900">{total} catatan</span>
            {adaFilter && (
              <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[0.68rem] text-slate-500">
                <Filter className="h-3 w-3" />tersaring
              </span>
            )}
          </div>

          <div className="relative flex-1">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <Input value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Cari aktivitas (mis. status, berita, akun)…"
                   className="pl-9" />
          </div>

          {/* Radix menolak SelectItem bernilai "" — "semua" dipakai sebagai
              penanda, lalu diterjemahkan kembali ke "" untuk kueri. */}
          <Select value={userId || 'semua'} onValueChange={(v) => setUserId(v === 'semua' ? '' : v)}>
            <SelectTrigger className="w-full sm:w-52">
              <SelectValue placeholder="Semua petugas" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="semua">Semua petugas</SelectItem>
              {petugas.map((p) => <SelectItem key={p.id} value={String(p.id)}>{p.nama}</SelectItem>)}
            </SelectContent>
          </Select>
        </div>

        <div className="mb-4">
          <FilterPeriode periode={periode} acuan={acuan} onPeriode={setPeriode} onAcuan={setAcuan} nonaktif={memuat} />
        </div>

        {memuat ? <Memuat /> : items.length === 0 ? <Kosong>Belum ada aktivitas tercatat.</Kosong> : (
          <>
            <ul className="divide-y divide-slate-100">
              {items.map((it) => {
                const tautan = tautanData(it);

                return (
                  <li key={it.id} className="flex items-start gap-3 py-3">
                    <span className={`mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-wide ring-1 ${
                      WARNA_AKSI[it.aksi] ?? WARNA_AKSI.LAINNYA
                    }`}>{it.aksi}</span>

                    <div className="min-w-0 flex-1">
                      <p className="text-sm text-slate-700">{it.ringkasan}</p>
                      <p className="mt-0.5 flex flex-wrap items-center gap-x-2 text-[0.68rem] text-slate-400">
                        <span className="inline-flex items-center gap-1">
                          <UserIcon className="h-3 w-3" />
                          {it.user?.userFullname || it.user?.userId || `#${it.userId}`}
                        </span>
                        <span>· {tglJam(it.createdAt)}</span>
                        <span>· {it.entitas}</span>
                        {it.ipAddress && <span>· {it.ipAddress}</span>}
                      </p>
                    </div>

                    {tautan && (
                      <Link href={tautan} title="Lihat data yang diubah"
                            className="mt-0.5 inline-flex shrink-0 items-center gap-1 text-xs font-medium text-brand hover:underline">
                        <Eye className="h-3.5 w-3.5" />Lihat
                      </Link>
                    )}
                  </li>
                );
              })}
            </ul>

            <Paginasi page={page} totalHalaman={totalPages} total={total} limit={PER_HALAMAN}
                      onGanti={setPage} nonaktif={memuat} />
          </>
        )}
      </Kartu>
    </LayoutDashboard>
  );
}
