import { useCallback, useEffect, useRef, useState } from 'react';
import {
  ChevronLeft, ChevronRight, Download, Maximize2, RotateCcw, RotateCw, X, ZoomIn, ZoomOut,
} from 'lucide-react';

/**
 * Penampil gambar layar penuh: zoom, putar, geser, unduh, dan pindah
 * antar-berkas. Port dari `components/shared/image-viewer.tsx`.
 *
 * 🔴 DIPAKAI DI SETIAP TEMPAT YANG MENAMPILKAN GAMBAR UNGGAHAN — jangan
 * menulis modal gambar sendiri lagi. Sampai 17 Agu 2026 docblock ini mengklaim
 * "dipakai di tiga tempat" padahal kenyataannya hanya SATU (detail akun);
 * enam tempat lain memakai modal seadanya yang cuma menampilkan gambar polos
 * tanpa zoom, tanpa putar, tanpa unduh. Foto KTP dari ponsel sering miring dan
 * pratinjau kecil tidak cukup untuk memastikan NIK-nya terbaca, jadi
 * ketiadaan kontrol itu bukan soal kenyamanan.
 *
 * Tempat pemakaiannya sekarang:
 *   Dashboard/Permohonan  berkas lampiran yang diperiksa petugas
 *   Dashboard/Akun        foto KTP & selfie saat verifikasi akun
 *   Dashboard/Galeri      foto galeri
 *   Dashboard/Media       pustaka media (gambar saja, PDF dilewati)
 *   Components/FormLayanan  berkas yang baru diunggah warga di formulir
 *   Components/UnggahGambar foto KTP saat pendaftaran
 *   Publik/FormAspirasi     bukti foto WBS
 *   Pages/Profil            foto profil
 *
 * `daftar` berisi `{ src, judul }`. Berikan SELURUH gambar yang ada di layar,
 * bukan hanya yang diklik — itulah yang membuat ←/→ berguna.
 */
export default function PenampilGambar({ daftar, indeksAwal = 0, onTutup }) {
  const [idx, setIdx] = useState(indeksAwal);
  const [skala, setSkala] = useState(1);
  const [putar, setPutar] = useState(0);
  const [geser, setGeser] = useState({ x: 0, y: 0 });
  const seret = useRef(null);

  const item = daftar[idx];

  const reset = useCallback(() => {
    setSkala(1);
    setPutar(0);
    setGeser({ x: 0, y: 0 });
  }, []);

  const pindah = useCallback((arah) => {
    setIdx((p) => (p + arah + daftar.length) % daftar.length);
    reset();
  }, [daftar.length, reset]);

  // Pintasan papan ketik — penting saat petugas memeriksa banyak berkas.
  useEffect(() => {
    const onKey = (e) => {
      if (e.key === 'Escape') onTutup();
      else if (e.key === 'ArrowRight' && daftar.length > 1) pindah(1);
      else if (e.key === 'ArrowLeft' && daftar.length > 1) pindah(-1);
      else if (e.key === '+' || e.key === '=') setSkala((s) => Math.min(6, s + 0.25));
      else if (e.key === '-') setSkala((s) => Math.max(0.25, s - 0.25));
      else if (e.key.toLowerCase() === 'r') setPutar((r) => (r + 90) % 360);
      else if (e.key === '0') reset();
    };
    window.addEventListener('keydown', onKey);
    // Kunci scroll latar selama penampil terbuka.
    const asal = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    return () => {
      window.removeEventListener('keydown', onKey);
      document.body.style.overflow = asal;
    };
  }, [onTutup, pindah, reset, daftar.length]);

  if (!item) return null;

  const tombol = 'inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-white transition-colors hover:bg-white/25 disabled:opacity-30';

  return (
    <div className="fixed inset-0 z-[100] flex flex-col bg-black/90 backdrop-blur-sm" onClick={onTutup}>
      {/* Bilah alat */}
      <div className="flex items-center gap-2 border-b border-white/10 px-4 py-2.5" onClick={(e) => e.stopPropagation()}>
        <div className="min-w-0 flex-1">
          <p className="truncate text-sm font-medium text-white">{item.judul}</p>
          {daftar.length > 1 && (
            <p className="text-xs text-white/50">Berkas {idx + 1} dari {daftar.length}</p>
          )}
        </div>

        <button className={tombol} title="Perkecil (−)" onClick={() => setSkala((s) => Math.max(0.25, s - 0.25))}>
          <ZoomOut className="h-4 w-4" />
        </button>
        <span className="w-12 text-center text-xs tabular-nums text-white/70">{Math.round(skala * 100)}%</span>
        <button className={tombol} title="Perbesar (+)" onClick={() => setSkala((s) => Math.min(6, s + 0.25))}>
          <ZoomIn className="h-4 w-4" />
        </button>
        <button className={tombol} title="Putar kiri" onClick={() => setPutar((r) => (r - 90 + 360) % 360)}>
          <RotateCcw className="h-4 w-4" />
        </button>
        <button className={tombol} title="Putar kanan (R)" onClick={() => setPutar((r) => (r + 90) % 360)}>
          <RotateCw className="h-4 w-4" />
        </button>
        <button className={tombol} title="Kembalikan ukuran (0)" onClick={reset}>
          <Maximize2 className="h-4 w-4" />
        </button>
        <a className={tombol} href={item.src} download target="_blank" rel="noreferrer"
           title="Unduh berkas" onClick={(e) => e.stopPropagation()}>
          <Download className="h-4 w-4" />
        </a>
        <button className={`${tombol} hover:bg-red-500/70`} title="Tutup (Esc)" onClick={onTutup}>
          <X className="h-4 w-4" />
        </button>
      </div>

      {/* Area gambar */}
      <div
        className="relative flex flex-1 items-center justify-center overflow-hidden"
        onClick={(e) => e.stopPropagation()}
        onWheel={(e) => {
          // Zoom mengikuti arah gulir — kebiasaan umum penampil gambar.
          setSkala((s) => Math.min(6, Math.max(0.25, s + (e.deltaY < 0 ? 0.2 : -0.2))));
        }}
        onMouseDown={(e) => { seret.current = { x: e.clientX - geser.x, y: e.clientY - geser.y }; }}
        onMouseMove={(e) => {
          if (!seret.current) return;
          setGeser({ x: e.clientX - seret.current.x, y: e.clientY - seret.current.y });
        }}
        onMouseUp={() => { seret.current = null; }}
        onMouseLeave={() => { seret.current = null; }}
        style={{ cursor: skala > 1 ? 'grab' : 'default' }}
      >
        {daftar.length > 1 && (
          <>
            <button onClick={() => pindah(-1)} aria-label="Berkas sebelumnya"
                    className="absolute left-3 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/25">
              <ChevronLeft className="h-6 w-6" />
            </button>
            <button onClick={() => pindah(1)} aria-label="Berkas berikutnya"
                    className="absolute right-3 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/25">
              <ChevronRight className="h-6 w-6" />
            </button>
          </>
        )}

        <img
          src={item.src}
          alt={item.judul}
          draggable={false}
          className="max-h-full max-w-full select-none object-contain transition-transform duration-150"
          style={{ transform: `translate(${geser.x}px, ${geser.y}px) scale(${skala}) rotate(${putar}deg)` }}
        />
      </div>

      <p className="border-t border-white/10 px-4 py-2 text-center text-[0.7rem] text-white/40">
        Gulir untuk zoom · seret untuk menggeser · R putar · 0 kembalikan · Esc tutup
      </p>
    </div>
  );
}
