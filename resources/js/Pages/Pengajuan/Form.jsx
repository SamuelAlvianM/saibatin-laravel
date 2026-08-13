import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import LayoutPengguna from '@/Components/LayoutPengguna';
import FormLayanan from '@/Components/FormLayanan';

export default function Form({ layanan, prefill, jam }) {
  return (
    <LayoutPengguna judul={layanan.title}>
      <Link href="/user/pengajuan/baru"
            className="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-brand">
        <ArrowLeft className="h-4 w-4" />Pilih layanan lain
      </Link>

      {/* `mandiri` — pengaju mengisi untuk dirinya sendiri. Renderer yang sama
          dipakai petugas dengan mandiri=false di Fase 5. */}
      <FormLayanan layanan={layanan} prefill={prefill} jam={jam} mandiri />
    </LayoutPengguna>
  );
}
