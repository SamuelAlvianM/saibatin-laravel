import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { CheckCircle2, ClipboardList, Download, FilePlus2, Loader2 } from 'lucide-react';
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
 * Header biru — port `app/user/pengajuan/page.tsx` portal asli.
 *
 * Hanya SATU tombol di sini ("Ajukan Permohonan"). "Pengaturan Akun" sengaja
 * tidak ikut: ia sudah ada di dropdown akun navbar, dan menduplikasinya cuma
 * menambah tombol yang harus dipindai mata tanpa menambah jalan baru.
 * Dideklarasikan di tingkat modul, bukan di dalam `Riwayat` — komponen yang
 * lahir di dalam komponen lain dipasang ulang tiap render (HANDOFF §5 no. 17).
 */
function Hero({ nama }) {
  return (
    <div className="relative overflow-hidden py-12"
         style={{ background: 'linear-gradient(135deg, #1b4b72 0%, #2176bd 100%)' }}>
      <div className="absolute inset-0 opacity-10"
           style={{ backgroundImage: 'radial-gradient(circle at 20% 50%, white 1px, transparent 1px)', backgroundSize: '40px 40px' }} />
      <div className="container relative z-10 mx-auto max-w-4xl px-4 md:px-8">
        <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
          <div className="flex items-center gap-4">
            <div className="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/20 bg-white/15 backdrop-blur-sm">
              <ClipboardList className="h-7 w-7 text-white" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-white md:text-3xl">Pengajuan Saya</h1>
              <p className="mt-0.5 text-sm text-white/80">
                Halo, {nama} — pantau semua permohonan Anda di sini.
              </p>
            </div>
          </div>
          <Link href="/user/pengajuan/baru"
                className="inline-flex shrink-0 items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-[#1b4b72] shadow-lg transition-colors hover:bg-slate-100">
            <FilePlus2 className="h-4 w-4" />Ajukan Permohonan
          </Link>
        </div>
      </div>
    </div>
  );
}

/**
 * Riwayat permohonan warga — paginasi **cursor** (load-on-scroll), sama seperti
 * endpointnya. Bukan halaman bernomor: daftar ini bertambah dari atas, dan
 * halaman bernomor akan menggeser isi tiap kali ada permohonan baru masuk.
 */
export default function Riwayat({ baru }) {
  const { auth } = usePage().props;
  const nama = auth.user?.nama || auth.user?.user_id || 'Warga';
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
    <LayoutPengguna judul="Pengajuan Saya" hero={<Hero nama={nama} />}>
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
            <div key={p.id} className="rounded-xl border border-slate-200 bg-white p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
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
                {/* 🔴 <a> biasa, BUKAN router.visit: balasannya berkas PDF,
                    bukan respons Inertia — kunjungan Inertia akan tersedak
                    lalu diam saja. `download` membuat peramban menyimpannya
                    dengan nama dari header, bukan membuka tab kosong. */}
                <a href={`/api/permohonan/${p.id}/pdf`} download
                   title={`Unduh tanda terima ${p.noregister}`}
                   className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:border-brand hover:text-brand">
                  <Download className="h-3.5 w-3.5" />
                  <span className="hidden sm:inline">PDF</span>
                </a>
              </div>
            </div>

            {p.status === 'DITOLAK' && <AlasanDitolak tolak={p.tolak} />}
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

/**
 * Alasan penolakan yang dibaca PEMOHON.
 *
 * 🔴 Sampai 2 Sep 2026 bagian ini tidak ada. Permohonan yang ditolak hanya
 * memunculkan lencana "DITOLAK", dan penjelasannya cuma dikirim lewat surel
 * dan WhatsApp — dua kanal yang bisa terlewat. Warga sungguhan melaporkan
 * ditolak berulang kali "karena data tidak lengkap" tanpa pernah tahu data
 * mana yang dimaksud, karena portalnya sendiri tidak pernah mengatakannya.
 *
 * Diletakkan LANGSUNG di kartunya, bukan di balik tombol atau halaman lain:
 * pemohon yang membuka halaman ini setelah ditolak sedang mencari tepat satu
 * hal, dan menyembunyikannya satu klik lebih jauh mengulang masalah yang sama.
 *
 * Bahasanya formal dan ringkas — ini layanan pemerintah, dan kalimat ini yang
 * jadi dasar pemohon memperbaiki berkasnya.
 */
function AlasanDitolak({ tolak }) {
  const alasan = tolak?.alasan ?? '';
  const rincian = tolak?.rincian ?? [];
  const keterangan = tolak?.keterangan ?? '';

  // Penolakan lama tidak punya struktur ini. Yang tidak punya isi sama sekali
  // tetap diberi kalimat yang mengarahkan, bukan dibiarkan kosong.
  if (! alasan && rincian.length === 0 && ! keterangan) {
    return (
      <p className="mt-3 rounded-lg border border-rose-200 bg-rose-50/70 px-3 py-2 text-sm text-rose-800">
        Alasan penolakan tidak tercatat. Silakan hubungi petugas Disdukcapil untuk keterangan lebih lanjut.
      </p>
    );
  }

  return (
    <div className="mt-3 rounded-lg border border-rose-200 bg-rose-50/70 p-3">
      {alasan && <p className="text-sm font-semibold text-rose-900">{alasan}</p>}

      {rincian.length > 0 && (
        <div className="mt-2">
          <p className="text-xs font-semibold uppercase tracking-wide text-rose-700/70">
            Data yang perlu dilengkapi
          </p>
          <ul className="mt-1 grid grid-cols-1 gap-x-4 gap-y-0.5 sm:grid-cols-2">
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
        <p className="mt-2 whitespace-pre-line border-t border-rose-200 pt-2 text-sm text-rose-900">
          {keterangan}
        </p>
      )}
    </div>
  );
}
