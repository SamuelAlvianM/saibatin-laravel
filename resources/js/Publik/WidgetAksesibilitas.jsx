import { useCallback, useEffect, useRef, useState } from 'react';
import {
  AArrowDown, AArrowUp, Accessibility, AudioLines, Blend, Contrast, Link2,
  MousePointer2, MousePointerClick, Palette, PauseOctagon, RotateCcw, Ruler,
  StretchHorizontal, Sun, Type, Volume2, X,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  FONT_BAWAAN_IDX, KUNCI_A11Y, LANGKAH_FONT, PREFS_BAWAAN, SPASI_MAKS,
  bacaPrefs, simpanPrefs, terapkanPrefs,
} from '@/lib/a11y';

/**
 * Widget aksesibilitas — port `components/shared/accessibility-widget.tsx`
 * (spek 14 kontrol di `PROMPT-DISABILITAS.md`).
 *
 * Sasarannya tiga kelompok:
 *   • low vision / tunanetra → skala teks, spasi, kontras, invert, skala abu,
 *     latar terang, sorot tautan, kursor besar, pembacaan teks (id-ID)
 *   • disleksia / kognitif  → font ramah disleksia, garis bantu baca
 *   • vestibular            → jeda animasi
 *
 * Preferensinya bertahan di localStorage dan diterapkan SEBELUM paint oleh
 * skrip kecil di `publik/layout.blade.php` — tanpa itu halaman berkedip dari
 * tampilan normal ke tampilan pilihan pengguna, tepat pada orang yang paling
 * terganggu oleh perubahan mendadak.
 *
 * 🔴 Tombol dideklarasikan di TINGKAT MODUL (`Ubin`, `Baris`). Komponen yang
 * lahir di dalam badan komponen lain jadi tipe baru tiap render dan React
 * memasang ulang seluruh subtree — jebakan HANDOFF §5 no. 17.
 */

const LABEL_SPASI = ['Normal', 'Lebar', 'Sangat Lebar'];

/** Blok teks yang dianggap satu satuan bacaan oleh mode TTS. */
const BLOK_TEKS = 'p,h1,h2,h3,h4,h5,h6,li,td,th,label,figcaption,blockquote,a,button';

function Ubin({ aktif, onClick, ikon: Ikon, label, ket, nonaktif = false }) {
  return (
    <button type="button" onClick={onClick} disabled={nonaktif} aria-pressed={aktif} title={ket}
            className={cn(
              'flex flex-col items-center gap-1.5 rounded-xl border px-2 py-3 text-center text-[0.68rem] font-medium leading-tight transition-colors',
              nonaktif && 'cursor-not-allowed opacity-40',
              aktif
                ? 'border-brand bg-brand text-white shadow-sm'
                : 'border-slate-200 bg-white text-slate-600 hover:border-brand hover:text-brand',
            )}>
      <Ikon className="h-4 w-4" />
      {label}
    </button>
  );
}

export default function WidgetAksesibilitas() {
  const [buka, setBuka] = useState(false);
  const [prefs, setPrefs] = useState(PREFS_BAWAAN);
  const [membaca, setMembaca] = useState(false);
  const [tanpaSuaraId, setTanpaSuaraId] = useState(false);
  const suara = useRef(null);
  const panelRef = useRef(null);
  const fabRef = useRef(null);
  const garisRef = useRef(null);

  // Dibaca sesudah mount: `localStorage` tidak ada saat modul dievaluasi di
  // lingkungan tanpa window, dan nilainya sudah diterapkan skrip anti-kedip.
  useEffect(() => { setPrefs(bacaPrefs()); }, []);

  // Daftar suara peramban datang ASINKRON — pada pemuatan pertama Chrome
  // mengembalikan larik kosong, lalu menembakkan `voiceschanged`. Memilih
  // suara sekali saja berarti bahasa Indonesia tidak pernah terpakai.
  useEffect(() => {
    const synth = window.speechSynthesis;
    if (!synth) return undefined;

    const pilih = () => {
      const semua = synth.getVoices();
      if (!semua.length) return;
      const id = semua.filter((v) => /^id[-_]?/i.test(v.lang) || /indonesia|bahasa/i.test(v.name));
      suara.current = id.find((v) => /google/i.test(v.name))
        ?? id.find((v) => /natural|online/i.test(v.name))
        ?? id[0] ?? null;
      setTanpaSuaraId(id.length === 0);
    };

    pilih();
    synth.addEventListener('voiceschanged', pilih);
    return () => synth.removeEventListener('voiceschanged', pilih);
  }, []);

  const ubah = useCallback((tambalan) => {
    setPrefs((lama) => {
      const baru = { ...lama, ...(typeof tambalan === 'function' ? tambalan(lama) : tambalan) };
      terapkanPrefs(baru);
      simpanPrefs(baru);
      return baru;
    });
  }, []);

  const hentikan = useCallback(() => {
    window.speechSynthesis?.cancel();
    setMembaca(false);
  }, []);

  const bacakan = useCallback((teks) => {
    const synth = window.speechSynthesis;
    if (!synth || !teks) return;

    const u = new SpeechSynthesisUtterance(teks);
    if (suara.current) u.voice = suara.current;
    u.lang = suara.current?.lang || 'id-ID';
    u.rate = 0.95;
    u.onend = () => setMembaca(false);
    u.onerror = () => setMembaca(false);
    synth.cancel();
    synth.speak(u);
    setMembaca(true);
  }, []);

  const setelUlang = useCallback(() => {
    setPrefs({ ...PREFS_BAWAAN });
    terapkanPrefs(PREFS_BAWAAN);
    try { localStorage.removeItem(KUNCI_A11Y); } catch { /* abaikan */ }
    hentikan();
  }, [hentikan]);

  /** Bacakan seluruh halaman — atau teks yang sedang disorot, bila ada. */
  const bacakanHalaman = useCallback(() => {
    if (membaca) { hentikan(); return; }

    const utama = document.querySelector('main') ?? document.body;
    const sorotan = window.getSelection()?.toString().trim();
    const teks = (sorotan || utama?.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 6000);
    if (teks) bacakan(teks);
  }, [membaca, bacakan, hentikan]);

  // Mode "baca saat klik".
  useEffect(() => {
    if (!prefs.ttsClick) return undefined;

    const onKlik = (e) => {
      if (e.target?.closest?.('[data-widget-a11y]')) return;
      const blok = e.target?.closest?.(BLOK_TEKS) ?? e.target;
      const teks = (blok?.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 2000);
      if (teks) bacakan(teks);
    };

    document.addEventListener('click', onKlik, true);
    return () => document.removeEventListener('click', onKlik, true);
  }, [prefs.ttsClick, bacakan]);

  // Mode "baca saat diarahkan". Ada jeda 320 ms supaya blok yang cuma DILEWATI
  // kursor tidak ikut dibacakan — tanpa itu menggerakkan tetikus dari atas ke
  // bawah halaman memicu belasan pembacaan yang saling memotong.
  useEffect(() => {
    if (!prefs.ttsHover) return undefined;

    let kini = null;
    let timer;
    const lepasSorot = () => { kini?.classList.remove('a11y-tts-hover'); kini = null; };

    const onArah = (e) => {
      if (e.target?.closest?.('[data-widget-a11y]')) return;
      const blok = e.target?.closest?.(BLOK_TEKS);
      if (!blok || blok === kini) return;

      clearTimeout(timer);
      lepasSorot();

      const teks = (blok.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 2000);
      if (!teks) return;

      kini = blok;
      blok.classList.add('a11y-tts-hover');
      timer = setTimeout(() => bacakan(teks), 320);
    };

    document.addEventListener('mouseover', onArah, true);
    return () => {
      document.removeEventListener('mouseover', onArah, true);
      clearTimeout(timer);
      lepasSorot();
    };
  }, [prefs.ttsHover, bacakan]);

  // Garis bantu baca mengikuti kursor.
  useEffect(() => {
    if (!prefs.readingGuide) return undefined;
    const gerak = (e) => {
      if (garisRef.current) garisRef.current.style.top = `${e.clientY + 20}px`;
    };
    document.addEventListener('mousemove', gerak);
    return () => document.removeEventListener('mousemove', gerak);
  }, [prefs.readingGuide]);

  // Esc menutup panel (fokus balik ke tombol), Tab berputar di dalamnya.
  useEffect(() => {
    if (!buka) return undefined;

    panelRef.current?.querySelector('button')?.focus();

    const onTombol = (e) => {
      if (e.key === 'Escape') { setBuka(false); fabRef.current?.focus(); return; }
      if (e.key !== 'Tab' || !panelRef.current) return;

      const els = Array.from(panelRef.current.querySelectorAll('button:not([disabled])'));
      if (!els.length) return;

      const [awal] = els;
      const akhir = els[els.length - 1];
      if (e.shiftKey && document.activeElement === awal) { e.preventDefault(); akhir.focus(); }
      else if (!e.shiftKey && document.activeElement === akhir) { e.preventDefault(); awal.focus(); }
    };

    document.addEventListener('keydown', onTombol);
    return () => document.removeEventListener('keydown', onTombol);
  }, [buka]);

  const persen = LANGKAH_FONT[Math.min(Math.max(prefs.fontIdx, 0), LANGKAH_FONT.length - 1)];

  return (
    <div data-widget-a11y>
      {prefs.readingGuide && <div ref={garisRef} className="a11y-reading-guide" aria-hidden="true" />}

      <button ref={fabRef} type="button" onClick={() => setBuka((o) => !o)}
              aria-label="Buka menu aksesibilitas" aria-expanded={buka}
              className="fixed right-3 top-1/2 z-[69] flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-brand text-white shadow-lg shadow-brand/30 transition-transform hover:scale-105 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand/30">
        <Accessibility className="h-6 w-6" />
      </button>

      {buka && (
        <>
          <div className="fixed inset-0 z-[69] bg-slate-900/20" onClick={() => setBuka(false)} aria-hidden="true" />

          <div ref={panelRef} role="dialog" aria-label="Menu aksesibilitas"
               className="fixed right-3 top-1/2 z-[70] max-h-[90vh] w-[19rem] max-w-[calc(100vw-1.5rem)] -translate-y-1/2 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl">
            <div className="mb-4 flex items-center justify-between">
              <h2 className="flex items-center gap-2 font-semibold text-slate-900">
                <Accessibility className="h-4 w-4 text-brand" />Aksesibilitas
              </h2>
              <button type="button" onClick={() => setBuka(false)} aria-label="Tutup menu aksesibilitas"
                      className="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                <X className="h-4 w-4" />
              </button>
            </div>

            {/* Ukuran teks */}
            <div className="mb-3 rounded-xl border border-slate-200 p-3">
              <p className="mb-2 text-[0.68rem] font-bold uppercase tracking-widest text-slate-400">Ukuran Teks</p>
              <div className="flex items-center gap-2">
                <button type="button" aria-label="Perkecil teks"
                        disabled={prefs.fontIdx <= 0}
                        onClick={() => ubah((p) => ({ fontIdx: Math.max(0, p.fontIdx - 1) }))}
                        className="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition-colors hover:border-brand hover:text-brand disabled:opacity-40">
                  <AArrowDown className="h-4 w-4" />
                </button>
                <span className="flex-1 text-center text-sm font-semibold tabular-nums text-slate-700">{persen}%</span>
                <button type="button" aria-label="Perbesar teks"
                        disabled={prefs.fontIdx >= LANGKAH_FONT.length - 1}
                        onClick={() => ubah((p) => ({ fontIdx: Math.min(LANGKAH_FONT.length - 1, p.fontIdx + 1) }))}
                        className="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition-colors hover:border-brand hover:text-brand disabled:opacity-40">
                  <AArrowUp className="h-4 w-4" />
                </button>
              </div>
            </div>

            {/* Keterbacaan */}
            <div className="mb-3 grid grid-cols-3 gap-2">
              <Ubin ikon={StretchHorizontal} label={`Spasi: ${LABEL_SPASI[prefs.spacing]}`}
                    ket="Renggangkan jarak huruf, kata, dan baris"
                    aktif={prefs.spacing > 0}
                    onClick={() => ubah((p) => ({ spacing: (p.spacing + 1) % (SPASI_MAKS + 1) }))} />
              <Ubin ikon={Type} label="Font Disleksia" ket="Ganti ke huruf yang lebih mudah dibedakan"
                    aktif={prefs.dyslexia} onClick={() => ubah((p) => ({ dyslexia: !p.dyslexia }))} />
              <Ubin ikon={Link2} label="Sorot Tautan" ket="Beri garis bawah tebal & stabilo pada tautan"
                    aktif={prefs.highlightLinks} onClick={() => ubah((p) => ({ highlightLinks: !p.highlightLinks }))} />
            </div>

            {/* Warna */}
            <div className="mb-3 grid grid-cols-3 gap-2">
              <Ubin ikon={Contrast} label="Kontras Tinggi" ket="Pertajam beda terang-gelap"
                    aktif={prefs.contrast} onClick={() => ubah((p) => ({ contrast: !p.contrast }))} />
              <Ubin ikon={Blend} label="Kontras Negatif" ket="Balikkan seluruh warna halaman"
                    aktif={prefs.invert} onClick={() => ubah((p) => ({ invert: !p.invert }))} />
              <Ubin ikon={Palette} label="Skala Abu" ket="Hilangkan seluruh warna"
                    aktif={prefs.grayscale} onClick={() => ubah((p) => ({ grayscale: !p.grayscale }))} />
              <Ubin ikon={Sun} label="Latar Terang" ket="Paksa latar putih polos"
                    aktif={prefs.lightBg} onClick={() => ubah((p) => ({ lightBg: !p.lightBg }))} />
              <Ubin ikon={Ruler} label="Garis Bantu Baca" ket="Garis mendatar yang mengikuti kursor"
                    aktif={prefs.readingGuide} onClick={() => ubah((p) => ({ readingGuide: !p.readingGuide }))} />
              <Ubin ikon={MousePointer2} label="Kursor Besar" ket="Perbesar penunjuk tetikus"
                    aktif={prefs.bigCursor} onClick={() => ubah((p) => ({ bigCursor: !p.bigCursor }))} />
            </div>

            {/* Gerak & suara */}
            <div className="mb-3 grid grid-cols-3 gap-2">
              <Ubin ikon={PauseOctagon} label="Jeda Animasi" ket="Hentikan seluruh animasi & transisi"
                    aktif={prefs.noMotion} onClick={() => ubah((p) => ({ noMotion: !p.noMotion }))} />
              <Ubin ikon={AudioLines} label="Baca Saat Diarahkan" ket="Bacakan teks yang ditunjuk kursor"
                    aktif={prefs.ttsHover} nonaktif={!window.speechSynthesis}
                    onClick={() => ubah((p) => ({ ttsHover: !p.ttsHover, ttsClick: false }))} />
              <Ubin ikon={MousePointerClick} label="Baca Saat Klik" ket="Bacakan teks yang diklik"
                    aktif={prefs.ttsClick} nonaktif={!window.speechSynthesis}
                    onClick={() => ubah((p) => ({ ttsClick: !p.ttsClick, ttsHover: false }))} />
            </div>

            <button type="button" onClick={bacakanHalaman} disabled={!window.speechSynthesis}
                    className={cn(
                      'mb-2 flex w-full items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-40',
                      membaca
                        ? 'border-brand bg-brand text-white'
                        : 'border-slate-200 text-slate-600 hover:border-brand hover:text-brand',
                    )}>
              <Volume2 className="h-4 w-4" />
              {membaca ? 'Hentikan Pembacaan' : 'Bacakan Halaman Ini'}
            </button>

            {tanpaSuaraId && (
              <p className="mb-2 rounded-lg bg-amber-50 px-3 py-2 text-[0.68rem] leading-relaxed text-amber-700">
                Peramban ini belum punya suara Bahasa Indonesia, jadi teks akan dibacakan
                dengan pelafalan bahasa lain. Tambahkan paket suara Indonesia di pengaturan
                sistem Anda untuk hasil terbaik.
              </p>
            )}

            <button type="button" onClick={setelUlang}
                    className="flex w-full items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800">
              <RotateCcw className="h-4 w-4" />Kembalikan ke Semula
            </button>
          </div>
        </>
      )}
    </div>
  );
}
