import { useState } from 'react';
import { AlertCircle, CheckCircle2, Loader2, Send } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { kirimJson } from '@/lib/api';

/**
 * Formulir Survei Kepuasan Masyarakat — **kuesioner resmi dinas**
 * (berkas Word 17 Agu 2026): 16 pertanyaan skala 1–4, identitas responden,
 * dan kolom keluhan/saran. Dasarnya Permenpan RB No. 14 Tahun 2017.
 *
 * 🔴 Kuesioner MILIK PORTAL INI, bukan iframe skm.go.id seperti SIDAKO. Alamat
 * iframe di sana menunjuk instansi Tana Tidung; menyalinnya berarti jawaban
 * warga Pesisir Barat masuk ke rekap dinas lain.
 *
 * Yang WAJIB hanya 16 penilaian — server menolak yang setengah terisi karena
 * jawaban tak lengkap merusak NRR tanpa terlihat rusak. Identitas seluruhnya
 * opsional, mengikuti berkas dinas ("boleh inisial atau tidak diisi"); dicegah
 * lebih awal di sini supaya warga tahu pertanyaan mana yang terlewat, bukan
 * sekadar ditolak setelah menekan Kirim.
 */

/** Pilihan berbentuk tombol — dipakai pertanyaan ya/tidak yang opsinya dua. */
function PilihanTombol({ id, opsi, nilai, onPilih, kolom = 'sm:grid-cols-2' }) {
  return (
    <div id={id} className={`grid grid-cols-1 gap-2 ${kolom}`}>
      {opsi.map((v) => (
        <button key={v} type="button" onClick={() => onPilih(nilai === v ? '' : v)}
                aria-pressed={nilai === v}
                className={`rounded-lg border px-3 py-2 text-sm font-medium transition-colors ${
                  nilai === v
                    ? 'border-brand bg-brand text-white'
                    : 'border-slate-300 bg-white text-slate-600 hover:border-brand'
                }`}>
          {v}
        </button>
      ))}
    </div>
  );
}

/**
 * Penanda "belum dipilih" untuk dropdown identitas.
 *
 * 🔴 `SelectItem` Radix TIDAK BOLEH bernilai string kosong — nilai itu sudah
 * dipakai Radix sendiri sebagai "belum ada pilihan", dan memakainya melempar
 * galat runtime yang mengosongkan halaman. Seluruh isian identitas di sini
 * opsional, jadi pilihan "tidak diisi" memang harus ada; penandanya
 * diterjemahkan kembali ke string kosong di `onValueChange`.
 */
const KOSONG = '__kosong__';

/** Dropdown identitas (opsional) — pilihan panjang tidak muat jadi deret tombol. */
function PilihanDropdown({ id, opsi, nilai, onPilih, placeholder = 'Pilih…' }) {
  return (
    <Select value={nilai || KOSONG} onValueChange={(v) => onPilih(v === KOSONG ? '' : v)}>
      <SelectTrigger id={id} className="w-full bg-white">
        <SelectValue placeholder={placeholder} />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value={KOSONG}>Pilih Opsi Berikut</SelectItem>
        {opsi.map((v) => <SelectItem key={v} value={v}>{v}</SelectItem>)}
      </SelectContent>
    </Select>
  );
}

export default function FormSkm({
  pertanyaan = [], skalaLabel = {}, pendidikan = [], pekerjaan = [], jenisDisabilitas = [],
}) {
  const [identitas, setIdentitas] = useState({
    nama: '', instansi: '', umur: '', jenisKel: '',
    pendidikan: '', pekerjaan: '', pekerjaanLain: '', produkLayanan: '',
    disabilitas: '', jenisDisabilitas: '',
  });
  const [jawaban, setJawaban] = useState({});
  const [saran, setSaran] = useState('');
  const [mengirim, setMengirim] = useState(false);
  const [sukses, setSukses] = useState(false);
  const [galat, setGalat] = useState(null);
  const [sorot, setSorot] = useState([]);

  const set = (k, v) => setIdentitas((p) => ({ ...p, [k]: v }));
  const terisi = pertanyaan.filter((p) => jawaban[p.kunci]).length;

  const kirim = async (e) => {
    e.preventDefault();
    setGalat(null);

    const belum = pertanyaan.map((p, i) => (jawaban[p.kunci] ? null : i)).filter((i) => i !== null);
    if (belum.length) {
      setSorot(belum);
      setGalat(`Masih ada ${belum.length} pertanyaan yang belum dinilai (nomor ${belum.map((i) => i + 1).join(', ')}).`);
      document.getElementById(`skm-p${belum[0]}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    setSorot([]);
    setMengirim(true);

    const j = await kirimJson('/api/skm', {
      nama: identitas.nama.trim() || null,
      instansi: identitas.instansi.trim() || null,
      umur: identitas.umur ? Number(identitas.umur) : null,
      jenisKel: identitas.jenisKel || null,
      pendidikan: identitas.pendidikan || null,
      // "Lainnya" menyimpan apa yang diketik warga, bukan kata "Lainnya" —
      // kalau tidak, rekap pekerjaan penuh baris tanpa makna.
      pekerjaan: identitas.pekerjaan === 'Lainnya'
        ? (identitas.pekerjaanLain.trim() || 'Lainnya')
        : (identitas.pekerjaan || null),
      produkLayanan: identitas.produkLayanan.trim() || null,
      disabilitas: identitas.disabilitas === '' ? null : identitas.disabilitas === 'Ya',
      jenisDisabilitas: identitas.disabilitas === 'Ya' ? (identitas.jenisDisabilitas || null) : null,
      jawaban,
      saran: saran.trim() || null,
    });

    setMengirim(false);
    if (j.error?.length) { setGalat(j.error[0]); return; }
    setSukses(true);
  };

  if (sukses) {
    return (
      <div className="rounded-2xl border border-slate-200/60 bg-white p-10 text-center shadow-sm">
        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50">
          <CheckCircle2 className="h-8 w-8 text-emerald-600" />
        </div>
        <h3 className="mb-2 text-xl font-semibold text-slate-900">Terima Kasih</h3>
        <p className="mx-auto max-w-md text-sm text-slate-500">
          Penilaian Anda sudah kami terima dan akan dihitung ke dalam Indeks Kepuasan
          Masyarakat (IKM) Disdukcapil Kabupaten Pesisir Barat.
        </p>
      </div>
    );
  }

  return (
    <form onSubmit={kirim} className="space-y-6">
      {/* ── Identitas responden (seluruhnya opsional) ─────────────────────── */}
      <div className="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm md:p-8">
        <div className="mb-4 border-b border-slate-100 pb-3">
          <h2 className="font-semibold text-slate-900">Identitas Responden</h2>
          <p className="mt-0.5 text-xs text-slate-500">
            Seluruhnya boleh dikosongkan — nama cukup inisial bila Anda tidak ingin disebut.
          </p>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div className="space-y-1.5">
            <Label htmlFor="skm-nama">Nama</Label>
            <Input id="skm-nama" value={identitas.nama} onChange={(e) => set('nama', e.target.value)}
                   placeholder="Boleh inisial atau dikosongkan" />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="skm-instansi">Instansi</Label>
            <Input id="skm-instansi" value={identitas.instansi} onChange={(e) => set('instansi', e.target.value)}
                   placeholder="Opsional" />
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="skm-umur">Umur</Label>
            <Input id="skm-umur" type="number" min="1" max="120" value={identitas.umur}
                   onChange={(e) => set('umur', e.target.value)} placeholder="mis. 34 tahun" />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="skm-jk">Jenis Kelamin</Label>
            <PilihanDropdown id="skm-jk" opsi={['Laki-laki', 'Perempuan']}
                             nilai={identitas.jenisKel} onPilih={(v) => set('jenisKel', v)}
                             placeholder="Pilih jenis kelamin" />
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="skm-pendidikan">Pendidikan Terakhir</Label>
            <PilihanDropdown id="skm-pendidikan" opsi={pendidikan}
                             nilai={identitas.pendidikan} onPilih={(v) => set('pendidikan', v)}
                             placeholder="Pilih pendidikan terakhir" />
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="skm-pekerjaan">Pekerjaan Utama</Label>
            <PilihanDropdown id="skm-pekerjaan" opsi={pekerjaan}
                             nilai={identitas.pekerjaan} onPilih={(v) => set('pekerjaan', v)}
                             placeholder="Pilih pekerjaan utama" />
            {identitas.pekerjaan === 'Lainnya' && (
              <Input value={identitas.pekerjaanLain} onChange={(e) => set('pekerjaanLain', e.target.value)}
                     placeholder="Sebutkan pekerjaan Anda" className="mt-2" />
            )}
          </div>

          <div className="space-y-1.5 sm:col-span-2">
            <Label htmlFor="skm-produk">Produk Pelayanan</Label>
            <Input id="skm-produk" value={identitas.produkLayanan}
                   onChange={(e) => set('produkLayanan', e.target.value)}
                   placeholder="contoh: Kartu Keluarga" />
          </div>

          <div className="space-y-1.5 sm:col-span-2">
            <Label htmlFor="skm-disabilitas">
              Apakah Anda penyandang disabilitas / pendamping penyandang disabilitas?
            </Label>
            <PilihanTombol id="skm-disabilitas" opsi={['Ya', 'Tidak']}
                           nilai={identitas.disabilitas} onPilih={(v) => set('disabilitas', v)} />
          </div>

          {/* Muncul hanya bila "Ya" — persis catatan "(Jika tidak, lewati)"
              pada berkas kuesioner dinas. */}
          {identitas.disabilitas === 'Ya' && (
            <div className="space-y-1.5 sm:col-span-2">
              <Label htmlFor="skm-jenis-disabilitas">Jenis disabilitas yang Anda miliki / dampingi</Label>
              <PilihanDropdown id="skm-jenis-disabilitas" opsi={jenisDisabilitas}
                               nilai={identitas.jenisDisabilitas}
                               onPilih={(v) => set('jenisDisabilitas', v)}
                               placeholder="Pilih jenis disabilitas" />
            </div>
          )}
        </div>
      </div>

      {/* ── 16 pertanyaan ────────────────────────────────────────────────── */}
      <div className="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm md:p-8">
        <div className="mb-5 flex flex-wrap items-baseline justify-between gap-2 border-b border-slate-100 pb-4">
          <div>
            <h2 className="font-semibold text-slate-900">Pendapat Responden tentang Pelayanan</h2>
            <p className="mt-0.5 text-xs text-slate-500">
              Pilih satu jawaban untuk setiap pertanyaan. Semuanya wajib dinilai.
            </p>
          </div>
          <span className="rounded-full bg-brand/10 px-3 py-1 text-xs font-semibold text-brand">
            {terisi}/{pertanyaan.length} terisi
          </span>
        </div>

        <ol className="space-y-5">
          {pertanyaan.map((p, i) => {
            const bermasalah = sorot.includes(i);
            // Sebagian pertanyaan memakai skala "setuju", sebagian "sesuai" —
            // begitulah berkas dinas menuliskannya.
            const label = skalaLabel[p.skala] ?? skalaLabel.setuju ?? [];

            return (
              <li key={p.kunci} id={`skm-p${i}`}
                  className={`rounded-xl border p-4 transition-colors ${
                    bermasalah ? 'border-rose-300 bg-rose-50/50' : 'border-slate-100 bg-slate-50/60'
                  }`}>
                <p className="mb-3 flex gap-2 text-sm text-slate-800">
                  <span className="font-bold text-brand">{i + 1}.</span>
                  <span>{p.teks}</span>
                </p>
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                  {label.map((teks, k) => {
                    const nilai = k + 1;
                    const dipilih = jawaban[p.kunci] === nilai;

                    return (
                      <button key={nilai} type="button" aria-pressed={dipilih}
                              onClick={() => setJawaban((prev) => ({ ...prev, [p.kunci]: nilai }))}
                              className={`rounded-lg border px-2 py-2 text-xs font-medium transition-colors ${
                                dipilih
                                  ? 'border-brand bg-brand text-white shadow-sm'
                                  : 'border-slate-300 bg-white text-slate-600 hover:border-brand hover:text-brand'
                              }`}>
                        <span className="block text-sm font-bold">{nilai}</span>
                        {teks}
                      </button>
                    );
                  })}
                </div>
              </li>
            );
          })}
        </ol>
      </div>

      <div className="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm md:p-8">
        <div className="space-y-1.5">
          <Label htmlFor="skm-saran">Keluhan / Saran Perbaikan (opsional)</Label>
          <Textarea id="skm-saran" rows={5} value={saran} onChange={(e) => setSaran(e.target.value)}
                    placeholder="Apa yang menurut Anda perlu diperbaiki dari pelayanan kami?" />
        </div>
      </div>

      {galat && (
        <div className="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
          <AlertCircle className="mt-0.5 h-4 w-4 flex-none" />
          <span>{galat}</span>
        </div>
      )}

      <Button type="submit" disabled={mengirim} size="lg">
        {mengirim ? <Loader2 className="mr-1.5 h-4 w-4 animate-spin" /> : <Send className="mr-1.5 h-4 w-4" />}
        Kirim Penilaian
      </Button>
    </form>
  );
}
