/**
 * Penulisan tanggal & jam di layar — DIPAKUKAN ke zona kantor.
 *
 * 🔴 KENAPA ADA. `new Date(iso).toLocaleString('id-ID', …)` merender memakai
 * zona PERAMBAN, bukan zona kantor. Terukur 2 Sep 2026 di laptop ber-zona
 * Asia/Jakarta: permohonan yang tercatat pukul 20.00 WIB tampil beda
 * jam di peramban ber-zona lain, sementara seluruh label di portal berkata WIB.
 *
 * Tidak ada galat, tidak ada peringatan, dan angkanya tetap terlihat wajar.
 * Justru itu yang membuatnya berbahaya: petugas di Jakarta dan petugas di
 * Labuha membaca jam berbeda untuk permohonan yang sama, dan yang tercetak di
 * tanda terima (dirender server, sudah WIT) berbeda lagi dari yang di layar.
 *
 * SATU SUMBER: zonanya datang dari `config('app.timezone')` lewat
 * `<meta name="zona-waktu">` di kedua layout. Bukan ditulis ulang di sini —
 * cermin yang bisa menyimpang adalah persoalan yang sedang dihindari.
 *
 * ⚠️ Dibaca SEKALI saat modul dimuat, bukan tiap pemanggilan: `<head>` tidak
 * berubah selama halaman hidup, dan membaca DOM di setiap baris tabel itu
 * pemborosan yang terasa pada daftar ratusan baris.
 *
 * ⚠️ Cadangannya `Asia/Jakarta`, bukan zona peramban. Kalau metanya hilang,
 * yang benar adalah tetap memakai zona kantor — bukan diam-diam kembali ke
 * perilaku lama yang justru sedang diperbaiki.
 *
 * ⚠️ INI KHUSUS TIMESTAMP DARI SERVER. Tanggal yang dipilih pengguna di
 * saringan periode (`FilterPeriode`) sengaja TIDAK memakai ini: ia bekerja
 * dalam kalender peramban dan mengirimkan tanggal itu apa adanya ke server,
 * jadi labelnya harus ikut kalender yang sama supaya tidak berselisih dengan
 * hasil saringannya sendiri.
 */

function bacaZona() {
  if (typeof document === 'undefined') return 'Asia/Jakarta';

  return document.querySelector('meta[name="zona-waktu"]')?.content || 'Asia/Jakarta';
}

export const ZONA_WAKTU = bacaZona();

/** `2 Sep 2026` */
export function tglSingkat(v) {
  return v
    ? new Date(v).toLocaleDateString('id-ID', {
      day: 'numeric', month: 'short', year: 'numeric', timeZone: ZONA_WAKTU,
    })
    : '—';
}

/** `2 Sep 2026, 20.00` */
export function tglJam(v) {
  return v
    ? new Date(v).toLocaleString('id-ID', {
      dateStyle: 'medium', timeStyle: 'short', timeZone: ZONA_WAKTU,
    })
    : '—';
}

/** `2 September 2026` — bentuk panjang untuk halaman yang lapang. */
export function tglPanjang(v) {
  return v
    ? new Date(v).toLocaleDateString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric', timeZone: ZONA_WAKTU,
    })
    : '—';
}
