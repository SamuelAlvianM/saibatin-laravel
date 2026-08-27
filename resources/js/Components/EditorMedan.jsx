import { lazy, Suspense, useState } from 'react';
import { Plus, Search, Shapes, Trash2 } from 'lucide-react';
import BidangGambar from '@/Components/BidangGambar';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { ikon, NAMA_IKON } from '@/lib/ikon';

/**
 * Satu medan formulir blok konten — port `components/konten/field-editor.tsx`.
 *
 * Bentuknya digerakkan skema dari server (`config/konten.php` → `medan`), bukan
 * ditulis ulang per blok: menambah kolom pada sebuah blok cukup di config, dan
 * dialog Mode Edit langsung mengikutinya.
 *
 * Tipe medan : text · textarea · list · richtext · image · items
 * Tipe kolom : text · image · icon · parent · richtext
 *
 * 🔴 Komponen di bawah dideklarasikan di TINGKAT MODUL, bukan di dalam badan
 * komponen lain. Komponen yang dibuat ulang tiap render jadi tipe baru tiap
 * render, React melepas & memasang ulang seluruh subtree-nya, dan fokus input
 * hilang tiap satu huruf. Sudah terjadi dua kali di port ini (HANDOFF §5 no. 17).
 */

// tiptap + ekstensinya ± 100 KB; hanya blok yang benar-benar punya kolom
// richtext (Produk Disdukcapil) yang membayarnya.
const PenyuntingKaya = lazy(() => import('@/Components/PenyuntingKaya'));

/** Kolom bernuansa uraian → textarea yang bisa memanjang, bukan satu baris. */
function panjang(nama) {
  return /desc|ket|penjelasan|subtitle|isi|jawaban/i.test(nama);
}

function Catatan({ teks }) {
  return (
    <p className="rounded-lg bg-blue-50 px-3 py-2 text-xs leading-relaxed text-blue-800 ring-1 ring-blue-100">
      {teks}
    </p>
  );
}

function TombolHapus({ onClick, judul }) {
  return (
    <button type="button" onClick={onClick} title={judul} aria-label={judul}
            className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-rose-600 transition-colors hover:bg-rose-50">
      <Trash2 className="h-4 w-4" />
    </button>
  );
}

/**
 * Kolom gambar: ubin `BidangGambar` yang sudah dipakai dashboard, plus
 * keterangan ukuran di bawahnya. Rasio yang disarankan ikut mengunci pemotongan
 * saat unggah, supaya slide carousel tetap seragam.
 */
function KolomGambar({ label, nilai, onUbah, rasio, petunjuk, onGalat }) {
  return (
    <div className="space-y-1">
      <BidangGambar label={label} nilai={nilai} onUbah={onUbah} rasio={rasio} onGalat={onGalat}
                    kelas="w-full" gaya={{ aspectRatio: String(rasio ?? 4 / 3) }} />
      {petunjuk && (
        <p className="text-center text-[0.65rem] font-medium text-slate-400">{petunjuk}</p>
      )}
    </div>
  );
}

/** Pemilih ikon: tombol berikon + panel grid yang bisa dicari. */
function BidangIkon({ nilai, onUbah }) {
  const [buka, setBuka] = useState(false);
  const [cari, setCari] = useState('');
  const Terpilih = nilai ? ikon(nilai) : null;
  const hasil = NAMA_IKON.filter((n) => n.toLowerCase().includes(cari.trim().toLowerCase()));

  return (
    <div className="relative">
      <Button type="button" variant="outline" onClick={() => setBuka((b) => !b)}
              className="h-9 w-full justify-start gap-2" title="Pilih ikon">
        {Terpilih ? <Terpilih className="h-4 w-4 shrink-0 text-brand" />
                  : <Shapes className="h-4 w-4 shrink-0 text-slate-300" />}
        <span className="truncate text-xs">{nilai || 'Pilih ikon'}</span>
      </Button>

      {buka && (
        <div className="absolute z-10 mt-1 w-full min-w-[15rem] rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
          <div className="relative mb-2">
            <Search className="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
            <Input value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Cari ikon…"
                   className="h-8 pl-8 text-xs" autoFocus />
          </div>
          <div className="grid max-h-48 grid-cols-6 gap-1.5 overflow-y-auto">
            {hasil.map((nama) => {
              const Ikon = ikon(nama);

              return (
                <button key={nama} type="button" title={nama}
                        onClick={() => { onUbah(nama); setBuka(false); }}
                        className={`flex aspect-square items-center justify-center rounded-lg border transition-colors ${
                          nilai === nama ? 'border-brand bg-brand/10 text-brand'
                                         : 'border-slate-200 text-slate-600 hover:border-brand/40 hover:bg-slate-50'
                        }`}>
                  <Ikon className="h-4 w-4" />
                </button>
              );
            })}
            {hasil.length === 0 && (
              <p className="col-span-6 py-4 text-center text-xs text-slate-400">Ikon tidak ditemukan.</p>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

/** Satu baris `items` — kartu: gambar di kiri, kolom teks bertumpuk di kanan. */
function BarisItem({ baris, nomor, kolom, semua, onUbahKolom, onHapus, onGalat }) {
  const kolomGambar = kolom.find((k) => k.tipe === 'image');
  const kolomTeks = kolom.filter((k) => k.tipe !== 'image');

  return (
    <div className="rounded-xl border border-slate-200 bg-slate-50/40 p-3">
      <div className="mb-2 flex items-center justify-between">
        <span className="rounded-full bg-brand/10 px-2.5 py-0.5 text-[0.68rem] font-bold text-brand">
          Baris {nomor}
        </span>
        <TombolHapus onClick={onHapus} judul="Hapus baris" />
      </div>

      <div className="flex flex-col gap-3 sm:flex-row">
        {kolomGambar && (
          <div className="w-full shrink-0 sm:w-36">
            <KolomGambar label={kolomGambar.label} nilai={baris[kolomGambar.nama] ?? ''}
                         rasio={kolomGambar.rasio} petunjuk={kolomGambar.petunjuk} onGalat={onGalat}
                         onUbah={(v) => onUbahKolom(kolomGambar.nama, v)} />
          </div>
        )}

        <div className="min-w-0 flex-1 space-y-2.5">
          {kolomTeks.map((k) => (
            <div key={k.nama} className="space-y-1">
              <p className="text-[0.7rem] font-medium uppercase tracking-wide text-slate-400">{k.label}</p>

              {k.tipe === 'icon' ? (
                <BidangIkon nilai={baris[k.nama] ?? ''} onUbah={(v) => onUbahKolom(k.nama, v)} />
              ) : k.tipe === 'parent' ? (
                // 🔴 SelectItem Radix tidak boleh bernilai "" — string kosong
                // sudah dipakai Radix sendiri sebagai "belum ada pilihan" dan
                // melempar galat runtime. Penanda `__akar__` diterjemahkan
                // kembali ke "" saat dipilih.
                <Select value={(baris[k.nama] ?? '') || '__akar__'}
                        onValueChange={(v) => onUbahKolom(k.nama, v === '__akar__' ? '' : v)}>
                  <SelectTrigger className="w-full bg-white" title={k.label}>
                    <SelectValue placeholder="— Paling atas —" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="__akar__">— Paling atas —</SelectItem>
                    {semua.map((r, i) => (
                      r !== baris && r.jabatan
                        ? <SelectItem key={i} value={r.jabatan}>{r.jabatan}</SelectItem>
                        : null
                    ))}
                  </SelectContent>
                </Select>
              ) : k.tipe === 'richtext' ? (
                <Suspense fallback={<p className="text-xs text-slate-400">Memuat penyunting…</p>}>
                  <PenyuntingKaya nilai={baris[k.nama] ?? ''} onGalat={onGalat}
                                  placeholder={k.label}
                                  onUbah={(v) => onUbahKolom(k.nama, v)} />
                </Suspense>
              ) : panjang(k.nama) ? (
                <Textarea rows={3} value={baris[k.nama] ?? ''} placeholder={k.label} className="bg-white"
                          onChange={(e) => onUbahKolom(k.nama, e.target.value)} />
              ) : (
                <Input value={baris[k.nama] ?? ''} placeholder={k.label} className="bg-white"
                       onChange={(e) => onUbahKolom(k.nama, e.target.value)} />
              )}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

export default function EditorMedan({ medan, nilai, onUbah, onGalat }) {
  if (medan.tipe === 'text') {
    return (
      <div className="space-y-1.5">
        <Label>{medan.label}</Label>
        <Input value={String(nilai ?? '')} onChange={(e) => onUbah(e.target.value)} />
        {medan.catatan && <Catatan teks={medan.catatan} />}
      </div>
    );
  }

  if (medan.tipe === 'textarea') {
    return (
      <div className="space-y-1.5">
        <Label>{medan.label}</Label>
        <Textarea rows={3} value={String(nilai ?? '')} onChange={(e) => onUbah(e.target.value)} />
        {medan.catatan && <Catatan teks={medan.catatan} />}
      </div>
    );
  }

  if (medan.tipe === 'image') {
    return (
      <div className="space-y-1.5">
        <Label>{medan.label}</Label>
        <div className="max-w-xs">
          <KolomGambar label={medan.label} nilai={String(nilai ?? '')} onUbah={onUbah}
                       rasio={medan.rasio} petunjuk={medan.petunjuk} onGalat={onGalat} />
        </div>
        {medan.catatan && <Catatan teks={medan.catatan} />}
      </div>
    );
  }

  if (medan.tipe === 'richtext') {
    return (
      <div className="space-y-1.5">
        <Label>{medan.label}</Label>
        <Suspense fallback={<p className="text-xs text-slate-400">Memuat penyunting…</p>}>
          <PenyuntingKaya nilai={String(nilai ?? '')} onUbah={onUbah} onGalat={onGalat}
                          placeholder="Tulis isi di sini…" />
        </Suspense>
      </div>
    );
  }

  if (medan.tipe === 'list') {
    // Tiap poin satu input terpisah — lebih jelas daripada satu textarea yang
    // dipecah per baris, dan urutannya tidak pernah ambigu.
    const daftar = Array.isArray(nilai) ? nilai : [];

    return (
      <div className="space-y-2">
        <Label>{medan.label}</Label>
        {medan.catatan && <Catatan teks={medan.catatan} />}

        <div className="space-y-2">
          {daftar.map((poin, i) => (
            <div key={i} className="flex items-start gap-2">
              <span className="w-5 shrink-0 pt-2 text-center text-xs font-bold text-slate-400">{i + 1}</span>
              <Textarea rows={2} value={poin} placeholder={`Poin ${i + 1}`}
                        onChange={(e) => onUbah(daftar.map((s, j) => (j === i ? e.target.value : s)))} />
              <TombolHapus onClick={() => onUbah(daftar.filter((_, j) => j !== i))} judul="Hapus poin" />
            </div>
          ))}
          {daftar.length === 0 && <p className="pl-7 text-xs text-slate-400">Belum ada poin.</p>}
        </div>

        <Button type="button" variant="outline" size="sm" onClick={() => onUbah([...daftar, ''])}>
          <Plus className="mr-1.5 h-4 w-4" /> Tambah Poin
        </Button>
      </div>
    );
  }

  // items
  const baris = Array.isArray(nilai) ? nilai : [];
  const kolom = medan.kolom ?? [];

  return (
    <div className="space-y-2">
      <Label>{medan.label}</Label>
      {medan.catatan && <Catatan teks={medan.catatan} />}

      <div className="space-y-3">
        {baris.map((b, i) => (
          <BarisItem key={i} baris={b} nomor={i + 1} kolom={kolom} semua={baris} onGalat={onGalat}
                     onUbahKolom={(nama, v) => onUbah(baris.map((r, j) => (j === i ? { ...r, [nama]: v } : r)))}
                     onHapus={() => onUbah(baris.filter((_, j) => j !== i))} />
        ))}
        {baris.length === 0 && <p className="text-xs text-slate-400">Belum ada baris.</p>}
      </div>

      <Button type="button" variant="outline" size="sm"
              onClick={() => onUbah([...baris, Object.fromEntries(kolom.map((k) => [k.nama, '']))])}>
        <Plus className="mr-1.5 h-4 w-4" /> Tambah Baris
      </Button>
    </div>
  );
}
