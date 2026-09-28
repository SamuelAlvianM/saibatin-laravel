import LayoutDashboard from '@/Components/LayoutDashboard';
import { KartuFoto, KartuBiodata, KartuSandi } from '@/Pages/Profil';

/**
 * Pengaturan akun DI DALAM kerangka dashboard — sidebar tetap tampil.
 *
 * 🔴 Berkas TERSENDIRI, bukan `Dashboard/Akun.jsx`: nama itu milik halaman
 * MANAJEMEN AKUN (daftar & verifikasi akun warga, route `/dashboard/users`).
 * Sempat tertimpa halaman ini (28 Sep 2026) sehingga menu Manajemen Akun
 * menampilkan formulir profil petugas sendiri.
 *
 * 🔴 SEBELUMNYA tidak ada. Satu-satunya halaman profil adalah `/profil` yang
 * memakai kerangka publik (`LayoutPengguna` + navbar situs), sehingga petugas
 * yang membuka pengaturan akunnya "keluar" dari dashboard — sidebar hilang dan
 * cara pulang hanya tombol Kembali peramban.
 *
 * 🔴 Formulirnya TIDAK disalin. Ketiga kartu diimpor dari `Pages/Profil.jsx` —
 * satu komponen dipakai dua halaman, jadi perbaikan formulir profil berlaku ke
 * keduanya sekaligus. Menyalinnya ke sini berarti dua tempat yang harus
 * diingat, dan yang satu pasti tertinggal.
 *
 * Warga (tanpa dashboard) tetap memakai `/profil` versi publik — halaman itu
 * tidak diubah sama sekali. Staf yang membuka `/profil` diarahkan ke sini
 * (lihat `ProfilPageController`).
 */
export default function PengaturanAkun({ awal }) {
  return (
    <LayoutDashboard judul="Pengaturan Akun" lebar="max-w-2xl">
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-slate-900">Pengaturan Akun</h1>
        <p className="text-sm text-slate-500">Perbarui biodata, foto, dan sandi akun Anda.</p>
      </div>

      <KartuFoto foto={awal.foto} diminta={false} />
      <KartuBiodata awal={awal} />
      <KartuSandi />
    </LayoutDashboard>
  );
}
