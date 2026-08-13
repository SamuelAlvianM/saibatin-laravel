import { useCallback, useEffect, useState } from 'react';
import {
  CloudUpload, Copy, FileText, Image as IkonGambar, Loader2, Search, Trash2, X,
} from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import MediaUnggah from '@/Components/MediaUnggah';
import { Kartu, Pesan, Tombol, useTunda } from '@/Components/Dasbor';
import { ambilJson, kirimJson } from '@/lib/api';
import { Input } from '@/Components/ui/input';

/**
 * Pustaka Media — port `app/dashboard/media/AdminMedia.tsx`.
 *
 * Satu tempat untuk seluruh gambar & PDF yang dipakai halaman publik. Gambar
 * dikonversi ke WebP saat diunggah (lihat `App\Services\PustakaMedia`), jadi
 * yang tersimpan di sini sudah siap pakai — bukan berkas kamera 6 MB.
 */

function ukuranBerkas(bita) {
  if (bita < 1024) return `${bita} B`;
  if (bita < 1024 * 1024) return `${(bita / 1024).toFixed(0)} KB`;
  return `${(bita / 1024 / 1024).toFixed(1)} MB`;
}

export default function Media() {
  const [items, setItems] = useState([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [cari, setCari] = useState('');
  const cariTertunda = useTunda(cari);
  const [memuat, setMemuat] = useState(true);
  const [unggah, setUnggah] = useState(false);
  const [pesan, setPesan] = useState(null);
  const perHalaman = 24;

  const muat = useCallback(async () => {
    setMemuat(true);
    const q = new URLSearchParams({ page: String(page) });
    if (cariTertunda.trim()) q.set('q', cariTertunda.trim());

    const j = await ambilJson(`/api/media?${q}`);
    setMemuat(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }
    setItems(j.data?.items ?? []);
    setTotal(j.data?.total ?? 0);
  }, [page, cariTertunda]);

  useEffect(() => { muat(); }, [muat]);

  const hapus = async (m) => {
    if (!window.confirm(`Hapus "${m.namaAsli}" dari pustaka media?`)) return;

    const j = await kirimJson(`/api/media/${m.id}`, {}, 'DELETE');
    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: 'Media dihapus' });
    setItems((p) => p.filter((x) => x.id !== m.id));
    setTotal((t) => Math.max(0, t - 1));
  };

  const salinUrl = (m) => {
    navigator.clipboard?.writeText(`${window.location.origin}${m.url}`);
    setPesan({ tipe: 'sukses', teks: 'URL disalin' });
  };

  const totalHalaman = Math.max(1, Math.ceil(total / perHalaman));

  return (
    <LayoutDashboard judul="Pustaka Media">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <Kartu>
        <div className="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
          <div className="flex items-center gap-2">
            <IkonGambar className="h-5 w-5 text-slate-700" />
            <h1 className="font-semibold text-slate-900">
              Semua Media <span className="text-sm font-normal text-slate-400">({total})</span>
            </h1>
          </div>
          <div className="flex w-full gap-2 sm:w-auto">
            <div className="relative flex-1 sm:w-64">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <Input value={cari} onChange={(e) => { setCari(e.target.value); setPage(1); }}
                     placeholder="Cari nama file..." className="pl-9" />
            </div>
            <Tombol onClick={() => setUnggah((s) => !s)}>
              {unggah ? <X className="h-4 w-4" /> : <CloudUpload className="h-4 w-4" />}
              {unggah ? 'Tutup' : 'Unggah'}
            </Tombol>
          </div>
        </div>

        {unggah && (
          <div className="mb-5">
            <MediaUnggah
              hanyaGambar={false}
              onGalat={(teks) => setPesan({ tipe: 'galat', teks })}
              onTerunggah={(m) => {
                setItems((p) => [m, ...p]);
                setTotal((t) => t + 1);
                setPesan({ tipe: 'sukses', teks: 'Berhasil ditambahkan ke pustaka' });
              }}
            />
          </div>
        )}

        {memuat ? (
          <div className="flex justify-center py-16"><Loader2 className="h-6 w-6 animate-spin text-brand" /></div>
        ) : items.length === 0 ? (
          <div className="py-16 text-center">
            <IkonGambar className="mx-auto mb-3 h-10 w-10 text-slate-300" />
            <p className="text-sm text-slate-500">Belum ada media. Klik &quot;Unggah&quot; untuk menambah.</p>
          </div>
        ) : (
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            {items.map((m) => (
              <div key={m.id} className="group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                <div className="relative aspect-square">
                  {m.mimeType?.startsWith('image/') ? (
                    <img src={m.url} alt={m.namaAsli} className="h-full w-full object-cover" />
                  ) : (
                    <div className="flex h-full flex-col items-center justify-center gap-1 text-slate-400">
                      <FileText className="h-7 w-7" />
                      <span className="text-[10px]">{m.namaFile?.split('.').pop()?.toUpperCase()}</span>
                    </div>
                  )}
                  <div className="absolute inset-0 flex items-center justify-center gap-2 bg-black/0 opacity-0 transition-colors group-hover:bg-black/40 group-hover:opacity-100">
                    <button onClick={() => salinUrl(m)} title="Salin URL"
                            className="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-700 hover:bg-slate-100">
                      <Copy className="h-4 w-4" />
                    </button>
                    <button onClick={() => hapus(m)} title="Hapus"
                            className="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-600 text-white hover:bg-rose-700">
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                </div>
                <div className="p-2">
                  <p className="truncate text-[0.7rem] font-medium text-slate-700" title={m.namaAsli}>{m.namaAsli}</p>
                  <p className="text-[0.62rem] text-slate-400">{ukuranBerkas(m.ukuran ?? 0)}</p>
                </div>
              </div>
            ))}
          </div>
        )}

        {totalHalaman > 1 && (
          <div className="mt-6 flex items-center justify-between">
            <p className="text-xs text-slate-400">Halaman {page} dari {totalHalaman}</p>
            <div className="flex gap-1">
              <Tombol varian="garis" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Sebelumnya</Tombol>
              <Tombol varian="garis" disabled={page >= totalHalaman} onClick={() => setPage((p) => p + 1)}>Berikutnya</Tombol>
            </div>
          </div>
        )}
      </Kartu>
    </LayoutDashboard>
  );
}
