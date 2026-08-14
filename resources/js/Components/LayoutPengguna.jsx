import { Head, Link, router, usePage } from '@inertiajs/react';
import { ClipboardList, FilePlus2, LayoutDashboard, LogOut, UserRound } from 'lucide-react';
import LoncengNotifikasi from '@/Components/LoncengNotifikasi';

/** Kerangka halaman area berizin (warga/OPD/petugas). */
export default function LayoutPengguna({ judul, children, lebar = 'max-w-5xl' }) {
  const { auth } = usePage().props;
  const { url } = usePage();

  const menu = [
    { href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { href: '/user/pengajuan', label: 'Pengajuan Saya', icon: ClipboardList },
    { href: '/user/pengajuan/baru', label: 'Ajukan Baru', icon: FilePlus2 },
    { href: '/profil', label: 'Profil Saya', icon: UserRound },
  ];

  return (
    <div className="min-h-screen bg-slate-50">
      <Head title={judul} />

      <header className="text-white" style={{ background: 'linear-gradient(135deg,#1b4b72,#2176bd)' }}>
        <div className={`mx-auto flex ${lebar} items-center justify-between gap-4 px-5 py-4`}>
          <Link href="/dashboard" className="flex items-center gap-3">
            <span className="flex h-10 w-10 items-center justify-center rounded-full bg-white p-1">
              <img src="/logo-saibatin.png" alt="Logo SAIBATIN" className="h-full w-full object-contain" />
            </span>
            <div>
              <p className="text-sm font-semibold leading-tight">Portal SAIBATIN</p>
              <p className="text-xs opacity-80">{auth.user?.nama ?? auth.user?.user_id}</p>
            </div>
          </Link>
          <div className="flex items-center gap-2">
            <LoncengNotifikasi nada="gelap" />
            <button onClick={() => router.post('/logout')}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1.5 text-sm font-medium transition-colors hover:bg-white/25">
              <LogOut className="h-4 w-4" />Keluar
            </button>
          </div>
        </div>

        <nav className={`mx-auto flex ${lebar} gap-1 px-3`}>
          {menu.map((m) => {
            const aktif = url === m.href || (m.href !== '/dashboard' && url.startsWith(m.href));
            return (
              <Link key={m.href} href={m.href}
                    className={`inline-flex items-center gap-1.5 rounded-t-lg px-3 py-2 text-sm font-medium transition-colors ${
                      aktif ? 'bg-slate-50 text-brand-dark' : 'text-white/80 hover:bg-white/10 hover:text-white'
                    }`}>
                <m.icon className="h-4 w-4" />{m.label}
              </Link>
            );
          })}
        </nav>
      </header>

      <main className={`mx-auto ${lebar} px-5 py-8`}>{children}</main>
    </div>
  );
}
