import { Suspense, lazy } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
  ArrowRight, CalendarDays, CheckCircle2, ClipboardList, Clock, Eye, Gauge,
  Hourglass, Landmark, MessageSquare, MessagesSquare, Newspaper, ScrollText,
  ShieldCheck, TrendingUp, UserCheck, Users, Wifi,
} from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import GrafikTampak from '@/Components/GrafikTampak';
import { Kartu, TombolEksporStatistik, angka, tglJam } from '@/Components/Dasbor';

// Highcharts ± 300 KB — dipisah jadi chunk sendiri supaya hanya terunduh saat
// beranda dashboard dibuka, bukan ikut bundel setiap halaman (termasuk login).
const GrafikHarian = lazy(() => import('@/Components/GrafikHarian'));

/**
 * Beranda dashboard petugas — port `app/dashboard/page.tsx`.
 *
 * Seluruh angkanya datang sebagai props dari `DashboardController`: halaman ini
 * murni tampilan, tidak memanggil satu endpoint pun. Persis seperti aslinya
 * yang server component — dan itu yang membuat halaman langsung tampil penuh,
 * bukan berkedip dari kosong ke terisi.
 */

const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

const persen = (bagian, total) => (total > 0 ? Math.round((bagian / total) * 100) : 0);

const STATUS_BAR = [
  { key: 'MENUNGGU', label: 'Menunggu', bar: 'bg-amber-400', teks: 'text-amber-600' },
  { key: 'DIPROSES', label: 'Diproses', bar: 'bg-sky-500', teks: 'text-sky-600' },
  { key: 'SELESAI', label: 'Selesai', bar: 'bg-emerald-500', teks: 'text-emerald-600' },
  { key: 'DITOLAK', label: 'Ditolak', bar: 'bg-rose-500', teks: 'text-rose-600' },
];

const PENGADUAN_BAR = [
  { key: 'BARU', label: 'Pengaduan Baru', bar: 'bg-amber-400', teks: 'text-amber-600' },
  { key: 'DIPROSES', label: 'Pengaduan Diproses', bar: 'bg-sky-500', teks: 'text-sky-600' },
  { key: 'SELESAI', label: 'Pengaduan Selesai', bar: 'bg-emerald-500', teks: 'text-emerald-600' },
];

const AKSI_WARNA = {
  BUAT: 'bg-emerald-50 text-emerald-700',
  UBAH: 'bg-sky-50 text-sky-700',
  HAPUS: 'bg-rose-50 text-rose-700',
  UNGGAH: 'bg-violet-50 text-violet-700',
  IMPOR: 'bg-indigo-50 text-indigo-700',
  LAINNYA: 'bg-slate-100 text-slate-600',
};

function BarisProgres({ label, nilai, total, bar, teks }) {
  return (
    <div className="space-y-1">
      <div className="flex items-center justify-between text-xs">
        <span className="font-medium text-slate-600">{label}</span>
        <span className={`font-bold tabular-nums ${teks}`}>
          {angka(nilai)} <span className="font-medium text-slate-400">({persen(nilai, total)}%)</span>
        </span>
      </div>
      <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
        <div className={`h-full rounded-full ${bar}`} style={{ width: `${persen(nilai, total)}%` }} />
      </div>
    </div>
  );
}

function Kotak({ nilai, label, kelas = 'bg-slate-50', warna = 'text-slate-900', ikon: Ikon }) {
  return (
    <div className={`rounded-xl p-3 text-center ${kelas}`}>
      <p className={`flex items-center justify-center gap-1 text-lg font-bold leading-none ${warna}`}>
        {Ikon && <Ikon className="h-4 w-4" />}{angka(nilai)}
      </p>
      <p className="mt-1 text-[0.65rem] font-medium text-slate-500">{label}</p>
    </div>
  );
}

export default function Beranda({ permohonan, aspirasi, akun, kunjungan, konten, logTerbaru }) {
  const { auth } = usePage().props;
  const sekarang = new Date();

  const selesai = permohonan.perStatus.SELESAI;
  const berjalan = permohonan.perStatus.MENUNGGU + permohonan.perStatus.DIPROSES;
  const totalPengaduan = Object.values(aspirasi.pengaduan).reduce((a, b) => a + b, 0);

  // `maksTren`/`maksJenis` dibuang bersama batang CSS-nya — Highcharts
  // menghitung skalanya sendiri.
  const totalHarian = permohonan.harian.reduce((a, t) => a + t.count, 0);

  const kpi = [
    { label: 'Total Permohonan', nilai: permohonan.total, ikon: ClipboardList, warna: 'bg-brand/10 text-brand' },
    { label: 'Selesai', nilai: selesai, ikon: CheckCircle2, warna: 'bg-emerald-50 text-emerald-600', lencana: `${persen(selesai, permohonan.total)}%` },
    { label: 'Sedang Berjalan', nilai: berjalan, ikon: Hourglass, warna: 'bg-amber-50 text-amber-600' },
    { label: 'Bulan Ini', nilai: permohonan.bulanIni, ikon: CalendarDays, warna: 'bg-sky-50 text-sky-600' },
  ];

  return (
    <LayoutDashboard judul="Statistik Rekap">
      <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 className="text-2xl font-semibold text-slate-900">Dashboard</h1>
          <p className="text-sm text-slate-500">
            {auth.user?.nama ?? auth.user?.user_id} · {auth.user?.level === 1 ? 'Super Admin' : 'Operator'} ·
            Ringkasan statistik &amp; progress pelayanan
          </p>
        </div>
        <div className="flex items-center gap-2">
          {/* Tanpa `bagian` = seluruh kartu statistik, satu sheet per kartu. */}
          <TombolEksporStatistik
            label="Export Excel"
            judul="Unduh SELURUH statistik dashboard (satu sheet per kartu)"
          />
          <span className="inline-flex items-center gap-1.5 rounded-full border border-brand/20 bg-brand/10 px-3 py-1.5 text-xs font-semibold text-brand">
            <TrendingUp className="h-3.5 w-3.5" />{BULAN[sekarang.getMonth()]} {sekarang.getFullYear()}
          </span>
        </div>
      </div>

      <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        {kpi.map((c) => (
          <div key={c.label} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex items-start justify-between">
              <span className={`flex h-9 w-9 items-center justify-center rounded-xl ${c.warna}`}>
                <c.ikon className="h-4 w-4" />
              </span>
              {c.lencana && (
                <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-600">{c.lencana}</span>
              )}
            </div>
            <p className="mt-3 text-2xl font-bold leading-none text-slate-900">{angka(c.nilai)}</p>
            <p className="mt-1.5 text-xs font-medium text-slate-500">{c.label}</p>
          </div>
        ))}
      </div>

      <div className="mb-4 grid gap-4 lg:grid-cols-3">
        <Kartu judul="Progress Permohonan" ikon={ClipboardList} ekspor="progress"
               aksi={<Link href="/dashboard/permohonan" className="inline-flex items-center gap-1 text-xs font-semibold text-brand">Kelola <ArrowRight className="h-3 w-3" /></Link>}>
          {permohonan.total === 0 ? (
            <p className="py-6 text-center text-sm text-slate-400">Belum ada permohonan.</p>
          ) : (
            <div className="space-y-3">
              {STATUS_BAR.map((s) => (
                <BarisProgres key={s.key} label={s.label} nilai={permohonan.perStatus[s.key]}
                              total={permohonan.total} bar={s.bar} teks={s.teks} />
              ))}
            </div>
          )}
        </Kartu>

        {/*
          🔴 Dulu batang CSS buatan tangan, dan RUSAK: batangnya bersarang di
          dalam flex `h-full` yang tingginya runtuh ke 0, jadi kartunya tampil
          sebagai deretan angka melayang di atas ruang kosong. Highcharts
          mengukur wadahnya sendiri, jadi tinggi yang runtuh tidak lagi
          menghapus grafiknya — sekaligus memenuhi keputusan user bahwa grafik
          memakai Highcharts.
        */}
        <Kartu judul="Tren Permohonan · 6 Bulan" ikon={TrendingUp} ekspor="tren">
          <GrafikTampak jenis="tren" data={permohonan.tren} tinggi={168} />
        </Kartu>

        {/* Kartunya hanya memuat 5 teratas; ekspornya SELURUH jenis layanan. */}
        <Kartu judul="Layanan Terpopuler" ikon={Gauge} ekspor="layanan">
          {permohonan.terpopuler.length === 0 ? (
            <p className="py-6 text-center text-sm text-slate-400">Belum ada data.</p>
          ) : (
            <GrafikTampak jenis="peringkat" data={permohonan.terpopuler} tinggi={190} />
          )}
        </Kartu>
      </div>

      <div className="mb-4">
        <Kartu judul="Permohonan per Tanggal · 30 Hari Terakhir" ikon={CalendarDays} ekspor="harian"
               aksi={<span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold tabular-nums text-slate-500">{angka(totalHarian)} permohonan</span>}>
          {totalHarian === 0 ? (
            <p className="py-8 text-center text-sm text-slate-400">Belum ada permohonan dalam 30 hari terakhir.</p>
          ) : (
            <Suspense fallback={<div className="h-44" />}>
              <GrafikHarian harian={permohonan.harian} />
            </Suspense>
          )}
        </Kartu>
      </div>

      {logTerbaru.length > 0 && (
        <div className="mb-4">
          <Kartu judul="Aktivitas Terbaru" ikon={ScrollText}
                 aksi={<Link href="/dashboard/log" className="inline-flex items-center gap-1 text-xs font-semibold text-brand">Lihat semua <ArrowRight className="h-3 w-3" /></Link>}>
            <ul className="divide-y divide-slate-100">
              {logTerbaru.map((l) => (
                <li key={l.id} className="flex items-start gap-2.5 py-2.5">
                  <span className={`mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-wide ${AKSI_WARNA[l.aksi] ?? AKSI_WARNA.LAINNYA}`}>
                    {l.aksi}
                  </span>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm text-slate-700">{l.ringkasan}</p>
                    <p className="text-[0.68rem] text-slate-400">{l.pelaku} · {tglJam(l.createdAt)}</p>
                  </div>
                </li>
              ))}
            </ul>
          </Kartu>
        </div>
      )}

      <div className="grid gap-4 lg:grid-cols-2">
        <Kartu judul="Aspirasi Warga" ikon={MessageSquare} ekspor="aspirasi"
               aksi={<Link href="/dashboard/pengaduan" className="inline-flex items-center gap-1 text-xs font-semibold text-brand">Kelola <ArrowRight className="h-3 w-3" /></Link>}>
          <div className="space-y-3">
            {totalPengaduan === 0 ? (
              <p className="py-2 text-center text-sm text-slate-400">Belum ada pengaduan.</p>
            ) : PENGADUAN_BAR.map((s) => (
              <BarisProgres key={s.key} label={s.label} nilai={aspirasi.pengaduan[s.key]}
                            total={totalPengaduan} bar={s.bar} teks={s.teks} />
            ))}

            <div className="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
              <div className="rounded-xl bg-slate-50 p-3 text-center">
                <MessagesSquare className="mx-auto mb-1 h-4 w-4 text-violet-500" />
                <p className="text-lg font-bold leading-none text-slate-900">{angka(aspirasi.kritikTotal)}</p>
                <p className="mt-1 text-[0.65rem] font-medium text-slate-500">
                  Kritik &amp; Saran{aspirasi.kritikBulanIni > 0 ? ` (+${aspirasi.kritikBulanIni} bln ini)` : ''}
                </p>
              </div>
              <div className="rounded-xl bg-slate-50 p-3 text-center">
                <Gauge className="mx-auto mb-1 h-4 w-4 text-teal-500" />
                <p className="text-lg font-bold leading-none text-slate-900">{angka(aspirasi.skmTotal)}</p>
                <p className="mt-1 text-[0.65rem] font-medium text-slate-500">Responden SKM</p>
              </div>
            </div>
          </div>
        </Kartu>

        <Kartu judul="Akun Pengguna" ikon={Users} ekspor="akun"
               aksi={<Link href="/dashboard/users" className="inline-flex items-center gap-1 text-xs font-semibold text-brand">Kelola <ArrowRight className="h-3 w-3" /></Link>}>
          <div className="mb-3 grid grid-cols-3 gap-2">
            <Kotak nilai={akun.total} label="Total" />
            <Kotak nilai={akun.aktif} label="Aktif" kelas="bg-emerald-50" warna="text-emerald-600" ikon={UserCheck} />
            <Kotak nilai={akun.menunggu} label="Menunggu" kelas="bg-amber-50" warna="text-amber-600" ikon={Clock} />
          </div>
          <div className="space-y-2">
            {[
              { label: 'Warga', nilai: akun.warga, ikon: Users },
              { label: 'Operator OPD', nilai: akun.opd, ikon: Landmark },
              { label: 'Staff Dinas', nilai: akun.staff, ikon: ShieldCheck },
            ].map((g) => (
              <div key={g.label} className="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2">
                <span className="flex items-center gap-2 text-xs font-medium text-slate-600">
                  <g.ikon className="h-3.5 w-3.5 text-slate-400" />{g.label}
                </span>
                <span className="text-sm font-bold tabular-nums text-slate-900">{angka(g.nilai)}</span>
              </div>
            ))}
          </div>
        </Kartu>

        <Kartu judul="Pengunjung Situs" ikon={Eye} ekspor="pengunjung">
          <p className="mb-3 text-sm leading-relaxed text-slate-600">
            Saat ini ada{' '}
            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 font-bold text-emerald-600">
              <Wifi className="h-3.5 w-3.5" />{angka(kunjungan.online)}
            </span>{' '}
            pengunjung yang online.
          </p>
          <div className="grid grid-cols-2 gap-2">
            <Kotak nilai={kunjungan.hariIni} label="Pengunjung Hari Ini" />
            <Kotak nilai={kunjungan.total} label="Total Kunjungan" />
          </div>
          <p className="mt-3 text-[0.68rem] leading-relaxed text-slate-400">
            Dihitung dari halaman publik (dashboard tidak ikut). Online = aktif dalam 5 menit terakhir.
          </p>
        </Kartu>

        <Kartu judul="Konten Situs" ikon={Newspaper} ekspor="konten">
          {/* Halaman pengelolanya dibangun di Fase 6 — di sini baru angkanya,
              jadi belum ditautkan supaya tidak mengarah ke halaman yang belum ada. */}
          <div className="grid grid-cols-2 gap-2">
            <Kotak nilai={konten.beritaTerbit} label="Berita Terbit" />
            <Kotak nilai={konten.beritaDraf} label="Draf Berita" />
            <Kotak nilai={konten.galeri} label="Foto Galeri" />
            <Kotak nilai={konten.produk} label="Dokumen Publikasi" />
          </div>
        </Kartu>
      </div>
    </LayoutDashboard>
  );
}
