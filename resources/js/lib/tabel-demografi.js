/**
 * Kelas sel & baris tabel rincian demografi — SATU sumber untuk tabel di
 * modal rincian kartu beranda (dipakai `RincianDemografi.jsx`).
 *
 * 🔴 Kontras. Tabelnya dulu abu-abu tipis di atas putih (kepala kolom
 * `text-slate-500` di atas `bg-slate-50`) — pucat sampai sulit dibaca.
 * Kepala kolom & baris TOTAL kini berlatar WARNA GELAP utama portal
 * (biru `#2176bd`) dengan teks putih, angka hitam `tabular-nums` rata kanan,
 * baris selang-seling + hover, dan 0 sengaja dibuat redup (`text-slate-400`)
 * agar yang bernilai yang menonjol.
 *
 * 🔴 Sticky. Kepala kolom menempel di ATAS dan kolom kecamatan menempel di
 * KIRI saat tabel digulir — tabel yang tinggi, atau yang lebih lebar dari
 * modal di ponsel, tetap terbaca kepala & wilayahnya. Latar sel sticky
 * memakai `bg-inherit` supaya baris selang-seling tetap terbaca lewat kolom
 * yang menempel.
 *
 * ⚠️ Warna portal ditulis HEX (bukan token) karena modul ini ikut disalin
 * antar-project saudara lalu warnanya disesuaikan per portal.
 */
export const TABEL_DEMOGRAFI = {
  /** Kepala kolom — sticky atas. `sorot` = kolom sumber kartu yang dilihat. */
  kepala: (sorot = false) =>
    `sticky top-0 z-10 px-3 py-2.5 text-[0.68rem] font-semibold uppercase tracking-wider whitespace-nowrap ${
      sorot ? 'bg-brand-dark text-brand-yellow/90' : 'bg-brand text-white'
    }`,
  /** Sel kolom pertama (kecamatan/desa) — sticky kiri, latar mengikuti baris. */
  selPertama: 'sticky left-0 z-20 bg-inherit px-3 py-2 font-medium text-slate-900 whitespace-nowrap',
  /** Sel kepala untuk kolom pertama — menempel dua arah (atas & kiri). */
  kepalaPertama: 'sticky left-0 top-0 z-30 bg-brand text-white whitespace-nowrap',
  /** Sel angka — `sorot` = kolom sumber kartu; 0 dibuat redup. */
  angka: (n, sorot = false) =>
    `px-3 py-2 text-right tabular-nums ${
      sorot
        ? 'bg-brand/10 font-semibold text-slate-900'
        : n === 0
          ? 'text-slate-400'
          : 'text-slate-900'
    }`,
  /** Baris TOTAL (tfoot) — satu warna dengan kepala kolom. */
  totalBaris: 'bg-brand font-semibold text-white',
  totalAngka: 'px-3 py-2.5 text-right tabular-nums',
  totalLabel: 'px-3 py-2.5',
};

/**
 * Baris tbody — selang-seling + hover. Sel sticky di dalamnya membaca
 * latar baris lewat `bg-inherit`, jadi belangnya ikut tampak di kolom yang
 * menempel.
 */
export const BARIS_DEMOGRAFI =
  'border-b border-slate-100 last:border-0 odd:bg-white even:bg-slate-50 hover:bg-brand/5';
