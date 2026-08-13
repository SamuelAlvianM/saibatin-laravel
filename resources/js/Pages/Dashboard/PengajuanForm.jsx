import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import LayoutDashboard from '@/Components/LayoutDashboard';
import FormLayanan from '@/Components/FormLayanan';

/**
 * Formulir satu layanan versi PETUGAS — renderer yang sama persis dengan yang
 * dipakai warga (`Pengajuan/Form.jsx`), hanya beda tiga hal:
 *
 *   mandiri=false  → pengantarnya "atas nama warga", bukan "untuk diri sendiri"
 *   tanpa prefill  → data pemohon adalah data WARGA di depan loket, bukan data
 *                    akun petugas yang sedang login
 *   kembaliKe      → daftar permohonan dashboard, bukan riwayat pribadi
 */
export default function PengajuanForm({ layanan, jam }) {
  return (
    <LayoutDashboard judul={layanan.title}>
      <Link href="/dashboard/pengajuan-baru"
            className="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-brand">
        <ArrowLeft className="h-4 w-4" />Pilih layanan lain
      </Link>

      <FormLayanan layanan={layanan} jam={jam} mandiri={false}
                   kembaliKe="/dashboard/permohonan" paramBaru="sorot" />
    </LayoutDashboard>
  );
}
