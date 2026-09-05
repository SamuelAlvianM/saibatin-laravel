import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, ArrowRight, Clock, EyeOff, FilePlus2, Search, SlidersHorizontal } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import PengaturanLayanan from '@/Components/PengaturanLayanan';
import { Pesan, Tombol } from '@/Components/Dasbor';
import { warnaKategori as warna, WARNA_NETRAL } from '@/lib/kategori';
import { ikon as ikonDari } from '@/lib/ikon';
import { Input } from '@/Components/ui/input';

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
export default function PengajuanBaru({ daftar, jam, tersembunyi = [], kategori = [] }) {
  const { auth } = usePage().props;
  const admin = (auth?.user?.level ?? 3) === 1;

  /*
   * Layanan yang DIMATIKAN hanya sampai ke sini bagi Super Admin — bagi peran
   * lain server sudah menyaringnya habis. Ia yang mematikannya, jadi ia perlu
   * melihat akibat pengaturannya sendiri dan tetap bisa mengujinya.
   */
  const mati = new Set(tersembunyi);

  const [cari, setCari] = useState('');
  const [kat, setKat] = useState('all');
  const [pengaturan, setPengaturan] = useState(false);
  const [pesan, setPesan] = useState(null);

  const q = cari.trim().toLowerCase();
  const cocokCari = (l) =>
    !q || l.title.toLowerCase().includes(q) || l.description.toLowerCase().includes(q);

  /*
   * Jumlah per tab dihitung dari hasil PENCARIAN, bukan dari seluruh daftar.
   * Kalau dihitung dari semua, tab bisa menunjukkan "Akta 5" lalu terbuka
   * kosong karena kata kuncinya tidak cocok satu pun — angka yang berbohong.
   */
  const hasilCari = daftar.filter(cocokCari);
  const jumlahKat = hasilCari.reduce((a, l) => ({ ...a, [l.category]: (a[l.category] ?? 0) + 1 }), {});

  // Tab hanya untuk kategori yang benar-benar punya layanan; `all` selalu ada.
  const tabs = kategori.filter((k) => k.id === 'all' || (jumlahKat[k.id] ?? 0) > 0);

  const tampil = hasilCari
    .filter((l) => kat === 'all' || l.category === kat)
    // Layanan tidak aktif selalu turun ke bawah. `sort` di JS stabil, jadi
    // urutan yang disetel petugas tetap dipakai untuk sisanya. Hanya terasa
    // bagi Super Admin; peran lain tidak menerima layanan mati sama sekali.
    .slice()
    .sort((a, b) => (mati.has(a.kunci) ? 1 : 0) - (mati.has(b.kunci) ? 1 : 0));

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
            <Input value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Cari layanan..."
                   className="pl-9" />
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


      {tabs.length > 1 && (
        <div className="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Kategori layanan">
          {tabs.map((k) => {
            const aktif = kat === k.id;
            const n = k.id === 'all' ? hasilCari.length : (jumlahKat[k.id] ?? 0);
            const w = warna(k.id);

            // Tab tidak aktif dibiarkan netral: kalau semuanya berwarna, tidak
            // ada lagi yang menandakan mana yang sedang dipilih.
            return (
              <button key={k.id} type="button" role="tab" aria-selected={aktif}
                      onClick={() => setKat(k.id)}
                      className={`inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors ${
                        aktif ? w.tab : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                      }`}>
                {k.name}
                <span className={`rounded-full px-1.5 text-xs tabular-nums ${
                  aktif ? w.hitung : 'bg-slate-100 text-slate-500'
                }`}>{n}</span>
              </button>
            );
          })}
        </div>
      )}

      {tampil.length === 0 ? (
        <div className="py-16 text-center text-sm text-slate-500">
          {q ? <>Tidak ada layanan cocok &quot;{cari}&quot;.</> : <>Tidak ada layanan di kategori ini.</>}
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {tampil.map((l) => {
            const I = ikonDari(l.icon);
            const nonaktif = mati.has(l.kunci);

            /*
             * 🔴 Kartu nonaktif SELALU abu-abu, MENGABAIKAN warna kategorinya.
             * Kalau ia ikut berwarna seperti yang lain, satu-satunya penanda
             * "tidak aktif" tinggal teks kecil — dan itu terlewat.
             *
             * ⚠️ Warna kategori dipakai TERBATAS: cuma glif ikonnya. Badan kartu
             * tetap putih dan kotak di belakang ikon tetap `bg-brand/10` untuk
             * semua kategori. Kartu berwarna penuh membuat halaman ramai dan
             * melemahkan satu-satunya warna yang memang harus menonjol, yaitu
             * penanda layanan tidak aktif.
             */
            const w = nonaktif ? WARNA_NETRAL : warna(l.category);
            return (
              <Link key={l.slug} href={`/dashboard/pengajuan-baru/${l.slug}`}
                    title={nonaktif
                      ? `${l.title} — dimatikan untuk semua peran; hanya Super Admin yang masih bisa membukanya`
                      : l.title}
                    className={`group flex items-center gap-3 rounded-2xl border p-4 text-left shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md ${
                      nonaktif
                        ? 'border-dashed border-slate-300 bg-slate-50 hover:border-slate-400'
                        : 'border-slate-200 bg-white hover:border-brand/40'
                    }`}>
                <div className={`flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-105 ${
                  nonaktif ? 'bg-slate-200 text-slate-500' : `bg-brand/10 ${w.ikon}`
                }`}>
                  <I className="h-5 w-5" />
                </div>
                <div className="min-w-0 flex-1">
                  <p className={`truncate text-sm font-semibold ${
                    nonaktif ? 'text-slate-500' : 'text-slate-900 group-hover:text-brand'
                  }`}>{l.title}</p>
                  {nonaktif ? (
                    <p className="flex items-center gap-1 text-xs font-medium text-slate-400">
                      <EyeOff className="h-3 w-3" /> Layanan tidak aktif
                    </p>
                  ) : (
                    <p className="line-clamp-1 text-xs text-slate-500">{l.description}</p>
                  )}
                </div>
                <ArrowRight className={`h-4 w-4 flex-shrink-0 text-slate-300 transition-all group-hover:translate-x-0.5 ${
                  nonaktif ? '' : 'group-hover:text-brand'
                }`} />
              </Link>
            );
          })}
        </div>
      )}

      {pengaturan && admin && (
        <PengaturanLayanan
          /*
           * 🔴 Menutup drawer harus MENYEGARKAN daftarnya.
           *
           * `tersembunyi` datang sebagai prop Inertia dari server dan tidak
           * berubah sendiri. Tanpa muat ulang, petugas mematikan sebuah
           * layanan, menutup drawer, lalu melihat layanan itu masih menyala di
           * belakangnya — dan menyimpulkan pengaturannya gagal tersimpan,
           * padahal sudah.
           *
           * ⚠️ `only` membatasi muat ulang pada prop yang memang berubah:
           * daftar layanan bisa panjang, dan menarik seluruh halaman untuk satu
           * centang adalah pemborosan yang terasa di jaringan kantor.
           */
          onTutup={(berubah) => {
            setPengaturan(false);
            if (berubah) router.reload({ only: ['tersembunyi', 'jam'] });
          }}
          onGalat={(teks) => setPesan({ tipe: 'galat', teks })}
        />
      )}
    </LayoutDashboard>
  );
}
