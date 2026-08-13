import { Suspense, lazy, useCallback, useEffect, useState } from 'react';
import { Eye, EyeOff, Loader2, Newspaper, Pencil, Plus, Trash2 } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import BidangGambar from '@/Components/BidangGambar';
import { Kartu, Kosong, Memuat, Modal, Pesan, Tombol, tglSingkat } from '@/Components/Dasbor';
import { ambilJson, kirimJson } from '@/lib/api';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';

// tiptap ± 400 KB — hanya diunduh saat form berita/konten dibuka.
const PenyuntingKaya = lazy(() => import('@/Components/PenyuntingKaya'));

/**
 * Kelola berita — port `app/dashboard/berita/AdminBerita.tsx`.
 *
 * Slug dibuat server (`BeritaAdminController`), tapi pratinjaunya ditampilkan
 * di sini seperti aslinya supaya penulis tahu alamat beritanya sebelum simpan.
 */

const KOSONG = { judul: '', kategori: '', ringkasan: '', konten: '', gambar: '', publish: false };

/** Cermin `lib/slug.ts` — hanya untuk PRATINJAU; yang mengikat tetap server. */
function slugify(teks) {
  return teks.toLowerCase().normalize('NFKD')
    .replace(/[^a-z0-9\s-]/g, '').trim()
    .replace(/\s+/g, '-').replace(/-+/g, '-')
    .slice(0, 120);
}

export default function Berita() {
  const [items, setItems] = useState([]);
  const [memuat, setMemuat] = useState(true);
  const [buka, setBuka] = useState(false);
  const [suntingId, setSuntingId] = useState(null);
  const [form, setForm] = useState(KOSONG);
  const [menyimpan, setMenyimpan] = useState(false);
  const [menghapusId, setMenghapusId] = useState(null);
  const [pesan, setPesan] = useState(null);

  const muat = useCallback(async () => {
    setMemuat(true);
    const j = await ambilJson('/api/admin/berita');
    setMemuat(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }
    setItems(j.data?.items ?? []);
  }, []);

  useEffect(() => { muat(); }, [muat]);

  const bukaBaru = () => { setSuntingId(null); setForm(KOSONG); setBuka(true); };

  const bukaSunting = (n) => {
    setSuntingId(n.id);
    setForm({
      judul: n.judul,
      kategori: n.kategori ?? '',
      ringkasan: n.ringkasan ?? '',
      konten: n.konten,
      gambar: n.gambar ?? '',
      publish: !!n.publish,
    });
    setBuka(true);
  };

  const simpan = async () => {
    // Isi kosong dari editor kaya tetap berupa "<p></p>" — periksa teksnya,
    // bukan HTML-nya, kalau tidak berita kosong lolos validasi.
    const teks = form.konten.replace(/<[^>]*>/g, '').trim();
    if (!form.judul.trim() || !teks) {
      setPesan({ tipe: 'galat', teks: 'Judul dan isi berita wajib diisi' });
      return;
    }

    setMenyimpan(true);
    const j = suntingId
      ? await kirimJson(`/api/admin/berita/${suntingId}`, form, 'PUT')
      : await kirimJson('/api/admin/berita', form);
    setMenyimpan(false);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Tersimpan' });
    setBuka(false);
    muat();
  };

  const hapus = async (n) => {
    if (!window.confirm(`Hapus berita "${n.judul}"?`)) return;

    setMenghapusId(n.id);
    const j = await kirimJson(`/api/admin/berita/${n.id}`, {}, 'DELETE');
    setMenghapusId(null);

    if (j.error?.length) { setPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    setPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Berita dihapus' });
    setItems((p) => p.filter((i) => i.id !== n.id));
  };

  return (
    <LayoutDashboard judul="Berita">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <Kartu>
        <div className="mb-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Newspaper className="h-5 w-5 text-slate-700" />
            <h1 className="font-semibold text-slate-900">Daftar Berita</h1>
          </div>
          <Tombol onClick={bukaBaru}><Plus className="h-4 w-4" />Tambah</Tombol>
        </div>

        {memuat ? <Memuat /> : items.length === 0 ? <Kosong>Belum ada berita.</Kosong> : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-slate-200 text-left text-slate-500">
                  <th className="py-2 pr-4 font-medium">Judul</th>
                  <th className="py-2 pr-4 font-medium">Kategori</th>
                  <th className="py-2 pr-4 font-medium">Status</th>
                  <th className="py-2 pr-4 font-medium">Tanggal</th>
                  <th className="py-2 pr-4 text-right font-medium">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {items.map((n) => (
                  <tr key={n.id} className="border-b border-slate-100">
                    <td className="py-2.5 pr-4">
                      <div className="font-medium text-slate-800">{n.judul}</div>
                      <div className="font-mono text-xs text-slate-400">/{n.slug}</div>
                    </td>
                    <td className="py-2.5 pr-4">{n.kategori ?? '-'}</td>
                    <td className="py-2.5 pr-4">
                      {n.publish ? (
                        <span className="inline-flex items-center gap-1 text-xs font-medium text-emerald-600">
                          <Eye className="h-3.5 w-3.5" /> Publish
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 text-xs font-medium text-slate-500">
                          <EyeOff className="h-3.5 w-3.5" /> Draft
                        </span>
                      )}
                    </td>
                    <td className="py-2.5 pr-4 text-xs text-slate-500">{tglSingkat(n.created_at)}</td>
                    <td className="py-2.5 pr-4">
                      <div className="flex justify-end gap-2">
                        <Tombol varian="garis" onClick={() => bukaSunting(n)} aria-label={`Ubah ${n.judul}`}>
                          <Pencil className="h-3.5 w-3.5" />
                        </Tombol>
                        <Tombol varian="garis" kelas="text-rose-600" disabled={menghapusId === n.id}
                                onClick={() => hapus(n)} aria-label={`Hapus ${n.judul}`}>
                          {menghapusId === n.id ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <Trash2 className="h-3.5 w-3.5" />}
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
        <Modal judul={suntingId ? 'Ubah Berita' : 'Tambah Berita'} lebar="max-w-3xl" onTutup={() => setBuka(false)}>
          <div className="max-h-[70vh] space-y-4 overflow-y-auto pr-1">
            {/* Penyunting di ATAS — menulis berita adalah pekerjaan utamanya. */}
            <div className="space-y-1.5">
              <span className="text-sm font-medium text-slate-700">Isi Berita</span>
              <Suspense fallback={<div className="h-[260px] rounded-lg border border-slate-200 bg-slate-50" />}>
                <PenyuntingKaya nilai={form.konten} placeholder="Tulis isi berita di sini..."
                                onUbah={(html) => setForm((f) => ({ ...f, konten: html }))}
                                onGalat={(teks) => setPesan({ tipe: 'galat', teks })} />
              </Suspense>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="space-y-1.5 sm:col-span-2">
                <Label htmlFor="judul" className="text-slate-700">Judul</Label>
                <Input id="judul" value={form.judul} onChange={(e) => setForm({ ...form, judul: e.target.value })} />
                {form.judul && <p className="font-mono text-xs text-slate-400">slug: /{slugify(form.judul) || '...'}</p>}
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="kategori" className="text-slate-700">Kategori</Label>
                <Input id="kategori" value={form.kategori} placeholder="mis. Pengumuman"
                       onChange={(e) => setForm({ ...form, kategori: e.target.value })} />
              </div>

              <div className="space-y-1.5">
                <span className="text-sm font-medium text-slate-700">Gambar Utama</span>
                <BidangGambar nilai={form.gambar} onUbah={(url) => setForm((f) => ({ ...f, gambar: url }))}
                              label="Gambar Berita" judul="Pilih Gambar Berita" rasio={16 / 9}
                              kelas="aspect-video w-full max-w-xs"
                              onGalat={(teks) => setPesan({ tipe: 'galat', teks })} />
              </div>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="ringkasan" className="text-slate-700">Ringkasan</Label>
              <Textarea id="ringkasan" rows={2} value={form.ringkasan}
                        placeholder="Ringkasan singkat yang tampil di daftar berita"
                        onChange={(e) => setForm({ ...form, ringkasan: e.target.value })} />
            </div>

            <div className="flex items-center gap-2">
              <Checkbox id="publish" checked={form.publish}
                        onCheckedChange={(v) => setForm({ ...form, publish: v === true })} />
              <Label htmlFor="publish" className="cursor-pointer font-normal select-none">
                Publikasikan sekarang
              </Label>
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
