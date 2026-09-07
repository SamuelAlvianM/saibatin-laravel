import { useEffect, useState } from 'react';
import {
  CheckCircle2, ChevronDown, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, Clock,
  FileSpreadsheet, FileText, Loader2, XCircle, X,
} from 'lucide-react';

import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Checkbox } from '@/Components/ui/checkbox';

/**
 * Potongan UI yang dipakai berulang di seluruh halaman dashboard petugas —
 * badge status, paginasi, kartu, pesan, dan pembantu tanggal.
 *
 * Dulu berkas ini juga memuat Tombol/Modal sendiri demi menghindari Radix.
 * Sejak user memilih memakai kit (14 Agu 2026), KONTROL FORMULIR datang dari
 * `Components/ui/*` — yang tinggal di sini adalah potongan yang memang tidak
 * ada padanannya di kit, bukan tiruan komponennya.
 */

// ── Status permohonan ──────────────────────────────────────────────────────

export const STATUS_PERMOHONAN = {
  MENUNGGU: { label: 'Menunggu', warna: 'text-amber-700 bg-amber-50 border-amber-200', ikon: Clock },
  DIPROSES: { label: 'Diproses', warna: 'text-sky-700 bg-sky-50 border-sky-200', ikon: Clock },
  SELESAI: { label: 'Selesai', warna: 'text-emerald-700 bg-emerald-50 border-emerald-200', ikon: CheckCircle2 },
  DITOLAK: { label: 'Ditolak', warna: 'text-rose-700 bg-rose-50 border-rose-200', ikon: XCircle },
};

/** Status yang FINAL — barisnya terkunci, hanya halaman Master yang bisa membuka. */
export const STATUS_FINAL = ['SELESAI', 'DITOLAK'];

export function LencanaStatus({ status, peta = STATUS_PERMOHONAN }) {
  const cfg = peta[status] ?? { label: status, warna: 'text-slate-600 bg-slate-50 border-slate-200', ikon: FileText };
  const Ikon = cfg.ikon ?? FileText;

  return (
    <span className={`inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium ${cfg.warna}`}>
      <Ikon className="h-3 w-3" />{cfg.label}
    </span>
  );
}

// ── Kerangka umum ──────────────────────────────────────────────────────────

/**
 * `ekspor` = kunci bagian statistik → memunculkan tombol unduh Excel di kanan
 * atas kartu, di samping `aksi` bila keduanya ada.
 */
export function Kartu({ judul, ikon: Ikon, aksi, ekspor, children, kelas = '' }) {
  return (
    <div className={`rounded-2xl border border-slate-200 bg-white p-5 shadow-sm ${kelas}`}>
      {(judul || aksi || ekspor) && (
        <div className="mb-4 flex items-center justify-between gap-2">
          <div className="flex min-w-0 items-center gap-2">
            {Ikon && (
              <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                <Ikon className="h-4 w-4" />
              </span>
            )}
            <h2 className="truncate text-sm font-bold text-slate-900">{judul}</h2>
          </div>
          {(aksi || ekspor) && (
            <div className="flex shrink-0 items-center gap-1.5">
              {aksi}
              {ekspor && <TombolEksporStatistik bagian={ekspor} />}
            </div>
          )}
        </div>
      )}
      {children}
    </div>
  );
}

/**
 * Tombol "Excel" di kanan atas kartu statistik dashboard.
 * Port `components/dashboard/export-statistik-button.tsx`.
 *
 * Sengaja mengunduh lewat fetch → blob, bukan `<a download>` biasa: berkasnya
 * dirakit di server (kop surat + logo + kueri agregat) sehingga ada jeda satu
 * dua detik. Tanpa keadaan "sedang menyiapkan", petugas mengira tombolnya tidak
 * bekerja lalu mengklik berkali-kali. Cara ini juga membuat galat 403/500 muncul
 * sebagai pesan, bukan sebagai tab kosong berisi JSON.
 */
export function TombolEksporStatistik({ bagian, label = 'Excel', judul, onGalat }) {
  const [sibuk, setSibuk] = useState(false);
  // Tanpa penanganan khusus, galat tetap harus terlihat — kartu statistik
  // memanggil tombol ini tanpa `onGalat`, dan unduhan yang diam-diam gagal
  // membuat petugas mengklik berulang kali tanpa tahu apa yang terjadi.
  const galat = onGalat ?? ((t) => window.alert(t));

  const unduh = async () => {
    if (sibuk) return;
    setSibuk(true);
    try {
      const url = bagian
        ? `/api/admin/statistik/export?bagian=${encodeURIComponent(bagian)}`
        : '/api/admin/statistik/export';
      const res = await fetch(url, { headers: { Accept: '*/*' }, credentials: 'same-origin' });
      if (!res.ok) {
        galat(res.status === 403
          ? 'Akun Anda tidak berhak mengunduh statistik.'
          : 'Gagal menyiapkan berkas Excel.');
        return;
      }

      // Nama berkas diambil dari Content-Disposition supaya sama dengan yang
      // ditentukan server (mengandung tanggal cetak).
      const disposisi = res.headers.get('Content-Disposition') ?? '';
      const cocok = disposisi.match(/filename="?([^";]+)"?/i);
      const namaFile = cocok?.[1] ?? `statistik-${bagian ?? 'semua'}.xlsx`;

      const objek = URL.createObjectURL(await res.blob());
      const a = document.createElement('a');
      a.href = objek;
      a.download = namaFile;
      a.rel = 'noopener';
      document.body.appendChild(a);
      a.click();
      a.remove();
      // Beri jeda sebelum melepas URL — Firefox membatalkan unduhan bila objek
      // sudah dicabut saat berkasnya belum selesai ditulis.
      setTimeout(() => URL.revokeObjectURL(objek), 10_000);
    } catch {
      galat('Gagal mengunduh — periksa koneksi lalu coba lagi.');
    } finally {
      setSibuk(false);
    }
  };

  return (
    <button
      type="button"
      onClick={unduh}
      disabled={sibuk}
      title={judul ?? 'Unduh data ini sebagai Excel berkop surat'}
      className="inline-flex shrink-0 items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[0.68rem] font-semibold text-emerald-700 transition-colors hover:border-emerald-300 hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
    >
      {sibuk ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <FileSpreadsheet className="h-3.5 w-3.5" />}
      {label}
    </button>
  );
}

export function Memuat({ kelas = 'py-12' }) {
  return (
    <div className={`flex justify-center ${kelas}`}>
      <Loader2 className="h-6 w-6 animate-spin text-brand" />
    </div>
  );
}

export function Kosong({ children }) {
  return <div className="py-12 text-center text-sm text-slate-500">{children}</div>;
}

export function Tombol({ varian = 'utama', kelas = '', anak, children, ...sisa }) {
  const gaya = {
    utama: 'bg-brand text-white hover:bg-brand-dark disabled:opacity-60',
    garis: 'border border-slate-300 bg-white text-slate-700 hover:border-brand hover:text-brand disabled:opacity-60',
    bahaya: 'bg-rose-600 text-white hover:bg-rose-700 disabled:opacity-60',
    sukses: 'bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60',
    // Aksi pendamping: hadir tapi tidak bersaing dengan tombol utama
    // di barisnya. Dipakai mis. tombol unduh di tiap baris kategori.
    polos: 'text-slate-600 hover:bg-slate-100 disabled:opacity-40',
  }[varian];

  return (
    <button
      className={`inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors ${gaya} ${kelas}`}
      {...sisa}
    >
      {children ?? anak}
    </button>
  );
}

/**
 * Pesan sekilas — pengganti `sonner`.
 *
 * Menghilang sendiri setelah beberapa detik supaya tidak menumpuk, tapi tetap
 * bisa ditutup manual: pesan galat kadang perlu dibaca ulang sambil membetulkan
 * isian, dan yang keburu hilang memaksa petugas mengulang aksinya cuma untuk
 * membacanya lagi.
 */
export function Pesan({ pesan, onTutup }) {
  useEffect(() => {
    if (!pesan) return undefined;
    const t = setTimeout(onTutup, pesan.tipe === 'galat' ? 8000 : 4000);
    return () => clearTimeout(t);
  }, [pesan, onTutup]);

  if (!pesan) return null;

  const galat = pesan.tipe === 'galat';

  return (
    <div
      role="status"
      /* 🔴 z-[60], bukan z-50. `Modal` di bawah juga z-50 dan SELALU dirender
          belakangan, jadi pada tumpukan yang sama modal menang — pesan galat
          dari server muncul PERSIS DI BALIK modal dan tidak pernah terbaca
          siapa pun. Gejalanya: tombol di dalam modal seolah tidak melakukan
          apa-apa, padahal servernya menolak dan pesannya memang dirender.
          ⚠️ Menambah lapisan melayang baru? Periksa `z-`-nya terhadap `Modal`,
          dan uji dengan galat yang MEMANG muncul dari dalam modal. */
      className={`fixed bottom-20 left-1/2 z-[60] flex max-w-[92vw] -translate-x-1/2 items-start gap-2 rounded-xl px-4 py-3 text-sm shadow-lg lg:bottom-6 ${
        galat ? 'bg-rose-600 text-white' : 'bg-emerald-600 text-white'
      }`}
    >
      <span className="leading-relaxed">{pesan.teks}</span>
      <button onClick={onTutup} aria-label="Tutup pesan" className="mt-0.5 opacity-80 hover:opacity-100">
        <X className="h-4 w-4" />
      </button>
    </div>
  );
}

/** Modal sederhana; menutup lewat latar atau tombol X. */
export function Modal({ judul, sub, lebar = 'max-w-md', onTutup, children }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" onClick={onTutup}>
      <div className={`relative w-full ${lebar} max-h-[90dvh] overflow-y-auto rounded-2xl bg-white p-6 shadow-xl`}
           onClick={(e) => e.stopPropagation()}>
        <div className="mb-4 flex items-start justify-between gap-3">
          <div>
            <h3 className="font-semibold text-slate-900">{judul}</h3>
            {sub && <p className="mt-0.5 font-mono text-xs text-slate-500">{sub}</p>}
          </div>
          <button onClick={onTutup} aria-label="Tutup" className="text-slate-400 hover:text-slate-600">
            <X className="h-5 w-5" />
          </button>
        </div>
        {children}
      </div>
    </div>
  );
}

// ── Paginasi bernomor ──────────────────────────────────────────────────────

/**
 * Deret nomor halaman dengan elipsis. Selalu menampilkan halaman pertama,
 * terakhir, dan tetangga halaman aktif — daftar 596 halaman tidak boleh
 * melebar sampai menggeser tabelnya.
 *
 * Jendelanya **dua** halaman ke tiap arah (permintaan user): dengan ±1 satu
 * klik hanya memindahkan satu halaman, sehingga melompat dua halaman selalu
 * butuh dua kali klik. Dengan ±2 deretnya jadi 1 … 6 7 [8] 9 10 … 60 —
 * masih muat satu baris, tapi lompatan dua halaman cukup sekali klik.
 */
function nomorHalaman(aktif, total) {
  if (total <= 9) return Array.from({ length: total }, (_, i) => i + 1);

  const sekitar = [aktif - 2, aktif - 1, aktif, aktif + 1, aktif + 2]
    .filter((n) => n > 1 && n < total);
  const hasil = [1, ...sekitar, total];

  return hasil.flatMap((n, i) => (i > 0 && n - hasil[i - 1] > 1 ? ['…', n] : [n]));
}

export function Paginasi({ page, totalHalaman, total, limit = 20, onGanti, nonaktif }) {
  if (totalHalaman <= 1) {
    return total > 0
      ? <p className="pt-4 text-center text-xs text-slate-400">{total} data</p>
      : null;
  }

  const dari = (page - 1) * limit + 1;
  const sampai = Math.min(page * limit, total);

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 pt-4">
      <p className="text-xs text-slate-400">
        Menampilkan {dari}–{sampai} dari {total} data
      </p>
      <div className="flex items-center gap-1">
        <button onClick={() => onGanti(1)} disabled={nonaktif || page <= 1} aria-label="Halaman pertama"
                title="Halaman pertama"
                className="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40">
          <ChevronsLeft className="h-4 w-4" />
        </button>
        <button onClick={() => onGanti(page - 1)} disabled={nonaktif || page <= 1} aria-label="Halaman sebelumnya"
                className="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40">
          <ChevronLeft className="h-4 w-4" />
        </button>

        {nomorHalaman(page, totalHalaman).map((n, i) =>
          n === '…' ? (
            <span key={`e${i}`} className="px-1 text-slate-400">…</span>
          ) : (
            <button key={n} onClick={() => onGanti(n)} disabled={nonaktif}
                    className={`h-8 min-w-8 rounded-lg px-2 text-sm font-medium transition-colors ${
                      n === page ? 'bg-brand text-white' : 'border border-slate-200 text-slate-600 hover:border-brand'
                    }`}>
              {n}
            </button>
          ),
        )}

        <button onClick={() => onGanti(page + 1)} disabled={nonaktif || page >= totalHalaman} aria-label="Halaman berikutnya"
                className="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40">
          <ChevronRight className="h-4 w-4" />
        </button>
        <button onClick={() => onGanti(totalHalaman)} disabled={nonaktif || page >= totalHalaman}
                aria-label="Halaman terakhir" title={`Halaman terakhir (${totalHalaman})`}
                className="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40">
          <ChevronsRight className="h-4 w-4" />
        </button>
      </div>
    </div>
  );
}

// ── Filter periode ─────────────────────────────────────────────────────────

export const PERIODE = [
  { kode: '', label: 'Semua waktu' },
  { kode: 'hari', label: 'Harian' },
  { kode: 'minggu', label: 'Mingguan' },
  { kode: 'bulan', label: 'Bulanan' },
  { kode: 'tahun', label: 'Tahunan' },
];

/** `Date` → "YYYY-MM-DD" waktu LOKAL (toISOString memakai UTC → bisa geser sehari). */
export function tulisAcuan(d) {
  const p = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
}

function geser(kode, acuan, langkah) {
  const d = new Date(acuan);
  if (kode === 'hari') d.setDate(d.getDate() + langkah);
  if (kode === 'minggu') d.setDate(d.getDate() + langkah * 7);
  if (kode === 'bulan') d.setMonth(d.getMonth() + langkah);
  if (kode === 'tahun') d.setFullYear(d.getFullYear() + langkah);
  return d;
}

function labelAcuan(kode, acuan) {
  const f = (o) => acuan.toLocaleDateString('id-ID', o);
  if (kode === 'hari') return f({ day: 'numeric', month: 'short', year: 'numeric' });
  if (kode === 'minggu') {
    const senin = new Date(acuan);
    senin.setDate(senin.getDate() - ((senin.getDay() + 6) % 7));
    return `Minggu ${senin.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })}`;
  }
  if (kode === 'bulan') return f({ month: 'long', year: 'numeric' });
  if (kode === 'tahun') return String(acuan.getFullYear());
  return '';
}

/**
 * Pemilih periode + penggeser rentang.
 *
 * Tombol ‹ › menggeser TITIK ACUAN, bukan menambah filter baru — itulah yang
 * membuat petugas bisa membaca "minggu lalu" dan "bulan lalu" tanpa memilih
 * tanggal manual. Perhitungan rentangnya ada di server (`App\Support\Periode`);
 * di sini hanya labelnya.
 */
export function FilterPeriode({ periode, acuan, onPeriode, onAcuan, nonaktif }) {
  return (
    <div className="flex flex-wrap items-center gap-2">
      {/* Radix menolak SelectItem bernilai "" — periode "Semua waktu" memakai
          penanda "semua", lalu diterjemahkan kembali ke "" untuk pemanggilnya. */}
      <Select value={periode || 'semua'} onValueChange={(v) => onPeriode(v === 'semua' ? '' : v)} disabled={nonaktif}>
        <SelectTrigger className="w-40">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          {PERIODE.map((p) => (
            <SelectItem key={p.kode} value={p.kode || 'semua'}>{p.label}</SelectItem>
          ))}
        </SelectContent>
      </Select>

      {periode && (
        <div className="flex items-center gap-1">
          <button onClick={() => onAcuan(geser(periode, acuan, -1))} disabled={nonaktif} aria-label="Periode sebelumnya"
                  className="flex h-9 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40">
            <ChevronLeft className="h-4 w-4" />
          </button>
          <span className="min-w-32 text-center text-xs font-semibold text-slate-600">{labelAcuan(periode, acuan)}</span>
          <button onClick={() => onAcuan(geser(periode, acuan, 1))} disabled={nonaktif} aria-label="Periode berikutnya"
                  className="flex h-9 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 disabled:opacity-40">
            <ChevronRight className="h-4 w-4" />
          </button>
        </div>
      )}
    </div>
  );
}

/** Hook kecil: nilai yang tertunda — dipakai kotak pencarian agar tidak membanjiri server. */
export function useTunda(nilai, jeda = 400) {
  const [tertunda, setTertunda] = useState(nilai);

  useEffect(() => {
    const t = setTimeout(() => setTertunda(nilai), jeda);
    return () => clearTimeout(t);
  }, [nilai, jeda]);

  return tertunda;
}

/** Tanggal ID ringkas — dipakai di banyak tabel. */
/*
 * 🔴 Diteruskan dari `lib/waktu.js`, yang memakukan zona ke zona KANTOR.
 * Sebelumnya keduanya memformat tanpa `timeZone`, jadi merender memakai zona
 * PERAMBAN — petugas di zona berbeda membaca jam yang meleset dari yang
 * tercetak di tanda terima. Diekspor ulang dari sini supaya belasan halaman
 * yang sudah mengimpornya ikut sembuh tanpa disentuh.
 */
export { tglJam, tglPanjang, tglSingkat } from '@/lib/waktu';


export const angka = (n) => Number(n ?? 0).toLocaleString('id-ID');

/**
 * Saringan pilih-banyak — dipakai untuk jenis permohonan.
 *
 * 🔴 Bukan Radix `Select`: komponen itu satu-nilai, menutup tiap kali diklik,
 * dan menolak `SelectItem` bernilai `""` — memaksanya jadi pilih-banyak berarti
 * melawan tiga perilaku bawaannya sekaligus. Popover + Checkbox jauh lebih
 * sedikit lawannya.
 *
 * Nilainya larik `string`, bukan angka: id datang dari URL dan kembali ke URL,
 * dan mencampur tipe di tengah jalan membuat `includes()` gagal diam-diam —
 * tidak ada galat, cuma centang yang tidak pernah menyala.
 */
export function FilterBanyak({
  nilai = [], onUbah, pilihan = [], label = 'Pilih', labelSemua = 'Semua', nonaktif,
}) {
  const [buka, setBuka] = useState(false);

  if (pilihan.length === 0) {
    return null;
  }

  const alihkan = (id) =>
    onUbah(nilai.includes(id) ? nilai.filter((v) => v !== id) : [...nilai, id]);

  // Ringkasan di tombol: nama kalau cuma satu, hitungan kalau lebih. Menuliskan
  // semua nama akan meregangkan tombol dan mendorong saringan lain keluar baris.
  const ringkas = nilai.length === 0
    ? labelSemua
    : nilai.length === 1
      ? (pilihan.find((p) => String(p.id) === nilai[0])?.nama ?? '1 dipilih')
      : `${nilai.length} dipilih`;

  return (
    <Popover open={buka} onOpenChange={setBuka}>
      <PopoverTrigger asChild>
        <button type="button" disabled={nonaktif} aria-label={label}
                className="flex h-9 w-52 items-center justify-between gap-2 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 transition-colors hover:border-slate-300 disabled:opacity-50">
          <span className="truncate">{ringkas}</span>
          <ChevronDown className="h-4 w-4 flex-shrink-0 text-slate-400" />
        </button>
      </PopoverTrigger>

      <PopoverContent align="start" className="w-64 p-0">
        <div className="flex items-center justify-between border-b border-slate-100 px-3 py-2">
          <span className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</span>
          {nilai.length > 0 && (
            <button type="button" onClick={() => onUbah([])}
                    className="text-xs font-medium text-brand hover:underline">
              Bersihkan
            </button>
          )}
        </div>

        <div className="max-h-72 overflow-y-auto p-1">
          {pilihan.map((p) => {
            const id = String(p.id);

            return (
              <label key={id}
                     className="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                <Checkbox checked={nilai.includes(id)} onCheckedChange={() => alihkan(id)} />
                <span className="min-w-0 flex-1">{p.nama}</span>
              </label>
            );
          })}
        </div>
      </PopoverContent>
    </Popover>
  );
}
