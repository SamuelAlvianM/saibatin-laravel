/**
 * Preset tampilan kartu "Statistik Demografi" beranda — port
 * `lib/beranda-statistik.ts` portal Next.js.
 *
 * 🔴 Kelas Tailwind-nya WAJIB ditulis literal di sini, tidak boleh dirakit dari
 * potongan (`from-${warna}-400`). Tailwind memindai berkas sumber sebagai teks;
 * kelas yang baru terbentuk saat runtime tidak pernah ikut ke CSS, dan kartunya
 * tampil tanpa warna sama sekali. Itu juga alasan server hanya mengirim NAMA
 * presetnya (`warna`), bukan kelasnya.
 */

export const WARNA_PRESET = {
  biru: { latar: 'bg-gradient-to-br from-[#2176bd] to-[#1b4b72]', teks: 'text-brand', label: 'Biru' },
  amber: { latar: 'bg-gradient-to-br from-amber-400 to-amber-600', teks: 'text-amber-600', label: 'Kuning' },
  sky: { latar: 'bg-gradient-to-br from-sky-400 to-sky-600', teks: 'text-sky-600', label: 'Langit' },
  rose: { latar: 'bg-gradient-to-br from-rose-400 to-rose-600', teks: 'text-rose-500', label: 'Merah' },
  teal: { latar: 'bg-gradient-to-br from-teal-400 to-teal-600', teks: 'text-teal-600', label: 'Tosca' },
  emerald: { latar: 'bg-gradient-to-br from-emerald-400 to-emerald-600', teks: 'text-emerald-600', label: 'Hijau' },
  violet: { latar: 'bg-gradient-to-br from-violet-400 to-violet-600', teks: 'text-violet-600', label: 'Ungu' },
  slate: { latar: 'bg-gradient-to-br from-slate-500 to-slate-700', teks: 'text-slate-600', label: 'Abu' },
};

export function warnaPreset(warna) {
  return WARNA_PRESET[warna] ?? WARNA_PRESET.biru;
}

/** Label ramah untuk kunci kolom singkat; kolom lain dipakai apa adanya. */
const LABEL_KOLOM = {
  L: 'Laki-laki',
  P: 'Perempuan',
  JML: 'Jumlah',
  KK_JML: 'Jumlah KK',
  JML_WKTP: 'Sudah Rekam KTP-el',
};

export const labelKolom = (k) => LABEL_KOLOM[k] ?? k;

/**
 * Susunan kartu bawaan — dipakai sebagai kerangka SEBELUM `/api/stats`
 * menjawab, supaya tinggi seksinya sudah benar sejak render pertama dan isi di
 * bawahnya tidak tersentak turun saat angka tiba.
 *
 * Harus sama dengan `config('konten.kartu_beranda')` di sisi server.
 */
export const KARTU_BAWAAN = [
  { title: 'Jumlah Penduduk', icon: 'Users', kategori: 'jenis-kelamin', kolom: 'JML', warna: 'biru' },
  { title: 'Kepala Keluarga', icon: 'Home', kategori: 'kk', kolom: 'JML', warna: 'amber' },
  { title: 'Laki-laki', icon: 'User', kategori: 'jenis-kelamin', kolom: 'L', warna: 'sky' },
  { title: 'Perempuan', icon: 'UserCircle', kategori: 'jenis-kelamin', kolom: 'P', warna: 'rose' },
  { title: 'Wajib KTP', icon: 'IdCard', kategori: 'wajib-ktp', kolom: 'JML', warna: 'teal' },
  { title: 'Sudah Rekam KTP-el', icon: 'ScanLine', kategori: 'wajib-ktp', kolom: 'JML_WKTP', warna: 'emerald' },
].map((k) => ({ ...k, value: 0, badge: null }));
