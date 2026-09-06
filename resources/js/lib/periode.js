/**
 * Periode data kependudukan: satu tahun + satu semester (1 atau 2).
 * Cerminan `App\Support\PeriodeDemografi` — keduanya HARUS sepakat, kalau tidak
 * label di layar dan label di berkas ekspor bisa menyebut periode berbeda.
 */

const ROMAWI = { 1: 'I', 2: 'II' };

/** "Semester II 2024" */
export const labelPeriode = (tahun, semester) =>
  `Semester ${ROMAWI[semester] ?? semester} ${tahun}`;

/** "DKB Semester II 2024" — badge beranda, menyebut sumber datanya. */
export const labelPeriodePanjang = (tahun, semester) =>
  `DKB ${labelPeriode(tahun, semester)}`;

/** Dua periode menunjuk hal yang sama? */
export const periodeSama = (a, b) =>
  !!a && !!b && a.tahun === b.tahun && a.semester === b.semester;

/**
 * Kunci ringkas satu periode: "2024-2".
 *
 * Dipakai sebagai kunci objek/Set di sisi peramban — mis. wadah periode mana
 * yang sedang terbuka, dan hitungan kategori milik periode mana. Satu fungsi
 * supaya "2024-2" tidak pernah dieja berbeda di dua tempat.
 */
export const kunciPeriode = (p) => `${p.tahun}-${p.semester}`;

/** `?tahun=…&semester=…`, atau string kosong bila periodenya belum ada. */
export const kueriPeriode = (periode) =>
  periode ? `tahun=${periode.tahun}&semester=${periode.semester}` : '';

/**
 * Semester yang MASUK AKAL untuk periode baru, dari sudut pandang hari ini.
 *
 * ⚠️ Ini cuma tebakan awal isian, bukan aturan. Dinas bebas mengunggah bulan
 * apa saja — berkas semester I bisa datang Juni, bisa Agustus, bisa Januari
 * tahun berikutnya — jadi petugas selalu boleh menggantinya.
 */
export function periodeDugaan(kini = new Date()) {
  const bulan = kini.getMonth() + 1;

  return bulan <= 6
    ? { tahun: kini.getFullYear() - 1, semester: 2 }
    : { tahun: kini.getFullYear(), semester: 1 };
}

/** Tahun yang boleh dipilih: dari yang sudah ada di data sampai tahun depan. */
export function tahunPilihan(tersedia = [], kini = new Date()) {
  const batasAtas = kini.getFullYear() + 1;
  const adaTahun = tersedia.map((p) => p.tahun);
  const terkecil = Math.min(batasAtas - 5, ...(adaTahun.length ? adaTahun : [batasAtas]));

  const daftar = [];
  for (let t = batasAtas; t >= terkecil; t -= 1) daftar.push(t);

  return daftar;
}

/**
 * Gabungkan dua daftar periode jadi satu, terbaru dulu, tanpa kembar.
 *
 * ⚠️ Jawaban API hanya memuat periode untuk SATU kategori. Menimpa daftar
 * begitu saja akan menghilangkan periode yang cuma dipunyai kategori lain —
 * dan periode itu lenyap dari pemilih meski datanya ada.
 */
export function gabungPeriode(...daftar) {
  const peta = new Map();

  for (const d of daftar) {
    for (const p of d ?? []) {
      const k = kunciPeriode(p);
      const ada = peta.get(k);
      peta.set(k, {
        tahun: p.tahun,
        semester: p.semester,
        baris: Math.max(ada?.baris ?? 0, p.baris ?? 0),
      });
    }
  }

  return [...peta.values()].sort(
    (a, b) => b.tahun - a.tahun || b.semester - a.semester,
  );
}
