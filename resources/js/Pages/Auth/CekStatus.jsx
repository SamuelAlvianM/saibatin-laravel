import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ArrowLeft, ClipboardList, Loader2, RefreshCw, Search } from 'lucide-react';
import KartuAuth from '@/Components/KartuAuth';
import { kirimJson } from '@/lib/api';

/**
 * Cek status pendaftaran tanpa login — penutup loop 4-status.
 *
 * Warga yang DITOLAK melihat alasannya di sini beserta bagian data yang perlu
 * diperbaiki, lalu bisa langsung mengajukan ulang. Tanpa halaman ini, status
 * DITOLAK jadi jalan buntu.
 */
const WARNA = {
  0: 'bg-amber-50 text-amber-700 ring-amber-200',
  1: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  2: 'bg-rose-50 text-rose-700 ring-rose-200',
  3: 'bg-slate-100 text-slate-600 ring-slate-300',
};

export default function CekStatus({ nikAwal }) {
  const [nik, setNik] = useState(nikAwal ?? '');
  const [memuat, setMemuat] = useState(false);
  const [hasil, setHasil] = useState(null);
  const [galat, setGalat] = useState(null);
  const [mengajukan, setMengajukan] = useState(false);
  const [pesanAjukan, setPesanAjukan] = useState(null);

  const periksa = async (e) => {
    e?.preventDefault();
    setGalat(null);
    setHasil(null);
    // Pesan "pengajuan ulang terkirim" HANYA dibersihkan saat pengguna memeriksa
    // NIK lagi secara sadar (ada event). Kalau dibersihkan tanpa syarat, pemuatan
    // ulang otomatis setelah Ajukan Ulang akan menghapus pesannya sebelum sempat
    // terbaca — persis yang terjadi saat diuji.
    if (e) setPesanAjukan(null);

    if (!/^\d{16}$/.test(nik)) {
      setGalat('NIK harus 16 digit angka');
      return;
    }

    setMemuat(true);
    try {
      const j = await kirimJson('/cek-status', { nik });
      if (j.error?.length) setGalat(j.error[0]);
      else setHasil(j.data);
    } catch {
      setGalat('Gagal menghubungi server. Coba lagi.');
    } finally {
      setMemuat(false);
    }
  };

  const ajukanUlang = async () => {
    setMengajukan(true);
    try {
      const j = await kirimJson('/ajukan-ulang', { nik });
      if (j.error?.length) setGalat(j.error[0]);
      else {
        setPesanAjukan(j.success?.[0] ?? 'Pengajuan ulang terkirim.');
        await periksa();
      }
    } finally {
      setMengajukan(false);
    }
  };

  // Datang dari login/registrasi dengan NIK di URL → langsung diperiksa.
  useEffect(() => { if (nikAwal) periksa(); }, []); // eslint-disable-line

  return (
    <KartuAuth
      judul="Cek Status Pendaftaran"
      subjudul="Masukkan NIK untuk melihat status akun Anda"
      ikon={<ClipboardList className="h-7 w-7" />}
      lebar="max-w-[480px]"
    >
      <div className="space-y-4 px-8 pb-8">
        <form onSubmit={periksa} className="flex gap-2">
          <input
            value={nik}
            onChange={(e) => setNik(e.target.value.replace(/\D/g, '').slice(0, 16))}
            inputMode="numeric"
            placeholder="16 digit NIK"
            className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 font-mono text-sm outline-none transition-all focus:border-brand focus:ring-2 focus:ring-brand/40"
          />
          <button
            type="submit"
            disabled={memuat}
            className="inline-flex shrink-0 items-center gap-1.5 rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-dark disabled:opacity-50"
          >
            {memuat ? <Loader2 className="h-4 w-4 animate-spin" /> : <Search className="h-4 w-4" />}
            Cek
          </button>
        </form>

        {galat && (
          <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{galat}</div>
        )}

        {pesanAjukan && (
          <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{pesanAjukan}</div>
        )}

        {hasil && !hasil.ada && (
          <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
            NIK ini belum pernah didaftarkan di portal.{' '}
            <Link href="/register" className="font-semibold text-brand hover:underline">Daftar sekarang</Link>
          </div>
        )}

        {hasil?.ada && (
          <div className="space-y-3 rounded-xl border border-slate-200 bg-white/70 p-4">
            <div className="flex items-start justify-between gap-3">
              <div className="min-w-0">
                <p className="text-xs uppercase tracking-wide text-slate-500">Nama pendaftar</p>
                <p className="truncate font-semibold text-slate-800">{hasil.nama || '—'}</p>
              </div>
              <span className={`shrink-0 rounded-full px-3 py-1 text-xs font-semibold ring-1 ${WARNA[hasil.status] ?? WARNA[0]}`}>
                {hasil.label}
              </span>
            </div>

            <p className="text-sm leading-relaxed text-slate-600">{hasil.pesan}</p>

            {hasil.status === 2 && (
              <>
                {hasil.kolomLabel?.length > 0 && (
                  <div className="rounded-lg border border-rose-200 bg-rose-50 p-3">
                    <p className="text-xs font-semibold uppercase tracking-wide text-rose-700">
                      Data yang perlu diperbaiki
                    </p>
                    <ul className="mt-1.5 flex flex-wrap gap-1.5">
                      {hasil.kolomLabel.map((k) => (
                        <li key={k} className="rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200">
                          {k}
                        </li>
                      ))}
                    </ul>
                  </div>
                )}

                {hasil.alasan && (
                  <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Alasan petugas</p>
                    <p className="mt-1 whitespace-pre-line text-sm text-slate-700">{hasil.alasan}</p>
                  </div>
                )}

                <div className="flex flex-col gap-2 sm:flex-row">
                  <button
                    onClick={ajukanUlang}
                    disabled={mengajukan}
                    className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-brand px-3 py-2 text-sm font-semibold text-brand transition-colors hover:bg-brand/5 disabled:opacity-50"
                  >
                    {mengajukan ? <Loader2 className="h-4 w-4 animate-spin" /> : <RefreshCw className="h-4 w-4" />}
                    Ajukan Ulang (data lama)
                  </button>
                  {/* NIK dibawa ke form pendaftaran supaya data lama terisi
                      dan bagian yang ditandai petugas langsung tersorot. */}
                  <button
                    onClick={() => router.visit(`/register?nik=${nik}`)}
                    className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-dark"
                  >
                    Perbaiki Data Dulu
                  </button>
                </div>
                <p className="text-[11px] leading-relaxed text-slate-500">
                  Ajukan ulang tanpa mengubah apa pun kemungkinan besar ditolak lagi —
                  perbaiki dulu bagian yang ditandai di atas.
                </p>
              </>
            )}

            {hasil.status === 1 && (
              <Link href="/login"
                    className="inline-flex w-full items-center justify-center rounded-lg bg-brand px-3 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-dark">
                Login Sekarang
              </Link>
            )}
          </div>
        )}

        <div className="flex justify-center pt-1">
          <Link href="/login" className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-brand">
            <ArrowLeft className="h-4 w-4" />Kembali ke Login
          </Link>
        </div>
      </div>
    </KartuAuth>
  );
}
