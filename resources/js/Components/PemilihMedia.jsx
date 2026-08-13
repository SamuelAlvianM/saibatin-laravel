import { useCallback, useEffect, useState } from 'react';
import {
  CloudUpload, FileText, Image as IkonGambar, LayoutGrid, Loader2, Search, Trash2,
} from 'lucide-react';
import MediaUnggah from '@/Components/MediaUnggah';
import { Modal, Tombol, useTunda } from '@/Components/Dasbor';
import { ambilJson, kirimJson } from '@/lib/api';
import { Input } from '@/Components/ui/input';

/**
 * Dialog pemilih media — port `components/media/media-picker.tsx`.
 * Dua tab: pustaka yang sudah ada, dan unggahan baru (yang langsung terpilih).
 */
export default function PemilihMedia({
  judul = 'Pilih Media', hanyaGambar = true, rasio, onPilih, onTutup, onGalat,
}) {
  const [tab, setTab] = useState('galeri');
  const [items, setItems] = useState([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [cari, setCari] = useState('');
  const cariTertunda = useTunda(cari);
  const [memuat, setMemuat] = useState(true);
  const perHalaman = 24;

  const muat = useCallback(async () => {
    setMemuat(true);
    const q = new URLSearchParams({ page: String(page) });
    if (cariTertunda.trim()) q.set('q', cariTertunda.trim());
    if (hanyaGambar) q.set('type', 'image');

    const j = await ambilJson(`/api/media?${q}`);
    setMemuat(false);

    if (j.error?.length) { onGalat?.(j.error[0]); return; }
    setItems(j.data?.items ?? []);
    setTotal(j.data?.total ?? 0);
  }, [page, cariTertunda, hanyaGambar, onGalat]);

  useEffect(() => { muat(); }, [muat]);

  const pilih = (m) => { onPilih(m); onTutup(); };

  const hapus = async (e, m) => {
    e.stopPropagation();
    if (!window.confirm(`Hapus "${m.namaAsli}" dari pustaka media?`)) return;

    const j = await kirimJson(`/api/media/${m.id}`, {}, 'DELETE');
    if (j.error?.length) { onGalat?.(j.error[0]); return; }
    setItems((p) => p.filter((x) => x.id !== m.id));
    setTotal((t) => Math.max(0, t - 1));
  };

  const totalHalaman = Math.max(1, Math.ceil(total / perHalaman));

  const kelasTab = (nilai) =>
    `-mb-px flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors ${
      tab === nilai ? 'border-brand text-brand' : 'border-transparent text-slate-500 hover:text-slate-700'
    }`;

  return (
    <Modal judul={judul} lebar="max-w-3xl" onTutup={onTutup}>
      <p className="-mt-2 text-sm text-slate-500">
        Pilih dari pustaka media atau unggah gambar baru (tarik &amp; lepas).
      </p>

      <div className="mt-3 flex gap-1 border-b border-slate-200">
        <button type="button" onClick={() => setTab('galeri')} className={kelasTab('galeri')}>
          <LayoutGrid className="h-4 w-4" /> Pustaka Media
        </button>
        <button type="button" onClick={() => setTab('upload')} className={kelasTab('upload')}>
          <CloudUpload className="h-4 w-4" /> Upload Baru
        </button>
      </div>

      {tab === 'upload' ? (
        <div className="py-4">
          <MediaUnggah hanyaGambar={hanyaGambar} rasio={rasio} onGalat={onGalat}
                       onTerunggah={(m) => pilih(m)} />
        </div>
      ) : (
        <div className="flex min-h-0 flex-col gap-3 pt-3">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <Input value={cari} onChange={(e) => { setCari(e.target.value); setPage(1); }}
                   placeholder="Cari nama file..." className="pl-9" />
          </div>

          <div className="min-h-[200px] flex-1 overflow-y-auto">
            {memuat ? (
              <div className="flex items-center justify-center py-16">
                <Loader2 className="h-6 w-6 animate-spin text-brand" />
              </div>
            ) : items.length === 0 ? (
              <div className="flex flex-col items-center justify-center py-16 text-center">
                <IkonGambar className="mb-2 h-10 w-10 text-slate-300" />
                <p className="text-sm text-slate-500">Belum ada media. Unggah lewat tab &quot;Upload Baru&quot;.</p>
              </div>
            ) : (
              <div className="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                {items.map((m) => (
                  <button key={m.id} type="button" onClick={() => pilih(m)} title={m.namaAsli}
                          className="group relative aspect-square overflow-hidden rounded-lg border border-slate-200 bg-slate-50 transition-all hover:border-brand hover:ring-2 hover:ring-brand/30">
                    {m.mimeType?.startsWith('image/') ? (
                      <img src={m.url} alt={m.namaAsli} className="h-full w-full object-cover" />
                    ) : (
                      <div className="flex h-full flex-col items-center justify-center gap-1 text-slate-500">
                        <FileText className="h-6 w-6" />
                        <span className="max-w-full truncate px-1 text-[10px]">{m.namaAsli}</span>
                      </div>
                    )}
                    <span role="button" tabIndex={-1} onClick={(e) => hapus(e, m)} title="Hapus media"
                          className="absolute right-1 top-1 hidden h-6 w-6 items-center justify-center rounded-md bg-rose-600 text-white shadow group-hover:flex">
                      <Trash2 className="h-3.5 w-3.5" />
                    </span>
                  </button>
                ))}
              </div>
            )}
          </div>

          {totalHalaman > 1 && (
            <div className="flex items-center justify-between pt-1">
              <p className="text-xs text-slate-500">{total} media — hal. {page}/{totalHalaman}</p>
              <div className="flex gap-1">
                <Tombol varian="garis" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Sebelumnya</Tombol>
                <Tombol varian="garis" disabled={page >= totalHalaman} onClick={() => setPage((p) => p + 1)}>Berikutnya</Tombol>
              </div>
            </div>
          )}
        </div>
      )}
    </Modal>
  );
}
