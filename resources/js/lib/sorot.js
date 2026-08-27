import { useEffect, useState } from 'react';

/**
 * Sorotan baris saat halaman dibuka dari notifikasi (`?sorot=<id>`).
 *
 * Alurnya: lonceng menambahkan `?sorot=<refId>` ke tautan notifikasi →
 * rute Inertia meneruskannya sebagai prop → hook ini menggulir ke barisnya lalu
 * memberinya kelas `.baris-disorot` (denyut oranye, lihat `resources/css/app.css`).
 * Sorotan hilang sendiri setelah 2,6 detik: ia penunjuk arah, bukan status.
 *
 * 🔴 DITULIS SEKALI DI SINI karena polanya dibutuhkan EMPAT halaman
 * (permohonan, pengaduan, kritik-saran, akun) dan tiga di antaranya sempat
 * tidak punya sama sekali — petugas mendarat di halaman yang benar tanpa tahu
 * baris mana yang dimaksud. Menyalin efeknya per halaman berarti tiga peluang
 * untuk lupa membersihkan timernya.
 *
 * `prefix` harus sama dengan `id` elemen barisnya, mis. `permohonan-12`.
 * `siap` = data sudah selesai dimuat; menggulir sebelum itu tidak menemukan apa pun.
 * `penanda` = nilai yang berubah saat isi tabel berganti (biasanya `items`),
 * supaya gulirannya diulang setelah baris yang dituju benar-benar dirender.
 */
export function useSorot(awal, prefix, siap, penanda) {
  const [sorotId, setSorotId] = useState(() => {
    const n = Number(awal);
    return Number.isFinite(n) && n > 0 ? n : null;
  });

  useEffect(() => {
    if (sorotId == null || !siap) return undefined;

    document
      .getElementById(`${prefix}-${sorotId}`)
      ?.scrollIntoView({ behavior: 'smooth', block: 'center' });

    const t = setTimeout(() => setSorotId(null), 2600);
    return () => clearTimeout(t);
  }, [sorotId, siap, prefix, penanda]);

  return sorotId;
}

/** Kelas baris — dipakai langsung di `className` tabel. */
export const kelasSorot = (aktif) => (aktif ? 'baris-disorot' : '');
