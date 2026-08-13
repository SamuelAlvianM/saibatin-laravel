import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, Eye, EyeOff, Loader2, ShieldCheck } from 'lucide-react';
import KartuAuth, { KotakGalat } from '@/Components/KartuAuth';

// Prop-nya `kunci`, bukan `key` — React menelan prop bernama `key`.
export default function ResetSandi({ kunci }) {
  const [lihat, setLihat] = useState(false);
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
            <label htmlFor={nama} className="text-sm font-medium text-slate-700">
              {nama === 'pass1' ? 'Password Baru' : 'Ulangi Password Baru'}
            </label>
            <div className="relative">
              <input
                id={nama}
                type={lihat ? 'text' : 'password'}
                autoComplete="new-password"
                value={data[nama]}
                onChange={(e) => setData(nama, e.target.value)}
                className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 pr-10 text-sm outline-none transition-all focus:border-brand focus:ring-2 focus:ring-brand/40"
              />
              {nama === 'pass1' && (
                <button type="button" tabIndex={-1} onClick={() => setLihat(!lihat)}
                        aria-label={lihat ? 'Sembunyikan sandi' : 'Tampilkan sandi'}
                        className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700">
                  {lihat ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              )}
            </div>
          </div>
        ))}

        <ul className="space-y-1 text-xs">
          <li className={terlaluPendek ? 'text-red-600' : 'text-slate-500'}>• Minimal 6 karakter</li>
          <li className={angkaSemua ? 'text-red-600' : 'text-slate-500'}>• Tidak boleh angka semua</li>
          <li className={tidakSama ? 'text-red-600' : 'text-slate-500'}>• Kedua isian harus sama</li>
        </ul>

        <button
          type="submit"
          disabled={processing || !data.key}
          className="flex w-full items-center justify-center rounded-md px-4 py-2.5 font-semibold text-white shadow-lg transition-all hover:shadow-xl active:scale-[0.98] disabled:opacity-50"
          style={{ background: 'linear-gradient(90deg,#2e6da4,#1b4b72)' }}
        >
          {processing ? <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Menyimpan...</> : 'Simpan Password Baru'}
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
