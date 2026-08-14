import LayoutPengguna from '@/Components/LayoutPengguna';
import FormLayanan from '@/Components/FormLayanan';

export default function Form({ layanan, prefill, jam }) {
  return (
    // Tautan kembalinya diurus layout (bentuk `BackButton` portal asli), jadi
    // halaman ini tidak lagi menggambar tautannya sendiri.
    <LayoutPengguna judul={layanan.title}
                    kembali={{ href: '/user/pengajuan/baru', label: 'Pilih layanan lain' }}>
      {/* `mandiri` — pengaju mengisi untuk dirinya sendiri. Renderer yang sama
          dipakai petugas dengan mandiri=false di Fase 5. */}
      <FormLayanan layanan={layanan} prefill={prefill} jam={jam} mandiri />
    </LayoutPengguna>
  );
}
