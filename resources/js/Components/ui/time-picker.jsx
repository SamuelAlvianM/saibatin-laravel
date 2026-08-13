import { useEffect, useRef, useState } from 'react';
import { Clock } from 'lucide-react';

import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

const HOURS = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));
const MINUTES = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'));

const isJam = (s) => /^([01]\d|2[0-3]):[0-5]\d$/.test(s);

/**
 * Masker ketik: ambil angkanya saja lalu sisipkan ":" otomatis, sehingga
 * pengguna cukup mengetik "1600" dan menjadi "16:00".
 */
function masker(input) {
  const angka = input.replace(/\D/g, '').slice(0, 4);
  if (angka.length <= 2) return angka;
  return `${angka.slice(0, 2)}:${angka.slice(2)}`;
}

/** Kolom angka (jam/menit) yang bisa discroll, item aktif disorot. */
function ScrollColumn({ label, items, active, onPick }) {
  const listRef = useRef(null);

  useEffect(() => {
    if (!active || !listRef.current) return;
    const el = listRef.current.querySelector(`[data-value="${active}"]`);
    el?.scrollIntoView({ block: 'center' });
  }, [active]);

  return (
    <div className="flex flex-col">
      <p className="pb-1 text-center text-xs font-medium text-muted-foreground">{label}</p>
      <div ref={listRef} className="h-52 w-16 overflow-y-auto rounded-md border border-input">
        {items.map((v) => (
          <button
            key={v}
            type="button"
            data-value={v}
            onClick={() => onPick(v)}
            className={cn(
              'block w-full px-2 py-1.5 text-center text-sm transition-colors hover:bg-accent',
              v === active && 'bg-primary text-primary-foreground hover:bg-primary',
            )}
          >
            {v}
          </button>
        ))}
      </div>
    </div>
  );
}

/**
 * Pemilih jam (HH:mm) — bisa DIKETIK langsung (masker "1600" → "16:00") atau
 * dipilih lewat popover dua kolom (jam & menit). Pengganti input type="time".
 */
export function TimePicker({ id, value, onChange, placeholder = 'Pilih jam', disabled, className }) {
  const [open, setOpen] = useState(false);
  // Teks mentah yang sedang diketik — dipisah dari `value` supaya bisa
  // mengetik sebagian ("16") tanpa nilainya ikut berubah.
  const [teks, setTeks] = useState(() => (isJam(value) ? value : ''));
  const [hour, minute] = isJam(value) ? value.split(':') : [undefined, undefined];

  useEffect(() => {
    setTeks(isJam(value) ? value : '');
  }, [value]);

  const terimaTeks = (mentah) => {
    const bertopeng = masker(mentah);
    setTeks(bertopeng);
    if (!bertopeng) {
      onChange('');
      return;
    }
    if (isJam(bertopeng)) onChange(bertopeng);
  };

  // Ketikan setengah jadi dikembalikan ke nilai sah terakhir.
  const rapikan = () => setTeks(isJam(value) ? value : '');

  const pick = (h, m) => {
    onChange(`${h ?? hour ?? '00'}:${m ?? minute ?? '00'}`);
  };

  return (
    <div className={cn('relative', className)}>
      <Input
        id={id}
        value={teks}
        onChange={(e) => terimaTeks(e.target.value)}
        onBlur={rapikan}
        disabled={disabled}
        placeholder={placeholder === 'Pilih jam' ? 'jj:mm' : placeholder}
        inputMode="numeric"
        autoComplete="off"
        aria-label={placeholder}
        className="h-9 w-full pr-9 tabular-nums"
      />
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            type="button"
            variant="ghost"
            size="icon"
            disabled={disabled}
            aria-label="Buka pemilih jam"
            className="absolute right-0 top-0 h-9 w-9 text-muted-foreground hover:bg-transparent hover:text-foreground"
          >
            <Clock className="h-4 w-4" />
          </Button>
        </PopoverTrigger>
        {/* pointer-events-auto: popover di-portal ke <body>, sedangkan Sheet/Dialog
            mengunci `body { pointer-events: none }` → tanpa ini kolom jam/menit
            tak bisa diklik maupun discroll saat picker dibuka di dalam panel. */}
        <PopoverContent className="pointer-events-auto w-auto p-3" align="end">
          <div className="flex gap-2">
            <ScrollColumn label="Jam" items={HOURS} active={hour} onPick={(v) => pick(v, undefined)} />
            <ScrollColumn
              label="Menit"
              items={MINUTES}
              active={minute}
              onPick={(v) => {
                pick(undefined, v);
                setOpen(false);
              }}
            />
          </div>
        </PopoverContent>
      </Popover>
    </div>
  );
}
