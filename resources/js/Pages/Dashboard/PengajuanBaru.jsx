import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, ArrowRight, Clock, FilePlus2, Search, SlidersHorizontal } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import PengaturanLayanan from '@/Components/PengaturanLayanan';
import { Pesan, Tombol } from '@/Components/Dasbor';
import { ikon as ikonDari } from '@/lib/ikon';

/**
 * Pemilih layanan untuk PETUGAS — mengisikan permohonan atas nama warga yang
 * datang ke loket. Port `app/dashboard/pengajuan-baru/PengajuanBaruClient.tsx`.
 *
 * Tiga hal yang sengaja BERBEDA dari versi warga (`Pengajuan/Pilih.jsx`):
 *
 * 1. Daftarnya TIDAK disaring visibilitas. Menyembunyikan layanan ditujukan
 *    untuk warga di portal publik; kanal loket justru gunanya saat layanan
 *    online ditutup — dan pengaturan yang menyembunyikannya ada di halaman ini.
 * 2. Jam layanan ditampilkan sebagai peringatan, bukan gerbang: yang menolak
 *    permohonan di luar jam adalah server (`LayananController::buat`), dan itu
 *    berlaku untuk petugas juga.
 * 3. Tombol **Pengaturan** (khusus Super Admin) membuka drawer jam kerja &
 *    ketersediaan layanan — di portal lama pun pengaturan itu tinggal di sini,
 *    bukan di halaman tersendiri.
 */
export default function PengajuanBaru({ daftar, jam }) {
  const { auth } = usePage().props;
  const admin = (auth?.user?.level ?? 3) === 1;

  const [cari, setCari] = useState('');
  const [pengaturan, setPengaturan] = useState(false);
  const [pesan, setPesan] = useState(null);

  const q = cari.trim().toLowerCase();
  const tampil = q
    ? daftar.filter((l) => l.title.toLowerCase().includes(q) || l.description.toLowerCase().includes(q))
    : daftar;

  return (
    <LayoutDashboard judul="Pengajuan Baru">
      <Pesan pesan={pesan} onTutup={() => setPesan(null)} />

      <Link href="/dashboard"
            className="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-brand">
        <ArrowLeft className="h-4 w-4" />Kembali
      </Link>

      <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="flex items-center gap-2 text-xl font-semibold text-slate-900">
            <FilePlus2 className="h-6 w-6 text-brand" /> Pengajuan Baru
          </h1>
          <p className="text-sm text-slate-500">
            Bantu warga mengajukan permohonan. Pilih jenis layanan untuk membuka formulirnya.
          </p>
        </div>
        <div className="flex items-center gap-2">
          <div className="relative w-full sm:w-64">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Cari layanan..."
                   className="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40" />
          </div>
          {admin && (
            <Tombol varian="garis" onClick={() => setPengaturan(true)} kelas="shrink-0"
                    title="Atur ketersediaan layanan & jam kerja permohonan">
              <SlidersHorizontal className="h-4 w-4" />
              <span className="hidden sm:inline">Pengaturan</span>
            </Tombol>
          )}
        </div>
      </div>

      {jam && !jam.open && (
        <div className="mb-5 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
          <Clock className="mt-0.5 h-4 w-4 shrink-0" />
          <p>
            <b>Layanan permohonan sedang tutup.</b> {jam.message} Permohonan baru —
            termasuk yang diisikan petugas — akan ditolak server sampai jam layanan
            dibuka kembali.
          </p>
        </div>
      )}

      {tampil.length === 0 ? (
        <div className="py-16 text-center text-sm text-slate-500">
          Tidak ada layanan cocok &quot;{cari}&quot;.
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {tampil.map((l) => {
            const I = ikonDari(l.icon);
            return (
              <Link key={l.slug} href={`/dashboard/pengajuan-baru/${l.slug}`}
                    className="group flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-brand/40 hover:shadow-md">
                <div className="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand transition-transform group-hover:scale-105">
                  <I className="h-5 w-5" />
                </div>
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-semibold text-slate-900 group-hover:text-brand">{l.title}</p>
                  <p className="line-clamp-1 text-xs text-slate-500">{l.description}</p>
                </div>
                <ArrowRight className="h-4 w-4 flex-shrink-0 text-slate-300 transition-all group-hover:translate-x-0.5 group-hover:text-brand" />
              </Link>
            );
          })}
        </div>
      )}

      {pengaturan && admin && (
        <PengaturanLayanan
          onTutup={() => setPengaturan(false)}
          onGalat={(teks) => setPesan({ tipe: 'galat', teks })}
        />
      )}
    </LayoutDashboard>
  );
}
