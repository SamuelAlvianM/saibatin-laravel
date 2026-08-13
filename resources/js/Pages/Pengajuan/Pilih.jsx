import { Link } from '@inertiajs/react';
import { useState } from 'react';
import LayoutPengguna from '@/Components/LayoutPengguna';
import { ikon as ikonDari } from '@/lib/ikon';

export default function Pilih({ daftar, kategori }) {
  const [aktif, setAktif] = useState('all');
  const [cari, setCari] = useState('');

  const tampil = daftar.filter((l) => {
    const cocokKategori = aktif === 'all' || l.category === aktif;
    const q = cari.trim().toLowerCase();
    const cocokCari = !q
      || l.title.toLowerCase().includes(q)
      || l.description.toLowerCase().includes(q);
    return cocokKategori && cocokCari;
  });

  return (
    <LayoutPengguna judul="Ajukan Permohonan">
      <div className="mb-6">
        <h1 className="text-xl font-bold text-slate-900">Ajukan Permohonan Baru</h1>
        <p className="mt-1 text-sm text-slate-500">
          Pilih layanan yang Anda butuhkan. Seluruh berkas diunggah dalam satu halaman.
        </p>
      </div>

      <div className="mb-5 space-y-3">
        <input
          value={cari}
          onChange={(e) => setCari(e.target.value)}
          placeholder="Cari layanan…"
          className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40"
        />
        <div className="flex flex-wrap gap-2">
          {kategori.map((k) => {
            const I = ikonDari(k.icon);
            return (
              <button key={k.id} onClick={() => setAktif(k.id)}
                      className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors ${
                        aktif === k.id ? 'bg-brand text-white' : 'border border-slate-300 bg-white text-slate-600 hover:border-brand hover:text-brand'
                      }`}>
                <I className="h-4 w-4" />{k.name}
              </button>
            );
          })}
        </div>
      </div>

      {tampil.length === 0 ? (
        <p className="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
          Tidak ada layanan yang cocok dengan pencarian Anda.
        </p>
      ) : (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {tampil.map((l) => {
            const I = ikonDari(l.icon);
            return (
              <Link key={l.slug} href={`/user/pengajuan/baru/${l.slug}`}
                    className="group rounded-xl border border-slate-200 bg-white p-4 transition-all hover:-translate-y-0.5 hover:border-brand/40 hover:shadow-md">
                <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-brand/10 text-brand transition-colors group-hover:bg-brand group-hover:text-white">
                  <I className="h-5 w-5" />
                </div>
                <h2 className="text-sm font-semibold leading-snug text-slate-800">{l.title}</h2>
                <p className="mt-1 text-xs leading-relaxed text-slate-500">{l.description}</p>
              </Link>
            );
          })}
        </div>
      )}

      <p className="mt-5 text-center text-xs text-slate-400">
        {tampil.length} dari {daftar.length} layanan
      </p>
    </LayoutPengguna>
  );
}
