import { useEffect, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
  BarChart3, ClipboardList, FilePlus2, FolderOpen, Gauge, Home, Images, LayoutDashboard as IkonDasbor, LayoutGrid, LogOut,
  MessageSquare, MessagesSquare, Newspaper, ScrollText, Users, X,
} from 'lucide-react';

/**
 * Kerangka dashboard petugas — port `components/shared/dashboard-sidebar.tsx`
 * + `app/dashboard/layout.tsx`.
 *
 * Tiga hal yang sengaja BERBEDA dari aslinya, dan alasannya:
 *
 * 1. Peran dibaca dari **props Inertia** (`auth.user.level`), bukan Redux.
 *    Sesi sudah ikut di setiap respons, jadi menu tidak pernah sempat salah
 *    render sebelum store terisi — di portal lama sidebar sempat memakai
 *    `level ?? 3` sehingga menu admin berkedip hilang saat halaman dimuat.
 * 2. Drawer mobile ditulis tangan, bukan Radix Sheet. Satu-satunya yang
 *    dibutuhkan overlay + panel; menariknya masuk berarti menambah Radix ke
 *    bundel demi satu komponen.
 * 3. 🔴 Menu yang halamannya BELUM dibangun (Fase 6–8) tidak ditampilkan.
 *    Tautan ke halaman yang membalas 404 lebih buruk daripada menu yang belum
 *    ada — petugas menganggapnya rusak, bukan belum jadi.
 */

const GRUP = [
  { items: [{ href: '/dashboard', label: 'Statistik Rekap', icon: IkonDasbor, exact: true }] },
  {
    judul: 'Layanan',
    items: [
      { href: '/dashboard/pengajuan-baru', label: 'Pengajuan Baru', icon: FilePlus2 },
      { href: '/dashboard/permohonan', label: 'Permohonan', icon: ClipboardList },
    ],
  },
  {
    // Seluruh grup ini khusus Super Admin — yang menerbitkan konten ke halaman
    // publik hanya admin, operator tidak.
    judul: 'Konten & Media',
    items: [
      { href: '/dashboard/berita', label: 'Berita', icon: Newspaper, adminSaja: true },
      { href: '/dashboard/media', label: 'Pustaka Media', icon: Images, adminSaja: true },
      { href: '/dashboard/produk', label: 'Dokumen Publikasi', icon: FolderOpen, adminSaja: true },
      { href: '/dashboard/demografi', label: 'Data Demografi', icon: BarChart3, adminSaja: true },
    ],
  },
  {
    judul: 'Aspirasi Warga',
    items: [
      { href: '/dashboard/pengaduan', label: 'Pengaduan', icon: MessageSquare },
      { href: '/dashboard/kritik-saran', label: 'Kritik & Saran', icon: MessagesSquare },
      { href: '/dashboard/skm', label: 'SKM & IKM', icon: Gauge },
    ],
  },
  {
    judul: 'Sistem',
    items: [
      { href: '/dashboard/users', label: 'Manajemen Akun', icon: Users },
      { href: '/dashboard/log', label: 'Log Aktivitas', icon: ScrollText, adminSaja: true },
    ],
  },
];

/** Menu yang hanya boleh dilihat Super Admin (level 1). */
function grupUntuk(level) {
  return GRUP
    .map((g) => ({ ...g, items: g.items.filter((m) => !m.adminSaja || level === 1) }))
    .filter((g) => g.items.length > 0);
}

function aktifkan(url, href, exact) {
  return exact ? url === href : url === href || url.startsWith(`${href}/`) || url.startsWith(`${href}?`);
}

function ItemMenu({ m, url, onKlik, kelas }) {
  const aktif = aktifkan(url, m.href, m.exact);

  return (
    <Link
      href={m.href}
      onClick={onKlik}
      className={`flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
        aktif ? 'bg-brand text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'
      } ${kelas ?? ''}`}
    >
      <m.icon className="h-4 w-4 flex-shrink-0" />
      {m.label}
    </Link>
  );
}

export default function LayoutDashboard({ judul, children, lebar = 'max-w-7xl' }) {
  const { props, url } = usePage();
  const auth = props.auth;
  const level = auth?.user?.level ?? 3;
  const nama = auth?.user?.nama || auth?.user?.user_id || 'Petugas';
  const grup = grupUntuk(level);

  const [menuTerbuka, setMenuTerbuka] = useState(false);

  // Drawer harus tertutup sendiri saat pindah halaman — kalau tidak, ia tetap
  // menutupi halaman baru yang sudah dirender di belakangnya.
  useEffect(() => setMenuTerbuka(false), [url]);

  const keluar = () => router.post('/logout');

  return (
    <div className="min-h-screen bg-slate-50 lg:flex">
      <Head title={judul ? `${judul} — Dashboard SAIBATIN` : 'Dashboard SAIBATIN'} />

      {/* ── Sidebar desktop ─────────────────────────────────────────────── */}
      <aside className="sticky top-0 z-30 hidden h-screen w-60 flex-shrink-0 flex-col border-r border-slate-200 bg-white lg:flex">
        <div className="flex items-center gap-2.5 border-b border-slate-200 px-4 py-3">
          <img src="/logo-saibatin.png" alt="Logo SAIBATIN" className="h-9 w-9 flex-shrink-0 object-contain" />
          <div className="min-w-0">
            <p className="truncate text-sm font-bold leading-tight text-slate-900">SAIBATIN</p>
            <p className="truncate text-[0.68rem] leading-tight text-slate-500">Dashboard Petugas</p>
          </div>
        </div>

        <nav className="flex-1 overflow-y-auto p-3">
          {grup.map((g, i) => (
            <div key={i} className="flex flex-col gap-0.5">
              {g.judul && (
                <p className="px-3 pb-1.5 pt-4 text-[0.62rem] font-bold uppercase tracking-widest text-slate-400">
                  {g.judul}
                </p>
              )}
              {g.items.map((m) => <ItemMenu key={m.href} m={m} url={url} />)}
            </div>
          ))}
        </nav>

        <div className="flex items-center gap-2 border-t border-slate-200 p-3">
          <span className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-brand/10 text-sm font-bold text-brand">
            {nama.charAt(0).toUpperCase()}
          </span>
          <div className="min-w-0 flex-1">
            <p className="truncate text-sm font-medium text-slate-700">{nama}</p>
            <p className="text-[0.65rem] text-slate-400">{level === 1 ? 'Super Admin' : 'Operator'}</p>
          </div>
          <button onClick={keluar} title="Keluar" aria-label="Keluar"
                  className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-rose-600 transition-colors hover:bg-rose-50">
            <LogOut className="h-4 w-4" />
          </button>
        </div>
      </aside>

      <div className="min-w-0 flex-1 pb-16 lg:pb-0">
        {/* ── Top-bar mobile ────────────────────────────────────────────── */}
        <header className="sticky top-0 z-30 flex items-center justify-between gap-3 px-4 py-2.5 text-white shadow-md lg:hidden"
                style={{ background: 'linear-gradient(135deg,#1b4b72,#2176bd)' }}>
          <div className="flex min-w-0 items-center gap-2.5">
            {/* Alas putih: logonya berwarna gelap, di atas gradien biru
                header ia nyaris tak terlihat tanpa ini. */}
            <span className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-white p-0.5">
              <img src="/logo-saibatin.png" alt="Logo SAIBATIN" className="h-full w-full object-contain" />
            </span>
            <div className="min-w-0">
              <p className="truncate text-sm font-bold leading-tight">SAIBATIN</p>
              <p className="truncate text-[0.68rem] leading-tight text-white/70">{nama}</p>
            </div>
          </div>
          <button onClick={keluar} aria-label="Keluar"
                  className="flex h-9 w-9 items-center justify-center rounded-xl border border-white/20 bg-white/10">
            <LogOut className="h-4 w-4" />
          </button>
        </header>

        <main className={`mx-auto ${lebar} px-4 py-6 md:px-6`}>{children}</main>
      </div>

      {/* ── Bottom-nav mobile ───────────────────────────────────────────── */}
      <nav className="fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-slate-200 bg-white/95 backdrop-blur lg:hidden"
           style={{ paddingBottom: 'env(safe-area-inset-bottom)' }} aria-label="Navigasi dashboard">
        {[
          { href: '/dashboard', label: 'Statistik', icon: IkonDasbor, exact: true },
          { href: '/dashboard/permohonan', label: 'Permohonan', icon: ClipboardList },
          { href: '/dashboard/pengaduan', label: 'Pengaduan', icon: MessageSquare },
        ].map((m) => {
          const aktif = aktifkan(url, m.href, m.exact);
          return (
            <Link key={m.href} href={m.href}
                  className={`flex flex-col items-center gap-0.5 py-2 text-[0.65rem] font-medium ${aktif ? 'text-brand' : 'text-slate-500'}`}>
              <m.icon className="h-5 w-5" />{m.label}
            </Link>
          );
        })}
        <button onClick={() => setMenuTerbuka(true)}
                className="flex flex-col items-center gap-0.5 py-2 text-[0.65rem] font-medium text-slate-500"
                aria-label="Buka semua menu dashboard">
          <LayoutGrid className="h-5 w-5" />Menu
        </button>
      </nav>

      {/* ── Drawer "Semua Menu" (mobile) ────────────────────────────────── */}
      {menuTerbuka && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div className="absolute inset-0 bg-slate-900/40" onClick={() => setMenuTerbuka(false)} />
          <div className="absolute inset-x-0 bottom-0 max-h-[80dvh] overflow-y-auto rounded-t-2xl bg-white p-4">
            <div className="mb-2 flex items-center justify-between">
              <p className="text-sm font-bold text-slate-900">Semua Menu</p>
              <button onClick={() => setMenuTerbuka(false)} aria-label="Tutup menu"
                      className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                <X className="h-4 w-4" />
              </button>
            </div>

            <a href="/" className="mb-2 flex items-center gap-2.5 rounded-xl bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700">
              <Home className="h-4 w-4 text-brand" />Kembali ke Beranda
            </a>

            {grup.map((g, i) => (
              <div key={i}>
                {g.judul && (
                  <p className="px-1 pb-1.5 pt-3 text-[0.62rem] font-bold uppercase tracking-widest text-slate-400">{g.judul}</p>
                )}
                <div className="flex flex-col gap-0.5">
                  {g.items.map((m) => (
                    <ItemMenu key={m.href} m={m} url={url} onKlik={() => setMenuTerbuka(false)} />
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
