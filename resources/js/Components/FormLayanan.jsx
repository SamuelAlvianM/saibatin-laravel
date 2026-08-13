import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import {
  ArrowLeft, CheckCircle2, FileText, Loader2, Send, Upload, X, ZoomIn,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { DatePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { TimePicker } from '@/Components/ui/time-picker';
import { kirimBerkas, kirimJson } from '@/lib/api';
import { cn } from '@/lib/utils';

/**
 * SATU renderer untuk SELURUH 15 layanan permohonan.
 *
 * Port dari `components/dashboard/staff-pengajuan-form.tsx`. Inilah pengganti
 * 15 berkas `*Modal.tsx` (± 19.800 baris) yang sudah mati di portal Next.js:
 * bentuk formulir dibaca dari skema (`config/layanan.php`), bukan ditulis satu
 * per satu per layanan.
 *
 * Dipakai dua peran dengan pengantar berbeda tapi renderer yang sama:
 *   mandiri=true  → warga/OPD mengisi untuk dirinya sendiri
 *   mandiri=false → petugas mengisi atas nama warga
 */

/** Validasi sisi klien. Server memeriksa ulang dengan aturan yang sama. */
function periksaField(fd, nilai) {
  const v = (nilai ?? '').trim();

  if (fd.required && !v) return `${fd.label} wajib diisi`;
  if (!v) return null;

  switch (fd.type) {
    case 'nik':
    case 'kk':
      return /^\d{16}$/.test(v) ? null : `${fd.label} harus 16 digit angka`;
    case 'phone':
      return /^0\d{9,12}$/.test(v) ? null : `${fd.label} harus 10–13 digit dan diawali 0`;
    case 'email':
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? null : `Format ${fd.label} tidak valid`;
    default:
      return null;
  }
}

// `paramBaru` — nama query yang membawa no. register hasil kirim ke halaman
// tujuan. Riwayat warga membacanya sebagai `baru`, daftar permohonan petugas
// sebagai `sorot` (yang juga dipakai server untuk melompat ke halamannya).
export default function FormLayanan({
  layanan, jam, mandiri = false, prefill = {},
  kembaliKe = '/user/pengajuan', paramBaru = 'baru',
}) {
  const [nilai, setNilai] = useState(() => ({ ...prefill }));
  const [namaBerkas, setNamaBerkas] = useState({});
  const [mengunggah, setMengunggah] = useState(null);
  const [galat, setGalat] = useState({});
  const [mengirim, setMengirim] = useState(false);
  const [seret, setSeret] = useState(null);
  const [lihat, setLihat] = useState(null);
  const [pesan, setPesan] = useState(null);

  const semuaField = useMemo(
    () => layanan.sections.flatMap((s) => s.fields),
    [layanan],
  );

  const set = (nama, v) => {
    setNilai((p) => ({ ...p, [nama]: v }));
    if (galat[nama]) setGalat((p) => ({ ...p, [nama]: '' }));
  };

  const unggah = async (fd, file) => {
    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      setPesan({ tipe: 'galat', teks: `${fd.label}: format harus JPG, PNG, atau WebP` });
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      setPesan({ tipe: 'galat', teks: `${fd.label}: ukuran maksimal 5 MB` });
      return;
    }

    setMengunggah(fd.name);
    const fd2 = new FormData();
    fd2.append('file', file);

    const j = await kirimBerkas(`/api/${layanan.slug}/upload`, fd2);
    setMengunggah(null);

    if (j.error?.length) {
      setPesan({ tipe: 'galat', teks: j.error[0] });
      return;
    }

    set(fd.name, j.success?.[0] ?? j.data?.url ?? '');
    setNamaBerkas((p) => ({ ...p, [fd.name]: file.name }));
    setPesan({ tipe: 'sukses', teks: `${fd.label} berhasil diunggah` });
  };

  const kirim = async () => {
    const baru = {};
    for (const fd of semuaField) {
      const e = periksaField(fd, nilai[fd.name] ?? '');
      if (e) baru[fd.name] = e;
    }
    setGalat(baru);

    const alasan = Object.values(baru);
    if (alasan.length) {
      setPesan({
        tipe: 'galat',
        teks: `${alasan.length} data belum lengkap`,
        rinci: alasan.slice(0, 4).join(' • ') + (alasan.length > 4 ? ' …' : ''),
      });
      // Bawa pengguna ke field bermasalah pertama — daftar galat di atas
      // formulir panjang mudah terlewat.
      const awal = semuaField.find((fd) => baru[fd.name]);
      if (awal) document.getElementById(`fld-${awal.name}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    setMengirim(true);
    const j = await kirimJson(`/api/${layanan.slug}/create`, nilai);
    setMengirim(false);

    if (j.error?.length) {
      setPesan({ tipe: 'galat', teks: 'Gagal mengajukan permohonan', rinci: j.error.slice(0, 4).join(' • ') });
      return;
    }

    router.visit(kembaliKe, {
      onSuccess: () => {},
      data: { [paramBaru]: j.data?.noregister ?? '' },
    });
  };

  // ── Render satu field ────────────────────────────────────────────────────
  // Hanya penanda GALAT; bentuk dasar field datang dari `Components/ui/*`,
  // sehingga 15 layanan tidak lagi punya 15 selera gaya sendiri.
  const kelas = (e) => (e ? 'border-destructive focus-visible:ring-destructive/20' : '');

  const renderField = (fd) => {
    const e = galat[fd.name];
    const v = nilai[fd.name] ?? '';

    if (fd.type === 'file') {
      return (
        <div id={`fld-${fd.name}`} className="space-y-1.5">
          <Label className="text-xs text-slate-700">
            {fd.label} {fd.required && <span className="text-destructive">*</span>}
          </Label>

          {v ? (
            <div className="overflow-hidden rounded-lg border-2 border-emerald-300 bg-emerald-50/50">
              <div className="group relative">
                <button type="button" onClick={() => setLihat({ src: v, judul: fd.label })}
                        title="Klik untuk perbesar" className="block w-full cursor-zoom-in">
                  <img src={v} alt={fd.label} className="h-28 w-full bg-slate-100 object-cover" />
                  <span className="absolute inset-0 flex items-center justify-center transition-colors group-hover:bg-black/25">
                    <ZoomIn className="h-5 w-5 text-white opacity-0 transition-opacity group-hover:opacity-100" />
                  </span>
                </button>
                <button type="button" onClick={() => { set(fd.name, ''); setNamaBerkas((p) => { const n = { ...p }; delete n[fd.name]; return n; }); }}
                        title="Batalkan / ganti"
                        className="absolute right-1.5 top-1.5 rounded-full bg-black/55 p-1 text-white transition-colors hover:bg-red-600">
                  <X className="h-3.5 w-3.5" />
                </button>
              </div>
              <p className="flex items-center gap-1 truncate px-2 py-1.5 text-xs text-emerald-700">
                <CheckCircle2 className="h-3.5 w-3.5 shrink-0" />
                <span className="truncate">{namaBerkas[fd.name] ?? 'Terunggah'}</span>
              </p>
            </div>
          ) : (
            <label
              onDragOver={(ev) => { ev.preventDefault(); setSeret(fd.name); }}
              onDragLeave={() => setSeret((d) => (d === fd.name ? null : d))}
              onDrop={(ev) => { ev.preventDefault(); setSeret(null); unggah(fd, ev.dataTransfer.files?.[0]); }}
              className={`flex min-h-[7rem] cursor-pointer flex-col items-center justify-center gap-1.5 rounded-lg border-2 border-dashed py-4 text-center text-xs transition-colors hover:border-primary/40 hover:text-primary ${
                seret === fd.name ? 'border-primary bg-primary/5 text-primary'
                  : e ? 'border-destructive/40 text-destructive' : 'border-slate-200 text-slate-400'
              }`}
            >
              {mengunggah === fd.name ? <Loader2 className="h-5 w-5 animate-spin" /> : <Upload className="h-5 w-5" />}
              <span>{mengunggah === fd.name ? 'Mengunggah...' : 'Pilih atau seret berkas'}</span>
              <span className="text-[0.65rem] text-slate-400">JPG/PNG/WebP, maks 5MB</span>
              <input type="file" accept=".jpg,.jpeg,.png,.webp" className="hidden"
                     disabled={mengunggah === fd.name}
                     onChange={(ev) => unggah(fd, ev.target.files?.[0])} />
            </label>
          )}
          {e && <p className="text-xs text-destructive">{e}</p>}
        </div>
      );
    }

    const angkaSaja = ['nik', 'kk', 'phone'].includes(fd.type);

    return (
      <div id={`fld-${fd.name}`} className="space-y-1.5">
        <Label htmlFor={fd.name} className="text-slate-700">
          {fd.label} {fd.required && <span className="text-destructive">*</span>}
        </Label>

        {fd.type === 'textarea' ? (
          <Textarea id={fd.name} rows={3} value={v} placeholder={fd.placeholder}
                    className={kelas(e)} onChange={(ev) => set(fd.name, ev.target.value)} />
        ) : fd.type === 'select' ? (
          <Select value={v} onValueChange={(nv) => set(fd.name, nv)}>
            <SelectTrigger id={fd.name} className={cn('w-full', kelas(e))}>
              <SelectValue placeholder="— Pilih —" />
            </SelectTrigger>
            <SelectContent>
              {(fd.options ?? []).map((o) => <SelectItem key={o} value={o}>{o}</SelectItem>)}
            </SelectContent>
          </Select>
        ) : fd.type === 'date' ? (
          /* Pemilih tanggal sendiri, bukan `input type="date"`: tampilannya
             seragam di semua peramban dan bisa diketik "17081945" langsung. */
          <DatePicker
            id={fd.name}
            value={v}
            onChange={(nv) => set(fd.name, nv)}
            placeholder={fd.placeholder ?? 'Pilih tanggal'}
            className={kelas(e)}
          />
        ) : fd.type === 'time' ? (
          <TimePicker id={fd.name} value={v} onChange={(nv) => set(fd.name, nv)} className={kelas(e)} />
        ) : (
          <Input
            id={fd.name}
            type={fd.type === 'number' ? 'number' : 'text'}
            inputMode={angkaSaja ? 'numeric' : undefined}
            maxLength={fd.type === 'nik' || fd.type === 'kk' ? 16 : fd.type === 'phone' ? 13 : undefined}
            value={v}
            placeholder={fd.placeholder}
            className={kelas(e)}
            onChange={(ev) => set(fd.name, angkaSaja ? ev.target.value.replace(/\D/g, '') : ev.target.value)}
          />
        )}
        {e && <p className="text-xs text-destructive">{e}</p>}
      </div>
    );
  };

  // ── Gerbang jam layanan ──────────────────────────────────────────────────
  if (jam && !jam.open) {
    return (
      <div className="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center">
        <h2 className="text-lg font-semibold text-amber-900">Layanan sedang tutup</h2>
        <p className="mx-auto mt-2 max-w-md text-sm leading-relaxed text-amber-800">{jam.message}</p>
        <Button variant="outline" onClick={() => router.visit(kembaliKe)}
                className="mt-4 border-amber-300 bg-white font-semibold text-amber-900 hover:bg-amber-100">
          <ArrowLeft className="h-4 w-4" />Kembali
        </Button>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <h2 className="flex items-center gap-2 text-lg font-semibold text-slate-900">
          <FileText className="h-5 w-5 text-brand" /> {layanan.title}
        </h2>
        <p className="text-sm text-slate-500">{layanan.desc}</p>
      </div>

      <div className="mb-6 rounded-xl border border-brand/20 bg-brand/5 px-4 py-2.5 text-sm text-brand">
        {mandiri
          ? <>Lengkapi seluruh data di bawah lalu kirim. Isi <b>satu halaman ini</b> sesuai dokumen asli — data Anda akan diverifikasi petugas.</>
          : <>Anda mengisi permohonan <b>atas nama warga</b>. Pastikan data sesuai dokumen asli sebelum dikirim.</>}
      </div>

      {pesan && (
        <div className={`mb-4 rounded-lg border p-3 text-sm ${
          pesan.tipe === 'galat' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'
        }`}>
          <p className="font-medium">{pesan.teks}</p>
          {pesan.rinci && <p className="mt-0.5 text-xs opacity-90">{pesan.rinci}</p>}
        </div>
      )}

      <div className="space-y-5">
        {layanan.sections.map((seksi, i) => {
          // Seksi dokumen (semua field bertipe file) tampil grid 3 kolom.
          const dokumen = seksi.fields.length > 0 && seksi.fields.every((fd) => fd.type === 'file');

          return (
            <div key={seksi.title} className="rounded-2xl border border-slate-200 bg-white p-5">
              <div className="mb-4 flex items-center gap-2.5 border-b border-slate-100 pb-3">
                <span className="flex h-6 w-6 items-center justify-center rounded-md bg-brand/10 text-xs font-bold text-brand">{i + 1}</span>
                <h3 className="text-sm font-semibold text-slate-700">{seksi.title}</h3>
              </div>
              <div className={`grid grid-cols-1 gap-4 ${dokumen ? 'sm:grid-cols-2 lg:grid-cols-3' : 'sm:grid-cols-2'}`}>
                {seksi.fields.map((fd) => (
                  <div key={fd.name} className={fd.half || fd.type === 'file' ? '' : 'sm:col-span-2'}>
                    {renderField(fd)}
                  </div>
                ))}
              </div>
            </div>
          );
        })}
      </div>

      <div className="mt-6 flex justify-end gap-3">
        <Button variant="outline" onClick={() => router.visit(kembaliKe)}>
          Batal
        </Button>
        <Button onClick={kirim} disabled={mengirim || !!mengunggah} className="font-semibold">
          {mengirim ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
          {mengirim ? 'Mengirim...' : 'Kirim Permohonan'}
        </Button>
      </div>

      {lihat && (
        <div onClick={() => setLihat(null)}
             className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
          <img src={lihat.src} alt={lihat.judul} className="max-h-full max-w-full object-contain" />
          <button className="absolute right-4 top-4 rounded-full bg-white/15 p-2 text-white hover:bg-white/25">
            <X className="h-5 w-5" />
          </button>
        </div>
      )}
    </div>
  );
}
