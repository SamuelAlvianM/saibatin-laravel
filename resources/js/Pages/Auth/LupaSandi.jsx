import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, KeyRound, Loader2, Send } from 'lucide-react';
import KartuAuth, { KotakGalat } from '@/Components/KartuAuth';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function LupaSandi() {
  const { flash } = usePage().props;
  const { data, setData, post, processing, errors } = useForm({ nik: '' });

  return (
    <KartuAuth
      judul="Lupa Password"
      subjudul="Masukkan NIK terdaftar Anda"
      ikon={<KeyRound className="h-7 w-7" />}
    >
      <form
        onSubmit={(e) => { e.preventDefault(); post('/forgot-password', { preserveScroll: true }); }}
        className="space-y-4 px-8 pb-8"
      >
        <KotakGalat errors={errors} />

        {flash?.sukses && (
          <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
            {flash.sukses}
          </div>
        )}

        <div className="space-y-2">
          <Label htmlFor="nik" className="text-slate-700">NIK</Label>
          <Input
            id="nik"
            value={data.nik}
            onChange={(e) => setData('nik', e.target.value.replace(/\D/g, '').slice(0, 16))}
            inputMode="numeric"
            placeholder="16 digit NIK"
            className="font-mono"
          />
          <p className="text-xs leading-relaxed text-slate-500">
            Tautan penyetelan ulang dikirim ke <strong>email terdaftar</strong> pada akun
            tersebut, dan berlaku 1 jam.
          </p>
        </div>

        <Button
          type="submit"
          size="lg"
          disabled={processing}
          className="w-full font-semibold text-white shadow-lg transition-all hover:shadow-xl active:scale-[0.98] disabled:opacity-50"
          style={{ background: 'linear-gradient(90deg,#2e6da4,#1b4b72)' }}
        >
          {processing
            ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Mengirim...</>
            : <><Send className="mr-2 h-4 w-4" />Kirim Tautan Reset</>}
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
