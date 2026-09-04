/**
 * Pemisah teks pengaduan dari lampiran bukti fotonya.
 *
 * 🔴 KENAPA PERLU. Formulir aspirasi menyimpan foto dengan cara menempelkan
 * URL-nya ke ujung kolom `isi`:
 *
 *     Lampu jalan mati sejak minggu lalu.
 *
 *     Bukti Foto:
 *     /uploads/pengaduan/2026-01-abc.jpg
 *
 * Dashboard mencetak `isi` apa adanya, jadi yang dilihat petugas adalah
 * ALAMAT BERKASNYA, bukan fotonya. Petugas harus menyalin teks itu ke bilah
 * alamat untuk sekadar melihat bukti yang dikirim warga — dan di daftar,
 * URL-nya ikut memakan kolom ringkasan sampai isi pengaduannya terpotong.
 *
 * ⚠️ Polanya SENGAJA ketat: hanya baris yang benar-benar berbentuk
 * `/uploads/…` berakhiran jpg/jpeg/png yang diperlakukan sebagai foto. Warga
 * bisa saja mengetik kalimat apa pun setelah kata "Bukti Foto:", dan kalimat
 * itu harus tetap terbaca sebagai teks — bukan hilang menjadi gambar rusak.
 */

/** Penanda yang ditulis FormAspirasi saat melampirkan foto. */
const PENANDA = '\n\nBukti Foto:';

const POLA_FOTO = /^\/uploads\/[^\s]+\.(jpe?g|png)$/i;

/**
 * @param {string} isi
 * @returns {{ teks: string, foto: string[] }}
 */
export function pisahBukti(isi) {
  const utuh = String(isi ?? '');
  const i = utuh.indexOf(PENANDA);

  if (i === -1) return { teks: utuh, foto: [] };

  const teks = utuh.slice(0, i).trimEnd();
  const ekor = utuh.slice(i + PENANDA.length);

  const foto = [];
  const sisa = [];

  for (const baris of ekor.split(/\r?\n/)) {
    const b = baris.trim();
    if (!b) continue;
    if (POLA_FOTO.test(b)) foto.push(b);
    else sisa.push(b);
  }

  /*
   * Baris yang bukan URL foto dikembalikan ke teks, tidak dibuang. Apa pun
   * yang ditulis warga adalah bagian dari laporannya; menghilangkannya dari
   * layar petugas berarti menyembunyikan keterangan yang mungkin justru
   * paling penting.
   */
  return {
    teks: sisa.length ? `${teks}\n\n${sisa.join('\n')}`.trim() : teks,
    foto,
  };
}

/** Ringkasan satu baris untuk kolom tabel — tanpa URL, tanpa ganti baris. */
export function ringkasIsi(isi) {
  return pisahBukti(isi).teks.replace(/\s+/g, ' ').trim();
}
