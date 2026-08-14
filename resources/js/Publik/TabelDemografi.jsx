import { useState } from 'react';
import RincianDemografi from '@/Publik/RincianDemografi';

/**
 * Laporan Data Demografi (`/media/demografi`) — port
 * `components/landingpage/demografi-view.tsx` SIDAKO.
 *
 * Tab kategori + tabel dua tingkat (kecamatan → desa/kelurahan). Tabelnya
 * memakai `RincianDemografi` yang sudah dipakai dialog kartu beranda: satu
 * komponen, dua tempat — bukan dua tabel yang pelan-pelan berbeda cara
 * menjumlahkan.
 *
 * Kategori aktif ikut ke URL (`?kategori=agama`) supaya satu tab tertentu bisa
 * dibagikan dan dibuka kembali. `replaceState`, bukan `pushState`: berpindah
 * tab bukan berpindah halaman, dan menumpuk riwayat membuat tombol Kembali
 * peramban terasa rusak.
 */
export default function TabelDemografi({ kategori = [] }) {
  const awal = typeof window !== 'undefined'
    ? new URLSearchParams(window.location.search).get('kategori')
    : null;

  const [aktif, setAktif] = useState(
    kategori.some((k) => k.slug === awal) ? awal : kategori[0]?.slug,
  );

  const pilih = (slug) => {
    setAktif(slug);
    const url = new URL(window.location.href);
    url.searchParams.set('kategori', slug);
    window.history.replaceState({}, '', url);
  };

  const terpilih = kategori.find((k) => k.slug === aktif);

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap gap-2" role="tablist" aria-label="Kategori data demografi">
        {kategori.map((k) => (
          <button key={k.slug} type="button" role="tab" aria-selected={k.slug === aktif}
                  onClick={() => pilih(k.slug)}
                  className={`rounded-full px-4 py-2 text-sm font-medium transition-colors ${
                    k.slug === aktif
                      ? 'bg-brand text-white shadow-sm'
                      : 'border border-slate-300 bg-white text-slate-600 hover:border-brand hover:text-brand'
                  }`}>
            {k.label}
          </button>
        ))}
      </div>

      {terpilih && (
        <div className="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm md:p-6">
          {/* `key` memaksa komponen dipasang ulang saat kategori berganti —
              tanpa itu tingkat rincian (kecamatan yang sedang dibuka) ikut
              terbawa ke kategori baru yang belum tentu punya wilayah itu. */}
          <RincianDemografi key={terpilih.slug} kategori={terpilih.slug}
                            kolom="JML" judul={terpilih.label} />
        </div>
      )}
    </div>
  );
}
