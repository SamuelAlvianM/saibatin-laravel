/**
 * Mengenali kategori demografi dari NAMA BERKAS yang diunggah.
 *
 * 🔴 KENAPA ADA. Dinas menerima satu paket berkas DKB sekaligus dan
 * mengunggahnya bersama-sama; petugas tidak menghafal berkas mana milik
 * kategori mana. Selama kategorinya harus ditunjuk lebih dulu lewat tombol
 * unggah pada kartunya masing-masing, salah taruh cuma soal waktu — dan salah
 * taruh berarti data pekerjaan tertimpa data pendidikan tanpa peringatan apa
 * pun, tanpa cara mengembalikannya.
 *
 * ⚠️ Nama berkas Dukcapil TIDAK seragam antar kabupaten: ada AGR_DRH_DUSUN, ada
 * AGR_GOL_DRH; ada AGR_PEKERJAAN, ada AGR_PKRJN_DUSUN. Karena itu polanya
 * longgar, dan nama kategori dalam bahasa Indonesia ikut dikenali — dinas yang
 * menamai berkasnya sendiri ("Data Pendidikan 2027.xlsx") tetap terbaca.
 */
const POLA_BERKAS = [
  { slug: 'jenis-kelamin', pola: /(agr[_\- ]*jk)|(jenis[_\- ]*kelamin)/i },
  { slug: 'agama', pola: /(agr[_\- ]*agama)|(\bagama\b)/i },
  { slug: 'gol-darah', pola: /(agr[_\- ]*(gol[_\- ]*)?drh)|(gol(ongan)?[_\- ]*darah)/i },
  { slug: 'pekerjaan', pola: /(agr[_\- ]*(pekerjaan|pkrjn))|(\bpekerjaan\b)/i },
  { slug: 'pendidikan', pola: /(agr[_\- ]*pddkn)|(\bpendidikan\b)/i },
  { slug: 'status-kawin', pola: /(agr[_\- ]*stat[_\- ]*kwn)|(status[_\- ]*(per)?kawin)/i },
  { slug: 'kk', pola: /(agr[_\- ]*kk)|(kartu[_\- ]*keluarga)/i },
  { slug: 'wajib-ktp', pola: /(agr[_\- ]*wktp)|(wajib[_\- ]*ktp)/i },
];

/**
 * Tebak kategori dari nama berkas, dicocokkan ke `daftar` kategori yang
 * memang dikenal peladen.
 *
 * `undefined` bila tidak ada yang cocok — pemanggil WAJIB mengatakannya kepada
 * petugas, bukan menebak sembarang kategori.
 */
export function deteksiKategori(namaBerkas, daftar = []) {
  const nama = String(namaBerkas ?? '').replace(/\.xlsx$/i, '');

  // 1. Pola berkas SIAK baku — paling dapat dipercaya.
  const cocok = POLA_BERKAS.find((p) => p.pola.test(nama));
  if (cocok) {
    const kat = daftar.find((k) => k.slug === cocok.slug);
    if (kat) return kat;
  }

  /*
   * 2. Kategori buatan dinas: dicocokkan lewat NAMANYA SENDIRI.
   *
   * Kategori seperti "Penyandang Disabilitas" tidak punya pola SIAK — dinas
   * yang menamainya, dan berkasnya pun mereka namai sendiri. Membandingkan
   * bentuk slug kedua sisi membuat "Penyandang Disabilitas 2027.xlsx",
   * "penyandang_disabilitas.xlsx", dan "Data-Penyandang-Disabilitas.xlsx"
   * sama-sama terbaca.
   *
   * ⚠️ Yang TERPANJANG menang. Kalau dinas punya "Akta" dan "Akta Kelahiran",
   * berkas "Akta Kelahiran.xlsx" mengandung keduanya; mengambil yang pertama
   * ketemu berarti data akta kelahiran mendarat di kategori "Akta".
   */
  const namaSlug = slugKategori(nama);

  return daftar
    .filter((k) => k.slug.length >= 4 && slugMemuat(namaSlug, k.slug))
    .sort((a, b) => b.slug.length - a.slug.length)[0];
}

/** Judul → slug, sama persis dengan `KategoriDemografi::slug()` di peladen. */
function slugKategori(judul) {
  return String(judul)
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 60);
}

/**
 * `nama` memuat `slug` sebagai POTONGAN UTUH, bukan sekadar substring.
 *
 * 🔴 Substring polos berbahaya di sini. Kategori bernama "Akta" akan cocok
 * dengan berkas "metadata-2027.xlsx", dan berkas itu lalu MENGGANTI seluruh
 * data akta pada periodenya — diam-diam, karena impor massal tidak bertanya.
 * Dengan batas tanda hubung, "akta-kelahiran" dan "akta" cocok, "metadata"
 * tidak.
 */
function slugMemuat(nama, slug) {
  return (
    nama === slug
    || nama.startsWith(`${slug}-`)
    || nama.endsWith(`-${slug}`)
    || nama.includes(`-${slug}-`)
  );
}
