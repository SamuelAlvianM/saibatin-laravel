import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { AlertCircle, CheckCircle2, X } from 'lucide-react';

/**
 * Toast global — penampil `flash.sukses` / `flash.galat`.
 *
 * 🔴 ALASAN KOMPONEN INI ADA. `HandleInertiaRequests` sudah membagikan
 * `flash.sukses` dan `flash.galat` ke setiap halaman sejak Fase 2, TAPI tidak
 * ada satu pun komponen yang merendernya. Akibatnya pesan seperti "Password
 * berhasil direset, silakan login" (`SandiController:127`) dikirim ke klien
 * lalu hilang tanpa jejak — warga menekan tombol dan seolah tidak terjadi apa-apa.
 *
 * 🔴 SENGAJA TIDAK MEMAKAI `usePage()`. Hook itu menuntut komponen berada DI
 * DALAM pohon `<App>` Inertia, yang berarti toast harus dipasang ulang di setiap
 * layout (dashboard, pengguna, dan tiap halaman auth yang berdiri sendiri) —
 * dan yang terlewat satu, pesannya hilang lagi. Sebagai gantinya komponen ini
 * menumpang `router.on('success')`, yang bekerja di luar konteks, sehingga cukup
 * dipasang SEKALI di `app.jsx` dan berlaku untuk seluruh halaman Inertia.
 *
 * Untuk pesan sisi klien (tanpa singgah ke server) pakai `toast()` di bawah.
 */

/** Berapa lama toast bertahan sebelum menutup sendiri. */
const DURASI = { sukses: 4000, galat: 6000 };

const NAMA_EVENT = 'saibatin:toast';

/**
 * Munculkan toast dari kode klien mana pun, termasuk dari luar React.
 *
 *   toast('Data tersimpan');
 *   toast('Gagal menyimpan', 'galat');
 */
export function toast(pesan, jenis = 'sukses') {
  if (!pesan) return;
  window.dispatchEvent(new CustomEvent(NAMA_EVENT, { detail: { pesan, jenis } }));
}

const GAYA = {
  sukses: {
    Ikon: CheckCircle2,
    kotak: 'border-emerald-200 bg-white',
    ikon: 'text-emerald-600',
    garis: 'bg-emerald-500',
  },
  galat: {
    Ikon: AlertCircle,
    kotak: 'border-rose-200 bg-white',
    ikon: 'text-rose-600',
    garis: 'bg-rose-500',
  },
};

function Toast({ item, onTutup }) {
  const { Ikon, kotak, ikon, garis } = GAYA[item.jenis] ?? GAYA.sukses;

  return (
    <div
      role="status"
      aria-live="polite"
      className={`animate-in fade-in slide-in-from-bottom-3 pointer-events-auto relative flex w-full gap-2.5 overflow-hidden rounded-xl border p-3.5 shadow-lg shadow-slate-900/10 duration-300 ${kotak}`}
    >
      {/* Pita warna di tepi kiri — pembeda sukses/galat yang tetap terbaca
          bagi yang kesulitan membedakan hijau dan merah. */}
      <span className={`absolute inset-y-0 left-0 w-1 ${garis}`} aria-hidden="true" />

      <Ikon className={`mt-0.5 h-4 w-4 flex-shrink-0 ${ikon}`} aria-hidden="true" />
      <p className="min-w-0 flex-1 text-xs leading-relaxed text-slate-700">{item.pesan}</p>

      <button
        type="button"
        onClick={onTutup}
        aria-label="Tutup pemberitahuan"
        className="-mr-1 -mt-1 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
      >
        <X className="h-3.5 w-3.5" />
      </button>
    </div>
  );
}

/**
 * Dipasang sekali di `app.jsx`.
 *
 * `awal` = `flash` dari muat halaman PERTAMA. Tanpa itu, pesan pada permintaan
 * yang bukan kunjungan Inertia (mis. redirect penuh sesudah reset sandi) tidak
 * pernah muncul, karena `router.on('success')` baru menyala pada kunjungan
 * berikutnya.
 */
export default function Toaster({ awal }) {
  const [daftar, setDaftar] = useState([]);
  const nomor = useRef(0);

  const tutup = useCallback((id) => {
    setDaftar((d) => d.filter((t) => t.id !== id));
  }, []);

  const tambah = useCallback((pesan, jenis) => {
    if (!pesan) return;
    const id = ++nomor.current;
    setDaftar((d) => [...d, { id, pesan, jenis }]);
    window.setTimeout(() => tutup(id), DURASI[jenis] ?? DURASI.sukses);
  }, [tutup]);

  const dariFlash = useCallback((flash) => {
    if (!flash) return;
    tambah(flash.sukses, 'sukses');
    tambah(flash.galat, 'galat');
  }, [tambah]);

  useEffect(() => {
    dariFlash(awal);

    // `router.on` mengembalikan fungsi pembersihnya sendiri.
    const lepasInertia = router.on('success', (e) => dariFlash(e.detail.page?.props?.flash));

    const dengar = (e) => tambah(e.detail?.pesan, e.detail?.jenis ?? 'sukses');
    window.addEventListener(NAMA_EVENT, dengar);

    return () => {
      lepasInertia();
      window.removeEventListener(NAMA_EVENT, dengar);
    };
    // Sengaja sekali jalan: `awal` hanya bermakna pada muat pertama.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  if (daftar.length === 0) return null;

  return (
    // `bottom-20` di ponsel supaya tidak tertimbun bottom-nav dashboard.
    <div
      className="pointer-events-none fixed inset-x-4 bottom-20 z-[60] flex flex-col gap-2 sm:inset-x-auto sm:bottom-6 sm:right-6 sm:w-80"
      style={{ paddingBottom: 'env(safe-area-inset-bottom)' }}
    >
      {daftar.map((item) => (
        <Toast key={item.id} item={item} onTutup={() => tutup(item.id)} />
      ))}
    </div>
  );
}
