import { useEffect, useMemo, useRef, useState } from 'react';
import { Check, ChevronsUpDown, Search } from 'lucide-react';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { cn } from '@/lib/utils';

/**
 * Diporting dari portal Next.js saudara (components/statistik/pilihan-dropdown.tsx).
 *
 * Dropdown bergaya portal (pengganti <select> bawaan): tombol pemicu yang
 * menampilkan pilihan saat ini, daftar berkelompok, tanda centang, dan —
 * bila `cari` aktif — kotak pencarian untuk daftar panjang (98 wilayah).
 *
 * Papan ketik: ↑/↓ berpindah, Enter memilih, Esc menutup (Radix).
 */
export function PilihanDropdown({
  id,
  nilai,
  grup,
  onPilih,
  ikon,
  cari = false,
  placeholderCari = 'Cari…',
  disabled,
  className,
}) {
  const [buka, setBuka] = useState(false);
  const [kueri, setKueri] = useState('');
  const [sorot, setSorot] = useState(0);
  const daftarRef = useRef(null);

  const terpilih = useMemo(() => {
    for (const g of grup) for (const o of g.opsi) if (o.nilai === nilai) return o;
    return null;
  }, [grup, nilai]);

  const tersaring = useMemo(() => {
    const q = kueri.trim().toLowerCase();
    if (!q) return grup;
    return grup
      .map((g) => ({ ...g, opsi: g.opsi.filter((o) => o.label.toLowerCase().includes(q)) }))
      .filter((g) => g.opsi.length);
  }, [grup, kueri]);

  const rata = useMemo(() => tersaring.flatMap((g) => g.opsi), [tersaring]);

  // Saat dibuka: sorot pilihan aktif dan gulir ke sana.
  useEffect(() => {
    if (!buka) return;
    setKueri('');
    const i = grup.flatMap((g) => g.opsi).findIndex((o) => o.nilai === nilai);
    setSorot(Math.max(0, i));
    requestAnimationFrame(() => {
      daftarRef.current?.querySelector('[data-aktif="true"]')?.scrollIntoView({ block: 'center' });
    });
  }, [buka, grup, nilai]);

  useEffect(() => {
    daftarRef.current?.querySelector(`[data-indeks="${sorot}"]`)?.scrollIntoView({ block: 'nearest' });
  }, [sorot]);

  function pilih(o) {
    setBuka(false);
    if (o.nilai !== nilai) onPilih(o.nilai);
  }

  function tombol(e) {
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setSorot((s) => Math.min(rata.length - 1, s + 1));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setSorot((s) => Math.max(0, s - 1));
    } else if (e.key === 'Enter' && rata[sorot]) {
      e.preventDefault();
      pilih(rata[sorot]);
    }
  }

  let indeks = -1;

  return (
    <Popover open={buka} onOpenChange={setBuka}>
      <PopoverTrigger asChild>
        <button
          id={id}
          type="button"
          disabled={disabled}
          aria-haspopup="listbox"
          className={cn(
            'group flex h-12 w-full cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-3.5 text-left shadow-sm transition',
            'hover:border-slate-300 focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20',
            'data-[state=open]:border-primary data-[state=open]:ring-2 data-[state=open]:ring-primary/20 disabled:cursor-wait disabled:opacity-60',
            className,
          )}
        >
          {ikon && <span className="text-primary">{ikon}</span>}
          <span className="min-w-0 flex-1 truncate text-sm font-medium text-slate-800">
            {terpilih?.label ?? 'Pilih…'}
          </span>
          <ChevronsUpDown className="h-4 w-4 shrink-0 text-slate-400 transition group-hover:text-slate-600" />
        </button>
      </PopoverTrigger>

      <PopoverContent
        align="start"
        sideOffset={6}
        className="w-[var(--radix-popover-trigger-width)] min-w-[17rem] overflow-hidden rounded-xl p-0 shadow-lg"
        onKeyDown={tombol}
      >
        {cari && (
          <div className="flex items-center gap-2 border-b border-slate-100 px-3">
            <Search className="h-4 w-4 shrink-0 text-slate-400" />
            <input
              autoFocus
              value={kueri}
              onChange={(e) => {
                setKueri(e.target.value);
                setSorot(0);
              }}
              placeholder={placeholderCari}
              className="h-11 w-full border-0 bg-transparent text-sm text-slate-800 shadow-none outline-none ring-0 placeholder:text-slate-400 focus:outline-none focus:ring-0 focus-visible:outline-none focus-visible:ring-0"
            />
          </div>
        )}

        <div ref={daftarRef} role="listbox" className="max-h-80 overflow-y-auto overscroll-contain p-1.5">
          {rata.length === 0 && <p className="px-3 py-6 text-center text-sm text-slate-500">Tidak ditemukan.</p>}
          {tersaring.map((g, gi) => (
            <div key={g.judul ?? gi} className={cn(gi > 0 && 'mt-1.5 border-t border-slate-100 pt-1.5')}>
              {g.judul && (
                <p className="px-2.5 pb-1 pt-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                  {g.judul}
                </p>
              )}
              {g.opsi.map((o) => {
                indeks += 1;
                const i = indeks;
                const aktif = o.nilai === nilai;
                return (
                  <button
                    key={o.nilai}
                    type="button"
                    role="option"
                    aria-selected={aktif}
                    data-aktif={aktif}
                    data-indeks={i}
                    onMouseEnter={() => setSorot(i)}
                    onClick={() => pilih(o)}
                    className={cn(
                      'flex w-full cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm transition-colors',
                      i === sorot ? 'bg-slate-100 text-slate-900' : 'text-slate-700',
                      aktif && 'font-semibold text-primary',
                    )}
                  >
                    <Check className={cn('h-4 w-4 shrink-0', aktif ? 'text-primary' : 'invisible')} />
                    <span className="min-w-0 flex-1 truncate">{o.label}</span>
                    {o.keterangan && kueri && (
                      <span className="shrink-0 text-xs font-normal text-slate-400">{o.keterangan}</span>
                    )}
                  </button>
                );
              })}
            </div>
          ))}
        </div>
      </PopoverContent>
    </Popover>
  );
}
