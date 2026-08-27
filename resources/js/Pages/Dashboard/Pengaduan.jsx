import { useCallback, useEffect, useState } from 'react';
import { CheckCircle2, Clock, Mail, MessageSquare, Phone, User } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import { Kartu, Kosong, Memuat, Modal, Pesan, Tombol, tglJam, tglSingkat } from '@/Components/Dasbor';
import { ambilJson, kirimJson } from '@/lib/api';
import { kelasSorot, useSorot } from '@/lib/sorot';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';

/**
 * Pengaduan masyarakat — port `app/dashboard/pengaduan/AdminPengaduan.tsx`.
 *
 * 🔴 Halaman ini memuat identitas pelapor, termasuk yang masuk lewat jalur WBS
 * yang kerahasiaannya dijanjikan di halaman publik. Isinya tidak boleh keluar
 * dari dashboard petugas dalam bentuk apa pun.
 */

const SARINGAN = [['', 'Semua'], ['belum', 'Belum Ditangani'], ['selesai', 'Sudah Ditangani']];

function Lencana({ status }) {
  if (status === 'SELESAI') {
    return (
      <span className="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
        <CheckCircle2 className="h-3 w-3" />Selesai
      </span>
    );
  }

  return (
    <span className="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
      <Clock className="h-3 w-3" />{status === 'DIPROSES' ? 'Diproses' : 'Belum ditangani'}
    </span>
  );
}

export default function Pengaduan({ sorot }) {
  const [items, setItems] = useState([]);
  const [saring, setSaring] = useState('');
  const [memuat, setMemuat] = useState(true);
  const [detail, setDetail] = useState(null);
  const [balasan, setBalasan] = useState('');
  const [menyimpan, setMenyimpan] = useState(false);
  const [pesan, setPesan] = useState(null);

  // Datang dari notifikasi pengaduan. Daftar ini TIDAK berpaginasi (300 teratas
  // sekaligus), jadi tidak perlu hitungan halaman di server — cukup menggulir.
  const sorotId = useSorot(sorot, 'pengaduan', !memuat, items);

  const muat = useCallback(async () => {
    setMemuat(true);
    const q = new URLSearchParams();
    if (saring) q.set('filter', saring);

    const j = await ambilJson(`/api/admin/pengaduan?${q}`);
    setMemuat(false);

    if (j.error?.length) setPesan({ tipe: 'galat', teks: j.error[0] });
    else setItems(j.data.items ?? []);
  }, [saring]);

  useEffect(() => { muat(); }, [muat]);

  const simpan = async (status) => {
    setMenyimpan(true);
    const j = await kirimJson(`/api/admin/pengaduan/${detail.id}`, { status, balasan }, 'PATCH');
    setMenyimpan(false);

    if (j.error?.length) {
      setPesan({ tipe: 'galat', teks: j.error[0] });
      return;
    }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Tersimpan' });
    setItems((p) => p.map((x) => (x.id === detail.id ? { ...x, status, balasan } : x)));
    setDetail(null);
  };

  return (
    <LayoutDashboard judul="Pengaduan">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <Kartu>
        <div className="mb-4 flex items-center gap-2">
          <MessageSquare className="h-5 w-5 text-slate-700" />
          <h1 className="font-semibold text-slate-900">Daftar Pengaduan</h1>
        </div>

        <div className="mb-4 flex flex-wrap gap-1">
          {SARINGAN.map(([nilai, label]) => (
            <button key={nilai} onClick={() => setSaring(nilai)}
                    className={`rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors ${
                      saring === nilai ? 'border-transparent bg-brand text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-brand'
                    }`}>
              {label}
            </button>
          ))}
        </div>

        {memuat ? <Memuat /> : items.length === 0 ? <Kosong>Tidak ada pengaduan.</Kosong> : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-slate-200 text-left text-slate-500">
                  {['Nama', 'Subjek', 'Isi', 'Tanggal', 'Status', 'Aksi'].map((h) => (
                    <th key={h} className="py-2 pr-4 font-medium">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {items.map((it) => (
                  <tr key={it.id} id={`pengaduan-${it.id}`}
                      onClick={() => { setDetail(it); setBalasan(it.balasan ?? ''); }}
                      className={`cursor-pointer border-b border-slate-100 hover:bg-slate-50/60 ${kelasSorot(sorotId === it.id)}`}>
                    <td className="py-2.5 pr-4 font-medium text-slate-800">{it.nama}</td>
                    <td className="py-2.5 pr-4">{it.subjek ?? '-'}</td>
                    <td className="max-w-xs truncate py-2.5 pr-4 text-slate-500">{it.isi}</td>
                    <td className="py-2.5 pr-4 text-xs text-slate-500">{tglSingkat(it.createdAt)}</td>
                    <td className="py-2.5 pr-4"><Lencana status={it.status} /></td>
                    <td className="py-2.5 pr-4" onClick={(e) => e.stopPropagation()}>
                      <Tombol varian="garis" onClick={() => { setDetail(it); setBalasan(it.balasan ?? ''); }}>Detail</Tombol>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Kartu>

      {detail && (
        <Modal judul={detail.subjek ?? 'Pengaduan'} lebar="max-w-lg" onTutup={() => setDetail(null)}>
          <div className="space-y-3 text-sm">
            <Lencana status={detail.status} />

            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
              <p className="flex items-center gap-2"><User className="h-4 w-4 text-slate-400" />{detail.nama}</p>
              <p className="flex items-center gap-2"><Phone className="h-4 w-4 text-slate-400" />{detail.hp || '-'}</p>
              <p className="flex items-center gap-2 break-all sm:col-span-2">
                <Mail className="h-4 w-4 shrink-0 text-slate-400" />{detail.email || '-'}
              </p>
            </div>

            <div className="rounded-lg bg-slate-50 p-3">
              <p className="mb-1 text-xs font-semibold text-slate-500">Isi Pengaduan</p>
              <p className="whitespace-pre-wrap text-slate-700">{detail.isi}</p>
            </div>

            <div>
              <Label htmlFor="balasan" className="text-slate-700">Balasan / Catatan</Label>
              <Textarea id="balasan" rows={3} value={balasan} onChange={(e) => setBalasan(e.target.value)}
                        placeholder="Tanggapan untuk pengadu (opsional)…" className="mt-1.5" />
            </div>

            <p className="text-xs text-slate-400">Dibuat: {tglJam(detail.createdAt)}</p>

            <div className="flex flex-wrap justify-end gap-2 pt-2">
              <Tombol varian="garis" onClick={() => simpan('DIPROSES')} disabled={menyimpan}>Tandai Diproses</Tombol>
              <Tombol varian="sukses" onClick={() => simpan('SELESAI')} disabled={menyimpan}>
                <CheckCircle2 className="h-4 w-4" />Tandai Selesai
              </Tombol>
            </div>
          </div>
        </Modal>
      )}
    </LayoutDashboard>
  );
}
