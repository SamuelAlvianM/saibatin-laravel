import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, ShieldCheck } from 'lucide-react';
import KartuAuth, { KotakGalat } from '@/Components/KartuAuth';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { PasswordInput } from '@/Components/ui/password-input';

// Prop-nya `kunci`, bukan `key` — React menelan prop bernama `key`.
export default function ResetSandi({ kunci }) {
  const { data, setData, post, processing, errors } = useForm({
    key: kunci ?? '',
    pass1: '',
    pass2: '',
    recaptchaToken: '',
  });

  // Aturannya sama persis dengan pendaftaran, dan diperiksa juga di server.
  // Yang di sini semata agar warga tahu lebih awal, bukan sebagai pengaman.
  const terlaluPendek = data.pass1.length > 0 && data.pass1.length < 6;
  const angkaSemua = /^\d+$/.test(data.pass1);
  const tidakSama = data.pass2.length > 0 && data.pass1 !== data.pass2;

  return (
    <KartuAuth
      judul="Setel Ulang Password"
      subjudul="Buat kata sandi baru untuk akun Anda"
      ikon={<ShieldCheck className="h-7 w-7" />}
    >
      <form
        onSubmit={(e) => { e.preventDefault(); post('/reset-password', { preserveScroll: true }); }}
        className="space-y-4 px-8 pb-8"
      >
        <KotakGalat errors={errors} />

        {!data.key && (
          <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            Tautan tidak memuat kode reset. Buka tautan dari email, atau
            {' '}<Link href="/forgot-password" className="font-semibold underline">minta tautan baru</Link>.
          </div>
        )}

        {['pass1', 'pass2'].map((nama) => (
          <div key={nama} className="space-y-2">
            <Label htmlFor={nama} className="text-slate-700">
              {nama === 'pass1' ? 'Password Baru' : 'Ulangi Password Baru'}
            </Label>
            <PasswordInput
              id={nama}
              autoComplete="new-password"
              value={data[nama]}
              onChange={(e) => setData(nama, e.target.value)}
            />
          </div>
        ))}

        <ul className="space-y-1 text-xs">
          <li className={terlaluPendek ? 'text-red-600' : 'text-slate-500'}>• Minimal 6 karakter</li>
          <li className={angkaSemua ? 'text-red-600' : 'text-slate-500'}>• Tidak boleh angka semua</li>
          <li className={tidakSama ? 'text-red-600' : 'text-slate-500'}>• Kedua isian harus sama</li>
        </ul>

        <Button
          type="submit"
          size="lg"
          disabled={processing || !data.key}
          className="w-full font-semibold text-white shadow-lg transition-all hover:shadow-xl active:scale-[0.98] disabled:opacity-50"
          style={{ background: 'linear-gradient(90deg,#2e6da4,#1b4b72)' }}
        >
          {processing ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Menyimpan...</> : 'Simpan Password Baru'}
        </Button>

        <div className="flex justify-center">
          <Link href="/login" className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-primary">
            <ArrowLeft className="h-4 w-4" />Kembali ke Login
          </Link>
        </div>
      </form>
    </KartuAuth>
  );
}
