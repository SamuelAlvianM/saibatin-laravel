import { useMemo, useRef, useState } from 'react';
import { Check, ChevronDown, Search, X } from 'lucide-react';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';

/**
 * Pemilih banyak isian dengan pencarian — dipakai untuk "Data yang Perlu
 * Dilengkapi" pada penolakan permohonan.
 *
 * 🔴 Bukan Radix `Select`. Komponen itu bernilai TUNGGAL, menutup setiap kali
 * diklik, dan menolak nilai kosong; memaksanya jadi pilih-banyak berarti
 * melawan seluruh perilaku bawaannya. Popover + Checkbox jauh lebih sedikit
 * lawannya, dan itulah pola yang sudah dipakai saringan daftar permohonan.
 *
 * 🔴 LABEL DIBIARKAN MEMBUNGKUS, TIDAK DIPOTONG. Permintaan tegas dari dinas:
 * "Nama Len…" tidak boleh terjadi. Isinya nama isian formulir resmi — petugas
 * harus bisa membaca utuh sebelum memilih, karena label itulah yang nanti
 * terbaca warga sebagai pernyataan dinas. Karena itu `items-start` +
 * `leading-snug`, tanpa `truncate` di mana pun pada barisnya.
 *
 * Jumlah kolom naik mengikuti lebar layar (1 → 2 → 3), bukan dipatok — daftar
 * isian bisa mencapai belasan baris, dan satu kolom membuatnya perlu digulir
 * jauh sementara empat kolom di layar sempit membuat labelnya menyempit sampai
 * terbaca dua-tiga huruf per baris.
 *
 * ⚠️ Kelas grid ditulis UTUH di `KOLOM`, tidak dirakit. Tailwind memindai kode
 * sebagai teks biasa; `sm:grid-cols-${n}` tidak pernah ikut ter-build dan
 * hasilnya kolomnya menumpuk ke bawah tanpa satu pun galat.
 *
 * @param {string[]} nilai label yang sedang terpilih
 * @param {(v: string[]) => void} onUbah
 * @param {Array<{judul: string, item: string[]}>} grup pilihan, sudah dikelompokkan
 * @param {boolean} galat tandai merah bila validasi gagal
 */
const KOLOM = 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3';

export default function PilihRincian({
  nilai = [], onUbah, grup = [], galat = false, nonaktif = false,
  placeholder = 'Pilih data yang perlu dilengkapi…',
}) {
  const [buka, setBuka] = useState(false);
  const [cari, setCari] = useState('');
  const kotakCari = useRef(null);

  const grupTersaring = useMemo(() => {
    const q = cari.trim().toLowerCase();

    if (q === '') return grup;

    return grup
      .map((g) => ({ ...g, item: g.item.filter((s) => s.toLowerCase().includes(q)) }))
      .filter((g) => g.item.length > 0);
  }, [grup, cari]);

  const jumlahTampil = grupTersaring.reduce((n, g) => n + g.item.length, 0);

  const alihkan = (label) =>
    onUbah(nilai.includes(label) ? nilai.filter((v) => v !== label) : [...nilai, label]);

  return (
    <div>
      <Popover
        open={buka}
        onOpenChange={(o) => {
          setBuka(o);
          if (! o) setCari('');
        }}
      >
        <PopoverTrigger asChild>
          <button
            type="button"
            disabled={nonaktif}
            aria-haspopup="listbox"
            aria-expanded={buka}
            className={`flex min-h-10 w-full items-center justify-between gap-2 rounded-md border bg-white px-3 py-2 text-left text-sm transition-colors disabled:opacity-50 ${
              galat
                ? 'border-rose-400 text-rose-700 focus:ring-2 focus:ring-rose-200'
                : 'border-slate-200 text-slate-700 hover:border-slate-300'
            }`}
          >
            <span className={nilai.length === 0 ? 'text-slate-400' : ''}>
              {nilai.length === 0
                ? placeholder
                : `${nilai.length} data dipilih`}
            </span>
            <ChevronDown className="h-4 w-4 shrink-0 text-slate-400" />
          </button>
        </PopoverTrigger>

        {/* Lebar mengikuti pemicunya supaya kolomnya punya ruang nyata; tanpa
            ini Popover memakai lebar isinya dan tiga kolom jadi mustahil. */}
        <PopoverContent
          align="start"
          className="w-[var(--radix-popover-trigger-width)] max-w-[min(48rem,92vw)] p-0"
          onOpenAutoFocus={(e) => {
            // Fokus langsung ke pencarian: daftar isian bisa belasan baris, dan
            // mengetik adalah cara tercepat menemukannya.
            e.preventDefault();
            kotakCari.current?.focus();
          }}
        >
          <div className="border-b border-slate-100 p-2">
            <div className="relative">
              <Search className="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <Input
                ref={kotakCari}
                value={cari}
                onChange={(e) => setCari(e.target.value)}
                placeholder="Cari isian atau lampiran…"
                className="h-9 pl-8"
              />
            </div>
          </div>

          <div className="max-h-80 overflow-y-auto p-2">
            {jumlahTampil === 0 ? (
              <p className="px-2 py-6 text-center text-sm text-slate-400">
                Tidak ada isian yang cocok.
              </p>
            ) : (
              grupTersaring.map((g) => (
                <div key={g.judul} className="mb-2 last:mb-0">
                  <p className="px-1 pb-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">
                    {g.judul}
                  </p>
                  <div className={`grid gap-x-3 gap-y-1 ${KOLOM}`}>
                    {g.item.map((label) => (
                      <label
                        key={label}
                        className="flex cursor-pointer items-start gap-2 rounded-md px-2 py-1.5 text-sm leading-snug text-slate-700 hover:bg-slate-50"
                      >
                        <Checkbox
                          className="mt-0.5 shrink-0"
                          checked={nilai.includes(label)}
                          onCheckedChange={() => alihkan(label)}
                        />
                        <span className="min-w-0">{label}</span>
                      </label>
                    ))}
                  </div>
                </div>
              ))
            )}
          </div>

          <div className="flex items-center justify-between gap-2 border-t border-slate-100 px-3 py-2">
            <span className="text-xs text-slate-400">{nilai.length} dipilih</span>
            <div className="flex items-center gap-3">
              {nilai.length > 0 && (
                <button type="button" onClick={() => onUbah([])}
                        className="text-xs font-medium text-slate-500 hover:text-slate-700 hover:underline">
                  Bersihkan
                </button>
              )}
              <button type="button" onClick={() => setBuka(false)}
                      className="inline-flex items-center gap-1 text-xs font-medium text-brand hover:underline">
                <Check className="h-3.5 w-3.5" />Selesai
              </button>
            </div>
          </div>
        </PopoverContent>
      </Popover>

      {/* Yang terpilih ditampilkan di LUAR dropdown sebagai keping yang bisa
          dilepas satu per satu. Tanpa ini petugas harus membuka dropdown lagi
          hanya untuk memastikan apa yang sudah ia pilih — dan daftar itulah
          yang akan dibaca warga, jadi harus terlihat sebelum disimpan. */}
      {nilai.length > 0 && (
        <ul className="mt-2 flex flex-wrap gap-1.5">
          {nilai.map((label) => (
            <li key={label}>
              <span className="inline-flex items-start gap-1.5 rounded-md border border-brand/30 bg-brand/5 py-1 pl-2.5 pr-1.5 text-xs leading-snug text-brand">
                {label}
                <button type="button" onClick={() => alihkan(label)}
                        aria-label={`Batalkan ${label}`}
                        className="mt-px rounded p-0.5 text-brand/60 transition-colors hover:bg-brand/10 hover:text-brand">
                  <X className="h-3 w-3" />
                </button>
              </span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
