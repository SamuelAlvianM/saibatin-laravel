import { useEffect, useMemo, useState } from 'react';
import { CalendarDays, Mail, MessagesSquare, Phone, Search, User } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import { Kartu, Memuat, Pesan } from '@/Components/Dasbor';
import { ambilJson } from '@/lib/api';
import { Input } from '@/Components/ui/input';

/**
 * Kritik & saran — port `app/dashboard/kritik-saran/AdminKritikSaran.tsx`.
 *
 * Hanya baca: masukan warga tidak dibalas lewat halaman ini (jalur balasannya
 * adalah Pengaduan). Karena itu pencariannya dijalankan di KLIEN atas daftar
 * yang sudah diambil — bukan bolak-balik ke server untuk data yang sudah ada
 * di layar.
 */
export default function KritikSaran() {
  const [items, setItems] = useState([]);
  const [memuat, setMemuat] = useState(true);
  const [cari, setCari] = useState('');
  const [pesan, setPesan] = useState(null);

  useEffect(() => {
    ambilJson('/api/kritik-saran').then((j) => {
      setMemuat(false);
      if (j.error?.length) setPesan({ tipe: 'galat', teks: j.error[0] });
      else setItems(j.data.items ?? []);
    });
  }, []);

  const tersaring = useMemo(() => {
    const k = cari.trim().toLowerCase();
    if (!k) return items;

    return items.filter((i) =>
      (i.nama ?? '').toLowerCase().includes(k)
      || (i.pesan ?? '').toLowerCase().includes(k)
      || (i.email ?? '').toLowerCase().includes(k));
  }, [items, cari]);

  return (
    <LayoutDashboard judul="Kritik & Saran">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <Kartu>
        <div className="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
          <div className="flex items-center gap-2">
            <MessagesSquare className="h-5 w-5 text-slate-700" />
            <h1 className="font-semibold text-slate-900">
              Daftar Masukan <span className="text-sm font-normal text-slate-400">({items.length})</span>
            </h1>
          </div>
          <div className="relative w-full sm:w-72">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <Input value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Cari nama / isi pesan…"
                   className="pl-9" />
          </div>
        </div>

        {memuat ? <Memuat kelas="py-16" /> : tersaring.length === 0 ? (
          <div className="py-16 text-center">
            <MessagesSquare className="mx-auto mb-3 h-10 w-10 text-slate-300" />
            <p className="text-sm text-slate-500">
              {items.length === 0 ? 'Belum ada kritik & saran yang masuk.' : 'Tidak ada yang cocok dengan pencarian.'}
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            {tersaring.map((it) => (
              <div key={it.id} className="rounded-xl border border-slate-200 p-4 transition-all hover:border-brand/30 hover:shadow-sm">
                <div className="flex min-w-0 items-center gap-2">
                  <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand/10">
                    <User className="h-4 w-4 text-brand" />
                  </div>
                  <div className="min-w-0">
                    <p className="truncate font-semibold text-slate-900">{it.nama}</p>
                    <p className="flex items-center gap-1 text-[0.7rem] text-slate-400">
                      <CalendarDays className="h-3 w-3" />
                      {new Date(it.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                    </p>
                  </div>
                </div>

                <p className="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-600">{it.pesan}</p>

                {(it.email || it.hp) && (
                  <div className="mt-3 flex flex-wrap gap-x-4 gap-y-1 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    {it.email && (
                      <a href={`mailto:${it.email}`} className="flex items-center gap-1.5 hover:text-brand">
                        <Mail className="h-3.5 w-3.5" />{it.email}
                      </a>
                    )}
                    {it.hp && (
                      <a href={`tel:${it.hp}`} className="flex items-center gap-1.5 hover:text-brand">
                        <Phone className="h-3.5 w-3.5" />{it.hp}
                      </a>
                    )}
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </Kartu>
    </LayoutDashboard>
  );
}
