/**
 * Struktur menu navbar publik — port `lib/navigation.ts` portal Next.js.
 *
 * Satu sumber untuk Navbar publik DAN editor "Konten Halaman" di dashboard
 * (kolom kiri editor itu daftar menu ini). Ditulis sebagai modul JS, bukan
 * `config/navigasi.php`, karena keduanya dipakai dari sisi klien — navbar
 * adalah island, dan editor konten berjalan di Inertia.
 *
 * Menu "Pelayanan Online" sengaja TIDAK ada: pembuatan permohonan sudah pindah
 * ke dashboard (warga & OPD) sebagai halaman penuh, bukan modal.
 */
export const menuNavigasi = [
  {
    title: 'Produk',
    items: [
      {
        title: 'Produk Disdukcapil',
        href: '/produk/produk-disdukcapil',
        description: 'Produk dan layanan Disdukcapil',
      },
      {
        title: 'Formulir Persyaratan',
        href: '/produk/formulir-persyaratan',
        description: 'Persyaratan pengurusan dokumen kependudukan',
      },
      {
        title: 'Hukum',
        href: '/produk/hukum',
        description: 'Produk hukum terkait kependudukan',
      },
      {
        title: 'Standar Operasional Prosedur (SOP)',
        href: '/produk/sop',
        description: 'Standar operasional prosedur pelayanan',
      },
    ],
  },
  {
    title: 'Media Informasi',
    items: [
      {
        title: 'Berita',
        href: '/media/berita',
        description: 'Berita dan informasi terkini',
      },
      {
        title: 'Galeri',
        href: '/galeri',
        description: 'Dokumentasi kegiatan Disdukcapil',
      },
      // "Data Demografi" (submenu per kategori) dihapus — sudah tercakup
      // halaman indeks "Laporan Data Demografi" di bawah.
      // "Peta" dihapus dari menu — sudah tercakup GIS Dukcapil di bawah.
      // "Survey Kepuasan Masyarakat" dipindah ke halaman Hubungi Kami.
      {
        title: 'GIS Dukcapil — Peta Sebaran Penduduk',
        href: '/media/gis',
        description: 'Peta sebaran jumlah penduduk per kecamatan',
      },
      {
        // Langsung ke halaman data ber-tab kategori (tanpa halaman indeks kartu).
        title: 'Laporan Data Demografi',
        href: '/media/demografi',
        description: 'Laporan lengkap data demografi',
      },
    ],
  },
  {
    title: 'PPID',
    items: [
      {
        title: 'Profil PPID',
        href: '/ppid/profil-ppid',
        description: 'Profil PPID Disdukcapil',
      },
      // Dua klasifikasi informasi publik berupa halaman indeks kartu — klik
      // langsung masuk halaman, tanpa submenu melayang.
      {
        title: 'Informasi Wajib Tersedia Setiap Saat',
        href: '/ppid/informasi-setiap-saat',
        description:
          'Daftar Informasi Publik yang wajib tersedia setiap saat (UU No. 14 Tahun 2008)',
      },
      {
        title: 'Informasi Wajib Diumumkan Secara Berkala',
        href: '/ppid/informasi-berkala',
        description:
          'Daftar Informasi Publik yang wajib diumumkan secara berkala (UU No. 14 Tahun 2008)',
      },
    ],
  },
  {
    title: 'Pengaduan',
    items: [
      {
        title: 'Pengaduan Masyarakat',
        href: '/pengaduan',
        description:
          'Sampaikan pengaduan layanan maupun laporan dugaan pelanggaran (WBS) — identitas pelapor dijaga kerahasiaannya',
      },
      {
        title: 'Kritik & Saran',
        href: '/hubungi-kami/kritik-saran',
        description: 'Kritik dan saran untuk peningkatan layanan',
      },
    ],
  },
  {
    // Tanpa dropdown — langsung ke halaman info kontak (alamat, jam kerja,
    // peta, tombol pengaduan).
    title: 'Hubungi Kami',
    href: '/hubungi-kami',
  },
];
