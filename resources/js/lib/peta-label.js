/**
 * Tata letak label peta kecamatan (satuan viewBox `lib/peta-wilayah.js`) dan
 * warna peta. Dipakai island peta DAN (lewat nilai yang sama di PHP) PDF.
 *
 * Empat kecamatan sekitar Krui (Way Krui, Pesisir Tengah, Krui Selatan) dan
 * Pulau Pisang terlalu kecil untuk tulisan — labelnya ditaruh di laut sebelah
 * barat (`luar`) dengan garis penunjuk; Lemong & Pesisir Utara ikut ke laut
 * supaya deretnya rapi.
 *
 * 🔴 Nilai yang sama tertulis di `app/Services/StatistikPdf.php` (LABEL). Ubah
 * keduanya bersamaan.
 */
export const LABEL_KECAMATAN = {
  LEMONG: { x: 16, y: 150, luar: true },
  'PESISIR UTARA': { x: 16, y: 220, luar: true },
  'PULAU PISANG': { x: 16, y: 290, luar: true },
  'WAY KRUI': { x: 16, y: 360, luar: true },
  'PESISIR TENGAH': { x: 16, y: 430, luar: true },
  'KRUI SELATAN': { x: 16, y: 500, luar: true },
  'KARYA PENGGAWA': { x: 330, y: 205 },
  'PESISIR SELATAN': { x: 455, y: 395 },
  NGAMBUR: { x: 545, y: 480 },
  NGARAS: { x: 612, y: 545 },
  BANGKUNAT: { x: 770, y: 725 },
};

export const LABEL_TETANGGA = [
  { x: 70, y: 40, teks: 'Kaur' },
  { x: 650, y: 170, teks: 'Lampung Barat' },
  { x: 915, y: 700, teks: 'Tanggamus' },
  { x: 260, y: 820, teks: 'Samudra Hindia', laut: true },
];

export const WARNA_LAUT = '#eaf4f8';
export const WARNA_DARAT_TETANGGA = '#e2e5e3';
export const GARIS_DARAT_TETANGGA = '#cdd3d0';
/** Sorotan kecamatan terpilih — kuning brand, kontras di atas palet biru. */
export const WARNA_SOROT = '#f5b301';

/** Lima kelas, terang → gelap. Sama dengan StatistikKependudukan::SKALA_WARNA. */
export const SKALA_WARNA = ['#dbe8f5', '#a9c8e8', '#6fa3d6', '#3a78b8', '#1b4b72'];
export const WARNA_TANPA_DATA = '#f1f3f5';

export const normalNama = (s) => String(s ?? '').trim().toUpperCase().replace(/\s+/g, ' ');

export function warnaUntuk(n, min, maks) {
  if (n === null || n === undefined) return WARNA_TANPA_DATA;
  if (maks <= min) return SKALA_WARNA[2];
  const i = Math.min(SKALA_WARNA.length - 1, Math.floor(((n - min) / (maks - min)) * SKALA_WARNA.length));
  return SKALA_WARNA[i];
}

export const namaCantik = (nama) =>
  String(nama ?? '')
    .toLowerCase()
    .replace(/(^|[\s\-/(])([a-z])/g, (_, a, b) => a + b.toUpperCase());

export function nilaiUkuran(k, u) {
  switch (u) {
    case 'penduduk':
      return k.penduduk;
    case 'kk':
      return k.kk;
    case 'jiwa-kk':
      return k.penduduk && k.kk ? k.penduduk / k.kk : null;
    case 'rasio-jk':
      return k.laki !== null && k.perempuan ? (k.laki / k.perempuan) * 100 : null;
    default:
      return null;
  }
}

export const fmtUkuran = (n, u) =>
  n === null || n === undefined
    ? '—'
    : u === 'penduduk' || u === 'kk'
      ? Math.round(n).toLocaleString('id-ID')
      : n.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
