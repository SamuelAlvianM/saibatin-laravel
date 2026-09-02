/**
 * Warna lembut per kategori layanan — dipakai BERSAMA oleh pemilih layanan
 * petugas (`Dashboard/PengajuanBaru`) dan pemohon (`Pengajuan/Pilih`).
 *
 * 🔴 SATU SUMBER, DUA HALAMAN. Pemilih layanan petugas dan pemilih layanan
 * pemohon menampilkan daftar yang sama; kalau petanya disalin ke masing-masing,
 * keduanya akan menyimpang pada perubahan berikutnya — kategori baru cuma
 * diberi warna di satu sisi, dan dua halaman berisi layanan yang sama mulai
 * terlihat seperti dua aplikasi berbeda.
 *
 * Dipakai TERBATAS: **glif ikon** di kartu, tab yang sedang aktif, dan lencana
 * jumlahnya. Badan kartu tetap putih dan kotak di belakang ikon tetap
 * `bg-brand/10` untuk semua kategori — yang berbeda hanya warna gambar
 * ikonnya. Kartu berwarna penuh membuat halaman ramai dan melemahkan
 * satu-satunya warna yang harus menonjol, yaitu penanda layanan tidak aktif.
 *
 * 🔴 Kelasnya ditulis UTUH, bukan dirakit (`bg-${w}-100`). Tailwind memindai
 * kode sebagai teks; kelas yang baru terbentuk saat runtime tidak pernah ikut
 * ter-build, dan hasilnya elemen tanpa warna sama sekali — tanpa galat apa pun.
 * Ini bukan teori: kelas yang dirakit sudah pernah menghasilkan elemen tanpa
 * warna sama sekali, tanpa satu pun galat yang menandainya.
 *
 * ⚠️ Berkas ini `.js`, bukan `.jsx`, sementara `app.css` cuma menyebut
 * `@source '../**\/*.jsx'`. Itu AMAN: `@source` MENAMBAH ke pemindaian otomatis
 * Tailwind v4, bukan menggantikannya — diperiksa langsung pada CSS hasil build,
 * kelas yang hanya ada di `lib/statistik-kartu.js` pun ikut terbit.
 *
 * `all` sengaja memakai warna merek: ia bukan kategori, melainkan "semuanya".
 */
export const WARNA_KATEGORI = {
  all: {
    tab: 'border-brand/40 bg-brand/10 text-brand',
    ikon: 'text-brand',
    hitung: 'bg-brand/15 text-brand',
  },
  akta: {
    tab: 'border-violet-300 bg-violet-100 text-violet-700',
    ikon: 'text-violet-600',
    hitung: 'bg-violet-200/70 text-violet-700',
  },
  kk: {
    tab: 'border-amber-300 bg-amber-100 text-amber-700',
    ikon: 'text-amber-600',
    hitung: 'bg-amber-200/70 text-amber-700',
  },
  identitas: {
    tab: 'border-emerald-300 bg-emerald-100 text-emerald-700',
    ikon: 'text-emerald-600',
    hitung: 'bg-emerald-200/70 text-emerald-700',
  },
  pindah: {
    tab: 'border-cyan-300 bg-cyan-100 text-cyan-700',
    ikon: 'text-cyan-600',
    hitung: 'bg-cyan-200/70 text-cyan-700',
  },
  data: {
    tab: 'border-rose-300 bg-rose-100 text-rose-700',
    ikon: 'text-rose-600',
    hitung: 'bg-rose-200/70 text-rose-700',
  },
};

/** Kategori tak dikenal tetap tampil rapi, bukan tanpa warna. */
export const WARNA_NETRAL = {
  tab: 'border-slate-300 bg-slate-100 text-slate-700',
  ikon: 'text-slate-600',
  hitung: 'bg-slate-200 text-slate-600',
};

export const warnaKategori = (id) => WARNA_KATEGORI[id] ?? WARNA_NETRAL;
