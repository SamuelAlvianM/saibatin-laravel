import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { CheckCircle2, ClipboardList, FilePlus2, Loader2 } from 'lucide-react';
import LayoutPengguna from '@/Components/LayoutPengguna';
import { ambilJson } from '@/lib/api';

const WARNA = {
  MENUNGGU: 'bg-amber-50 text-amber-700 ring-amber-200',
  DIPROSES: 'bg-sky-50 text-sky-700 ring-sky-200',
  SELESAI: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  DITOLAK: 'bg-rose-50 text-rose-700 ring-rose-200',
};

const TAB = ['semua', 'MENUNGGU', 'DIPROSES', 'SELESAI', 'DITOLAK'];

/**
 * Riwayat permohonan warga — paginasi **cursor** (load-on-scroll), sama seperti
 * endpointnya. Bukan halaman bernomor: daftar ini bertambah dari atas, dan
 * halaman bernomor akan menggeser isi tiap kali ada permohonan baru masuk.
 */
export default function Riwayat({ baru }) {
  const [tab, setTab] = useState('semua');
  const [items, setItems] = useState([]);
  const [counts, setCounts] = useState(null);
  const [cursor, setCursor] = useState(null);
  const [memuat, setMemuat] = useState(true);
  const [habis, setHabis] = useState(false);

  const muat = async (status, kursor = null) => {
    setMemuat(true);
    const q = new URLSearchParams({ status, limit: '12' });
    if (kursor) q.set('cursor', kursor);

    const j = await ambilJson(`/api/permohonan?${q}`);
    setMemuat(false);

    if (j.error?.length) return;

    setItems((p) => (kursor ? [...p, ...j.data.items] : j.data.items));
    setCursor(j.data.nextCursor);
    setHabis(!j.data.nextCursor);
    if (j.data.counts) setCounts(j.data.counts);
  };

  useEffect(() => { muat(tab); }, [tab]); // eslint-disable-line

  return (
    <LayoutPengguna judul="Pengajuan Saya">
      <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-xl font-bold text-slate-900">Pengajuan Saya</h1>
          <p className="mt-1 text-sm text-slate-500">Pantau status permohonan yang Anda ajukan.</p>
        </div>
        <Link href="/user/pengajuan/baru"
              className="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-dark">
          <FilePlus2 className="h-4 w-4" />Ajukan Baru
        </Link>
      </div>

      {baru && (
        <div className="mb-4 flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
          <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />
          <span>Permohonan terkirim dengan No. Registrasi <strong className="font-mono">{baru}</strong>. Menunggu diproses petugas.</span>
        </div>
      )}

      <div className="mb-4 flex flex-wrap gap-2">
        {TAB.map((t) => (
          <button key={t} onClick={() => { setTab(t); setItems([]); setCursor(null); }}
                  className={`rounded-full px-3 py-1.5 text-sm font-medium transition-colors ${
                    tab === t ? 'bg-brand text-white' : 'border border-slate-300 bg-white text-slate-600 hover:border-brand hover:text-brand'
                  }`}>
            {t === 'semua' ? 'Semua' : t.charAt(0) + t.slice(1).toLowerCase()}
            {counts && <span className="ml-1.5 opacity-70">{counts[t] ?? 0}</span>}
          </button>
        ))}
      </div>

      {items.length === 0 && !memuat ? (
        <div className="rounded-xl border border-slate-200 bg-white p-10 text-center">
          <ClipboardList className="mx-auto mb-3 h-8 w-8 text-slate-300" />
          <p className="text-sm text-slate-500">Belum ada permohonan pada tab ini.</p>
        </div>
      ) : (
        <div className="space-y-2">
          {items.map((p) => (
            <div key={p.id} className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4">
              <div className="min-w-0">
                <p className="truncate text-sm font-semibold text-slate-800">{p.jenisNama}</p>
                <p className="mt-0.5 font-mono text-xs text-slate-500">{p.noregister}</p>
              </div>
              <div className="flex items-center gap-3">
                <span className="text-xs text-slate-400">
                  {new Date(p.createdAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                </span>
                <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ${WARNA[p.status] ?? WARNA.MENUNGGU}`}>
                  {p.status}
                </span>
              </div>
            </div>
          ))}
        </div>
      )}

      {memuat && (
        <p className="flex items-center justify-center gap-2 py-6 text-sm text-slate-500">
          <Loader2 className="h-4 w-4 animate-spin" />Memuat…
        </p>
      )}

      {!memuat && !habis && cursor && (
        <div className="mt-4 flex justify-center">
          <button onClick={() => muat(tab, cursor)}
                  className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:border-brand hover:text-brand">
            Muat lebih banyak
          </button>
        </div>
      )}
    </LayoutPengguna>
  );
}
