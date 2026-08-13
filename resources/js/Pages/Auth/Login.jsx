import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
  AlertCircle, ArrowRight, ClipboardList, Eye, EyeOff,
  KeyRound, Loader2, UserPlus,
} from 'lucide-react';

import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

/**
 * Halaman login — port dari `app/login/LoginContent.tsx` (portal Next.js).
 *
 * Tata letak, warna, dan seluruh teksnya dipertahankan. Yang berubah hanya
 * cara datanya mengalir: dulu `fetch` → thunk Redux → state; sekarang
 * `useForm` Inertia → POST biasa → Laravel membalas redirect atau errors.
 * Tidak ada store, tidak ada permintaan sesi terpisah.
 *
 * Kontrol formulirnya memakai kit `Components/ui/*` yang sama dengan portal,
 * jadi tinggi field, cincin fokus, dan warnanya tidak lagi ditebak per halaman.
 */
export default function Login({ redirect }) {
  const { cekStatus, situs } = usePage().props;
  const [lihatSandi, setLihatSandi] = useState(false);
  const [fokus, setFokus] = useState(null);

  const { data, setData, post, processing, errors } = useForm({
    user_id: '',
    password: '',
    remember: false,
    recaptchaToken: '',
    redirect: redirect ?? '/dashboard',
  });

  // reCAPTCHA hanya aktif bila site key diisi. Tanpa key (dev), tombol tidak
  // boleh "memuat" selamanya — itu bug yang sudah pernah ada di portal lama.
  const recaptchaAktif = Boolean(situs?.recaptcha_site_key);

  const kirim = (e) => {
    e.preventDefault();
    post('/login', { preserveScroll: true });
  };

  const daftarGalat = Object.values(errors).filter(Boolean);

  // Sorotan field yang sedang difokus — cincin biru + latar tipis, persis
  // seperti portal. Ditulis sekali supaya kedua field tidak berbeda diam-diam.
  const kelasField = (nama, isi) =>
    [
      'w-full transition-all duration-300',
      fokus === nama ? 'ring-2 ring-primary border-primary shadow-lg shadow-primary/20' : '',
      isi ? 'bg-primary/5' : '',
    ]
      .filter(Boolean)
      .join(' ');

  return (
    <div
      className="relative flex min-h-screen items-center justify-center overflow-hidden p-4"
      style={{ background: 'linear-gradient(160deg,#e8f0f9 0%,#dceaf7 40%,#c8dcf0 100%)' }}
    >
      <Head title="Login" />

      {/* Aksen latar */}
      <div className="pointer-events-none absolute inset-0 overflow-hidden">
        <div className="absolute -right-40 -top-40 h-96 w-96 rounded-full blur-3xl"
             style={{ background: 'rgba(33,118,189,0.12)' }} />
        <div className="absolute -bottom-40 -left-40 h-96 w-96 rounded-full blur-3xl"
             style={{ background: 'rgba(33,118,189,0.08)' }} />
      </div>

      <Card
        className="kartu-kaca relative z-10 w-full max-w-[420px] gap-0 overflow-hidden rounded-xl border-0 py-0"
        style={{ boxShadow: '0 8px 40px rgba(27,75,114,0.18)' }}
      >
        {/* Garis aksen biru di tepi atas */}
        <div className="absolute inset-x-0 top-0 h-[3px] rounded-t-xl"
             style={{ background: '#2176bd', boxShadow: '0 2px 12px rgba(33,118,189,0.45)' }} />

        <CardHeader className="space-y-3 px-8 pb-5 pt-8 text-center">
          <div className="flex justify-center">
            {/* Lambang dinas, bukan lingkaran berinisial: halaman ini pintu masuk
                warga dan logo resmilah yang menandakan situsnya benar. */}
            <div className="h-16 w-16 drop-shadow-md">
              <img
                src="/logo-saibatin.png"
                alt="Logo Disdukcapil Pesisir Barat"
                className="h-full w-full object-contain"
              />
            </div>
          </div>
          <div>
            <CardTitle className="text-2xl font-bold text-slate-800">Selamat Datang</CardTitle>
            <CardDescription className="mt-1 text-sm text-slate-500">
              Portal SAIBATIN — Disdukcapil Kab. Pesisir Barat
            </CardDescription>
          </div>
        </CardHeader>

        <form onSubmit={kirim}>
          <CardContent className="space-y-4 px-8">
            {daftarGalat.length > 0 && (
              <Alert variant="destructive" className="animate-in fade-in slide-in-from-top-2 duration-300">
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>
                  <ul className="list-inside list-disc space-y-1">
                    {daftarGalat.map((g, i) => <li key={i}>{g}</li>)}
                  </ul>
                </AlertDescription>
              </Alert>
            )}

            {/* Ajakan ke Cek Status — muncul saat login ditolak karena keadaan
                akun (menunggu/ditolak/nonaktif), bukan karena sandi salah. */}
            {cekStatus && (
              <div className="animate-in fade-in slide-in-from-top-2 space-y-2.5 rounded-xl border-2 border-primary/40 bg-primary/5 p-4 duration-300">
                <div className="flex items-start gap-2.5">
                  <ClipboardList className="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                  <div className="min-w-0">
                    <p className="text-sm font-semibold text-primary">
                      {cekStatus.status === 2
                        ? 'Pendaftaran Anda ditolak'
                        : cekStatus.status === 3
                          ? 'Akun Anda dinonaktifkan'
                          : 'Pendaftaran Anda masih diproses'}
                    </p>
                    <p className="mt-0.5 text-xs leading-relaxed text-slate-600">
                      {cekStatus.status === 2
                        ? 'Buka Cek Status untuk melihat alasannya, lalu perbaiki data dan ajukan ulang.'
                        : cekStatus.status === 3
                          ? 'Buka Cek Status untuk melihat keterangannya. Hubungi Staff Disdukcapil untuk mengaktifkan kembali.'
                          : 'Pendaftaran Anda sedang menunggu verifikasi petugas. Pantau statusnya di halaman Cek Status.'}
                    </p>
                  </div>
                </div>
                <Link
                  href={`/cek-status${cekStatus.nik ? `?nik=${cekStatus.nik}` : ''}`}
                  className="flex w-full items-center justify-center gap-1.5 rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                  <ClipboardList className="h-4 w-4" />
                  Cek Status Pendaftaran
                  <ArrowRight className="h-4 w-4" />
                </Link>
              </div>
            )}

            <div className="space-y-2">
              <Label
                htmlFor="user_id"
                className={`transition-colors duration-200 ${fokus === 'user_id' ? 'text-primary' : ''}`}
              >
                NIK / User ID
              </Label>
              <Input
                id="user_id"
                name="user_id"
                type="text"
                autoComplete="username"
                placeholder="NIK (warga) atau username (instansi/staff)"
                value={data.user_id}
                onChange={(e) => setData('user_id', e.target.value)}
                onFocus={() => setFokus('user_id')}
                onBlur={() => setFokus(null)}
                disabled={processing}
                className={kelasField('user_id', data.user_id)}
              />
            </div>

            <div className="space-y-2">
              <Label
                htmlFor="password"
                className={`transition-colors duration-200 ${fokus === 'password' ? 'text-primary' : ''}`}
              >
                Password
              </Label>
              <div className="relative">
                <Input
                  id="password"
                  name="password"
                  type={lihatSandi ? 'text' : 'password'}
                  autoComplete="current-password"
                  placeholder="Masukkan password Anda"
                  value={data.password}
                  onChange={(e) => setData('password', e.target.value)}
                  onFocus={() => setFokus('password')}
                  onBlur={() => setFokus(null)}
                  disabled={processing}
                  className={`pr-10 ${kelasField('password', data.password)}`}
                />
                <button
                  type="button"
                  tabIndex={-1}
                  aria-label={lihatSandi ? 'Sembunyikan sandi' : 'Tampilkan sandi'}
                  onClick={() => setLihatSandi(!lihatSandi)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 transition-colors duration-200 hover:text-slate-700 focus:outline-none"
                >
                  {lihatSandi ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
            </div>

            <div className="flex items-center space-x-2">
              <Checkbox
                id="remember"
                checked={data.remember}
                onCheckedChange={(v) => setData('remember', v === true)}
              />
              <Label htmlFor="remember" className="cursor-pointer text-sm font-normal select-none">
                Remember Me
              </Label>
            </div>
          </CardContent>

          <CardFooter className="flex flex-col space-y-4 px-8 pb-8 pt-4">
            <Button
              type="submit"
              disabled={processing}
              className="w-full font-semibold text-white shadow-lg transition-all duration-300 hover:shadow-xl active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
              style={{ background: 'linear-gradient(90deg,#2e6da4,#1b4b72)' }}
            >
              {processing ? (
                <><Loader2 className="mr-2 h-4 w-4 animate-spin" />Memproses...</>
              ) : (
                <>Login<ArrowRight className="ml-2 h-4 w-4" /></>
              )}
            </Button>

            <div className="flex items-center justify-center gap-4 text-sm">
              <Link href="/register"
                    className="inline-flex items-center gap-1 font-medium text-primary transition-colors hover:text-primary/80 hover:underline">
                <UserPlus className="h-4 w-4" />DAFTAR
              </Link>
              <span className="text-slate-300">|</span>
              <Link href="/forgot-password"
                    className="inline-flex items-center gap-1 font-medium text-destructive transition-colors hover:text-destructive/80 hover:underline">
                <KeyRound className="h-4 w-4" />LUPA PASSWORD
              </Link>
            </div>

            <div className="flex justify-center">
              <Link href="/cek-status"
                    className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:border-primary/40 hover:text-primary">
                <ClipboardList className="h-4 w-4" />
                Cek Status Pendaftaran (Akun Baru)
              </Link>
            </div>

            <div className="w-full space-y-2.5 rounded-xl border border-primary/15 bg-primary/[0.04] p-4">
              <h3 className="text-xs font-semibold uppercase tracking-wide text-primary">Catatan</h3>
              <div className="space-y-1.5 text-xs leading-relaxed text-slate-600">
                <p>- Kode Aktivasi (Password Sementara) dan notifikasi Pengajuan Online dikirim melalui WhatsApp dan E-Mail</p>
                <p>- Gunakan nomor WhatsApp &amp; E-Mail aktif saat pendaftaran. Jika belum, silahkan lengkapi akun profil pendaftaran anda dengan nomor WhatsApp dan E-Mail aktif.</p>
              </div>
            </div>

            {!recaptchaAktif && (
              <p className="text-center text-[11px] text-amber-700">
                reCAPTCHA nonaktif (kunci belum diisi) — hanya untuk pengembangan.
              </p>
            )}
          </CardFooter>
        </form>
      </Card>

      <div className="absolute inset-x-0 bottom-4 text-center text-xs text-slate-400">
        <p>SAIBATIN — Disdukcapil Kabupaten Pesisir Barat &copy; {situs?.tahun ?? '2024'}</p>
      </div>
    </div>
  );
}
