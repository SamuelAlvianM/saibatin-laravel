import {
  Baby, Book, Clock, FileText, Gift, Heart, Home, IdCard, MapPin, Printer,
  ScanLine, ScrollText, ShieldCheck, Smile, User, UserCircle, UserPlus, Users, Zap,
} from 'lucide-react';

/**
 * Peta nama ikon → komponen.
 *
 * 🔴 JANGAN diganti `import * as Ikon from 'lucide-react'`. Impor bintang
 * menarik SELURUH set ikon lucide ke dalam bundel — diukur: 367 KB → 1.280 KB
 * (3,5×). Vite tidak bisa menyingkirkan yang tak terpakai karena aksesnya
 * dinamis (`Ikon[nama]`).
 *
 * Ikon disimpan sebagai NAMA di `config/layanan.php` supaya berkas config-nya
 * tetap aman dibaca dari sisi server; resolusinya di sini.
 *
 * Menambah layanan dengan ikon baru? Tambahkan juga di daftar impor di atas.
 */
const PETA = {
  Baby, Book, FileText, Heart, Home, IdCard, MapPin, Printer,
  ScrollText, UserPlus, Users, Zap,
  // Dipakai kartu "janji pelayanan" di tab Maklumat (config/konten.php).
  Clock, Gift, ShieldCheck, Smile,
  // Dipakai kartu statistik demografi beranda (config/konten.php kartu_beranda).
  ScanLine, User, UserCircle,
};

export function ikon(nama) {
  return PETA[nama] ?? FileText;
}
