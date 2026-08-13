import { useCallback, useEffect, useRef, useState } from 'react';
import {
  ExternalLink, FileText, Globe, Loader2, Plus, Search, Trash2, Upload,
} from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import { Kartu, Kosong, Memuat, Modal, Pesan, Tombol, tglSingkat } from '@/Components/Dasbor';
import { ambilJson, kirimBerkas, kirimJson } from '@/lib/api';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
  Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue,
} from '@/Components/ui/select';

/**
 * Dokumen Publikasi — port `app/dashboard/produk/AdminProduk.tsx`.
 *
 * Kategori dokumen datang dari server (`config/dokumen.php`), bukan disalin
 * ulang di sini: nilainya sama dengan kolom `jenis` yang sudah ada di produksi,
 * dan tiap kategori menentukan di halaman publik mana berkasnya muncul.
 */

const GRUP = ['Produk Layanan', 'PPID / Transparansi'];

export default function Produk({ kategori }) {
  const [jenis, setJenis] = useState('PERSYARATAN');
  const [items, setItems] = useState([]);
  const [memuat, setMemuat] = useState(true);
  const [cari, setCari] = useState('');
  const [buka, setBuka] = useState(false);
  const [judul, setJudul] = useState('');
  const [file, setFile] = useState('');
  const [mengunggah, setMengunggah] = useState(false);
  const [menyimpan, setMenyimpan] = useState(false);
  const [menghapusId, setMenghapusId] = useState(null);
  const [pesan, setPesan] = useState(null);
  const inputBerkas = useRef(null);

  const aktif = kategori.find((k) => k.key === jenis);

  const tersaring = cari.trim()
    ? items.filter((p) => p.judul.toLowerCase().includes(cari.trim().toLowerCase()))
    : items;

  const muat = useCallback(async () => {
    setMemuat(true);
    const j = await ambilJson(`/api/admin/produk?jenis=${jenis}`);
    setMemuat(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }
    setItems(j.data?.items ?? []);
  }, [jenis]);

  useEffect(() => { muat(); }, [muat]);

  const pilihBerkas = async (e) => {
    const f = e.target.files?.[0];
    if (!f) return;

    setMengunggah(true);
    const fd = new FormData();
    fd.append('file', f);
    fd.append('folder', 'produk');

    const j = await kirimBerkas('/api/upload', fd);
    setMengunggah(false);
    // Supaya memilih berkas yang sama lagi tetap memicu onChange.
    if (inputBerkas.current) inputBerkas.current.value = '';

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }
    if (!j.data?.url) { setPesan({ tipe: 'galat', teks: 'Respons server tidak dikenali' }); return; }

    setFile(j.data.url);
    setPesan({ tipe: 'sukses', teks: 'File terunggah' });
  };

  const simpan = async () => {
    if (!judul.trim() || !file) {
      setPesan({ tipe: 'galat', teks: 'Judul dan file wajib diisi' });
      return;
    }

    setMenyimpan(true);
    const j = await kirimJson('/api/admin/produk', { jenis, judul, file });
    setMenyimpan(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Tersimpan' });
    setBuka(false);
    muat();
  };

  const hapus = async (p) => {
    if (!window.confirm(`Hapus "${p.judul}"?`)) return;

    setMenghapusId(p.id);
    const j = await kirimJson(`/api/admin/produk/${p.id}`, {}, 'DELETE');
    setMenghapusId(null);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Dihapus' });
    setItems((p2) => p2.filter((i) => i.id !== p.id));
  };

  return (
    <LayoutDashboard judul="Dokumen Publikasi">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <Kartu>
        <div className="mb-4 grid gap-3 lg:grid-cols-[320px_1fr]">
          <div className="space-y-1.5">
            <Label htmlFor="jenis" className="text-slate-700">Kategori Dokumen</Label>
            <Select value={jenis} onValueChange={(v) => { setJenis(v); setCari(''); }}>
              <SelectTrigger id="jenis" className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {GRUP.map((g) => (
                  <SelectGroup key={g}>
                    <SelectLabel>{g}</SelectLabel>
                    {kategori.filter((k) => k.group === g).map((k) => (
                      <SelectItem key={k.key} value={k.key}>{k.label}</SelectItem>
                    ))}
                  </SelectGroup>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="rounded-xl border border-brand/20 bg-brand/5 px-4 py-3 text-sm text-slate-700">
            <p className="flex items-center gap-1.5 font-medium text-slate-800">
              <Globe className="h-4 w-4 text-brand" aria-hidden />
              Berkas kategori ini otomatis tampil di halaman publik:
            </p>
            <div className="mt-1.5 flex flex-wrap gap-2">
              {aktif?.halaman.map((h) => (
                <a key={h.href} href={h.href} target="_blank" rel="noopener noreferrer"
                   className="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-medium text-brand ring-1 ring-brand/20 hover:bg-brand/10">
                  {h.label} <ExternalLink className="h-3 w-3" aria-hidden />
                </a>
              ))}
            </div>
          </div>
        </div>

        <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
          <div className="relative flex-1">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <Input value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Cari nama dokumen..."
                   className="pl-9" />
          </div>
          <Tombol onClick={() => { setJudul(''); setFile(''); setBuka(true); }} kelas="sm:w-auto">
            <Plus className="h-4 w-4" />Tambah Dokumen
          </Tombol>
        </div>

        {memuat ? <Memuat /> : tersaring.length === 0 ? (
          <Kosong>
            {cari.trim() ? `Tidak ada dokumen cocok "${cari}".` : 'Belum ada dokumen pada kategori ini.'}
          </Kosong>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-slate-200 text-left text-slate-500">
                  <th className="py-2 pr-4 font-medium">Nama Dokumen</th>
                  <th className="py-2 pr-4 font-medium">File</th>
                  <th className="py-2 pr-4 font-medium">Diunggah oleh</th>
                  <th className="py-2 pr-4 font-medium">Tanggal</th>
                  <th className="py-2 pr-4 text-right font-medium">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {tersaring.map((p) => (
                  <tr key={p.id} className="border-b border-slate-100">
                    <td className="py-2.5 pr-4 font-medium text-slate-800">
                      <span className="inline-flex items-center gap-2">
                        <FileText className="h-4 w-4 text-brand" /> {p.judul}
                      </span>
                    </td>
                    <td className="py-2.5 pr-4">
                      {p.file ? (
                        <a href={p.file} target="_blank" rel="noopener noreferrer"
                           className="inline-flex items-center gap-1 text-xs text-brand hover:underline">
                          Lihat <ExternalLink className="h-3 w-3" />
                        </a>
                      ) : '-'}
                    </td>
                    <td className="py-2.5 pr-4 text-xs text-slate-500">
                      {p.uploaded_by_name ?? <span className="text-slate-300">—</span>}
                    </td>
                    <td className="py-2.5 pr-4 text-xs text-slate-500">{tglSingkat(p.created_at)}</td>
                    <td className="py-2.5 pr-4">
                      <div className="flex justify-end">
                        <Tombol varian="garis" kelas="text-rose-600" disabled={menghapusId === p.id}
                                onClick={() => hapus(p)} aria-label={`Hapus ${p.judul}`}>
                          {menghapusId === p.id ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <Trash2 className="h-3.5 w-3.5" />}
                        </Tombol>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Kartu>

      {buka && (
        <Modal judul={`Tambah Dokumen — ${aktif?.label ?? ''}`} onTutup={() => setBuka(false)}>
          <div className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="judul-dok" className="text-slate-700">Nama Dokumen</Label>
              <Input id="judul-dok" value={judul} onChange={(e) => setJudul(e.target.value)}
                     placeholder={`Contoh: ${aktif?.label ?? 'Dokumen'} ${new Date().getFullYear()}`} />
              <p className="text-xs text-slate-400">
                Nama ini tampil sebagai &quot;Nama Berkas&quot; di halaman publik.
              </p>
            </div>

            <div className="space-y-1.5">
              <span className="text-sm font-medium text-slate-700">File (PDF/JPG/PNG, maks 5MB)</span>
              <input ref={inputBerkas} type="file" accept=".pdf,image/png,image/jpeg" className="hidden" onChange={pilihBerkas} />
              {file ? (
                <div className="flex items-center justify-between rounded-lg border border-slate-200 p-3 text-sm">
                  <a href={file} target="_blank" rel="noopener noreferrer"
                     className="inline-flex items-center gap-1 truncate text-brand hover:underline">
                    <FileText className="h-4 w-4 shrink-0" /><span className="truncate">{file.split('/').pop()}</span>
                  </a>
                  <button type="button" onClick={() => setFile('')} className="text-xs text-rose-600 hover:underline">Ganti</button>
                </div>
              ) : (
                <button type="button" onClick={() => inputBerkas.current?.click()} disabled={mengunggah}
                        className="flex w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-200 py-6 text-slate-400 hover:border-brand/40 hover:text-brand">
                  {mengunggah ? <Loader2 className="h-6 w-6 animate-spin" /> : <Upload className="h-6 w-6" />}
                  <span className="text-sm">{mengunggah ? 'Mengunggah...' : 'Pilih file'}</span>
                </button>
              )}
            </div>

            <p className="rounded-lg bg-brand/5 px-3 py-2 text-xs text-slate-600">
              Setelah disimpan, dokumen langsung tampil di:{' '}
              <b>{aktif?.halaman.map((h) => h.label).join(', ')}</b>
            </p>

            <div className="flex justify-end gap-2 pt-2">
              <Tombol varian="garis" onClick={() => setBuka(false)}>Batal</Tombol>
              <Tombol onClick={simpan} disabled={menyimpan || mengunggah}>
                {menyimpan && <Loader2 className="h-4 w-4 animate-spin" />}Simpan
              </Tombol>
            </div>
          </div>
        </Modal>
      )}
    </LayoutDashboard>
  );
}
