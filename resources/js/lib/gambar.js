/**
 * Penyiapan gambar sebelum dikirim ke server.
 * Port dari `lib/kirim-gambar.ts` (portal Next.js, commit 999099f).
 *
 * 🔴 KENAPA ADA: mod_security (WAF bawaan cPanel) MEMBLOKIR badan permintaan
 * yang memuat data URI base64. Permintaannya ditolak sebelum sampai ke aplikasi —
 * yang terlihat di browser adalah halaman 404, bukan galat JSON, jadi gejalanya
 * menyesatkan: situsnya sehat tapi pendaftaran mati total.
 *
 * Terukur langsung di produksi SAIBATIN (13 Agu 2026):
 *   {"nik":"1"}                                   -> 400 JSON  (sampai ke aplikasi)
 *   {"foto":"<1KB huruf biasa>"}                  -> 400 JSON
 *   {"foto":"data:image/"}                        -> 400 JSON
 *   {"foto":"base64,xx"}                          -> 400 JSON
 *   {"foto":"data:image/jpeg;base64,<isi>"}       -> 404 HTML  ← DIBLOKIR
 *   {"foto":"<base64 polos 900 KB>"}              -> 400 JSON  (lolos)
 *
 * Jadi yang memicu bukan ukurannya, melainkan tanda tangan data URI-nya. Karena
 * itu awalan `data:...;base64,` dibuang di klien, dan server menerima kedua
 * bentuk (lihat `App\Services\FotoProfil`).
 *
 * ⚠️ Batas terpisah: Apache menolak badan ≥ 1 MB dengan 413. Total selfie + KTP
 * harus tetap di bawah itu — karena itu KTP dikecilkan di browser.
 */

/** Buang awalan data URI sehingga tersisa base64 polos. Aman dipanggil dua kali. */
export function tanpaAwalanDataUrl(nilai) {
  if (!nilai) return nilai;
  const i = nilai.indexOf('base64,');
  return i === -1 ? nilai : nilai.slice(i + 'base64,'.length);
}
