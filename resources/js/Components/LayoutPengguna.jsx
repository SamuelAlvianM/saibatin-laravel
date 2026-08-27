import { Head, router, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import FooterPublik from '@/Components/FooterPublik';
import Navbar from '@/Publik/Navbar';

/**
 * Kerangka halaman area berizin (warga/OPD): `/profil`, `/user/pengajuan`,
 * `/user/pengajuan/baru`, dan formulir layanannya.
 *
 * 🔴 DULU berupa bilah tab biru sendiri (Dashboard · Pengajuan Saya · Ajukan
 * Baru · Profil Saya). Itu **karangan port ini**, bukan bentuk aslinya:
 * dibuat waktu Fase 2/4 ketika situs publik belum ada, jadi belum ada navbar
 * yang bisa dipakai ulang. Di SAIBATIN maupun SIDAKO, `app/layout.tsx`
 * merender **navbar publik yang sama untuk SEMUA halaman**, termasuk halaman
 * warga — dan "Pengaturan Akun" cukup lewat dropdown akun di navbar itu.
 * Komentarnya bahkan tertulis di `app/user/pengajuan/page.tsx` portal asli:
 * *"Pengaturan Akun cukup lewat dropdown akun di navbar — tidak diduplikasi
 * di sini."* Sekarang situs publiknya ada, jadi tabnya dibuang dan navbar
 * aslinya dipakai.
 *
 * 🔴 `onKeluar` WAJIB diteruskan ke Navbar. Jalur keluar bawaannya form POST
 * dengan token dari `<meta name="csrf-token">`, dan di halaman Inertia meta
 * itu basi begitu sesi diregenerasi saat login → 419 tanpa penjelasan
 * (HANDOFF §5 no. 9). `router.post` memakai cookie XSRF yang selalu segar.
 *
 * @param kembali  tautan "← Kembali" di atas judul (bentuk `BackButton` asli).
 * @param hero     header biru bergaya `/user/pengajuan`; dipakai menggantikan
 *                 `kembali` pada halaman yang aslinya memang punya hero.
 */
export default function LayoutPengguna({
  judul,
  children,
  lebar = 'max-w-4xl',
  kembali = null,
  hero = null,
}) {
  const { auth } = usePage().props;

  return (
    <div className="min-h-screen bg-slate-50">
      <Head title={judul} />

      {/* `lonceng` hanya dinyalakan di sini: halaman ini Inertia, jadi
          navigasi dari daftar notifikasi punya konteks yang dibutuhkannya.
          Backend sudah membuat notifikasi untuk warga sejak Fase 3, tapi
          sampai 17 Agu 2026 tidak ada satu pun tempat warga bisa melihatnya. */}
      <Navbar user={auth.user} onKeluar={() => router.post('/logout')} lonceng />

      {hero}

      <main className={`container mx-auto ${lebar} px-4 py-8 md:px-8`}>
        {kembali && (
          <a href={kembali.href}
             className="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-brand">
            <ArrowLeft className="h-4 w-4" />
            {kembali.label ?? 'Kembali'}
          </a>
        )}
        {children}
      </main>

      <FooterPublik />
    </div>
  );
}
