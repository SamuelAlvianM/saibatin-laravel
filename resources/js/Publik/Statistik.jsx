import { lazy, Suspense, useEffect, useRef, useState } from 'react';
import {
  ArrowRight, Building2, CalendarClock, CalendarDays, CheckCircle2, ExternalLink,
  FileCheck, Hourglass, Loader2, Map as MapIcon, MapPin, MousePointerClick, Trees,
} from 'lucide-react';
import {
  Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { ikon } from '@/lib/ikon';
import { KARTU_BAWAAN, warnaPreset } from '@/lib/statistik-kartu';
import { ambilJson } from '@/lib/api';

/**
 * Statistik beranda — port `components/landingpage/stats.tsx`.
 *
 * Tiga bagian: 6 kartu demografi (kiri), ringkasan pelayanan (kanan), dan peta
 * kantor (bawah). Angkanya dari `/api/stats`; kartu mana yang tampil diatur
 * petugas lewat blok konten `beranda.statistik`.
 *
 * Sebelum jawaban tiba, kartu digambar dengan susunan bawaan bernilai 0 — bukan
 * kerangka abu-abu. Tinggi seksinya langsung benar, jadi seluruh isi beranda di
 * bawahnya tidak tersentak turun saat angka masuk.
 *
 * ⛔ Mode edit (klik kartu → editor data Excel) TIDAK ikut: itu bagian dari
 * "Konten Halaman", yang memang baru dibangun setelah situs publiknya ada.
 * Klik kartu di sini hanya membuka rincian per kecamatan.
 */

const PetaKantor = lazy(() => import('./PetaKantor'));
const RincianDemografi = lazy(() => import('./RincianDemografi'));

const KACA = 'rounded-2xl bg-gradient-to-br from-brand/[0.09] to-brand/[0.03] border border-brand/15 shadow-[0_4px_20px_rgba(33,118,189,0.06)]';
const KEPALA_IKON = 'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-brand bg-gradient-to-br from-brand/25 to-brand/10 ring-1 ring-brand/15';

const angka = (n) => Number(n ?? 0).toLocaleString('id-ID');

/**
 * Angka yang menghitung naik saat kartunya masuk layar.
 *
 * Memakai IntersectionObserver + requestAnimationFrame, bukan framer-motion:
 * yang dibutuhkan cuma satu nilai yang bertambah, sekali per kartu.
 *
 * 🔴 `requestAnimationFrame` TIDAK PERNAH JALAN di tab yang tersembunyi.
 * Tanpa penjaga di bawah, pengunjung yang membuka portal di tab latar
 * (ctrl-klik, "buka di tab baru", pemulihan sesi) mendapat kartu bernilai
 * **0 selamanya**: pengamat sempat memicu animasi, menandainya "sudah
 * berjalan", lalu animasinya tidak pernah benar-benar dieksekusi. Karena itu
 * saat halaman tidak terlihat — atau pengguna mematikan animasi di
 * sistemnya — angkanya langsung dipasang tanpa dianimasikan.
 */
function AngkaNaik({ nilai, durasi = 1600 }) {
  const ref = useRef(null);
  const [tampil, setTampil] = useState(0);
  const sudah = useRef(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return undefined;

    // Nilai berubah setelah animasi pernah jalan (mis. data baru tiba) →
    // langsung pakai angkanya, jangan menghitung ulang dari nol.
    if (sudah.current) {
      setTampil(nilai);
      return undefined;
    }

    const langsung = () => {
      sudah.current = true;
      setTampil(nilai);
    };

    if (document.hidden || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      langsung();
      return undefined;
    }

    const pengamat = new IntersectionObserver(([masuk]) => {
      if (!masuk.isIntersecting || sudah.current) return;
      pengamat.disconnect();

      // Tab sempat disembunyikan setelah pengamat dipasang.
      if (document.hidden) {
        langsung();
        return;
      }

      sudah.current = true;
      const mulai = performance.now();

      const langkah = (kini) => {
        const t = Math.min(1, (kini - mulai) / durasi);
        // easeOutCubic — cepat di awal, melambat di akhir. Bingkai terakhir
        // memakai nilai persisnya, bukan hasil pembulatan.
        setTampil(t >= 1 ? nilai : Math.floor((1 - (1 - t) ** 3) * nilai));
        if (t < 1) requestAnimationFrame(langkah);
      };

      requestAnimationFrame(langkah);
    }, { rootMargin: '-60px' });

    pengamat.observe(el);
    return () => pengamat.disconnect();
  }, [nilai, durasi]);

  return <span ref={ref}>{angka(tampil)}</span>;
}

function Garis() {
  return <div className="h-px w-full bg-brand/10" />;
}

function KartuDemografi({ kartu, onKlik }) {
  const Ikon = ikon(kartu.icon);
  const warna = warnaPreset(kartu.warna);

  return (
    <button type="button" onClick={onKlik} title={`Lihat rincian ${kartu.title} per kecamatan`}
            className={`${KACA} group relative flex h-full w-full cursor-pointer flex-col justify-between gap-5 p-5 text-left transition-[box-shadow,border-color] duration-300 hover:border-brand/30 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40`}>
      <MousePointerClick className="absolute right-3 top-3 h-4 w-4 text-transparent transition-colors duration-300 group-hover:text-brand/40" />

      <div className="flex items-start justify-between">
        <div className={`flex h-12 w-12 items-center justify-center rounded-2xl shadow-sm transition-transform duration-300 group-hover:scale-105 ${warna.latar}`}>
          <span className="text-white"><Ikon className="h-6 w-6" /></span>
        </div>
        {kartu.badge && (
          <span className={`rounded-full border border-brand/10 bg-white px-2.5 py-1 text-xs font-bold shadow-sm ${warna.teks}`}>
            {kartu.badge}
          </span>
        )}
      </div>

      <div>
        <p className="mb-2 text-[1.7rem] font-bold leading-none tracking-tight text-slate-900">
          <AngkaNaik nilai={kartu.value} />
        </p>
        <p className="text-[0.66rem] font-semibold uppercase tracking-widest text-slate-500">{kartu.title}</p>
      </div>
    </button>
  );
}

function TrenMini({ data }) {
  const maks = Math.max(1, ...data.map((d) => d.count));

  return (
    <div className="flex h-16 items-end justify-between gap-1.5">
      {data.map((d, i) => (
        <div key={i} className="flex flex-1 flex-col items-center gap-1">
          <div className="relative flex h-12 w-full items-end justify-center">
            <div className="w-full max-w-[22px] rounded-md bg-gradient-to-t from-[#1b4b72] to-[#7db8e8]"
                 style={{ height: `${Math.max(6, (d.count / maks) * 100)}%` }}
                 title={`${d.label}: ${d.count}`} />
          </div>
          <span className="text-[0.6rem] font-medium text-slate-400">{d.label}</span>
        </div>
      ))}
    </div>
  );
}

function KartuPelayanan({ pelayanan }) {
  const maksJenis = Math.max(1, ...pelayanan.topJenis.map((t) => t.count));

  const metrik = [
    { label: 'Bulan Ini', nilai: pelayanan.bulanIni, Ikon: CalendarDays, warna: 'text-brand bg-brand/10' },
    { label: 'Selesai', nilai: pelayanan.selesai, Ikon: CheckCircle2, warna: 'text-emerald-600 bg-emerald-50' },
    { label: 'Berjalan', nilai: pelayanan.aktif, Ikon: Hourglass, warna: 'text-amber-600 bg-amber-50' },
  ];

  return (
    <div className={`${KACA} relative flex h-full w-full flex-col overflow-hidden`}>
      <div className="flex items-center justify-between px-5 pb-4 pt-5">
        <div className="flex items-center gap-3">
          <div className={KEPALA_IKON}><FileCheck className="h-5 w-5" /></div>
          <div>
            <p className="text-[0.62rem] font-bold uppercase tracking-widest text-brand">Total Pelayanan</p>
            <p className="mt-0.5 text-2xl font-bold leading-none text-slate-900">
              <AngkaNaik nilai={pelayanan.total} />
            </p>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-3 gap-2 px-5 pb-4">
        {metrik.map((m) => (
          <div key={m.label} className="rounded-xl border border-brand/10 bg-white/70 p-2.5 text-center">
            <div className={`mx-auto mb-1.5 flex h-7 w-7 items-center justify-center rounded-lg ${m.warna}`}>
              <m.Ikon className="h-4 w-4" />
            </div>
            <p className="text-lg font-bold leading-none text-slate-900">{angka(m.nilai)}</p>
            <p className="mt-1 text-[0.6rem] font-medium text-slate-400">{m.label}</p>
          </div>
        ))}
      </div>

      <Garis />

      <div className="px-5 pb-3 pt-4">
        <p className="mb-3 text-[0.62rem] font-bold uppercase tracking-widest text-slate-400">Tren Permohonan · 6 Bulan</p>
        <TrenMini data={pelayanan.trend6} />
      </div>

      <Garis />

      <div className="flex-1 px-5 pb-4 pt-4">
        <p className="mb-3 text-[0.62rem] font-bold uppercase tracking-widest text-slate-400">Layanan Terpopuler</p>
        {pelayanan.topJenis.length === 0 ? (
          <p className="py-2 text-xs text-slate-400">Belum ada data permohonan.</p>
        ) : (
          <div className="space-y-2.5">
            {pelayanan.topJenis.map((t) => (
              <div key={t.nama} className="space-y-1">
                <div className="flex items-center justify-between gap-2">
                  <span className="truncate text-xs text-slate-600">{t.nama}</span>
                  <span className="shrink-0 text-xs font-bold text-slate-900">{angka(t.count)}</span>
                </div>
                <div className="h-1.5 w-full overflow-hidden rounded-full bg-brand/10">
                  <div className="h-full rounded-full bg-gradient-to-r from-[#1b4b72] to-[#7db8e8]"
                       style={{ width: `${(t.count / maksJenis) * 100}%` }} />
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <Garis />

      <div className="mt-auto flex items-center justify-between px-5 py-3">
        <p className="text-xs text-slate-400">Data langsung dari sistem</p>
        <a href="/user/pengajuan/baru" className="flex items-center gap-1 text-xs font-semibold text-brand transition-colors hover:text-brand-dark">
          Ajukan <ArrowRight className="h-3 w-3" />
        </a>
      </div>
    </div>
  );
}

function KartuPeta() {
  const wilayah = [
    { label: 'Kecamatan', nilai: '5', Ikon: Building2, warna: 'bg-brand/10 text-brand' },
    { label: 'Desa / Kelurahan', nilai: '32', Ikon: Trees, warna: 'bg-emerald-50 text-emerald-600' },
  ];

  return (
    <div className={`${KACA} relative overflow-hidden`}>
      <div className="flex items-center justify-between px-5 pb-3 pt-5">
        <div className="flex items-center gap-3">
          <div className={KEPALA_IKON}><MapIcon className="h-5 w-5" /></div>
          <div>
            <p className="text-[0.62rem] font-bold uppercase tracking-widest text-brand">Wilayah Administrasi</p>
            <p className="text-sm font-semibold text-slate-800">Lokasi Kantor Disdukcapil</p>
          </div>
        </div>
        <a href="/media/gis" className="flex items-center gap-1 text-xs font-semibold text-brand transition-colors hover:text-brand-dark">
          Peta Lengkap <ExternalLink className="h-3 w-3" />
        </a>
      </div>

      <div className="flex flex-col items-stretch gap-4 px-4 pb-4 md:flex-row">
        {/* z-0: panel Leaflet punya z-index sendiri yang bisa menaungi konten
            di bawahnya kalau tidak dikurung. */}
        <div className="relative z-0 min-h-[15rem] flex-1 overflow-hidden rounded-xl ring-1 ring-brand/15">
          <Suspense fallback={
            <div className="flex h-full w-full items-center justify-center bg-slate-100">
              <Loader2 className="h-5 w-5 animate-spin text-slate-400" />
            </div>
          }>
            <PetaKantor />
          </Suspense>
        </div>

        <div className="flex flex-col gap-3 md:w-60">
          <div className="grid flex-1 grid-cols-2 gap-3 md:grid-cols-1">
            {wilayah.map((w) => (
              <div key={w.label} className="flex items-center gap-3.5 rounded-xl border border-brand/[0.08] bg-white/60 px-4 py-3.5">
                <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ${w.warna}`}>
                  <w.Ikon className="h-5 w-5" />
                </div>
                <div>
                  <p className="text-2xl font-bold leading-none text-slate-900">{w.nilai}</p>
                  <p className="mt-1 text-[0.68rem] font-medium uppercase tracking-wide text-slate-500">{w.label}</p>
                </div>
              </div>
            ))}
          </div>
          <div className="flex items-start gap-2 rounded-xl border border-brand/[0.08] bg-brand/[0.05] px-3.5 py-3">
            <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-brand" />
            <p className="text-[0.72rem] leading-snug text-slate-500">
              Kompleks Perkantoran Pemda, Way Redak, Krui, Kabupaten Pesisir Barat, Lampung
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

const AWAL = {
  kartuDemografi: KARTU_BAWAAN,
  pelayanan: { total: 0, selesai: 0, aktif: 0, bulanIni: 0, topJenis: [], trend6: [] },
  periodeKependudukan: 'DKB Semester II 2024',
};

export default function Statistik() {
  const [stats, setStats] = useState(AWAL);
  const [rincian, setRincian] = useState(null);

  useEffect(() => {
    let batal = false;

    ambilJson('/api/stats').then((j) => {
      if (batal || !j?.data) return;
      setStats((p) => ({ ...p, ...j.data }));
    });

    return () => { batal = true; };
  }, []);

  return (
    <div className="space-y-4">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="mb-1 text-[0.66rem] font-bold uppercase tracking-widest text-slate-400">Data Kependudukan</p>
          <h2 className="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Statistik Demografi</h2>
        </div>
        <span className="flex shrink-0 items-center gap-1.5 rounded-full border border-brand/20 bg-brand/10 px-3 py-1.5 text-xs font-semibold text-brand">
          <CalendarClock className="h-3.5 w-3.5" />{stats.periodeKependudukan}
        </span>
      </div>

      <p className="flex items-center gap-1.5 text-xs text-slate-400">
        <MousePointerClick className="h-3.5 w-3.5" />
        Klik kartu untuk melihat rincian per kecamatan &amp; desa.
      </p>

      <div className="grid grid-cols-1 items-stretch gap-3 lg:grid-cols-12 lg:gap-4">
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:col-span-7 lg:grid-cols-2 lg:grid-rows-3">
          {stats.kartuDemografi.map((k, i) => (
            <KartuDemografi key={`${k.title}-${i}`} kartu={k} onKlik={() => setRincian(k)} />
          ))}
        </div>

        <div className="flex lg:col-span-5">
          <KartuPelayanan pelayanan={stats.pelayanan} />
        </div>
      </div>

      <KartuPeta />

      <Dialog open={!!rincian} onOpenChange={(b) => !b && setRincian(null)}>
        <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
          <DialogHeader>
            <DialogTitle>{rincian?.title ?? 'Data Demografi'}</DialogTitle>
            <DialogDescription>
              Angka per kecamatan di Kabupaten Pesisir Barat — klik nama kecamatan untuk rincian tiap desa.
            </DialogDescription>
          </DialogHeader>

          {rincian && (
            <Suspense fallback={
              <div className="flex justify-center py-16"><Loader2 className="h-6 w-6 animate-spin text-brand" /></div>
            }>
              <RincianDemografi kategori={rincian.kategori} kolom={rincian.kolom} judul={rincian.title} />
            </Suspense>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
