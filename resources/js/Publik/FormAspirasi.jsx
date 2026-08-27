import { useRef, useState } from 'react';
import {
  AlertCircle, CheckCircle2, ImagePlus, Loader2, Send, ShieldCheck, X,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import PenampilGambar from '@/Components/PenampilGambar';
import { kirimBerkas, kirimJson } from '@/lib/api';

/**
 * Formulir aspirasi publik — port `components/shared/wbs-form.tsx` SIDAKO
 * (+ formulir kritik & saran `app/hubungi-kami/kritik-saran/page.tsx`).
 *
 * SATU komponen, tiga varian, karena ketiganya formulir yang sama dengan nada
 * berbeda. Memecahnya jadi tiga berkas berarti tiga tempat yang pelan-pelan
 * menyimpang padahal endpoint & tabelnya sama:
 *
 *   wbs          → pelaporan dugaan pelanggaran      → POST /api/pengaduan
 *   pengaduan    → pengaduan & konsultasi umum       → POST /api/pengaduan
 *   kritik-saran → masukan peningkatan layanan       → POST /api/kritik-saran
 *
 * Yang berubah antar-varian: label, placeholder, subjek, dan dua field khusus
 * WBS (waktu/tempat kejadian & pihak terlibat) yang disembunyikan pada varian
 * lain — menanyakan "pihak yang diduga terlibat" kepada warga yang cuma ingin
 * bertanya jelas tidak pada tempatnya.
 *
 * 🔴 Bukti foto masuk `storage/app/private/`, bukan `public/`. Namanya diawali
 * `wbs_` sehingga `BerkasController` hanya mengizinkan petugas membukanya —
 * pratinjau di formulir memakai objectURL LOKAL, karena pelapor anonim memang
 * tidak akan bisa membuka kembali berkas yang baru saja diunggahnya.
 */

const TEKS = {
  wbs: {
    judul: 'Formulir Laporan WBS',
    petunjuk: 'Isi sesuai fakta yang Anda ketahui. Nama boleh dikosongkan bila Anda ingin melapor secara anonim.',
    labelNama: 'Nama Pelapor (opsional)',
    labelUraian: 'Uraian Kejadian',
    placeholderUraian: 'Jelaskan dugaan penyalahgunaan wewenang/pelanggaran yang Anda ketahui secara detail…',
    subjek: '[WBS] Laporan Whistle Blowing System',
    namaKosong: 'Pelapor Anonim',
    wajibDiisi: 'Uraian kejadian wajib diisi.',
    gagal: 'Gagal mengirim laporan',
    judulSukses: 'Laporan Terkirim',
    pesanSukses: 'Terima kasih. Laporan WBS Anda akan diverifikasi dan ditindaklanjuti sesuai ketentuan. Identitas Anda dijamin kerahasiaannya.',
    tombolLagi: 'Kirim Laporan Lain',
    tombolKirim: 'Kirim Laporan',
  },
  pengaduan: {
    judul: 'Formulir Pengaduan & Konsultasi',
    petunjuk: 'Sampaikan pengaduan atau pertanyaan Anda. Isi kontak agar kami dapat menyampaikan jawabannya.',
    labelNama: 'Nama (opsional)',
    labelUraian: 'Pengaduan / Pertanyaan Anda',
    placeholderUraian: 'Tuliskan pengaduan atau hal yang ingin Anda konsultasikan selengkap mungkin…',
    subjek: '[Pengaduan & Konsultasi] Pengajuan dari portal',
    namaKosong: 'Tanpa Nama',
    wajibDiisi: 'Pengaduan atau pertanyaan wajib diisi.',
    gagal: 'Gagal mengirim pengaduan',
    judulSukses: 'Pengaduan Terkirim',
    pesanSukses: 'Terima kasih. Pengaduan atau konsultasi Anda dicatat, diverifikasi, lalu diteruskan kepada bidang terkait. Jawaban akan disampaikan melalui kontak yang Anda isi.',
    tombolLagi: 'Kirim Lagi',
    tombolKirim: 'Kirim Pengaduan',
  },
  'kritik-saran': {
    judul: 'Formulir Kritik & Saran',
    petunjuk: 'Masukan Anda menjadi bahan evaluasi peningkatan mutu layanan.',
    labelNama: 'Nama',
    labelUraian: 'Kritik / Saran Anda',
    placeholderUraian: 'Tuliskan kritik atau saran Anda untuk peningkatan pelayanan…',
    subjek: null,
    namaKosong: '',
    wajibDiisi: 'Nama dan pesan wajib diisi.',
    gagal: 'Gagal mengirim kritik & saran',
    judulSukses: 'Masukan Terkirim',
    pesanSukses: 'Terima kasih. Masukan Anda sudah kami terima dan akan menjadi bahan evaluasi peningkatan layanan.',
    tombolLagi: 'Kirim Lagi',
    tombolKirim: 'Kirim Masukan',
  },
};

const MAKS_FOTO = 4;

export default function FormAspirasi({ varian = 'pengaduan' }) {
  const t = TEKS[varian] ?? TEKS.pengaduan;
  const wbs = varian === 'wbs';
  const kritik = varian === 'kritik-saran';

  const [nama, setNama] = useState('');
  const [kontak, setKontak] = useState('');
  const [email, setEmail] = useState('');
  const [uraian, setUraian] = useState('');
  const [waktuTempat, setWaktuTempat] = useState('');
  const [pihakTerlibat, setPihakTerlibat] = useState('');
  const [foto, setFoto] = useState([]);
  const [lihat, setLihat] = useState(null);
  const [mengunggah, setMengunggah] = useState(false);
  const [mengirim, setMengirim] = useState(false);
  const [sukses, setSukses] = useState(false);
  const [galat, setGalat] = useState(null);
  const inputBerkas = useRef(null);

  const pilihBerkas = async (daftar) => {
    if (!daftar?.length) return;
    setGalat(null);

    const sisa = MAKS_FOTO - foto.length;
    if (sisa <= 0) { setGalat(`Maksimal ${MAKS_FOTO} foto.`); return; }

    setMengunggah(true);
    for (const f of Array.from(daftar).slice(0, sisa)) {
      if (!/\.(jpe?g|png)$/i.test(f.name)) { setGalat('Foto harus berformat JPG atau PNG.'); continue; }
      if (f.size > 5 * 1024 * 1024) { setGalat(`Foto "${f.name}" melebihi 5 MB.`); continue; }

      const fd = new FormData();
      fd.append('file', f);
      const j = await kirimBerkas('/api/pengaduan/upload', fd);

      if (j.error?.length || !j.data?.url) {
        setGalat(j.error?.[0] ?? 'Gagal mengunggah foto');
        continue;
      }
      // Pratinjau memakai objectURL LOKAL: berkas di server bersifat privat,
      // jadi pelapor anonim tidak akan bisa memuatnya kembali lewat URL-nya.
      setFoto((p) => [...p, { url: j.data.url, pratinjau: URL.createObjectURL(f), nama: f.name }]);
    }
    setMengunggah(false);
  };

  const hapusFoto = (idx) => {
    setFoto((p) => {
      if (p[idx]) URL.revokeObjectURL(p[idx].pratinjau);
      return p.filter((_, i) => i !== idx);
    });
  };

  const kirim = async (e) => {
    e.preventDefault();
    setGalat(null);

    if (!uraian.trim() || (kritik && !nama.trim())) { setGalat(t.wajibDiisi); return; }

    setMengirim(true);

    let j;
    if (kritik) {
      j = await kirimJson('/api/kritik-saran', {
        nama: nama.trim(), hp: kontak.trim(), email: email.trim(), pesan: uraian.trim(),
      });
    } else {
      // Field khusus WBS digabung ke `isi` — tabel `t_pengaduan` sengaja tidak
      // ditambahi kolom baru supaya port ini tetap 0 migrasi terhadap produksi.
      let isi = [
        `${wbs ? 'Uraian kejadian' : 'Isi pengaduan/konsultasi'}: ${uraian.trim()}`,
        wbs && waktuTempat.trim() && `Waktu & tempat kejadian: ${waktuTempat.trim()}`,
        wbs && pihakTerlibat.trim() && `Pihak yang terlibat: ${pihakTerlibat.trim()}`,
      ].filter(Boolean).join('\n');

      if (foto.length) isi += `\n\nBukti Foto:\n${foto.map((f) => f.url).join('\n')}`;

      j = await kirimJson('/api/pengaduan', {
        nama: nama.trim() || t.namaKosong,
        hp: kontak.trim(),
        email: email.trim(),
        subjek: t.subjek,
        isi,
      });
    }

    setMengirim(false);

    if (j.error?.length) { setGalat(j.error[0] ?? t.gagal); return; }

    setSukses(true);
    setNama(''); setKontak(''); setEmail(''); setUraian('');
    setWaktuTempat(''); setPihakTerlibat('');
    foto.forEach((f) => URL.revokeObjectURL(f.pratinjau));
    setFoto([]);
  };

  if (sukses) {
    return (
      <div className="rounded-2xl border border-slate-200/60 bg-white p-10 text-center shadow-sm">
        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50">
          <CheckCircle2 className="h-8 w-8 text-emerald-600" />
        </div>
        <h3 className="mb-2 text-xl font-semibold text-slate-900">{t.judulSukses}</h3>
        <p className="mb-6 text-sm text-slate-500">{t.pesanSukses}</p>
        <Button onClick={() => setSukses(false)}>{t.tombolLagi}</Button>
      </div>
    );
  }

  return (
    <div className="rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm md:p-8">
      <div className="mb-5 flex items-start gap-3 border-b border-slate-100 pb-5">
        <div className="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-brand/10 text-brand">
          <ShieldCheck className="h-5 w-5" />
        </div>
        <div>
          <h2 className="font-semibold text-slate-900">{t.judul}</h2>
          <p className="mt-0.5 text-xs leading-relaxed text-slate-500">{t.petunjuk}</p>
        </div>
      </div>

      {galat && (
        <div className="mb-4 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
          <AlertCircle className="mt-0.5 h-4 w-4 flex-none" />
          <span>{galat}</span>
        </div>
      )}

      <form onSubmit={kirim} className="space-y-4">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div className="space-y-1.5">
            <Label htmlFor="asp-nama">{t.labelNama}</Label>
            <Input id="asp-nama" value={nama} onChange={(e) => setNama(e.target.value)}
                   required={kritik} placeholder="Nama lengkap" />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="asp-kontak">No. HP / WhatsApp {kritik ? '(opsional)' : ''}</Label>
            <Input id="asp-kontak" value={kontak} onChange={(e) => setKontak(e.target.value)}
                   placeholder="08xxxxxxxxxx" />
          </div>
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="asp-email">Email (opsional)</Label>
          <Input id="asp-email" type="email" value={email} onChange={(e) => setEmail(e.target.value)}
                 placeholder="nama@contoh.com" />
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="asp-uraian">{t.labelUraian}</Label>
          <Textarea id="asp-uraian" rows={6} value={uraian} required
                    onChange={(e) => setUraian(e.target.value)} placeholder={t.placeholderUraian} />
        </div>

        {wbs && (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label htmlFor="asp-waktu">Waktu &amp; Tempat Kejadian</Label>
              <Input id="asp-waktu" value={waktuTempat} onChange={(e) => setWaktuTempat(e.target.value)}
                     placeholder="mis. 3 Agustus 2026, loket pelayanan" />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="asp-pihak">Pihak yang Diduga Terlibat</Label>
              <Input id="asp-pihak" value={pihakTerlibat} onChange={(e) => setPihakTerlibat(e.target.value)}
                     placeholder="Nama/jabatan bila diketahui" />
            </div>
          </div>
        )}

        {!kritik && (
          <div className="space-y-2">
            <Label>Bukti Foto (opsional, maks {MAKS_FOTO} foto @5 MB)</Label>

            {foto.length > 0 && (
              <div className="flex flex-wrap gap-2">
                {foto.map((f, i) => (
                  <div key={f.url} className="relative">
                    <button type="button" onClick={() => setLihat(i)}
                            title={`Klik untuk memperbesar — ${f.nama}`}
                            className="block cursor-zoom-in">
                      <img src={f.pratinjau} alt={f.nama}
                           className="h-20 w-20 rounded-lg border border-slate-200 object-cover" />
                    </button>
                    <button type="button" onClick={() => hapusFoto(i)} aria-label={`Hapus ${f.nama}`}
                            className="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-rose-600 text-white shadow">
                      <X className="h-3 w-3" />
                    </button>
                  </div>
                ))}
              </div>
            )}

            <input ref={inputBerkas} type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                   multiple className="hidden"
                   onChange={(e) => { pilihBerkas(e.target.files); e.target.value = ''; }} />

            <Button type="button" variant="outline" disabled={mengunggah || foto.length >= MAKS_FOTO}
                    onClick={() => inputBerkas.current?.click()}>
              {mengunggah
                ? <Loader2 className="mr-1.5 h-4 w-4 animate-spin" />
                : <ImagePlus className="mr-1.5 h-4 w-4" />}
              Tambah Foto
            </Button>
            <p className="text-xs text-slate-400">
              Foto hanya dapat dilihat petugas yang menangani laporan.
            </p>
          </div>
        )}

        <Button type="submit" disabled={mengirim || mengunggah} className="w-full sm:w-auto">
          {mengirim ? <Loader2 className="mr-1.5 h-4 w-4 animate-spin" /> : <Send className="mr-1.5 h-4 w-4" />}
          {t.tombolKirim}
        </Button>
      </form>

      {/* 🔴 `pratinjau` (objectURL lokal), BUKAN `url` hasil unggah. Pelapor WBS
          boleh anonim, jadi ia tidak punya sesi dan berkas yang sudah tersimpan
          tidak akan bisa dimuatnya kembali lewat URL-nya sendiri. */}
      {lihat !== null && foto[lihat] && (
        <PenampilGambar
          daftar={foto.map((f) => ({ src: f.pratinjau, judul: f.nama }))}
          indeksAwal={lihat}
          onTutup={() => setLihat(null)}
        />
      )}
    </div>
  );
}
