import { useCallback, useEffect, useRef, useState } from 'react';
import { FileText, Loader2, Plus, Trash2, Upload } from 'lucide-react';
import { Pesan, Tombol, tglSingkat } from '@/Components/Dasbor';
import { Input } from '@/Components/ui/input';
import { ambilJson, kirimBerkas, kirimJson } from '@/lib/api';
import { modeEditAktif, pantauModeEdit } from '@/lib/mode-edit';

/**
 * Panel dokumen halaman publik — hanya tampil saat MODE EDIT menyala.
 *
 * Port `TambahDokumen` di `components/shared/info-page.tsx`: petugas menambah
 * atau menghapus berkas halaman ini tanpa berpindah ke dashboard Dokumen
 * Publikasi lalu mencari kategorinya sendiri. Kategorinya sudah ditentukan
 * halaman tempat panel ini dipasang (`config/dokumen.php`), jadi tidak ada
 * dropdown yang bisa salah pilih.
 *
 * ⚠️ Beda tipis dari aslinya, dan disengaja: di portal Next.js tombol hapus
 * menempel pada tiap baris TABEL berkas. Tabel di sini dirender Blade di server
 * (justru supaya isinya terbaca mesin pencari), jadi daftar yang bisa dihapus
 * ditampilkan di panel ini — bukan dengan membangun ulang tabelnya di klien.
 *
 * Halaman dimuat ulang setelah berubah: isinya milik server, dan hanya muat
 * ulang yang membuat tabel di atas ikut mutakhir.
 */
export default function DokumenEdit({ jenis = [] }) {
  const [modeEdit, setModeEdit] = useState(modeEditAktif);
  const [items, setItems] = useState([]);
  const [memuat, setMemuat] = useState(false);
  const [judul, setJudul] = useState('');
  const [berkas, setBerkas] = useState('');
  const [namaBerkas, setNamaBerkas] = useState('');
  const [sibuk, setSibuk] = useState(false);
  const [pesan, setPesan] = useState(null);
  const inputBerkas = useRef(null);

  useEffect(() => pantauModeEdit(setModeEdit), []);

  const muat = useCallback(async () => {
    setMemuat(true);
    // Satu halaman bisa menampilkan lebih dari satu kategori dokumen, dan
    // endpoint-nya menyaring satu kategori per panggilan.
    const hasil = await Promise.all(jenis.map((j) => ambilJson(`/api/admin/produk?jenis=${encodeURIComponent(j)}`)));
    setMemuat(false);

    const galat = hasil.find((j) => j.error?.length);
    if (galat) { setPesan({ tipe: 'galat', teks: galat.error[0] }); return; }

    setItems(hasil.flatMap((j) => j.data?.items ?? []));
  }, [jenis]);

  useEffect(() => { if (modeEdit) muat(); }, [modeEdit, muat]);

  if (!modeEdit || jenis.length === 0) return null;

  const pilihBerkas = async (e) => {
    const f = e.target.files?.[0];
    if (!f) return;

    setSibuk(true);
    const fd = new FormData();
    fd.append('file', f);
    fd.append('folder', 'produk');

    const j = await kirimBerkas('/api/upload', fd);
    setSibuk(false);
    // Supaya memilih berkas yang sama lagi tetap memicu onChange.
    if (inputBerkas.current) inputBerkas.current.value = '';

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }
    if (!j.data?.url) { setPesan({ tipe: 'galat', teks: 'Respons server tidak dikenali' }); return; }

    setBerkas(j.data.url);
    setNamaBerkas(f.name);
    if (!judul.trim()) setJudul(f.name.replace(/\.[^.]+$/, ''));
  };

  const simpan = async () => {
    if (!judul.trim() || !berkas) {
      setPesan({ tipe: 'galat', teks: 'Judul dan berkas wajib diisi' });
      return;
    }

    setSibuk(true);
    const j = await kirimJson('/api/admin/produk', { jenis: jenis[0], judul: judul.trim(), file: berkas });
    setSibuk(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }
    window.location.reload();
  };

  const hapus = async (d) => {
    if (!window.confirm(`Hapus dokumen "${d.judul}"?`)) return;

    setSibuk(true);
    const j = await kirimJson(`/api/admin/produk/${d.id}`, {}, 'DELETE');
    setSibuk(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }
    window.location.reload();
  };

  return (
    <div className="mt-4 rounded-2xl border-2 border-dashed border-brand/40 bg-brand/[0.03] p-5">
      <p className="mb-3 flex items-center gap-2 text-sm font-semibold text-brand">
        <FileText className="h-4 w-4" /> Dokumen halaman ini (mode edit)
      </p>

      {memuat ? (
        <p className="flex items-center gap-2 text-sm text-slate-500">
          <Loader2 className="h-4 w-4 animate-spin" /> Memuat daftar dokumen…
        </p>
      ) : items.length === 0 ? (
        <p className="text-sm text-slate-500">Belum ada dokumen di halaman ini.</p>
      ) : (
        <ul className="mb-4 space-y-1.5">
          {items.map((d) => (
            <li key={d.id} className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2">
              <a href={d.file} target="_blank" rel="noopener noreferrer"
                 className="min-w-0 flex-1 truncate text-sm font-medium text-brand hover:underline">
                {d.judul}
              </a>
              <span className="shrink-0 text-xs text-slate-400">{tglSingkat(d.created_at)}</span>
              <button type="button" onClick={() => hapus(d)} disabled={sibuk}
                      title={`Hapus ${d.judul}`} aria-label={`Hapus ${d.judul}`}
                      className="shrink-0 text-slate-400 transition-colors hover:text-rose-600 disabled:opacity-50">
                <Trash2 className="h-4 w-4" />
              </button>
            </li>
          ))}
        </ul>
      )}

      <div className="flex flex-col gap-2 border-t border-brand/20 pt-4 sm:flex-row sm:items-center">
        <Input value={judul} onChange={(e) => setJudul(e.target.value)}
               placeholder="Judul dokumen" className="bg-white sm:flex-1" />

        <input ref={inputBerkas} type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,image/*"
               onChange={pilihBerkas} className="hidden" id="unggah-dokumen-halaman" />
        <Tombol varian="garis" onClick={() => inputBerkas.current?.click()} disabled={sibuk}>
          <Upload className="h-4 w-4" /> {namaBerkas || 'Pilih berkas'}
        </Tombol>

        <Tombol onClick={simpan} disabled={sibuk}>
          {sibuk ? <Loader2 className="h-4 w-4 animate-spin" /> : <Plus className="h-4 w-4" />} Tambah
        </Tombol>
      </div>

      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />
    </div>
  );
}
