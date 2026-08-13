import { useState } from 'react';
import { KeyRound, LockOpen, ShieldAlert } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import { Pesan, Tombol } from '@/Components/Dasbor';
import { kirimJson } from '@/lib/api';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { PasswordInput } from '@/Components/ui/password-input';

/**
 * Halaman MASTER — port `app/dashboard/master/page.tsx`.
 *
 * Satu-satunya jalan membuka kunci permohonan berstatus final (Selesai/Ditolak)
 * agar bisa diproses ulang. Butuh sandi master (env `MASTER_PASSWORD`) selain
 * sesi petugas, dan setiap pembukaan tercatat di log serta di catatan
 * permohonannya.
 *
 * 🔴 Sengaja TIDAK ditautkan dari sidebar — dibuka lewat URL saja. Itu bukan
 * "keamanan lewat kerahasiaan" (endpointnya tetap minta sandi), melainkan agar
 * membalik status final tidak pernah terasa seperti tindakan sehari-hari.
 */
export default function Master() {
  const [sandi, setSandi] = useState('');
  const [noregister, setNoregister] = useState('');
  const [sibuk, setSibuk] = useState(false);
  const [riwayat, setRiwayat] = useState([]);
  const [pesan, setPesan] = useState(null);

  const bukaKunci = async (e) => {
    e.preventDefault();
    setSibuk(true);

    const j = await kirimJson('/api/admin/master', { password: sandi, noregister });
    setSibuk(false);

    if (j.error?.length) {
      setPesan({ tipe: 'galat', teks: j.error[0] });
      return;
    }

    const teks = j.success?.[0] ?? `Kunci ${noregister} dibuka`;
    setPesan({ tipe: 'sukses', teks });
    setRiwayat((r) => [teks, ...r]);
    setNoregister('');
  };

  return (
    <LayoutDashboard judul="Master" lebar="max-w-xl">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <div className="space-y-5">
        <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
          <p className="flex items-center gap-2 font-semibold">
            <ShieldAlert className="h-4 w-4" />Halaman Master
          </p>
          <p className="mt-1 leading-relaxed">
            Akses tertinggi aplikasi. Gunakan HANYA untuk membuka kunci permohonan yang sudah final
            (Selesai/Ditolak) bila memang perlu diproses ulang. Setiap pembukaan kunci tercatat pada
            catatan permohonan.
          </p>
        </div>

        <form onSubmit={bukaKunci} className="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <div className="flex items-center gap-2">
            <KeyRound className="h-5 w-5 text-brand" />
            <h1 className="font-semibold text-slate-900">Buka Kunci Permohonan</h1>
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="sandi" className="text-slate-700">Password Master *</Label>
            <PasswordInput id="sandi" value={sandi} required
                           onChange={(e) => setSandi(e.target.value)}
                           placeholder="Password master aplikasi" />
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="noreg" className="text-slate-700">No. Register Permohonan *</Label>
            <Input id="noreg" value={noregister} required onChange={(e) => setNoregister(e.target.value)}
                   placeholder="No. register permohonan yang terkunci" className="font-mono" />
            <p className="text-xs text-slate-400">
              Lihat kolom No. Register di menu Permohonan (baris bergembok).
            </p>
          </div>

          <Tombol kelas="w-full" type="submit" disabled={sibuk || !sandi || !noregister}>
            <LockOpen className="h-4 w-4" />Buka Kunci — kembalikan ke Diproses
          </Tombol>
        </form>

        {riwayat.length > 0 && (
          <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="mb-2 text-sm font-semibold text-slate-800">Riwayat sesi ini</p>
            <ul className="space-y-1 text-xs text-slate-500">
              {riwayat.map((r, i) => <li key={i}>• {r}</li>)}
            </ul>
          </div>
        )}
      </div>
    </LayoutDashboard>
  );
}
