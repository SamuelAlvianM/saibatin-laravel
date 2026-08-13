import { useCallback, useEffect, useRef, useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

/**
 * Carousel hero — port `components/landingpage/carousel.tsx`.
 *
 * Slide-nya datang dari Konten Halaman (`beranda.carousel`) lewat props yang
 * DIRENDER SERVER, bukan diambil sendiri: carousel ada di paling atas halaman,
 * dan menunggu satu permintaan tambahan berarti bagian terbesar beranda kosong
 * selama beberapa ratus milidetik pertama.
 *
 * 🔴 Transisi slide digerakkan kelas CSS di `app.css` (`carousel-slide-*`),
 * bukan framer-motion. Durasi 0,7s di sana terikat dengan `KUNCI_MS` di bawah —
 * ubah keduanya bersamaan, kalau tidak tombol bisa ditekan saat slide masih
 * bergerak dan animasinya patah di tengah.
 */

const KUNCI_MS = 750;
const OTOMATIS_MS = 5000;

/** Dipakai bila petugas belum mengisi satu slide pun dari dashboard. */
const BAWAAN = [
  {
    id: 1,
    title: 'Pelayanan Adminduk Online',
    subtitle: 'Ajukan permohonan dokumen kependudukan kapan saja, di mana saja',
    image: '/logo-saibatin.png',
    color: '#0d1b2a',
  },
];

function CincinProgres({ durasi, kunci }) {
  const r = 10;
  const keliling = 2 * Math.PI * r;

  return (
    <svg width="44" height="44" className="absolute inset-0 -rotate-90">
      <circle cx="22" cy="22" r={r} fill="none" stroke="rgba(255,255,255,0.15)" strokeWidth="2" />
      <circle key={kunci} cx="22" cy="22" r={r} fill="none" stroke="white" strokeWidth="2"
              strokeLinecap="round" strokeDasharray={keliling}
              style={{ animation: `carousel-ring ${durasi}ms linear forwards` }} />
      <style>{`@keyframes carousel-ring { from { stroke-dashoffset: ${keliling}; } to { stroke-dashoffset: 0; } }`}</style>
    </svg>
  );
}

export default function Carousel({ slides, tinggi = 'h-full' }) {
  const daftar = Array.isArray(slides) && slides.length > 0 ? slides : BAWAAN;

  const [indeks, setIndeks] = useState(0);
  const [bergerak, setBergerak] = useState(false);
  const [arah, setArah] = useState('next');
  const [jeda, setJeda] = useState(false);
  const [kunciProgres, setKunciProgres] = useState(0);

  const sentuhX = useRef(0);
  const sentuhY = useRef(0);

  const geser = useCallback((ke) => {
    if (bergerak) return;
    setArah(ke);
    setBergerak(true);
    setIndeks((p) => (ke === 'next' ? (p + 1) % daftar.length : (p - 1 + daftar.length) % daftar.length));
    setKunciProgres((k) => k + 1);
  }, [bergerak, daftar.length]);

  const keSlide = (i) => {
    if (bergerak || i === indeks) return;
    setArah(i > indeks ? 'next' : 'prev');
    setBergerak(true);
    setIndeks(i);
    setKunciProgres((k) => k + 1);
  };

  // Buka kunci setelah animasi CSS-nya selesai.
  useEffect(() => {
    const t = setTimeout(() => setBergerak(false), KUNCI_MS);
    return () => clearTimeout(t);
  }, [indeks]);

  // Putar otomatis — berhenti saat kursor/jari menyentuh, dan tidak berjalan
  // sama sekali bila hanya ada satu slide.
  useEffect(() => {
    if (jeda || daftar.length < 2) return undefined;
    const t = setTimeout(() => geser('next'), OTOMATIS_MS);
    return () => clearTimeout(t);
  }, [jeda, geser, kunciProgres, daftar.length]);

  const mulaiSentuh = (e) => {
    sentuhX.current = e.touches[0].clientX;
    sentuhY.current = e.touches[0].clientY;
    setJeda(true);
  };

  const selesaiSentuh = (e) => {
    const dx = e.changedTouches[0].clientX - sentuhX.current;
    const dy = e.changedTouches[0].clientY - sentuhY.current;
    setJeda(false);
    // Gerakan yang lebih vertikal daripada horizontal itu gulir halaman,
    // bukan usaha mengganti slide.
    if (Math.abs(dx) < Math.abs(dy) || Math.abs(dx) < 50) return;
    geser(dx < 0 ? 'next' : 'prev');
  };

  return (
    <div className={`relative w-full ${tinggi} select-none overflow-hidden bg-black`}
         onMouseEnter={() => setJeda(true)} onMouseLeave={() => setJeda(false)}
         onTouchStart={mulaiSentuh} onTouchEnd={selesaiSentuh}>
      <div className="relative h-full w-full">
        {daftar.map((s, i) => {
          const aktif = i === indeks;
          const sebelumnya = i === (indeks - 1 + daftar.length) % daftar.length;

          let kelas = 'absolute inset-0 opacity-0 pointer-events-none';
          if (aktif) kelas = `absolute inset-0 carousel-slide-enter-${arah}`;
          else if (sebelumnya && bergerak) kelas = `absolute inset-0 carousel-slide-exit-${arah}`;

          return (
            <div key={s.id ?? i} className={kelas}>
              <div className="absolute inset-0 overflow-hidden">
                {/* Latar buram mengisi sisa bingkai kalau rasio foto berbeda dari
                    rasio kontainer — tanpa ini muncul bilah hitam di sisinya. */}
                <div aria-hidden style={{ backgroundImage: `url(${s.image})` }}
                     className={`absolute inset-0 scale-110 bg-cover bg-center opacity-70 blur-xl ${aktif ? 'carousel-image-scale' : ''}`} />
                {/* Foto utama `contain` supaya SELURUH isinya terlihat. */}
                <div style={{ backgroundImage: `url(${s.image})` }}
                     className="absolute inset-0 bg-contain bg-center bg-no-repeat" />
                <div className="absolute inset-0" style={{
                  background: `linear-gradient(105deg, ${s.color}80 0%, ${s.color}40 35%, transparent 70%),
                               linear-gradient(to top, ${s.color}d9 0%, ${s.color}59 32%, transparent 58%)`,
                }} />
              </div>

              <div className="relative flex h-full flex-col justify-end px-6 pb-14 md:px-14 lg:px-20">
                <div className="max-w-xl">
                  <h2 className={`carousel-font-title mb-3 text-4xl font-light leading-tight text-white [text-shadow:0_2px_18px_rgba(0,0,0,0.55)] md:text-6xl lg:text-7xl ${
                    aktif ? 'carousel-text-enter' : 'opacity-0'
                  }`}>{s.title}</h2>
                  <p className={`carousel-font-subtitle text-sm font-light tracking-wide text-white/80 [text-shadow:0_1px_10px_rgba(0,0,0,0.55)] md:text-base ${
                    aktif ? 'carousel-subtitle-enter' : 'opacity-0'
                  }`}>{s.subtitle}</p>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {daftar.length > 1 && (
        <>
          {/* Panah disembunyikan di ponsel — di sana gantinya usap layar. */}
          <div className="hidden md:block">
            <button onClick={() => geser('prev')} disabled={bergerak} aria-label="Slide sebelumnya"
                    className="carousel-nav-button absolute left-5 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white disabled:cursor-not-allowed disabled:opacity-30">
              <ChevronLeft className="h-5 w-5" />
            </button>
            <button onClick={() => geser('next')} disabled={bergerak} aria-label="Slide berikutnya"
                    className="carousel-nav-button absolute right-5 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white disabled:cursor-not-allowed disabled:opacity-30">
              <ChevronRight className="h-5 w-5" />
            </button>
          </div>

          <div className="absolute inset-x-6 bottom-5 z-20 flex items-center justify-between md:inset-x-14 lg:inset-x-20">
            <div className="flex items-center gap-2.5">
              {daftar.map((_, i) => (
                <button key={i} onClick={() => keSlide(i)} disabled={bergerak} aria-label={`Ke slide ${i + 1}`}
                        className="carousel-nav-dot disabled:cursor-not-allowed">
                  <div className="h-1.5 rounded-full transition-all duration-300"
                       style={{
                         width: i === indeks ? 26 : 6,
                         backgroundColor: i === indeks ? 'rgba(255,255,255,1)' : 'rgba(255,255,255,0.35)',
                       }} />
                </button>
              ))}
            </div>

            <div className="relative flex h-11 w-11 items-center justify-center">
              {jeda ? (
                <div className="flex gap-1 opacity-55">
                  <div className="h-3.5 w-1 rounded-full bg-white" />
                  <div className="h-3.5 w-1 rounded-full bg-white" />
                </div>
              ) : (
                <CincinProgres durasi={OTOMATIS_MS} kunci={kunciProgres} />
              )}
            </div>
          </div>

          <div className="absolute inset-x-0 top-0 z-20 h-0.5 bg-white/10">
            <div key={kunciProgres} className="h-full bg-white/40"
                 style={{ animation: jeda ? 'none' : `carousel-bar ${OTOMATIS_MS}ms linear forwards` }} />
            <style>{'@keyframes carousel-bar { from { width: 0%; } to { width: 100%; } }'}</style>
          </div>
        </>
      )}
    </div>
  );
}
