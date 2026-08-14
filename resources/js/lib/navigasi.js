/**
 * Struktur menu navbar publik.
 *
 * Satu sumber untuk Navbar publik DAN editor "Konten Halaman" di dashboard
 * (kolom kiri editor itu daftar menu ini). Ditulis sebagai modul JS, bukan
 * `config/navigasi.php`, karena keduanya dipakai dari sisi klien — navbar
 * adalah island, dan editor konten berjalan di Inertia.
 *
 * 🔴 SUSUNANNYA MENGIKUTI SIDAKO (`sidako-platform/lib/navigation.ts`), bukan
 * SAIBATIN Next.js — keputusan user 14 Agu 2026. Alasannya: portal SIDAKO
 * sudah menerima permintaan dinas (Document from S.A.M) dan struktur menunya
 * satu generasi lebih maju, sementara portal SAIBATIN yang jadi sumber port
 * tertinggal. Yang disalin HANYA susunan & fitur; branding, geo, dan nama
 * daerah tetap Pesisir Barat (aturan journal induk §2 no. 2).
 *
 * Menu "Pelayanan Online" sengaja TIDAK ada: pembuatan permohonan sudah pindah
 * ke dashboard (warga & OPD) sebagai halaman penuh, bukan modal.
 */
export const menuNavigasi = [
  {
    // Dulu bernama "Produk"; diganti jadi "Informasi Produk" mengikuti SIDAKO.
    // Path `/produk/*` sengaja TIDAK ikut berubah supaya tautan lama & berkas
    // yang sudah diunggah tetap hidup.
    title: 'Informasi Produk',
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
      // Tiga sub-menu tambahan (permintaan dinas poin 1 di SIDAKO). Ketiganya
      // menerima dokumen PDF (lewat kategori di `config/dokumen.php`) sekaligus
      // gambar/infografis (field `gambar` pada blok `info.produk.*`).
      {
        title: 'Standar Pelayanan (SP)',
        href: '/produk/standar-pelayanan',
        description: 'Standar pelayanan publik: persyaratan, waktu, dan biaya',
      },
      {
        title: 'Alur Pelayanan',
        href: '/produk/alur-pelayanan',
        description: 'Tahapan pelayanan dari pendaftaran sampai dokumen diserahkan',
      },
      {
        title: 'Inovasi',
        href: '/produk/inovasi',
        description: 'Inovasi layanan Disdukcapil Pesisir Barat',
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
    // Disederhanakan dari 3 item jadi 3 menu utama yang masing-masing mendarat
    // di halaman ber-sub-tab (lihat `publik/partials/subnav-ppid.blade.php`):
    // "Tentang PPID" → 6 tab, "Informasi Publik" → 2 tab (Setiap Saat /
    // Berkala), "Layanan & Formulir PPID" → 6 tab.
    title: 'PPID',
    items: [
      {
        title: 'Tentang PPID',
        href: '/ppid/profil-ppid',
        description:
          'Profil, gambaran pembentukan, visi-misi, struktur organisasi, maklumat, serta tugas dan tanggung jawab PPID',
      },
      {
        title: 'Informasi Publik',
        href: '/ppid/informasi-setiap-saat',
        description:
          'Daftar Informasi Publik yang wajib tersedia setiap saat maupun diumumkan secara berkala (UU No. 14 Tahun 2008)',
      },
      {
        title: 'Layanan & Formulir PPID',
        href: '/ppid/formulir-ppid',
        description:
          'Formulir permohonan & keberatan, SK, register, uji konsekuensi, penyelesaian sengketa, dan inovasi layanan PPID',
      },
    ],
  },
  {
    // Menu Pengaduan & WBS disatukan: dua kanal ini isinya sama dan menuju
    // endpoint yang sama (`/api/pengaduan`), jadi cukup satu menu langsung ke
    // halaman WBS. Halaman `/pengaduan` lama di-redirect ke sini agar tautan
    // lama tidak mati.
    title: 'WBS',
    href: '/wbs/tentang-wbs',
  },
  {
    // Permintaan dinas (Document from S.A.M, poin 3): menu baru SETELAH WBS.
    // Pengaduan & Konsultasi memakai formulir yang sama dengan WBS
    // (`/api/pengaduan`) — bedanya kanal umum vs pelaporan pelanggaran.
    title: 'Pusat Bantuan',
    items: [
      {
        title: 'FAQ',
        href: '/pusat-bantuan/faq',
        description: 'Pertanyaan yang sering diajukan seputar layanan adminduk',
      },
      {
        title: 'Pengaduan & Konsultasi',
        href: '/pusat-bantuan/pengaduan-konsultasi',
        description: 'Alur layanan pengaduan dan konsultasi beserta formulir pengajuannya',
      },
      {
        title: 'Penipuan IKD',
        href: '/pusat-bantuan/penipuan-ikd',
        description: 'Waspada modus penipuan yang mengatasnamakan Disdukcapil Pesisir Barat',
      },
    ],
  },
  {
    // Tanpa dropdown — mendarat di halaman internal dulu (bukan langsung
    // melempar ke skm.go.id). Halaman itu menyematkan formulir SKM sendiri.
    //
    // Label dipendekkan dari "Survei Kepuasan Masyarakat" jadi "Survei
    // Kepuasan" supaya deretan menu desktop tetap muat setelah "Pusat Bantuan"
    // ditambahkan. Judul halaman & metadata-nya TETAP lengkap.
    title: 'Survei Kepuasan',
    href: '/survei-kepuasan',
  },
  // "Hubungi Kami" tidak lagi di navbar (mengikuti SIDAKO) — informasi kontak
  // sudah permanen di footer. Halaman `/hubungi-kami` tetap ada dan ditautkan
  // dari sana.
];
