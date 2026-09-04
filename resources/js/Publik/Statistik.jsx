import { lazy, Suspense, useEffect, useRef, useState } from 'react';
import {
  ArrowRight, Building2, CalendarClock, CalendarDays, CheckCircle2, ChevronDown,
  ExternalLink, FileCheck, Hourglass, Loader2, Map as MapIcon, MapPin,
  MousePointerClick, Trees,
} from 'lucide-react';
import {
  Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import GrafikTampak from '@/Components/GrafikTampak';
import { ikon } from '@/lib/ikon';
import { KARTU_BAWAAN, warnaPreset } from '@/lib/statistik-kartu';
import { ambilJson } from '@/lib/api';
import { kueriPeriode, labelPeriode, periodeSama } from '@/lib/periode';

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
 * 🔴 DUA cara komponen ini pernah macet di 0, dan keduanya diam-diam:
 *
 * 1. **`requestAnimationFrame` tidak jalan di tab tersembunyi.** Pengunjung
 *    yang membuka portal di tab latar (ctrl-klik, "buka di tab baru",
 *    pemulihan sesi) mendapat kartu bernilai 0 selamanya: pengamat memicu
 *    animasi dan menandainya "sudah berjalan", lalu animasinya tidak pernah
 *    dieksekusi. → dijaga jalur `langsung()`.
 *
 * 2. **Animasi berjalan menuju sasaran yang sudah basi.** Kartu yang SUDAH di
 *    layar saat halaman dibuka memulai animasi ketika `/api/stats` belum tiba,
 *    jadi sasarannya masih 0. Data datang beberapa ratus milidetik kemudian,
 *    nilai barunya sempat terpasang — lalu **ditimpa lagi** oleh loop rAF yang
 *    masih berjalan menuju 0, sampai bingkai terakhirnya memakukan 0. Kartu
 *    yang baru terlihat setelah data tiba tampil benar, jadi gejalanya
 *    campur-campur: sebagian kartu berangka, sebagian 0. Persis itu yang
 *    terlihat di beranda: `0, 0, 0, 85.504, 121.739, 0, 11.902, 0`.
 *
 *    Perbaikannya dua lapis: sasaran dibaca dari **ref** tiap bingkai (jadi
 *    data yang datang di tengah animasi mengarahkan ulang, bukan dibuang), dan
 *    bingkai yang tertunda **dibatalkan** saat efeknya dibersihkan.
 *
 * 3. **Kartu yang tidak pernah masuk layar tidak pernah dianimasikan** — dan
 *    angka yang tertinggal bukan sekadar kosong, melainkan **0**, yang terbaca
 *    warga sebagai "tidak ada penduduk". Terjadi saat pengunjung melompat jauh
 *    ke bawah, mendarat lewat tautan berjangkar, atau peramban memulihkan
 *    posisi gulir.
 *
 *    🔴 Jangan menebaknya dari geometri (`boundingClientRect`): kombinasi
 *    `rootMargin` negatif, kartu yang tersangkut di tepi, dan panggilan balik
 *    pertama yang datang sebelum gulirannya selesai membuatnya meleset — sudah
 *    dicoba dan tetap 0. Yang dipakai sekarang **jaring pengaman berbasis
 *    waktu**: begitu datanya tiba, kartu yang belum juga mulai beranimasi
 *    setelah sesaat langsung dipasangi angkanya. Animasinya hiasan; angka yang
 *    benar tidak boleh bergantung padanya.
 */

/** Jeda sebelum angka dipasang paksa, dihitung sejak datanya tiba. */
const TENGGAT_ANIMASI = 1200;

function AngkaNaik({ nilai, durasi = 1600 }) {
  const ref = useRef(null);
  const [tampil, setTampil] = useState(0);

  // Sasaran animasi dibaca dari ref, bukan dari closure — data yang tiba di
  // tengah animasi mengarahkan ulang alih-alih terbuang.
  const sasaran = useRef(nilai);
  sasaran.current = nilai;
  const selesai = useRef(false);
  const berjalan = useRef(false);
  /** Diisi efek utama supaya efek `nilai` di bawah bisa memanggilnya. */
  const pasangLangsung = useRef(() => {});

  // Dua tugas: memasang nilai yang datang setelah animasi beres, DAN memasang
  // jaring pengaman untuk kartu yang tidak pernah masuk layar.
  useEffect(() => {
    if (selesai.current) {
      setTampil(nilai);
      return undefined;
    }
    if (berjalan.current || nilai === 0) return undefined;

    const timer = setTimeout(() => {
      if (!berjalan.current && !selesai.current) pasangLangsung.current();
    }, TENGGAT_ANIMASI);

    return () => clearTimeout(timer);
  }, [nilai]);

  // Dipasang SEKALI. Sengaja tidak bergantung pada `nilai`: kalau efek ini
  // ikut dijalankan ulang tiap data berubah, animasinya melompat balik ke nol
  // lalu naik lagi tepat saat pengguna sedang membacanya.
  useEffect(() => {
    const el = ref.current;
    if (!el) return undefined;

    let frame = 0;

    const langsung = () => {
      selesai.current = true;
      setTampil(sasaran.current);
    };
    pasangLangsung.current = langsung;

    if (document.hidden || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      langsung();
      return undefined;
    }

    const pengamat = new IntersectionObserver(([masuk]) => {
      if (!masuk.isIntersecting || selesai.current) return;
      pengamat.disconnect();

      // Tab sempat disembunyikan setelah pengamat dipasang.
      if (document.hidden) {
        langsung();
        return;
      }

      berjalan.current = true;
      const mulai = performance.now();

      const langkah = (kini) => {
        const t = Math.min(1, (kini - mulai) / durasi);
        const tujuan = sasaran.current;

        // easeOutCubic — cepat di awal, melambat di akhir. Bingkai terakhir
        // memakai nilai persisnya, bukan hasil pembulatan.
        setTampil(t >= 1 ? tujuan : Math.floor((1 - (1 - t) ** 3) * tujuan));

        if (t < 1) frame = requestAnimationFrame(langkah);
        else selesai.current = true;   // ditandai SESUDAH beres, bukan sebelum
      };

      frame = requestAnimationFrame(langkah);
    }, { rootMargin: '-60px' });

    pengamat.observe(el);

    return () => {
      pengamat.disconnect();
      if (frame) cancelAnimationFrame(frame);
    };
  }, [durasi]);

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
        {/*
          🔴 `null` = DATANYA BELUM ADA, dan itu berbeda dari nol.

          Nol adalah pernyataan — "kabupaten ini punya 0 kepala keluarga".
          Kartu yang kolom sumbernya tidak ditemukan tidak boleh membuat
          pernyataan itu; ia menampilkan "—". Lihat `jumlahKolom()` di
          StatistikController.
        */}
        {kartu.value === null || kartu.value === undefined ? (
          <p className="mb-2 text-[1.7rem] font-bold leading-none tracking-tight text-slate-300"
             title="Data untuk kartu ini belum tersedia — hubungi admin untuk mengatur sumber datanya">
            &mdash;
          </p>
        ) : (
          <p className="mb-2 text-[1.7rem] font-bold leading-none tracking-tight text-slate-900">
            <AngkaNaik nilai={kartu.value} />
          </p>
        )}
        <p className="text-[0.66rem] font-semibold uppercase tracking-widest text-slate-500">{kartu.title}</p>
        {(kartu.value === null || kartu.value === undefined) && (
          <p className="mt-1 text-[0.6rem] font-medium text-slate-400">Belum ada data</p>
        )}
      </div>
    </button>
  );
}

function KartuPelayanan({ pelayanan }) {
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

      {/*
        Grafiknya Highcharts (keputusan user), dan SELALU lewat gerbang
        tampilan: kartu ini jauh di bawah layar beranda, jadi 300 KB Highcharts
        tidak boleh ikut pemuatan awal setiap pengunjung.
      */}
      <div className="px-5 pb-3 pt-4">
        <p className="mb-3 text-[0.62rem] font-bold uppercase tracking-widest text-slate-400">Tren Permohonan · 6 Bulan</p>
        <GrafikTampak jenis="tren" data={pelayanan.trend6} tinggi={110} kecil />
      </div>

      <Garis />

      <div className="flex-1 px-5 pb-4 pt-4">
        <p className="mb-3 text-[0.62rem] font-bold uppercase tracking-widest text-slate-400">Layanan Terpopuler</p>
        {pelayanan.topJenis.length === 0 ? (
          <p className="py-2 text-xs text-slate-400">Belum ada data permohonan.</p>
        ) : (
          <GrafikTampak jenis="peringkat" data={pelayanan.topJenis} tinggi={140} kecil />
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


/**
 * Badge periode DKB di beranda — sekaligus pemilihnya.
 *
 * 🔴 Sebelum ini badge-nya sekadar `<span>` berisi tulisan dari `.env`: tidak
 * bisa diklik, tidak berhubungan dengan datanya, dan tidak ada cara apa pun
 * bagi warga melihat semester lain.
 *
 * ⚠️ Dibuat MENONJOL dan jelas bisa diklik — berlatar penuh warna merek,
 * berbayang, dengan panah yang berputar saat terbuka. Badge yang tampak seperti
 * label pasif tidak akan pernah dicoba diklik siapa pun, dan fitur filternya
 * jadi ada tapi tak terpakai.
 *
 * Satu periode saja → tetap tampil, tapi sebagai label biasa. Menawarkan
 * pilihan yang isinya cuma satu hanya membuang waktu orang.
 */
function PemilihPeriodePublik({ periode, tersedia = [], onPilih }) {
  const [buka, setBuka] = useState(false);
  const bungkus = useRef(null);

  useEffect(() => {
    if (!buka) return undefined;

    const klikLuar = (e) => {
      if (bungkus.current && !bungkus.current.contains(e.target)) setBuka(false);
    };
    const tekan = (e) => { if (e.key === 'Escape') setBuka(false); };

    document.addEventListener('mousedown', klikLuar);
    document.addEventListener('keydown', tekan);

    return () => {
      document.removeEventListener('mousedown', klikLuar);
      document.removeEventListener('keydown', tekan);
    };
  }, [buka]);

  if (!periode) {
    return (
      <span className="flex shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-400">
        <CalendarClock className="h-3.5 w-3.5" />
        Belum ada data
      </span>
    );
  }

  const label = `DKB ${labelPeriode(periode.tahun, periode.semester)}`;

  if (tersedia.length <= 1) {
    return (
      <span className="flex shrink-0 items-center gap-1.5 rounded-full border border-brand/20 bg-brand/10 px-3 py-1.5 text-xs font-semibold text-brand">
        <CalendarClock className="h-3.5 w-3.5" />{label}
      </span>
    );
  }

  return (
    <div ref={bungkus} className="relative shrink-0">
      <button
        type="button"
        onClick={() => setBuka((b) => !b)}
        aria-haspopup="listbox"
        aria-expanded={buka}
        title="Pilih tahun & semester data yang ingin dilihat"
        className="flex items-center gap-2 rounded-full bg-brand px-4 py-2 text-xs font-bold text-white shadow-md shadow-brand/25 transition-all hover:shadow-lg hover:brightness-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40"
      >
        <CalendarClock className="h-4 w-4" />
        {label}
        <ChevronDown className={`h-4 w-4 transition-transform ${buka ? 'rotate-180' : ''}`} />
      </button>

      {/* Petunjuk kecil di bawah badge — sekali lihat, warga tahu ini pilihan. */}
      {!buka && (
        <span className="pointer-events-none absolute right-1 top-full mt-1 whitespace-nowrap text-[0.6rem] font-medium text-brand/70">
          ganti periode ▾
        </span>
      )}

      {buka && (
        <div
          role="listbox"
          className="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
        >
          <p className="border-b border-slate-100 bg-slate-50 px-3 py-2 text-[0.65rem] font-bold uppercase tracking-widest text-slate-500">
            Periode data kependudukan
          </p>
          <div className="max-h-60 overflow-y-auto">
            {tersedia.map((t) => {
              const aktif = periodeSama(t, periode);

              return (
                <button
                  key={`${t.tahun}-${t.semester}`}
                  type="button"
                  role="option"
                  aria-selected={aktif}
                  onClick={() => { setBuka(false); onPilih({ tahun: t.tahun, semester: t.semester }); }}
                  className={`flex w-full items-center justify-between px-3 py-2.5 text-left text-sm transition-colors ${
                    aktif ? 'bg-brand/10 font-bold text-brand' : 'text-slate-700 hover:bg-slate-50'
                  }`}
                >
                  {labelPeriode(t.tahun, t.semester)}
                  {aktif && <CheckCircle2 className="h-4 w-4" />}
                </button>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}

const AWAL = {
  kartuDemografi: KARTU_BAWAAN,
  pelayanan: { total: 0, selesai: 0, aktif: 0, bulanIni: 0, topJenis: [], trend6: [] },
  /*
   * ⚠️ Kosong, bukan "DKB Semester II 2024".
   *
   * Kerangka awal ini tampil sepersekian detik sebelum `/api/stats` menjawab.
   * Menuliskan periode tertentu di sini berarti halaman sempat mengumumkan
   * periode yang belum tentu benar — dan untuk angka kependudukan resmi,
   * keterangan yang keliru lebih buruk daripada belum ada keterangan.
   */
  periodeKependudukan: null,
  periode: null,
  periodeTersedia: [],
};

export default function Statistik() {
  const [stats, setStats] = useState(AWAL);
  const [rincian, setRincian] = useState(null);
  /*
   * Periode yang DIMINTA warga. `null` = "yang terbaru", dan itu memang
   * keadaan awalnya: pengunjung yang tidak memilih apa pun harus melihat data
   * terbaru, bukan periode yang kebetulan tertulis di kode.
   */
  const [diminta, setDiminta] = useState(null);

  useEffect(() => {
    let batal = false;
    const q = kueriPeriode(diminta);

    ambilJson(`/api/stats${q ? `?${q}` : ''}`).then((j) => {
      if (batal || !j?.data) return;
      setStats((p) => ({ ...p, ...j.data }));
    });

    return () => { batal = true; };
  }, [diminta?.tahun, diminta?.semester]); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <div className="space-y-4">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="mb-1 text-[0.66rem] font-bold uppercase tracking-widest text-slate-400">Data Kependudukan</p>
          <h2 className="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">Statistik Demografi</h2>
        </div>
        {/*
          🔴 Badge ini dulu cuma TULISAN — dan tulisannya diketik di `.env`,
          lepas sama sekali dari data yang ditampilkan di bawahnya. Kini ia
          tombol: isinya dihitung dari periode yang benar-benar dipakai, dan
          warga bisa berpindah ke periode lain yang datanya ada.
        */}
        <PemilihPeriodePublik
          periode={stats.periode}
          tersedia={stats.periodeTersedia}
          onPilih={setDiminta}
        />
      </div>

      <p className="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-400">
        <span className="flex items-center gap-1.5">
          <MousePointerClick className="h-3.5 w-3.5" />
          Klik kartu untuk melihat rincian per kecamatan &amp; desa.
        </span>
        {stats.periodeTersedia?.length > 1 && (
          <span className="flex items-center gap-1.5">
            <CalendarClock className="h-3.5 w-3.5" />
            Tersedia {stats.periodeTersedia.length} periode — klik badge tahun di kanan atas.
          </span>
        )}
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
              <RincianDemografi kategori={rincian.kategori} kolom={rincian.kolom} judul={rincian.title}
                                periode={stats.periode} />
            </Suspense>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
