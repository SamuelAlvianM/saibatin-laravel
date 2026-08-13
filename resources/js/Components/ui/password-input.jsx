import { useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';

import { Input } from '@/Components/ui/input';
import { cn } from '@/lib/utils';

/**
 * Input sandi dengan tombol mata di dalam field (kanan) untuk
 * menampilkan/menyembunyikan isian. Sakelarnya berlaku per-field.
 */
export function PasswordInput({ className, ...props }) {
  const [show, setShow] = useState(false);

  return (
    <div className="relative">
      <Input type={show ? 'text' : 'password'} className={cn('pr-10', className)} {...props} />
      <button
        type="button"
        tabIndex={-1}
        onClick={() => setShow((s) => !s)}
        title={show ? 'Sembunyikan sandi' : 'Tampilkan sandi'}
        aria-label={show ? 'Sembunyikan sandi' : 'Tampilkan sandi'}
        className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition-colors hover:text-primary"
      >
        {show ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
      </button>
    </div>
  );
}
