import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Gabungkan className bersyarat lalu selesaikan bentrokan utilitas Tailwind
 * (yang terakhir menang). Dipakai seluruh komponen di `Components/ui/`.
 */
export function cn(...inputs) {
  return twMerge(clsx(inputs));
}
