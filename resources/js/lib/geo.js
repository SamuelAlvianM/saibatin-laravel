/**
 * Titik perkiraan pusat tiap kecamatan Kabupaten Pesisir Barat (Lampung) —
 * port `lib/pesisir-barat-geo.ts`.
 *
 * Koordinat bersifat PERKIRAAN (indikatif). Pencocokan dengan data demografi
 * memakai nama wilayah yang dinormalisasi (huruf besar, spasi tunggal) —
 * bukan kode wilayah, karena rekap DKB dan daftar ini datang dari dua sumber
 * berbeda dan hanya namanya yang pasti sama.
 */

export const PUSAT_PETA = [-5.25, 103.86];
export const ZOOM_AWAL = 10;

export const KECAMATAN_GEO = [
  { nama: 'LEMONG', lat: -4.905, lng: 103.975 },
  { nama: 'PESISIR UTARA', lat: -5.02, lng: 103.99 },
  { nama: 'PULAU PISANG', lat: -5.115, lng: 103.795 },
  { nama: 'KARYA PENGGAWA', lat: -5.105, lng: 103.955 },
  { nama: 'PESISIR TENGAH', lat: -5.193, lng: 103.942 },
  { nama: 'WAY KRUI', lat: -5.225, lng: 103.935 },
  { nama: 'KRUI SELATAN', lat: -5.26, lng: 103.925 },
  { nama: 'PESISIR SELATAN', lat: -5.36, lng: 103.85 },
  { nama: 'NGAMBUR', lat: -5.46, lng: 103.78 },
  { nama: 'NGARAS', lat: -5.56, lng: 103.72 },
  { nama: 'BENGKUNAT', lat: -5.68, lng: 103.63 },
];

/**
 * 🔴 Nama di rekap DKB tidak selalu sama persis dengan daftar di atas.
 * Terukur pada data asli (11 kecamatan):
 *
 *   "PULAUPISANG"         → tanpa spasi
 *   "BENGKUNAT BELIMBING" → nama LAMA kecamatan Ngaras
 *
 * Tanpa penanganan ini keduanya jatuh diam-diam: petanya tetap tampil rapi
 * dengan 9 lingkaran, hanya totalnya kurang 31.604 jiwa — dan tidak ada yang
 * terlihat rusak. Karena itu spasi dibuang saat mencocokkan, dan nama lama
 * didaftarkan sebagai alias.
 *
 * ⚠️ Bug yang sama masih ada di portal Next.js (`lib/pesisir-barat-geo.ts`) —
 * di sana pencocokannya hanya merapikan spasi, tidak membuangnya.
 */
const ALIAS = {
  'BENGKUNATBELIMBING': 'NGARAS',
};

const norm = (s) => String(s ?? '').trim().toUpperCase().replace(/\s+/g, '');

const PETA_NAMA = new Map(KECAMATAN_GEO.map((k) => [norm(k.nama), k]));

/** Koordinat kecamatan dari nama wilayah data demografi, atau undefined. */
export function geoWilayah(wilayah) {
  const kunci = norm(wilayah);
  return PETA_NAMA.get(kunci) ?? PETA_NAMA.get(norm(ALIAS[kunci] ?? ''));
}
