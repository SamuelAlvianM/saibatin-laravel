import { useCallback, useEffect, useState } from 'react';
import { Image as IkonGambar, Loader2, Plus, Trash2 } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import BidangGambar from '@/Components/BidangGambar';
import { Kartu, Kosong, Memuat, Modal, Pesan, Tombol } from '@/Components/Dasbor';
import { ambilJson, kirimJson } from '@/lib/api';

/**
 * Galeri foto — port `app/dashboard/galeri/AdminGaleri.tsx`.
 *
 * Fotonya diambil dari pustaka media (bukan diunggah langsung ke sini), jadi
 * satu gambar bisa dipakai galeri sekaligus berita tanpa tersimpan dua kali.
 */

const KATEGORI = ['PELAYANAN', 'BUPATI'];

export default function Galeri() {
  const [items, setItems] = useState([]);
  const [memuat, setMemuat] = useState(true);
  const [buka, setBuka] = useState(false);
  const [judul, setJudul] = useState('');
  const [kategori, setKategori] = useState('PELAYANAN');
  const [gambar, setGambar] = useState('');
  const [menyimpan, setMenyimpan] = useState(false);
  const [menghapusId, setMenghapusId] = useState(null);
  const [pesan, setPesan] = useState(null);

  const muat = useCallback(async () => {
    setMemuat(true);
    const j = await ambilJson('/api/galeri?limit=50');
    setMemuat(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }
    setItems(j.data?.items ?? []);
  }, []);

  useEffect(() => { muat(); }, [muat]);

  const bukaBaru = () => {
    setJudul('');
    setKategori('PELAYANAN');
    setGambar('');
    setBuka(true);
  };

  const simpan = async () => {
    if (!judul.trim() || !gambar) {
      setPesan({ tipe: 'galat', teks: 'Judul dan foto wajib diisi' });
      return;
    }

    setMenyimpan(true);
    const j = await kirimJson('/api/galeri', { judul, kategori, gambar });
    setMenyimpan(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Foto ditambahkan' });
    setBuka(false);
    muat();
  };

  const hapus = async (f) => {
    if (!window.confirm(`Hapus foto "${f.judul}"?`)) return;

    setMenghapusId(f.id);
    const j = await kirimJson(`/api/admin/galeri/${f.id}`, {}, 'DELETE');
    setMenghapusId(null);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Foto dihapus' });
    setItems((p) => p.filter((i) => i.id !== f.id));
  };

  return (
    <LayoutDashboard judul="Galeri">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <Kartu>
        <div className="mb-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <IkonGambar className="h-5 w-5 text-slate-700" />
            <h1 className="font-semibold text-slate-900">Daftar Foto</h1>
          </div>
          <Tombol onClick={bukaBaru}><Plus className="h-4 w-4" />Upload Foto</Tombol>
        </div>

        {memuat ? <Memuat /> : items.length === 0 ? <Kosong>Belum ada foto.</Kosong> : (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            {items.map((f) => (
              <div key={f.id} className="group relative overflow-hidden rounded-xl border border-slate-200">
                <div className="aspect-square bg-slate-50">
                  <img src={f.gambar} alt={f.judul} className="h-full w-full object-contain p-1" />
                </div>
                <div className="p-2">
                  <p className="truncate text-sm font-medium text-slate-800">{f.judul}</p>
                  <p className="text-xs text-slate-400">{f.kategori}</p>
                </div>
                <button onClick={() => hapus(f)} disabled={menghapusId === f.id} aria-label={`Hapus foto ${f.judul}`}
                        className="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-rose-600 opacity-0 shadow transition-opacity group-hover:opacity-100">
                  {menghapusId === f.id ? <Loader2 className="h-4 w-4 animate-spin" /> : <Trash2 className="h-4 w-4" />}
                </button>
              </div>
            ))}
          </div>
        )}
      </Kartu>

      {buka && (
        <Modal judul="Upload Foto" onTutup={() => setBuka(false)}>
          <div className="space-y-4">
            <div className="space-y-1.5">
              <label htmlFor="judul-foto" className="text-sm font-medium text-slate-700">Judul</label>
              <input id="judul-foto" value={judul} onChange={(e) => setJudul(e.target.value)}
                     className="h-9 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40" />
            </div>

            <div className="space-y-1.5">
              <span className="text-sm font-medium text-slate-700">Kategori</span>
              <div className="flex gap-2">
                {KATEGORI.map((k) => (
                  <button key={k} type="button" onClick={() => setKategori(k)}
                          className={`rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors ${
                            kategori === k ? 'border-transparent bg-brand text-white' : 'border-slate-200 bg-white text-slate-600'
                          }`}>
                    {k}
                  </button>
                ))}
              </div>
            </div>

            <div className="space-y-1.5">
              <span className="text-sm font-medium text-slate-700">Foto</span>
              <BidangGambar nilai={gambar} onUbah={setGambar} label="Foto" judul="Pilih Foto Galeri"
                            kelas="aspect-video w-full" onGalat={(teks) => setPesan({ tipe: 'galat', teks })} />
            </div>

            <div className="flex justify-end gap-2 pt-2">
              <Tombol varian="garis" onClick={() => setBuka(false)}>Batal</Tombol>
              <Tombol onClick={simpan} disabled={menyimpan}>
                {menyimpan && <Loader2 className="h-4 w-4 animate-spin" />}Simpan
              </Tombol>
            </div>
          </div>
        </Modal>
      )}
    </LayoutDashboard>
  );
}
