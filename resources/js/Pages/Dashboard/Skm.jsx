import { useEffect, useState } from 'react';
import { ClipboardCheck, Gauge, Star, Users } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import { Kartu, Kosong, Memuat, tglSingkat } from '@/Components/Dasbor';
import { ambilJson } from '@/lib/api';

/**
 * Rekap Survei Kepuasan Masyarakat — port `app/dashboard/skm/SkmDashboard.tsx`.
 *
 * Ambang mutu mengikuti **Permenpan RB 14/2017**; angkanya bukan pilihan desain
 * dan tidak boleh dibulatkan sendiri — nilai inilah yang dilaporkan dinas.
 */

function mutu(ikm) {
  if (ikm >= 88.31) return { label: 'A — Sangat Baik', warna: 'text-emerald-600' };
  if (ikm >= 76.61) return { label: 'B — Baik', warna: 'text-brand' };
  if (ikm >= 65.0) return { label: 'C — Kurang Baik', warna: 'text-amber-600' };
  return { label: 'D — Tidak Baik', warna: 'text-rose-600' };
}

export default function Skm() {
  const [data, setData] = useState(null);
  const [memuat, setMemuat] = useState(true);

  useEffect(() => {
    ambilJson('/api/admin/skm').then((j) => {
      setData(j.data);
      setMemuat(false);
    });
  }, []);

  if (memuat) {
    return <LayoutDashboard judul="SKM & IKM"><Memuat kelas="py-20" /></LayoutDashboard>;
  }
  if (!data) {
    return <LayoutDashboard judul="SKM & IKM"><Kosong>Gagal memuat data survei.</Kosong></LayoutDashboard>;
  }

  const m = mutu(data.nilaiIKM);
  const kartu = [
    { label: 'Total Responden', nilai: data.totalResponden, ikon: Users, warna: 'text-brand' },
    { label: 'Rata-rata Skor', nilai: `${data.rataKeseluruhan} / ${data.skalaMax}`, ikon: Star, warna: 'text-amber-500' },
    { label: 'Nilai IKM', nilai: data.nilaiIKM, ikon: Gauge, warna: 'text-emerald-600' },
    { label: 'Mutu Pelayanan', nilai: m.label, ikon: ClipboardCheck, warna: m.warna, kecil: true },
  ];

  return (
    <LayoutDashboard judul="SKM & IKM">
      <div className="mb-4">
        <h1 className="text-2xl font-semibold text-slate-900">Survei Kepuasan Masyarakat</h1>
        <p className="text-sm text-slate-500">Rekap penilaian warga atas pelayanan Disdukcapil.</p>
      </div>

      <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        {kartu.map((c) => (
          <div key={c.label} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <c.ikon className={`mb-2 h-5 w-5 ${c.warna}`} />
            <p className={`font-semibold text-slate-900 ${c.kecil ? 'text-base' : 'text-2xl'}`}>{c.nilai}</p>
            <p className="text-xs text-slate-500">{c.label}</p>
          </div>
        ))}
      </div>

      <div className="mb-6">
        <Kartu judul="Rata-rata Nilai per Aspek">
          <p className="mb-5 text-xs text-slate-500">
            Skala Permenpan RB 14/2017 — 1 ({(data.skalaLabel?.[0] ?? 'tidak baik').toLowerCase()}) sampai{' '}
            {data.skalaMax} ({(data.skalaLabel?.[data.skalaMax - 1] ?? 'sangat baik').toLowerCase()})
          </p>

          {data.totalResponden === 0 ? <Kosong>Belum ada responden.</Kosong> : (
            <div className="space-y-4">
              {data.rataPerAspek.map((a) => (
                <div key={a.aspek}>
                  <div className="mb-1 flex justify-between text-sm">
                    <span className="font-medium text-slate-700">{a.aspek}</span>
                    <span className="text-slate-500">{a.rata.toFixed(2)}</span>
                  </div>
                  <div className="h-3 w-full overflow-hidden rounded-full bg-slate-100">
                    <div className="h-full rounded-full transition-all"
                         style={{
                           width: `${(a.rata / data.skalaMax) * 100}%`,
                           background: 'linear-gradient(90deg, #2176bd, #6cb2eb)',
                         }} />
                  </div>
                </div>
              ))}
            </div>
          )}
        </Kartu>
      </div>

      <Kartu judul="Responden Terbaru">
        {data.respondenTerbaru.length === 0 ? <Kosong>Belum ada responden.</Kosong> : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-slate-200 text-left text-slate-500">
                  {['Nama', 'Tanggal', 'Rata Skor', 'Saran'].map((h) => (
                    <th key={h} className="py-2 pr-4 font-medium">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {data.respondenTerbaru.map((r) => (
                  <tr key={r.id} className="border-b border-slate-100">
                    <td className="py-2.5 pr-4 font-medium text-slate-800">{r.nama}</td>
                    <td className="py-2.5 pr-4 text-xs text-slate-500">{tglSingkat(r.createdAt)}</td>
                    <td className="py-2.5 pr-4">{r.rataSkor.toFixed(2)}</td>
                    <td className="max-w-xs truncate py-2.5 pr-4 text-slate-500">{r.saran ?? '-'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Kartu>
    </LayoutDashboard>
  );
}
