/**
 * Preferensi aksesibilitas — port `lib/a11y.ts`.
 *
 * Disimpan sebagai SATU objek JSON di localStorage lalu diterapkan ke `<html>`
 * sebagai kelas `a11y-*` + inline style (`font-size`, `--a11y-filter`). Efek
 * visualnya sendiri ada terpusat di `resources/css/app.css`.
 *
 * 🔴 Skrip anti-kedip (`A11Y_INIT`) sengaja dijalankan sebelum body dirender,
 * dari Blade. Tanpa itu halaman tampil sekejap dengan gaya normal lalu berubah
 * — persis bagi pengguna yang paling terganggu oleh perubahan mendadak.
 */

export const KUNCI_A11Y = 'saibatin-a11y';

/** Skala font global dalam persen; indeks 1 = 100% (bawaan). */
export const LANGKAH_FONT = [90, 100, 110, 125, 150, 175, 200];
export const FONT_BAWAAN_IDX = 1;

/** Tingkat spasi teks: 0 = normal, 1 = lebar, 2 = sangat lebar. */
export const SPASI_MAKS = 2;

export const PREFS_BAWAAN = {
  v: 2,
  fontIdx: FONT_BAWAAN_IDX,
  spacing: 0,
  dyslexia: false,
  contrast: false,
  invert: false,
  grayscale: false,
  lightBg: false,
  highlightLinks: false,
  readingGuide: false,
  bigCursor: false,
  noMotion: false,
  ttsClick: false,
  ttsHover: false,
};

export function bacaPrefs() {
  try {
    const mentah = localStorage.getItem(KUNCI_A11Y);
    if (!mentah) return { ...PREFS_BAWAAN };
    const o = JSON.parse(mentah);
    if (!o || typeof o !== 'object') return { ...PREFS_BAWAAN };
    return { ...PREFS_BAWAAN, ...o, v: 2 };
  } catch {
    return { ...PREFS_BAWAAN };
  }
}

export function simpanPrefs(p) {
  try {
    localStorage.setItem(KUNCI_A11Y, JSON.stringify(p));
  } catch {
    // Storage penuh atau diblokir — efeknya tetap diterapkan, hanya tidak
    // bertahan antar-halaman. Lebih baik daripada menjatuhkan widget.
  }
}

/** Terapkan preferensi ke `<html>`. Logikanya kembar dengan `A11Y_INIT`. */
export function terapkanPrefs(p) {
  const root = document.documentElement;

  const idx = Math.min(Math.max(p.fontIdx, 0), LANGKAH_FONT.length - 1);
  root.style.fontSize = idx === FONT_BAWAAN_IDX ? '' : `${LANGKAH_FONT[idx]}%`;

  // Ketiga filter dikomposisikan jadi SATU custom property — menumpuk tiga
  // properti `filter` di elemen berbeda akan melipatgandakan biaya render.
  const filter = [
    p.contrast && 'contrast(1.2)',
    p.grayscale && 'grayscale(1)',
    p.invert && 'invert(1) hue-rotate(180deg)',
  ].filter(Boolean).join(' ');

  if (filter) root.style.setProperty('--a11y-filter', filter);
  else root.style.removeProperty('--a11y-filter');

  root.classList.toggle('a11y-contrast', p.contrast);
  root.classList.toggle('a11y-invert', p.invert);
  root.classList.toggle('a11y-underline', p.highlightLinks);
  root.classList.toggle('a11y-dyslexia', p.dyslexia);
  root.classList.toggle('a11y-lightbg', p.lightBg);
  root.classList.toggle('a11y-cursor', p.bigCursor);
  root.classList.toggle('a11y-no-motion', p.noMotion);

  root.classList.remove('a11y-spacing-1', 'a11y-spacing-2');
  if (p.spacing > 0) root.classList.add(`a11y-spacing-${Math.min(p.spacing, SPASI_MAKS)}`);
}
