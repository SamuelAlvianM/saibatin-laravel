import { useEffect, useRef, useState } from 'react';
import {
  Building2, ChevronDown, ChevronRight, FileText, Home, Landmark,
  LayoutDashboard, LifeBuoy, LogOut, Menu, Newspaper, Phone, ShieldAlert,
  Smile, User as UserIcon, X,
} from 'lucide-react';

import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetTrigger } from '@/Components/ui/sheet';
import LoncengNotifikasi from '@/Components/LoncengNotifikasi';
import { menuNavigasi } from '@/lib/navigasi';
import { cn } from '@/lib/utils';

/**
 * Navbar situs publik — port `components/shared/navbar.tsx`.
 *
 * Dipasang dari Blade sebagai island (`data-island="Navbar"`). Tiga hal yang
 * memang harus beda dari aslinya:
 *
 * 1. **Keadaan login datang sebagai prop**, bukan dari store Redux — halaman
 *    publik di port ini tidak menjalankan Inertia, jadi tidak ada store sama
 *    sekali. Server yang sudah tahu sesinya, jadi dia yang mengirimkan.
 * 2. **`<a>` biasa, bukan `<Link>`** — antar-halaman publik memang pindah
 *    halaman penuh; itu justru yang membuatnya terbaca mesin pencari.
 * 3. **Logout lewat form POST ber-CSRF**, bukan thunk.
 */

/**
 * 🔴 Kuncinya adalah LABEL menu di `lib/navigasi.js`. Mengganti label di sana
 * tanpa mengganti kunci di sini membuat ikonnya hilang diam-diam — tidak ada
 * galat, menunya cuma jadi teks polos di antara menu lain yang berikon.
 */
const IKON_MENU = {
  Permohonan: FileText,
  'Pelayanan Online': Building2,
  'Informasi Produk': FileText,
  'Media Informasi': Newspaper,
  PPID: Landmark,
  WBS: ShieldAlert,
  'Pusat Bantuan': LifeBuoy,
  'Survei Kepuasan': Smile,
  'Hubungi Kami': Phone,
};

const KELAS_MENU_ATAS = cn(
  'relative flex items-center gap-1.5 whitespace-nowrap rounded-md px-2.5 py-2 text-sm font-medium text-white/90',
  'transition-all duration-300 ease-out hover:bg-white/10 hover:text-yellow-300',
  'before:absolute before:bottom-0 before:left-1/2 before:h-0.5 before:w-0 before:-translate-x-1/2 before:bg-yellow-300',
  'before:transition-all before:duration-300 before:ease-out hover:before:w-[calc(100%-1.25rem)]',
);

/** Satu item di dalam panel dropdown — keterangannya muncul saat ditunjuk. */
function ItemDropdown({ item, onTutup, index, terlihat }) {
  const [tampilKet, setTampilKet] = useState(false);
  const [ditunjuk, setDitunjuk] = useState(false);
  const jeda = useRef(undefined);

  useEffect(() => () => clearTimeout(jeda.current), []);

  return (
    <a
      href={item.href}
      className={cn(
        'mx-2 block rounded-md px-4 py-3 transition-all duration-300 ease-out',
        'hover:bg-gradient-to-r hover:from-accent/80 hover:to-accent/40 hover:shadow-sm hover:translate-x-1',
        terlihat ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-1',
      )}
      style={{
        transitionDelay: terlihat ? `${index * 40}ms` : '0ms',
        transitionDuration: terlihat ? '250ms' : '200ms',
      }}
      onMouseEnter={() => {
        setDitunjuk(true);
        jeda.current = setTimeout(() => setTampilKet(true), 250);
      }}
      onMouseLeave={() => {
        setDitunjuk(false);
        clearTimeout(jeda.current);
        setTampilKet(false);
      }}
      onClick={onTutup}
    >
      <div className={cn('text-sm font-medium transition-all duration-300 ease-out', ditunjuk && 'translate-x-1 text-primary')}>
        {item.title}
      </div>
      <div
        className={cn(
          'overflow-hidden text-xs text-muted-foreground transition-all duration-300 ease-out',
          tampilKet ? 'mt-1 max-h-20 opacity-100' : 'max-h-0 opacity-0',
        )}
      >
        {item.description}
      </div>
    </a>
  );
}

/** Menu atas yang punya dropdown. Tingginya dianimasikan dari 0 ke scrollHeight. */
function MenuDropdown({ title, items, icon: Ikon }) {
  const [buka, setBuka] = useState(false);
  const [ditunjuk, setDitunjuk] = useState(false);
  const [tinggi, setTinggi] = useState(0);
  const isiRef = useRef(null);
  const akarRef = useRef(null);

  // Tinggi panel dihitung ulang saat isinya berubah — kalau dipaku, item yang
  // teksnya membungkus dua baris akan terpotong.
  useEffect(() => {
    if (!buka || !isiRef.current) return;
    const ukur = () => isiRef.current && setTinggi(isiRef.current.scrollHeight);
    ukur();
    const ro = new ResizeObserver(ukur);
    ro.observe(isiRef.current);
    return () => ro.disconnect();
  }, [buka]);

  useEffect(() => {
    const klikLuar = (e) => {
      if (akarRef.current && !akarRef.current.contains(e.target)) setBuka(false);
    };
    if (buka) document.addEventListener('mousedown', klikLuar);
    return () => document.removeEventListener('mousedown', klikLuar);
  }, [buka]);

  return (
    <div className="relative" ref={akarRef}>
      <button
        onClick={() => setBuka((p) => !p)}
        onMouseEnter={() => setDitunjuk(true)}
        onMouseLeave={() => setDitunjuk(false)}
        aria-expanded={buka}
        className={cn(KELAS_MENU_ATAS, buka && 'bg-white/10 text-yellow-300')}
      >
        {Ikon && (
          <Ikon
            className={cn('h-4 w-4 flex-shrink-0 transition-transform duration-300 ease-out', ditunjuk && 'scale-110')}
            strokeWidth={2}
          />
        )}
        <span>{title}</span>
        <ChevronDown
          className={cn(
            'h-4 w-4 flex-shrink-0 transition-all duration-300 ease-out',
            buka && 'rotate-180',
            ditunjuk && !buka && 'translate-y-0.5',
          )}
          strokeWidth={2}
        />
      </button>

      <div className="absolute left-0 top-full z-50 mt-2">
        <div
          className="min-w-70 transition-[height,opacity] duration-300 ease-out"
          style={{ height: buka ? tinggi : 0, opacity: buka ? 1 : 0, pointerEvents: buka ? 'auto' : 'none' }}
        >
          <div
            ref={isiRef}
            className={cn('rounded-xl py-2 transition-all duration-300 ease-out', buka ? 'scale-100 shadow-xl' : 'scale-95 shadow-lg')}
            style={{
              background: 'rgba(255,255,255,0.97)',
              backdropFilter: 'blur(12px)',
              border: '1px solid rgba(33,118,189,0.12)',
              boxShadow: '0 8px 32px rgba(33,118,189,0.12)',
              overflow: buka ? 'visible' : 'hidden',
            }}
          >
            {items.map((item, i) => (
              <ItemDropdown key={item.title} item={item} index={i} terlihat={buka} onTutup={() => setBuka(false)} />
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

/** Kotak ikon kecil di kiri item menu mobile. */
function IkonItemMobile({ icon: Ikon, aktif }) {
  if (!Ikon) return null;
  return (
    <span
      className={cn(
        'flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg transition-colors',
        aktif ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-500',
      )}
    >
      <Ikon className="h-4 w-4" strokeWidth={2} />
    </span>
  );
}

function ItemMenuMobile({ title, href, items, onTutup, icon: Ikon }) {
  const [buka, setBuka] = useState(false);

  if (!items) {
    return (
      <a
        href={href ?? `/${title.toLowerCase().replace(/\s+/g, '-')}`}
        onClick={onTutup}
        className="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[0.925rem] font-medium text-slate-700 transition-colors hover:bg-primary/5 hover:text-primary"
      >
        <IkonItemMobile icon={Ikon} />
        {title}
      </a>
    );
  }

  return (
    <div>
      <button
        onClick={() => setBuka((p) => !p)}
        aria-expanded={buka}
        className={cn(
          'flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left text-[0.925rem] font-medium transition-colors',
          buka ? 'bg-primary/5 text-primary' : 'text-slate-700 hover:bg-primary/5 hover:text-primary',
        )}
      >
        <span className="flex items-center gap-3">
          <IkonItemMobile icon={Ikon} aktif={buka} />
          {title}
        </span>
        <ChevronDown
          className={cn('h-4 w-4 flex-shrink-0 text-slate-400 transition-transform duration-300 ease-out', buka && 'rotate-180 text-primary')}
          strokeWidth={2}
        />
      </button>

      <div className={cn('overflow-hidden transition-all duration-300 ease-out', buka ? 'max-h-[2000px] opacity-100' : 'max-h-0 opacity-0')}>
        <div className="ml-[1.35rem] mt-1 space-y-0.5 border-l border-slate-200 pb-1 pl-3">
          {items.map((item) => (
            <a
              key={item.title}
              href={item.href}
              onClick={onTutup}
              className="block rounded-lg px-3 py-2 text-sm text-slate-600 transition-colors hover:bg-primary/5 hover:text-primary"
            >
              {item.title}
            </a>
          ))}
        </div>
      </div>
    </div>
  );
}

/**
 * Sudut kanan navbar: tombol login, atau nama pengguna + menu akun.
 *
 * Logout memakai form POST sungguhan (Laravel menolak GET untuk ini, dan
 * benar begitu — logout lewat GET bisa dipicu tag <img> di situs lain).
 */
function AreaAkun({ user, mobile, onNavigasi, onKeluar }) {
  const [buka, setBuka] = useState(false);
  const akarRef = useRef(null);
  const formRef = useRef(null);

  useEffect(() => {
    const klikLuar = (e) => {
      if (akarRef.current && !akarRef.current.contains(e.target)) setBuka(false);
    };
    if (buka) document.addEventListener('mousedown', klikLuar);
    return () => document.removeEventListener('mousedown', klikLuar);
  }, [buka]);

  /**
   * 🔴 DUA jalur keluar, dan memilih yang salah berarti 419 tanpa penjelasan.
   *
   * Di halaman **Blade** tidak ada Inertia, jadi keluar lewat form POST biasa
   * dengan token dari `<meta name="csrf-token">` — meta itu selalu segar
   * karena halamannya memang dirender ulang penuh setiap kali.
   *
   * Di halaman **Inertia** meta itu BASI: `<head>` tidak pernah dirender ulang,
   * sementara login memanggil `session()->regenerate()`. Karena itu pemanggil
   * Inertia mengirim `onKeluar` sendiri (`router.post('/logout')`), yang
   * memakai cookie XSRF-TOKEN yang disegarkan tiap respons.
   * Lihat HANDOFF §5 no. 9.
   */
  const keluar = () => (onKeluar ? onKeluar() : formRef.current?.submit());
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  const FormKeluar = onKeluar ? null : (
    <form ref={formRef} method="POST" action="/logout" className="hidden">
      <input type="hidden" name="_token" value={csrf} />
    </form>
  );

  if (!user) {
    return (
      <Button
        asChild
        className={cn(
          mobile && 'w-full',
          'transition-all duration-300 ease-out hover:-translate-y-0.5 hover:scale-105 hover:shadow-lg active:translate-y-0 active:scale-95',
        )}
      >
        <a href="/login" onClick={onNavigasi}>Login/Daftar</a>
      </Button>
    );
  }

  const nama = user.nama || user.user_id || 'Pengguna';
  /*
   * 🔴 `level <= 2` MENYINGKIRKAN OPD dari dashboardnya sendiri.
   *
   * Rute `/dashboard` sudah dijaga `peran:petugas,opd` — akun instansi memang
   * diizinkan masuk, lengkap dengan sidebar-nya (Pengajuan Baru & Permohonan
   * Saya). Tapi header masih mengantar mereka ke `/user/pengajuan`, halaman
   * warga. Satu akun, dua pintu masuk berbeda, dan tak ada satu pun yang
   * memberitahu mana yang benar.
   */
  const petugas = (user.level ?? 3) <= 2;
  const punyaDashboard = petugas || user.level === 4;
  const areaHref = punyaDashboard ? '/dashboard' : '/user/pengajuan';
  const areaLabel = punyaDashboard ? 'Dashboard' : 'Pengajuan Saya';

  if (mobile) {
    return (
      <div className="w-full space-y-1">
        {FormKeluar}
        <div className="flex items-center gap-2 px-1 py-2 text-sm font-medium">
          <span className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-slate-900 text-white">
            <UserIcon className="h-4 w-4" />
          </span>
          <span className="truncate">{nama}</span>
        </div>
        <a href={areaHref} onClick={onNavigasi}
           className="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent/50">
          <LayoutDashboard className="h-4 w-4" /> {areaLabel}
        </a>
        <a href="/profil" onClick={onNavigasi}
           className="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent/50">
          <UserIcon className="h-4 w-4" /> Pengaturan Akun
        </a>
        <button onClick={keluar}
                className="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-destructive transition-colors hover:bg-destructive/10">
          <LogOut className="h-4 w-4" /> Keluar
        </button>
      </div>
    );
  }

  return (
    <div className="relative" ref={akarRef}>
      {FormKeluar}
      <button
        onClick={() => setBuka((p) => !p)}
        className={cn(
          'inline-flex items-center gap-2 rounded-md px-2.5 py-1.5 text-sm font-medium text-white transition-colors hover:bg-white/15',
          buka && 'bg-white/15',
        )}
      >
        <span className="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-white/20 text-white ring-1 ring-white/40">
          <UserIcon className="h-4 w-4" />
        </span>
        <span className="max-w-[10rem] truncate text-white">{nama}</span>
        <ChevronDown className={cn('h-4 w-4 text-white transition-transform', buka && 'rotate-180')} />
      </button>

      {/* Panel selalu dirender supaya buka/tutupnya bisa dianimasikan. */}
      <div
        className={cn(
          'absolute right-0 top-full z-50 mt-2 min-w-52 origin-top-right rounded-xl py-1 transition-all duration-200 ease-out',
          buka ? 'visible translate-y-0 scale-100 opacity-100' : 'invisible pointer-events-none -translate-y-1 scale-95 opacity-0',
        )}
        style={{
          background: 'rgba(255,255,255,0.97)',
          backdropFilter: 'blur(12px)',
          border: '1px solid rgba(33,118,189,0.12)',
          boxShadow: '0 8px 32px rgba(33,118,189,0.15)',
        }}
      >
        <a href={areaHref} className="flex items-center gap-2 px-4 py-2.5 text-sm transition-colors hover:bg-accent/50">
          <LayoutDashboard className="h-4 w-4" /> {areaLabel}
        </a>
        <a href="/profil" className="flex items-center gap-2 px-4 py-2.5 text-sm transition-colors hover:bg-accent/50">
          <UserIcon className="h-4 w-4" /> Pengaturan Akun
        </a>
        <div className="my-1 border-t" />
        <button onClick={keluar}
                className="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-destructive transition-colors hover:bg-destructive/10">
          <LogOut className="h-4 w-4" /> Keluar
        </button>
      </div>
    </div>
  );
}

/**
 * @param lonceng  tampilkan lonceng notifikasi di sudut kanan.
 *
 * 🔴 Sengaja BUKAN otomatis-menyala saat ada `user`. Navbar ini juga dirender
 * sebagai React island di halaman **Blade** publik, dan lonceng itu menavigasi
 * lewat `router.visit()` milik Inertia yang tidak punya konteks di sana.
 * Hanya pemanggil dari halaman Inertia (`LayoutPengguna`) yang menyalakannya;
 * dashboard petugas punya loncengnya sendiri di `LayoutDashboard`.
 */
export default function Navbar({ user = null, onKeluar = null, lonceng = false }) {
  const [tergulir, setTergulir] = useState(false);
  const [mobileBuka, setMobileBuka] = useState(false);
  const [logoDitunjuk, setLogoDitunjuk] = useState(false);

  useEffect(() => {
    const onGulir = () => setTergulir(window.scrollY > 10);
    onGulir();
    window.addEventListener('scroll', onGulir);
    return () => window.removeEventListener('scroll', onGulir);
  }, []);

  // Akun OPD (level 4): navbar disederhanakan — hanya Permohonan; pengaturan
  // akun tetap lewat dropdown profil.
  const opd = user?.level === 4;
  const menu = opd ? [{ title: 'Permohonan', href: '/user/pengajuan' }] : menuNavigasi;

  return (
    <header className={cn('glass-nav sticky top-0 z-50 w-full transition-all duration-500 ease-out',
                          tergulir ? 'shadow-lg shadow-blue-900/20' : 'shadow-md shadow-blue-900/10')}>
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <nav className="flex min-h-16 items-center justify-between gap-4">
          {/* Logo */}
          <a href="/" className="group flex flex-shrink-0 items-center space-x-2 sm:space-x-3"
             onMouseEnter={() => setLogoDitunjuk(true)} onMouseLeave={() => setLogoDitunjuk(false)}>
            <img src="/logo-saibatin.png" alt="Logo Disdukcapil Pesisir Barat"
                 className={cn('h-10 w-10 object-contain transition-all duration-500 ease-out', logoDitunjuk && 'scale-120')} />
            <div className="flex flex-col">
              <span className={cn('text-base font-bold leading-tight text-white transition-all duration-300 ease-out',
                                  logoDitunjuk && 'translate-x-1 text-yellow-300')}>
                SAIBATIN
              </span>
              <span className={cn('hidden text-xs leading-tight text-primary-foreground/80 transition-all duration-300 ease-out sm:block',
                                  logoDitunjuk && 'translate-x-1')}>
                Disdukcapil Kab. Pesisir Barat
              </span>
            </div>
          </a>

          {/* Menu desktop */}
          <div className="hidden flex-1 flex-nowrap items-center justify-center gap-0.5 px-2 lg:flex">
            {menu.map((item) => {
              const Ikon = IKON_MENU[item.title];
              if (!item.items?.length && item.href) {
                return (
                  <a key={item.title} href={item.href} className={KELAS_MENU_ATAS}>
                    {Ikon && <Ikon className="h-4 w-4 flex-shrink-0" strokeWidth={2} />}
                    {item.title}
                  </a>
                );
              }
              return <MenuDropdown key={item.title} title={item.title} items={item.items} icon={Ikon} />;
            })}
          </div>

          {/* Akun (desktop) */}
          <div className="hidden flex-shrink-0 items-center gap-1.5 lg:flex">
            {lonceng && user && <LoncengNotifikasi nada="gelap" />}
            <AreaAkun user={user} onKeluar={onKeluar} />
          </div>

          {/* Mobile: hamburger */}
          <div className="flex items-center gap-1 lg:hidden">
            {lonceng && user && <LoncengNotifikasi nada="gelap" />}
            <Sheet open={mobileBuka} onOpenChange={setMobileBuka}>
              <SheetTrigger asChild>
                <button aria-label="Buka menu navigasi"
                        className="flex h-10 w-10 items-center justify-center rounded-xl border border-white/20 bg-white/10 text-white backdrop-blur transition-colors hover:bg-white/20">
                  <Menu className="h-5 w-5" strokeWidth={2} />
                </button>
              </SheetTrigger>
              <SheetContent side="right" showCloseButton={false}
                            className="flex w-[320px] flex-col gap-0 border-l-0 p-0 sm:w-[380px] sm:max-w-[380px]">
                {/* Kepala panel */}
                <div className="flex items-center justify-between px-5 py-4"
                     style={{ background: 'linear-gradient(135deg,#1b4b72 0%,#2176bd 100%)' }}>
                  <div className="flex items-center gap-2.5">
                    <img src="/logo-saibatin.png" alt="Logo SAIBATIN" className="h-9 w-9 object-contain" />
                    <div>
                      <p className="text-sm font-bold leading-tight text-white">SAIBATIN</p>
                      <p className="text-[0.7rem] leading-tight text-white/70">Disdukcapil Kab. Pesisir Barat</p>
                    </div>
                  </div>
                  <button onClick={() => setMobileBuka(false)} aria-label="Tutup menu"
                          className="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/25">
                    <X className="h-4 w-4" strokeWidth={2} />
                  </button>
                </div>

                <nav className="flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
                  <p className="px-3 pb-1.5 text-[0.65rem] font-bold uppercase tracking-widest text-slate-400">Menu</p>
                  {!opd && (
                    <a href="/" onClick={() => setMobileBuka(false)}
                       className="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[0.925rem] font-medium text-slate-700 transition-colors hover:bg-primary/5 hover:text-primary">
                      <IkonItemMobile icon={Home} />
                      Beranda
                    </a>
                  )}
                  {menu.map((item) => (
                    <ItemMenuMobile key={item.title} title={item.title} href={item.href} items={item.items}
                                    onTutup={() => setMobileBuka(false)} icon={IKON_MENU[item.title]} />
                  ))}
                </nav>

                <div className="border-t border-slate-100 bg-slate-50/80 p-4">
                  <AreaAkun user={user} mobile onKeluar={onKeluar} onNavigasi={() => setMobileBuka(false)} />
                </div>
              </SheetContent>
            </Sheet>
          </div>
        </nav>
      </div>
    </header>
  );
}
