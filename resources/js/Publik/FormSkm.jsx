import { useState } from 'react';
import { AlertCircle, CheckCircle2, Loader2, Send } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { kirimJson } from '@/lib/api';

/**
 * Formulir Survei Kepuasan Masyarakat — 9 unsur, skala 1–4
 * (Permenpan RB No. 14 Tahun 2017).
 *
 * 🔴 Kuesioner MILIK PORTAL INI, bukan iframe skm.go.id seperti SIDAKO.
 * Alamat iframe di sana menunjuk instansi Tana Tidung; menyalinnya berarti
 * jawaban warga Pesisir Barat masuk ke rekap dinas lain. Endpoint & rekap
 * IKM-nya sudah ada di port ini sejak Fase 3.
 *
 * Seluruh 9 unsur wajib dinilai — server menolak yang setengah terisi, karena
 * jawaban tak lengkap merusak perhitungan IKM tanpa terlihat rusak. Di sini
 * dicegah lebih awal supaya warga tahu unsur mana yang terlewat, bukan sekadar
 * ditolak setelah menekan Kirim.
 */
export default function FormSkm({ aspek = [], skalaLabel = [] }) {
  const [nama, setNama] = useState('');
  const [jenisKel, setJenisKel] = useState('');
  const [umur, setUmur] = useState('');
  const [pekerjaan, setPekerjaan] = useState('');
  const [saran, setSaran] = useState('');
  const [jawaban, setJawaban] = useState({});
  const [mengirim, setMengirim] = useState(false);
  const [sukses, setSukses] = useState(false);
  const [galat, setGalat] = useState(null);
  const [sorot, setSorot] = useState([]);

  const terisi = Object.keys(jawaban).length;

  const kirim = async (e) => {
    e.preventDefault();
    setGalat(null);

    const belum = aspek.map((_, i) => i).filter((i) => !jawaban[String(i)]);
    if (belum.length) {
      setSorot(belum);
      setGalat(`Masih ada ${belum.length} unsur yang belum dinilai (nomor ${belum.map((i) => i + 1).join(', ')}).`);
      document.getElementById(`unsur-${belum[0]}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    setSorot([]);
    setMengirim(true);
    const j = await kirimJson('/api/skm', {
      nama: nama.trim(),
      jenisKel: jenisKel || null,
      umur: umur ? Number(umur) : null,
      pekerjaan: pekerjaan.trim() || null,
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
      <div className="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm md:p-8">
        <h2 className="mb-4 font-semibold text-slate-900">Identitas Responden</h2>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div className="space-y-1.5 sm:col-span-2">
            <Label htmlFor="skm-nama">Nama</Label>
            <Input id="skm-nama" value={nama} onChange={(e) => setNama(e.target.value)} required
                   placeholder="Nama lengkap" />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="skm-jk">Jenis Kelamin (opsional)</Label>
            {/* Radio dua pilihan, bukan Select: dua opsi di balik dropdown
                justru menambah satu klik tanpa menghemat ruang apa pun. */}
            <div id="skm-jk" className="flex gap-2">
              {['Laki-laki', 'Perempuan'].map((v) => (
                <button key={v} type="button" onClick={() => setJenisKel(jenisKel === v ? '' : v)}
                        className={`flex-1 rounded-lg border px-3 py-2 text-sm font-medium transition-colors ${
                          jenisKel === v
                            ? 'border-brand bg-brand text-white'
                            : 'border-slate-300 bg-white text-slate-600 hover:border-brand'
                        }`}>
                  {v}
                </button>
              ))}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="skm-umur">Umur (opsional)</Label>
            <Input id="skm-umur" type="number" min="1" max="120" value={umur}
                   onChange={(e) => setUmur(e.target.value)} placeholder="mis. 34" />
          </div>
          <div className="space-y-1.5 sm:col-span-2">
            <Label htmlFor="skm-kerja">Pekerjaan (opsional)</Label>
            <Input id="skm-kerja" value={pekerjaan} onChange={(e) => setPekerjaan(e.target.value)}
                   placeholder="mis. Wiraswasta" />
          </div>
        </div>
      </div>

      <div className="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm md:p-8">
        <div className="mb-5 flex flex-wrap items-baseline justify-between gap-2 border-b border-slate-100 pb-4">
          <div>
            <h2 className="font-semibold text-slate-900">Penilaian 9 Unsur Pelayanan</h2>
            <p className="mt-0.5 text-xs text-slate-500">
              Pilih satu nilai untuk setiap unsur. Semuanya wajib dinilai.
            </p>
          </div>
          <span className="rounded-full bg-brand/10 px-3 py-1 text-xs font-semibold text-brand">
            {terisi}/{aspek.length} terisi
          </span>
        </div>

        <ol className="space-y-5">
          {aspek.map((teks, i) => {
            const bermasalah = sorot.includes(i);
            return (
              <li key={i} id={`unsur-${i}`}
                  className={`rounded-xl border p-4 transition-colors ${
                    bermasalah ? 'border-rose-300 bg-rose-50/50' : 'border-slate-100 bg-slate-50/60'
                  }`}>
                <p className="mb-3 flex gap-2 text-sm text-slate-800">
                  <span className="font-bold text-brand">{i + 1}.</span>
                  <span>{teks}</span>
                </p>
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                  {skalaLabel.map((label, k) => {
                    const nilai = k + 1;
                    const dipilih = jawaban[String(i)] === nilai;
                    return (
                      <button key={nilai} type="button"
                              aria-pressed={dipilih}
                              onClick={() => setJawaban((p) => ({ ...p, [String(i)]: nilai }))}
                              className={`rounded-lg border px-2 py-2 text-xs font-medium transition-colors ${
                                dipilih
                                  ? 'border-brand bg-brand text-white shadow-sm'
                                  : 'border-slate-300 bg-white text-slate-600 hover:border-brand hover:text-brand'
                              }`}>
                        <span className="block text-sm font-bold">{nilai}</span>
                        {label}
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
          <Label htmlFor="skm-saran">Saran Perbaikan (opsional)</Label>
          <Textarea id="skm-saran" rows={4} value={saran} onChange={(e) => setSaran(e.target.value)}
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
