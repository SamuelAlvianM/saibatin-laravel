import { Head } from '@inertiajs/react';

/**
 * Kerangka halaman auth (login, daftar, lupa sandi, cek status).
 *
 * Latar, kartu kaca, garis aksen biru, dan footer-nya persis sama di keempat
 * halaman portal Next.js. Disatukan di sini supaya perubahan gaya cukup di satu
 * tempat — bukan karena hemat baris, tapi supaya keempatnya tidak pelan-pelan
 * jadi berbeda.
 */
export default function KartuAuth({ judul, ikon, subjudul, lebar = 'max-w-[420px]', children, tahun = '2024' }) {
  return (
    <div
      className="relative flex min-h-screen items-center justify-center overflow-hidden p-4"
      style={{ background: 'linear-gradient(160deg,#e8f0f9 0%,#dceaf7 40%,#c8dcf0 100%)' }}
    >
      <Head title={judul} />

      <div className="pointer-events-none absolute inset-0 overflow-hidden">
        <div className="absolute -right-40 -top-40 h-96 w-96 rounded-full blur-3xl"
             style={{ background: 'rgba(33,118,189,0.12)' }} />
        <div className="absolute -bottom-40 -left-40 h-96 w-96 rounded-full blur-3xl"
             style={{ background: 'rgba(33,118,189,0.08)' }} />
      </div>

      <div className={`kartu-kaca relative z-10 my-10 w-full ${lebar} overflow-hidden rounded-xl`}
           style={{ boxShadow: '0 8px 40px rgba(27,75,114,0.18)' }}>
        <div className="absolute inset-x-0 top-0 h-[3px]"
             style={{ background: '#2176bd', boxShadow: '0 2px 12px rgba(33,118,189,0.45)' }} />

        <div className="space-y-3 px-8 pb-5 pt-8 text-center">
          <div className="flex justify-center">
            {/* Tanpa ikon khusus, yang tampil adalah LOGO instansi di atas alas
                putih — bukan lingkaran biru berinisial. Halaman ini pintu masuk
                warga, dan lambang dinaslah yang menandakan situsnya resmi. */}
            {ikon ? (
              <div className="flex h-16 w-16 items-center justify-center rounded-full text-white shadow-md"
                   style={{ background: 'linear-gradient(135deg,#2176bd,#1b4b72)' }}>
                {ikon}
              </div>
            ) : (
              <div className="flex h-16 w-16 items-center justify-center rounded-full bg-white p-2 shadow-md ring-1 ring-slate-200">
                <img src="/logo-saibatin.png" alt="Logo SAIBATIN" className="h-full w-full object-contain" />
              </div>
            )}
          </div>
          <div>
            <h1 className="text-2xl font-bold text-slate-800">{judul}</h1>
            {subjudul && <p className="mt-1 text-sm text-slate-500">{subjudul}</p>}
          </div>
        </div>

        {children}
      </div>

      <div className="absolute inset-x-0 bottom-4 text-center text-xs text-slate-400">
        <p>SAIBATIN — Disdukcapil Kabupaten Pesisir Barat &copy; {tahun}</p>
      </div>
    </div>
  );
}

/** Kotak galat seragam untuk seluruh form auth. */
export function KotakGalat({ errors }) {
  const daftar = Object.values(errors ?? {}).filter(Boolean);
  if (daftar.length === 0) return null;

  return (
    <div className="flex gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
      <span aria-hidden className="mt-0.5 shrink-0">⚠</span>
      <ul className="list-inside list-disc space-y-1">
        {daftar.map((g, i) => <li key={i}>{g}</li>)}
      </ul>
    </div>
  );
}
