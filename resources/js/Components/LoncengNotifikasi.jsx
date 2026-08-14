import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
  Bell, BellOff, CheckCheck, ClipboardCheck, ClipboardList, FilePlus2,
  MessageSquareReply, MessageSquareWarning, MessagesSquare, UserCheck,
  UserPlus, Volume2, VolumeX,
} from 'lucide-react';
import { ambilJson, kirimJson } from '@/lib/api';
import { cn } from '@/lib/utils';

/**
 * Lonceng notifikasi in-app — port `components/shared/notification-bell.tsx`.
 *
 * 🔴 Sempat TIDAK ADA di port ini: `/api/notifikasi` sudah dibuat sejak Fase 3
 * dan seluruh alur backend rajin membuat notifikasi (permohonan, pengaduan,
 * kritik, akun), tapi tak satu pun bisa dilihat karena tidak ada yang
 * memanggilnya. Petugas kehilangan satu-satunya pemberitahuan permohonan masuk.
 *
 * `nada`: 'gelap' (ikon putih, di header biru) atau 'terang' (ikon abu).
 */

const KUNCI_SUARA = 'saibatin-notif-sound'; // '1' = nyala (bawaan), '0' = bisu
const JEDA_POLL = 25000;

const TIPE_IKON = {
  PERMOHONAN_STATUS: { ikon: ClipboardCheck, warna: 'text-brand bg-brand/10' },
  PERMOHONAN_BARU: { ikon: FilePlus2, warna: 'text-emerald-600 bg-emerald-50' },
  PENGADUAN_BARU: { ikon: MessageSquareWarning, warna: 'text-amber-600 bg-amber-50' },
  PENGADUAN_BALASAN: { ikon: MessageSquareReply, warna: 'text-amber-600 bg-amber-50' },
  KRITIK_BARU: { ikon: MessagesSquare, warna: 'text-violet-600 bg-violet-50' },
  SKM_BARU: { ikon: ClipboardList, warna: 'text-sky-600 bg-sky-50' },
  AKUN_BARU: { ikon: UserPlus, warna: 'text-rose-600 bg-rose-50' },
  AKUN_STATUS: { ikon: UserCheck, warna: 'text-emerald-600 bg-emerald-50' },
};

function waktuRelatif(iso) {
  const diff = Date.now() - new Date(iso).getTime();
  const menit = Math.floor(diff / 60000);
  if (menit < 1) return 'Baru saja';
  if (menit < 60) return `${menit} menit lalu`;
  const jam = Math.floor(menit / 60);
  if (jam < 24) return `${jam} jam lalu`;
  const hari = Math.floor(jam / 24);
  if (hari < 7) return `${hari} hari lalu`;
  return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

/**
 * 🔴 AudioContext BERSAMA, dibuat sekali dan dibuka pada interaksi pengguna
 * pertama. Kebijakan autoplay peramban membuat konteks yang lahir TANPA
 * gerakan pengguna berstatus "suspended" — oscillator jalan, suara tidak
 * keluar. Itu sebab lama bunyi notifikasi tak pernah terdengar di portal:
 * konteksnya dibuat saat polling, bukan saat diklik.
 */
let konteksAudio = null;

function ambilKonteks() {
  if (typeof window === 'undefined') return null;
  try {
    if (!konteksAudio) {
      const Ctx = window.AudioContext || window.webkitAudioContext;
      konteksAudio = new Ctx();
    }
    return konteksAudio;
  } catch {
    return null;
  }
}

function bukaAudio() {
  const ctx = ambilKonteks();
  if (ctx && ctx.state === 'suspended') ctx.resume().catch(() => {});
}

/** "Ding" dua nada lewat Web Audio API — tanpa berkas aset. */
function bunyikan() {
  const ctx = ambilKonteks();
  if (!ctx) return;

  const jadwalkan = () => {
    try {
      const kini = ctx.currentTime;
      for (const n of [{ f: 880, t: 0 }, { f: 1174.7, t: 0.12 }]) {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.value = n.f;
        gain.gain.setValueAtTime(0.0001, kini + n.t);
        gain.gain.exponentialRampToValueAtTime(0.18, kini + n.t + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, kini + n.t + 0.35);
        osc.connect(gain).connect(ctx.destination);
        osc.start(kini + n.t);
        osc.stop(kini + n.t + 0.36);
      }
    } catch { /* abaikan */ }
  };

  if (ctx.state === 'suspended') {
    ctx.resume().then(() => { if (ctx.state === 'running') jadwalkan(); }).catch(() => {});
  } else {
    jadwalkan();
  }
}

export default function LoncengNotifikasi({ nada = 'gelap', sisi = 'kanan' }) {
  const [buka, setBuka] = useState(false);
  const [items, setItems] = useState([]);
  const [belum, setBelum] = useState(0);
  const [memuat, setMemuat] = useState(true);
  const [bisu, setBisu] = useState(false);
  const ref = useRef(null);
  const idTerakhir = useRef(0);
  const sudahMuat = useRef(false);
  const bisuRef = useRef(false);

  useEffect(() => { setBisu(localStorage.getItem(KUNCI_SUARA) === '0'); }, []);
  useEffect(() => { bisuRef.current = bisu; }, [bisu]);

  useEffect(() => {
    window.addEventListener('pointerdown', bukaAudio);
    window.addEventListener('keydown', bukaAudio);
    return () => {
      window.removeEventListener('pointerdown', bukaAudio);
      window.removeEventListener('keydown', bukaAudio);
    };
  }, []);

  const muat = useCallback(async () => {
    const j = await ambilJson('/api/notifikasi?limit=20');
    setMemuat(false);

    const d = j?.data;
    if (!d?.items) return;

    setItems(d.items);
    setBelum(d.unread ?? 0);

    // Notifikasi baru → bunyikan; kecuali pemuatan pertama atau sedang bisu.
    const idMaks = d.items.reduce((m, n) => Math.max(m, n.id), 0);
    if (sudahMuat.current) {
      const adaBaru = d.items.some((n) => n.id > idTerakhir.current && !n.dibaca);
      if (adaBaru && !bisuRef.current) bunyikan();
    }
    if (idMaks > idTerakhir.current) idTerakhir.current = idMaks;
    sudahMuat.current = true;
  }, []);

  useEffect(() => {
    muat();
    const iv = setInterval(muat, JEDA_POLL);
    const onFokus = () => muat();
    window.addEventListener('focus', onFokus);
    return () => {
      clearInterval(iv);
      window.removeEventListener('focus', onFokus);
    };
  }, [muat]);

  // Tutup saat klik di luar.
  useEffect(() => {
    const onKlik = (e) => {
      if (ref.current && !ref.current.contains(e.target)) setBuka(false);
    };
    if (buka) document.addEventListener('mousedown', onKlik);
    return () => document.removeEventListener('mousedown', onKlik);
  }, [buka]);

  const tukarBisu = () => {
    const berikut = !bisuRef.current;
    setBisu(berikut);
    localStorage.setItem(KUNCI_SUARA, berikut ? '0' : '1');
    // Menyalakan bunyi = klik pengguna, jadi konteks audio pasti boleh aktif.
    if (!berikut) bunyikan();
  };

  const tandaiSemua = async () => {
    setBelum(0);
    setItems((p) => p.map((n) => ({ ...n, dibaca: true })));
    await kirimJson('/api/notifikasi', undefined, 'PATCH');
  };

  const bukaNotif = (n) => {
    setBuka(false);
    if (!n.dibaca) {
      setBelum((u) => Math.max(0, u - 1));
      setItems((p) => p.map((x) => (x.id === n.id ? { ...x, dibaca: true } : x)));
      kirimJson(`/api/notifikasi/${n.id}`, undefined, 'PATCH');
    }
    if (!n.link) return;

    // Bawa id data yang dirujuk supaya halaman tujuan bisa langsung membuka
    // halaman yang memuatnya lalu menyorot barisnya — tanpa ini petugas
    // mendarat di halaman 1 dan harus mencari sendiri.
    router.visit(n.refId && !n.link.includes('?') ? `${n.link}?sorot=${n.refId}` : n.link);
  };

  const warnaIkon = nada === 'gelap' ? 'text-white' : 'text-slate-600';
  const hover = nada === 'gelap' ? 'hover:bg-white/15' : 'hover:bg-slate-100';

  return (
    <div className="relative" ref={ref}>
      <button type="button" onClick={() => setBuka((o) => !o)} aria-expanded={buka}
              aria-label={`Notifikasi${belum > 0 ? ` (${belum} belum dibaca)` : ''}`}
              className={cn(
                'relative flex h-9 w-9 items-center justify-center rounded-full transition-colors',
                warnaIkon, hover,
                buka && (nada === 'gelap' ? 'bg-white/15' : 'bg-slate-100'),
              )}>
        <Bell className="h-5 w-5" strokeWidth={2} />
        {belum > 0 && (
          <span className="absolute -right-0.5 -top-0.5 flex min-w-[1.125rem] items-center justify-center rounded-full bg-red-500 px-1 text-[0.6rem] font-bold leading-4 text-white ring-2 ring-white">
            {belum > 99 ? '99+' : belum}
          </span>
        )}
      </button>

      {buka && (
        <div role="dialog" aria-label="Daftar notifikasi"
             className={cn(
               'absolute top-full z-[60] mt-2 w-80 max-w-[calc(100vw-1.5rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl',
               sisi === 'kanan' ? 'right-0' : 'left-0',
             )}>
          <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p className="text-sm font-semibold text-slate-900">Notifikasi</p>
            <div className="flex items-center gap-1">
              <button type="button" onClick={tukarBisu}
                      title={bisu ? 'Bunyi mati' : 'Bunyi nyala'}
                      aria-label={bisu ? 'Nyalakan bunyi notifikasi' : 'Matikan bunyi notifikasi'}
                      className="flex h-7 w-7 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100">
                {bisu ? <VolumeX className="h-4 w-4" /> : <Volume2 className="h-4 w-4" />}
              </button>
              {belum > 0 && (
                <button type="button" onClick={tandaiSemua}
                        className="flex items-center gap-1 rounded-md px-2 py-1 text-[0.72rem] font-medium text-brand transition-colors hover:bg-brand/5">
                  <CheckCheck className="h-3.5 w-3.5" />Tandai dibaca
                </button>
              )}
            </div>
          </div>

          <div className="max-h-[70vh] overflow-y-auto sm:max-h-96">
            {memuat && items.length === 0 ? (
              <p className="px-4 py-10 text-center text-sm text-slate-400">Memuat…</p>
            ) : items.length === 0 ? (
              <div className="flex flex-col items-center gap-2 px-4 py-10 text-center">
                <BellOff className="h-8 w-8 text-slate-300" />
                <p className="text-sm text-slate-400">Belum ada notifikasi</p>
              </div>
            ) : (
              <ul className="divide-y divide-slate-50">
                {items.map((n) => {
                  const meta = TIPE_IKON[n.tipe] ?? { ikon: Bell, warna: 'text-slate-600 bg-slate-100' };
                  const I = meta.ikon;
                  return (
                    <li key={n.id}>
                      <button type="button" onClick={() => bukaNotif(n)}
                              className={cn(
                                'flex w-full gap-3 px-4 py-3 text-left transition-colors hover:bg-slate-50',
                                !n.dibaca && 'bg-brand/[0.04]',
                              )}>
                        <span className={cn('flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full', meta.warna)}>
                          <I className="h-[1.125rem] w-[1.125rem]" />
                        </span>
                        <span className="min-w-0 flex-1">
                          <span className="flex items-center gap-2">
                            <span className="truncate text-sm font-semibold text-slate-800">{n.judul}</span>
                            {!n.dibaca && <span className="h-2 w-2 flex-shrink-0 rounded-full bg-red-500" />}
                          </span>
                          <span className="mt-0.5 line-clamp-2 block text-xs text-slate-500">{n.isi}</span>
                          <span className="mt-1 block text-[0.68rem] text-slate-400">{waktuRelatif(n.createdAt)}</span>
                        </span>
                      </button>
                    </li>
                  );
                })}
              </ul>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
