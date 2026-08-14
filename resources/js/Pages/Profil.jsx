import { router } from '@inertiajs/react';
import { useState } from 'react';
import {
  Camera, CreditCard, Info, KeyRound, Loader2, Lock, Mail, MapPin, Phone,
  Save, Trash2, User, UserRound,
} from 'lucide-react';
import LayoutPengguna from '@/Components/LayoutPengguna';
import AmbilSelfie from '@/Components/AmbilSelfie';
import { Pesan } from '@/Components/Dasbor';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { PasswordInput } from '@/Components/ui/password-input';
import { Textarea } from '@/Components/ui/textarea';
import { kirimJson } from '@/lib/api';
import { tanpaAwalanDataUrl } from '@/lib/gambar';

/**
 * "Profil Saya" — port `app/profil/page.tsx` beserta tiga komponennya
 * (`ProfilForm`, `FotoProfilCard`, `ChangePasswordForm`).
 *
 * Tiga kartu memakai tiga endpoint yang berbeda dan tidak saling menunggu, jadi
 * masing-masing punya keadaan "sedang menyimpan" sendiri. Pesannya satu di atas
 * — dua toast yang bertumpuk lebih membingungkan daripada menolong.
 *
 * 🔴 Komponen kartu DIDEKLARASIKAN DI TINGKAT MODUL, bukan di dalam badan
 * `Profil`. Kalau tidak, tiap render menghasilkan tipe komponen baru, React
 * melepas & memasang ulang seluruh subtree, dan fokus input lepas tiap satu
 * huruf (jebakan HANDOFF §5 no. 17 — sudah terjadi dua kali).
 */

const GRADIEN = { background: 'linear-gradient(135deg, #2176bd, #1b4b72)' };

/** Foto wajah akun sendiri. */
function KartuFoto({ foto, diminta, onPesan }) {
  const [ambil, setAmbil] = useState(diminta && !foto);
  const [baru, setBaru] = useState('');
  const [sibuk, setSibuk] = useState(false);

  const simpan = async () => {
    if (!baru) return;
    setSibuk(true);
    // Awalan data URI dibuang: WAF cPanel memblokir badan yang memuatnya
    // (lihat lib/gambar.js).
    const j = await kirimJson('/api/profil/foto', { foto: tanpaAwalanDataUrl(baru) }, 'PUT');
    setSibuk(false);

    if (j.error?.length) { onPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    onPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Foto profil disimpan' });
    setBaru('');
    setAmbil(false);
    router.reload({ only: ['awal'] });
  };

  const hapus = async () => {
    setSibuk(true);
    const j = await kirimJson('/api/profil/foto', undefined, 'DELETE');
    setSibuk(false);

    if (j.error?.length) { onPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    onPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Foto profil dihapus' });
    router.reload({ only: ['awal'] });
  };

  return (
    <div className={`mb-6 rounded-2xl border border-slate-200 bg-white p-6 md:p-8 ${
      diminta && !foto ? 'ring-2 ring-brand/40' : ''
    }`}>
      <div className="mb-5 flex items-center gap-2">
        <Camera className="h-4 w-4 text-brand" />
        <h2 className="font-semibold text-slate-900">Foto Profil</h2>
      </div>

      {diminta && !foto && (
        <div className="mb-5 flex gap-2.5 rounded-xl border border-brand/25 bg-brand/5 p-3.5">
          <Info className="mt-0.5 h-4 w-4 shrink-0 text-brand" />
          <p className="text-xs leading-relaxed text-slate-600">
            Selamat datang! Lengkapi foto wajah Anda agar petugas lebih mudah
            memverifikasi permohonan. <b>Tidak wajib</b> — Anda bisa melewati
            langkah ini dan mengisinya kapan saja.
          </p>
        </div>
      )}

      {ambil ? (
        <div className="space-y-4">
          <AmbilSelfie nilai={baru} onChange={setBaru} />
          <div className="flex flex-wrap gap-2">
            <Button onClick={simpan} disabled={!baru || sibuk}>
              {sibuk && <Loader2 className="mr-1.5 h-4 w-4 animate-spin" />}
              Simpan Foto
            </Button>
            <Button variant="ghost" disabled={sibuk}
                    onClick={() => { setBaru(''); setAmbil(false); }}>
              Batal
            </Button>
          </div>
        </div>
      ) : (
        <div className="flex flex-wrap items-center gap-5">
          {foto ? (
            <img src={foto} alt="Foto profil Anda"
                 className="h-24 w-24 rounded-xl border border-slate-200 object-cover" />
          ) : (
            <div className="flex h-24 w-24 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-slate-300">
              <UserRound className="h-10 w-10" />
            </div>
          )}

          <div className="space-y-2">
            <p className="text-xs text-slate-500">
              {foto
                ? 'Foto ini hanya terlihat oleh Anda dan petugas.'
                : 'Belum ada foto. Ambil foto wajah Anda lewat kamera perangkat.'}
            </p>
            <div className="flex flex-wrap gap-2">
              <Button variant="outline" onClick={() => setAmbil(true)} disabled={sibuk}>
                <Camera className="mr-1.5 h-4 w-4" />{foto ? 'Ganti Foto' : 'Ambil Foto'}
              </Button>
              {foto && (
                <Button variant="ghost" onClick={hapus} disabled={sibuk}
                        className="text-rose-600 hover:bg-rose-50 hover:text-rose-700">
                  {sibuk
                    ? <Loader2 className="mr-1.5 h-4 w-4 animate-spin" />
                    : <Trash2 className="mr-1.5 h-4 w-4" />}
                  Hapus
                </Button>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

/** Biodata. NIK & No. KK sengaja terkunci — keduanya identitas terverifikasi. */
function KartuBiodata({ awal, onPesan }) {
  const [nama, setNama] = useState(awal.nama);
  const [hp, setHp] = useState(awal.hp);
  const [email, setEmail] = useState(awal.email);
  const [alamat, setAlamat] = useState(awal.alamat);
  const [menyimpan, setMenyimpan] = useState(false);

  const kirim = async (e) => {
    e.preventDefault();
    setMenyimpan(true);
    const j = await kirimJson('/api/profil', { nama, hp, email, alamat }, 'PUT');
    setMenyimpan(false);

    if (j.error?.length) { onPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    onPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Profil diperbarui' });
    router.reload({ only: ['awal'] });
  };

  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-6 md:p-8">
      <div className="mb-6 flex items-center gap-4 border-b border-slate-200/60 pb-6">
        <div className="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-full text-white"
             style={GRADIEN}>
          <User className="h-7 w-7" />
        </div>
        <div>
          <p className="font-semibold text-slate-900">{awal.nama || awal.userId}</p>
          <p className="text-xs text-slate-500">
            {awal.levelNama} &middot; User ID: <span className="font-mono">{awal.userId}</span>
          </p>
        </div>
      </div>

      <form onSubmit={kirim} className="space-y-5">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div className="space-y-1.5">
            <Label className="flex items-center gap-1.5 text-slate-600">
              <CreditCard className="h-3.5 w-3.5" /> NIK
            </Label>
            <Input value={awal.nik || '-'} disabled className="font-mono" />
          </div>
          <div className="space-y-1.5">
            <Label className="flex items-center gap-1.5 text-slate-600">
              <CreditCard className="h-3.5 w-3.5" /> No. KK
            </Label>
            <Input value={awal.nokk || '-'} disabled className="font-mono" />
          </div>
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="nama" className="flex items-center gap-1.5">
            <User className="h-3.5 w-3.5" /> Nama Lengkap
          </Label>
          <Input id="nama" value={nama} onChange={(e) => setNama(e.target.value)} required />
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div className="space-y-1.5">
            <Label htmlFor="hp" className="flex items-center gap-1.5">
              <Phone className="h-3.5 w-3.5" /> No. HP / WhatsApp
            </Label>
            <Input id="hp" value={hp} onChange={(e) => setHp(e.target.value)} placeholder="08xxxxxxxxxx" />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="email" className="flex items-center gap-1.5">
              <Mail className="h-3.5 w-3.5" /> Email
            </Label>
            <Input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
          </div>
        </div>

        <div className="space-y-1.5">
          <Label htmlFor="alamat" className="flex items-center gap-1.5">
            <MapPin className="h-3.5 w-3.5" /> Alamat
          </Label>
          <Textarea id="alamat" value={alamat} onChange={(e) => setAlamat(e.target.value)} rows={3} />
        </div>

        <Button type="submit" disabled={menyimpan} className="text-white" style={GRADIEN}>
          {menyimpan ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
          <span className="ml-1.5">Simpan Perubahan</span>
        </Button>
      </form>
    </div>
  );
}

function KartuSandi({ onPesan }) {
  const [lama, setLama] = useState('');
  const [baru, setBaru] = useState('');
  const [konfirmasi, setKonfirmasi] = useState('');
  const [menyimpan, setMenyimpan] = useState(false);

  const kirim = async (e) => {
    e.preventDefault();

    // Diperiksa juga di server; yang di sini semata agar pengguna tahu lebih
    // awal, bukan sebagai pengaman.
    if (baru !== konfirmasi) {
      onPesan({ tipe: 'galat', teks: 'Konfirmasi password tidak sama' });
      return;
    }

    setMenyimpan(true);
    const j = await kirimJson('/api/profil/change-password', {
      passwordLama: lama, passwordBaru: baru, konfirmasi,
    });
    setMenyimpan(false);

    if (j.error?.length) { onPesan({ tipe: 'galat', teks: j.error[0] }); return; }

    onPesan({ tipe: 'sukses', teks: j.success?.[0] ?? 'Password berhasil diubah' });
    setLama(''); setBaru(''); setKonfirmasi('');
  };

  return (
    <div className="mt-6 rounded-2xl border border-slate-200 bg-white p-6 md:p-8">
      <div className="mb-4 flex items-center gap-3 border-b border-slate-200/60 pb-4">
        <div className="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full text-white"
             style={GRADIEN}>
          <KeyRound className="h-5 w-5" />
        </div>
        <div>
          <h2 className="font-semibold text-slate-900">Ganti Password</h2>
          <p className="text-xs text-slate-500">Demi keamanan, gunakan kombinasi yang sulit ditebak.</p>
        </div>
      </div>

      <form onSubmit={kirim} className="space-y-4">
        <div className="space-y-1.5">
          <Label htmlFor="sandi-lama" className="flex items-center gap-1.5">
            <Lock className="h-3.5 w-3.5" /> Password Lama
          </Label>
          <PasswordInput id="sandi-lama" value={lama}
                         onChange={(e) => setLama(e.target.value)} required />
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div className="space-y-1.5">
            <Label htmlFor="sandi-baru">Password Baru</Label>
            <PasswordInput id="sandi-baru" value={baru}
                           onChange={(e) => setBaru(e.target.value)} required />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="sandi-konfirmasi">Konfirmasi Password</Label>
            <PasswordInput id="sandi-konfirmasi" value={konfirmasi}
                           onChange={(e) => setKonfirmasi(e.target.value)} required />
          </div>
        </div>

        <Button type="submit" disabled={menyimpan} className="text-white" style={GRADIEN}>
          {menyimpan ? <Loader2 className="h-4 w-4 animate-spin" /> : <KeyRound className="h-4 w-4" />}
          <span className="ml-1.5">Ubah Password</span>
        </Button>
      </form>
    </div>
  );
}

export default function Profil({ awal, dimintaFoto }) {
  const [pesan, setPesan] = useState(null);

  return (
    // Tautan "← Kembali" ke /dashboard persis `BackButton href="/dashboard"`
    // di `app/profil/page.tsx` portal asli.
    <LayoutPengguna judul="Profil Saya" lebar="max-w-2xl"
                    kembali={{ href: '/dashboard', label: 'Kembali' }}>
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-slate-900">Profil Saya</h1>
        <p className="text-sm text-slate-500">Perbarui biodata akun Anda.</p>
      </div>

      <KartuFoto foto={awal.foto} diminta={dimintaFoto} onPesan={setPesan} />
      <KartuBiodata awal={awal} onPesan={setPesan} />
      <KartuSandi onPesan={setPesan} />
    </LayoutPengguna>
  );
}
