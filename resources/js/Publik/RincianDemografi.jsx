import { useEffect, useState } from 'react';
import { ChevronLeft, Loader2 } from 'lucide-react';
import { labelKolom } from '@/lib/statistik-kartu';
import { ambilJson } from '@/lib/api';

/**
 * Rincian satu kartu demografi — port `components/landingpage/demografi-metric.tsx`.
 *
 * Dua tingkat: daftar kecamatan, lalu daftar pekon di dalamnya. Keduanya dari
 * endpoint yang sama (`/api/demografi`, dibedakan `?parent=`), jadi tidak ada
 * data yang diunduh untuk kecamatan yang tidak pernah dibuka.
 *
 * 🔴 Angka kecamatan di sini adalah PENJUMLAHAN pekon di bawahnya — dihitung
 * server. Jangan menjumlahkannya lagi di sini bersama baris kecamatan, karena
 * hasilnya jadi dua kali lipat (lihat komentar di `Api\DemografiController`).
 */

const angka = (n) => Number(n ?? 0).toLocaleString('id-ID');

export default function RincianDemografi({ kategori, kolom, judul }) {
  const [data, setData] = useState(null);
  const [memuat, setMemuat] = useState(true);
  const [galat, setGalat] = useState(null);
  // Kecamatan yang sedang dibuka rinciannya; null = tampilan daftar kecamatan.
  const [induk, setInduk] = useState(null);

  useEffect(() => {
    let batal = false;
    setMemuat(true);
    setGalat(null);

    const q = new URLSearchParams({ kategori });
    if (induk) q.set('parent', induk.kode);

    ambilJson(`/api/demografi?${q}`).then((j) => {
      if (batal) return;
      setMemuat(false);

      if (j.error?.length) {
        setGalat(j.error[0]);
        return;
      }
      setData(j.data);
    });

    return () => { batal = true; };
  }, [kategori, induk]);

  const baris = data?.items ?? [];
  // Kolom kartu ditaruh paling depan supaya angka yang sedang dilihat pengguna
  // tidak perlu dicari di antara belasan kolom lain.
  const kolomTampil = [kolom, ...(data?.kolom ?? []).filter((k) => k !== kolom)];
  const total = baris.reduce((a, b) => a + Number(b.data?.[kolom] ?? 0), 0);

  if (memuat) {
    return (
      <div className="flex justify-center py-16">
        <Loader2 className="h-6 w-6 animate-spin text-brand" />
      </div>
    );
  }

  if (galat) {
    return <p className="py-10 text-center text-sm text-slate-500">{galat}</p>;
  }

  if (baris.length === 0) {
    return <p className="py-10 text-center text-sm text-slate-500">Data belum tersedia untuk kategori ini.</p>;
  }

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center justify-between gap-2">
        {induk ? (
          <button onClick={() => setInduk(null)}
                  className="inline-flex items-center gap-1 text-sm font-medium text-brand hover:underline">
            <ChevronLeft className="h-4 w-4" />Kembali ke daftar kecamatan
          </button>
        ) : (
          <p className="text-xs text-slate-400">Klik nama kecamatan untuk melihat rincian tiap desa/kelurahan.</p>
        )}

        <span className="rounded-full bg-brand/10 px-3 py-1 text-xs font-semibold text-brand">
          {induk ? induk.wilayah : 'Total'} · {labelKolom(kolom)} {angka(total)}
        </span>
      </div>

      <div className="overflow-x-auto rounded-xl border border-slate-200">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
              <th className="px-3 py-2 font-medium">{induk ? 'Desa / Kelurahan' : 'Kecamatan'}</th>
              {kolomTampil.map((k) => (
                <th key={k} className={`px-3 py-2 text-right font-medium ${k === kolom ? 'text-brand' : ''}`}>
                  {labelKolom(k)}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {baris.map((r) => (
              <tr key={r.kode} className="border-b border-slate-100 last:border-0">
                <td className="px-3 py-2">
                  {induk ? (
                    <span className="text-slate-700">{r.wilayah}</span>
                  ) : (
                    <button onClick={() => setInduk(r)} className="text-left font-medium text-slate-800 hover:text-brand hover:underline">
                      {r.wilayah}
                      {r.jumlahPekon > 0 && (
                        <span className="ml-1.5 text-xs font-normal text-slate-400">({r.jumlahPekon} desa)</span>
                      )}
                    </button>
                  )}
                </td>
                {kolomTampil.map((k) => (
                  <td key={k} className={`px-3 py-2 text-right tabular-nums ${
                    k === kolom ? 'font-bold text-slate-900' : 'text-slate-500'
                  }`}>
                    {angka(r.data?.[k])}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <p className="text-[0.7rem] text-slate-400">
        Sumber: rekap Data Kependudukan Bersih (DKB) Disdukcapil Kabupaten Pesisir Barat — {judul}.
      </p>
    </div>
  );
}
