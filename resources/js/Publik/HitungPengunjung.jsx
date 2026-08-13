import { useEffect, useState } from 'react';
import { Users } from 'lucide-react';

const fmt = (n) => Number(n ?? 0).toLocaleString('id-ID');

/**
 * Penghitung pengunjung di footer (teks saja) — port
 * `components/shared/visitor-count.tsx`.
 *
 * Angkanya diambil setelah halaman tampil, dan selama belum ada datanya
 * komponen ini TIDAK merender apa pun. Itu disengaja: baris kosong yang
 * tiba-tiba terisi angka lebih baik daripada placeholder yang menggeser
 * tata letak footer.
 */
export default function HitungPengunjung() {
  const [stat, setStat] = useState(null);

  useEffect(() => {
    let aktif = true;
    fetch('/api/kunjungan')
      .then((r) => r.json())
      .then((j) => {
        if (aktif && j?.data) setStat(j.data);
      })
      .catch(() => {});
    return () => {
      aktif = false;
    };
  }, []);

  if (!stat) return null;

  return (
    <p className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
      <Users className="h-3.5 w-3.5 text-yellow-400" />
      <span>
        Total pengunjung: <span className="font-semibold text-slate-300">{fmt(stat.total)}</span>
      </span>
      <span className="text-slate-700">&middot;</span>
      <span>Hari ini: <span className="text-slate-400">{fmt(stat.hariIni)}</span></span>
      <span className="text-slate-700">&middot;</span>
      <span>Online: <span className="text-slate-400">{fmt(stat.online)}</span></span>
    </p>
  );
}
