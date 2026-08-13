import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, KeyRound, Loader2, Send } from 'lucide-react';
import KartuAuth, { KotakGalat } from '@/Components/KartuAuth';

export default function LupaSandi() {
  const { flash } = usePage().props;
  const { data, setData, post, processing, errors } = useForm({ nik: '', recaptchaToken: '' });

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
          <label htmlFor="nik" className="text-sm font-medium text-slate-700">NIK</label>
          <input
            id="nik"
            value={data.nik}
            onChange={(e) => setData('nik', e.target.value.replace(/\D/g, '').slice(0, 16))}
            inputMode="numeric"
            placeholder="16 digit NIK"
            className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 font-mono text-sm outline-none transition-all focus:border-brand focus:ring-2 focus:ring-brand/40"
          />
          <p className="text-xs leading-relaxed text-slate-500">
            Tautan penyetelan ulang dikirim ke <strong>email terdaftar</strong> pada akun
            tersebut, dan berlaku 1 jam.
          </p>
        </div>

        <button
          type="submit"
          disabled={processing}
          className="flex w-full items-center justify-center rounded-md px-4 py-2.5 font-semibold text-white shadow-lg transition-all hover:shadow-xl active:scale-[0.98] disabled:opacity-50"
          style={{ background: 'linear-gradient(90deg,#2e6da4,#1b4b72)' }}
        >
          {processing
            ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Mengirim...</>
            : <><Send className="mr-2 h-4 w-4" />Kirim Tautan Reset</>}
        </button>

        <div className="flex justify-center">
          <Link href="/login" className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-brand">
            <ArrowLeft className="h-4 w-4" />Kembali ke Login
          </Link>
        </div>
      </form>
    </KartuAuth>
  );
}
