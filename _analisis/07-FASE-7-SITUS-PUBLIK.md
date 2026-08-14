# Fase 7 — Situs publik

> **14 Agustus 2026.** Status: **SELESAI.** Seluruh halaman publik sudah ada dan
> diuji di browser; susunan & isinya **mengikuti SIDAKO** (keputusan user, §8).
> Temuan yang butuh keputusan user di §5, sisa pekerjaan di §6.

## 1. Bentuk yang dipilih: Blade + React island

Port ini **tanpa Inertia SSR** (butuh daemon Node; cPanel user tidak punya).
Konsekuensinya halaman Inertia sampai ke mesin pencari sebagai `<div id="app">`
kosong — dan halaman publik justru yang paling perlu terbaca Google.

Karena itu situs publik memakai jalur berbeda dari dashboard:

| | Dashboard & formulir | Situs publik |
|---|---|---|
| Render | Inertia + React penuh | **Blade di server** |
| Titik masuk | `resources/js/app.jsx` | `resources/js/publik.jsx` |
| React | seluruh halaman | hanya **island** per elemen |

Island dipasang lewat atribut, dan dimuat MALAS — halaman berita tidak ikut
mengunduh kode bagan struktur:

```blade
<div data-island="Carousel" data-props='@json(["slides" => $slides])'></div>
```

`publik.jsx` memindai `[data-island]`, `import()` berkas senama di
`resources/js/Publik/`, lalu me-*mount*-nya. Island yang tidak ditemukan hanya
menulis galat di konsol — sisa halaman tetap HTML yang berguna.

## 2. 🔴 Beda yang DISENGAJA dari portal Next.js

Di portal Next.js, teks hero, isi tab profil, dan daftar berita beranda
semuanya diambil klien lewat `fetch` **setelah** halaman tampil. Di sini
ketiganya dibaca `PublikController` dan ikut dalam HTML.

Alasannya bukan selera:

1. **Justru isi itulah yang perlu terindeks.** Di portal lama, judul & tautan
   berita tidak pernah ada di HTML yang dilihat crawler.
2. **Tidak ada teks yang berkedip masuk.** Hero yang terisi belakangan menggeser
   tata letak tepat saat halaman mulai dibaca.
3. **Hemat satu permintaan.** Island menerima hasil yang sama lewat props, jadi
   tidak ada `fetch` kedua untuk data yang sudah ada.

Tampilannya tetap sama persis — yang berpindah hanya tempat datanya dirakit.

## 3. Yang sudah jadi

### 3.1 Kerangka (sesi sebelumnya)
`publik/layout.blade.php` (SEO/OG/ikon/font), `partials/footer.blade.php`,
island `Navbar` + `HitungPengunjung`, `lib/navigasi.js`, `lib/konten-statis.js`,
aset (`og-saibatin.png`, 8 logo relasi).

### 3.2 Beranda (sesi ini) — `publik/beranda.blade.php`
Susunan seksinya mengikuti `app/page.tsx`: Hero → Statistik → Alur Layanan →
Berita Terbaru → Profil Instansi → Relasi Terkait.

| Seksi | Cara render | Catatan |
|---|---|---|
| Hero | **Blade** | teks dari `beranda.hero`; pencarian layanan `<form method=get>` biasa |
| Carousel | island `Carousel.jsx` | slide dari `beranda.carousel`, transisi CSS |
| Statistik | island `Statistik.jsx` | 6 kartu demografi + ringkasan pelayanan + peta kantor |
| Alur Layanan | **Blade** | 3 langkah, statis |
| Berita Terbaru | **Blade** | 3 berita terbit dari DB |
| Profil Instansi | island `ProfilTabs.jsx` | 5 tab; bagan struktur di-*lazy load* |
| Relasi Terkait | **Blade** | 8 tautan + logo |

"Menu Layanan Populer" **tidak** ikut — di portal Next.js seksi itu sudah
dikomentari, jadi menghidupkannya di sini justru membuat kedua situs berbeda.

### 3.3 Statistik beranda — `Publik/Statistik.jsx`

Port `components/landingpage/stats.tsx` (529 baris). Tiga bagian: 6 kartu
demografi (kiri), ringkasan pelayanan (kanan), peta kantor (bawah).

- Angka dari `/api/stats`; susunan kartunya dari blok `beranda.statistik`, dan
  bila blok itu belum pernah diatur, `StatistikController` kini jatuh ke
  `config('konten.kartu_beranda')` — sebelumnya balas larik kosong, dan beranda
  tanpa satu kartu pun terbaca sebagai rusak padahal cuma belum disentuh.
- Endpoint sekarang ikut mengirim **`warna`** (nama preset), bukan kelas
  Tailwind: kelas harus literal agar ter-scan, jadi pemetaannya di
  `lib/statistik-kartu.js`.
- Klik kartu → dialog rincian per kecamatan, klik kecamatan → rincian per
  desa. Keduanya `/api/demografi` (dibedakan `?parent=`), dimuat malas.
- **Peta Leaflet** (`PetaKantor.jsx`) dimuat malas — 154 KB, dan hanya kartu
  terbawah beranda yang memakainya. Penanda berdenyut dibuat `L.divIcon` + CSS,
  tanpa berkas gambar.
- ⛔ **Mode edit tidak ikut** (klik kartu → editor Excel). Itu bagian "Konten
  Halaman" yang memang dibangun setelah situs publiknya ada.

### 3.4 Berita — `/media/berita` + `/media/berita/{slug}`

Port `app/media/berita/page.tsx` (279) & `[slug]/page.tsx` (129). **Keduanya
dirender server**, tanpa island sama sekali.

- **Paginasi bernomor lewat URL** (`?page=2`), bukan state React. Di portal
  Next.js halaman 2 tidak punya alamat sendiri: tidak bisa di-bookmark, tidak
  bisa dibagikan, tidak pernah diindeks. Di sini tiap halaman punya URL-nya.
  Partial `publik/partials/paginasi.blade.php` dipakai ulang halaman publik lain.
- Artikel unggulan hanya di halaman pertama (sama seperti aslinya).
- Detail: `<title>`/`description` dari artikel, **og:image per artikel**
  (`@yield('og_gambar')` di layout — bukan tag kedua, karena pengurai kartu
  berbagi mengambil og:image yang pertama), `article:published_time`, dan
  **JSON-LD `NewsArticle`**. Ketiganya tambahan yang tidak ada di portal
  Next.js — di sana artikelnya tidak pernah sampai ke crawler sama sekali.
- Slug tak dikenal → **404** sungguhan, bukan halaman kosong.
- `<x-kepala-publik>` (pita gradien + lengkung) dibuat sebagai komponen supaya
  halaman publik berikutnya tidak menyalin markup yang sama.

### 3.5 Galeri — `/galeri`

Port `app/galeri/page.tsx` (150). Kisi foto + penyaring kategori + penampil
layar penuh.

- Kisi & penyaringnya **dirender server**; penyaringnya lewat URL
  (`?kategori=PELAYANAN`), bukan state klien.
- Penampil layar penuh ditulis sebagai **skrip kecil biasa, bukan island** — ia
  cuma menempel pada gambar yang sudah ada di HTML. Satu pendengar di kisi-nya
  (bukan 24 pendengar per kartu), tutup lewat tombol/latar/**Esc**, dan gulir
  halaman dikunci selama terbuka.
- 🔴 **Memperbaiki galeri yang terpotong di portal live** — lihat §5.3.

### 3.6 Produk & PPID — 22 halaman informasi + 2 halaman indeks

Port `app/produk/[...slug]`, `app/ppid/[...slug]`,
`components/shared/info-page.tsx` (337), dan `components/ppid/informasi-index.tsx`.

- **SATU view (`publik/info.blade.php`) melayani 22 alamat.** Isinya dari
  `config/info-halaman.php` (4 produk + 18 PPID), ditimpa blok CMS
  `info.<grup>.<slug>` per-kunci bila petugas menyuntingnya. Menambah halaman
  informasi baru = menambah satu entri config, tanpa menyentuh view atau rute.
- **Berkasnya menempel sendiri.** Kategori dokumen dipetakan ke ALAMAT halaman
  lewat `config/dokumen.php` (`halaman[].href`), bukan ditebak dari slug —
  karena satu halaman bisa menampilkan beberapa kategori dan satu kategori bisa
  muncul di beberapa halaman (SOP ada di Produk maupun PPID). Petugas cukup
  mengunggah PDF dengan kategori yang benar.
- **Dua halaman indeks PPID** (`informasi-setiap-saat` 9 kartu,
  `informasi-berkala` 8 kartu) dari `config/ppid.php`. Tiap kartu menampilkan
  **jumlah dokumen** yang sudah terunggah di halaman tujuannya — itulah yang
  membedakan kategori terisi dari yang kosong tanpa warga membuka satu per satu.
  Keduanya ditautkan dari navbar; sebelum ini tautannya **404**.
- Rutenya aksi controller, bukan closure — closure tidak bisa `route:cache`,
  dan cache rute itu yang dipakai di cPanel.
- ⛔ Panel unggah dokumen dari halaman publik (mode edit) tidak ikut — bagian
  "Konten Halaman".
- 🟡 `/produk/produk-disdukcapil` memakai view informasi generik; portal
  Next.js punya tampilan khusus (`produk-disdukcapil-view.tsx`, 153 baris).
  Isinya sama, tata letaknya belum.

### 3.7 Ikon Blade dibangkitkan dari lucide

`config/ikon.php` (29 ikon) **dibangkitkan dari paket `lucide-react` yang
terpasang** — bentuknya identik dengan ikon React di dashboard, tanpa memuat
React di halaman publik. Sebelumnya path SVG ditulis tangan; itu tidak
terskala begitu kartu PPID butuh 16 ikon sekaligus.

⚠️ Sebagian nama lucide hanya **alias** (`smile.mjs` → `face-slightly-smiling.mjs`),
jadi pembangkitnya harus mengikuti re-export, bukan langsung membaca berkasnya.

Nama ikon yang tidak dikenal digambar sebagai lingkaran putus-putus, bukan SVG
kosong — kotak yang hilang diam-diam jauh lebih sulit disadari.

### 3.8 Pendukung baru
- `config/konten.php` → bagian **`bawaan`**: isi default 7 blok CMS. Selama blok
  belum pernah disunting, isi inilah yang tampil — situs tidak perlu di-seed dan
  tidak pernah tampil kosong.
- `App\Support\Konten` — pembaca blok CMS sisi server (satu kueri, DB menimpa
  bawaan per-kunci).
- `resources/views/components/ikon.blade.php` — SVG lucide untuk halaman Blade,
  supaya halaman publik tidak memuat React hanya demi enam ikon.
- Kelas CSS carousel + `.masuk-naik` disalin/diadaptasi dari `globals.css`.
- `react-organizational-chart@2.2.1` (bagan struktur).

## 4. 🔴 Empat jebakan yang memakan waktu — jangan diulang

### 4.1 `config()` memotong kunci yang mengandung TITIK

```php
config('konten.bawaan.beranda.hero')   // ❌ selalu null
```

Laravel membaca titik sebagai penelusuran bertingkat, jadi ia mencari
`bawaan → beranda → hero`, padahal kuncinya string harfiah `"beranda.hero"`.
Akibatnya bawaan tidak pernah terpakai dan beranda **500** begitu ada baris DB
yang tidak memuat seluruh kunci. Kunci bertitik itu sudah ada di produksi dan
tidak boleh diganti — jadi ambil seluruh larik lalu indeks manual:

```php
$bawaan = config('konten.bawaan', []);
$bawaan['beranda.hero'] ?? [];         // ✅
```

### 4.2 Ping kunjungan kena 419 — `/api/*` ada di grup `web`

Skrip pencacah di layout mem-POST `/api/kunjungan` tanpa token CSRF. Karena
`routes/api.php` portal ini dimuat **di dalam middleware `web`** (autentikasinya
sesi cookie), setiap ping dijawab **419** dan hitungan kunjungan tidak pernah
bertambah — diam-diam, sebab galatnya hanya muncul di konsol. Wajib mengirim
`X-CSRF-TOKEN` dari `<meta name="csrf-token">`.

### 4.3 🔴 `requestAnimationFrame` TIDAK jalan di tab tersembunyi

Angka kartu statistik menghitung naik saat kartunya masuk layar
(IntersectionObserver + rAF). Versi pertama macet di **0 selamanya** untuk
pengunjung yang membuka portal di **tab latar** — ctrl-klik, "buka di tab baru",
atau pemulihan sesi peramban: pengamat sempat memicu animasi dan menandainya
"sudah berjalan", lalu rAF-nya tidak pernah dieksekusi karena halaman tidak
dirender. Angkanya tidak pernah muncul walau tabnya kemudian dibuka.

Perbaikannya: bila `document.hidden` (atau pengguna mematikan animasi di
sistemnya), nilai **langsung dipasang** tanpa dianimasikan.

Ini juga penjelasan mengapa pengujian lewat Browser pane menampilkan 0 —
tab yang tidak ditampilkan memang tidak menjalankan rAF (jebakan lama
HANDOFF §6.5 soal screenshot berakar sama). Verifikasi angkanya harus setelah
`scrollIntoView`, dan sekarang berhasil karena jalur "langsung" itu.

### 4.4 🔴 Larik bersarang di dalam Blade — dua cara gagal, satu diam-diam

Menyusun JSON-LD di view gagal dua kali:

1. `@json([...])` bertingkat → **compile error** `Unclosed '[' does not match ')'`.
   Blade memotong argumen direktif pada `)` PERTAMA yang ditemuinya, jadi larik
   bersarang tidak akan pernah utuh. Ini berisik, jadi cepat ketahuan.
2. Diganti blok `@php … @endphp` + `<script>` **di dalam `@section('kepala')`**
   → halaman tampil normal, tidak ada galat, **tapi seluruh isi seksi setelah
   blok itu hilang dari HTML.** `<meta>` sebelum blok tetap ada, `<script>`
   sesudahnya lenyap tanpa jejak. Ini yang berbahaya: tanpa memeriksa HTML
   mentahnya, halaman terlihat baik-baik saja.

Yang dipakai sekarang: lariknya dirakit di **controller**, view hanya
`{!! $ldJson !!}`. Aturan umumnya — **jangan menaruh logika larik di dalam
`@section`**; kirim sudah jadi dari controller.

## 5. Temuan yang perlu keputusan user

1. **"5 Kecamatan · 32 Desa/Kelurahan" di kartu peta itu ANGKA MATI** — disalin
   apa adanya dari `stats.tsx`. Data demografi di DB yang sama menunjukkan
   **11 kecamatan · 118 desa**. Jadi angka itu salah di portal Next.js juga,
   bukan akibat port. Tidak saya ubah sendiri karena "100% sama UI" dikunci —
   tapi ini menyesatkan warga dan layak dibetulkan di keduanya.
2. **147,7 MB gambar publik warisan** (`berita` 21 MB · `gallery` 19 MB ·
   `galeri` 64 KB · `produk` 108 MB) ada di `public/uploads/` portal Next.js.
   Path-nya (`/uploads/berita/…`) tersimpan di DB, jadi harus tetap bisa dibuka
   setelah cutover. Karena ini memang konten publik, tempatnya di `public/`
   port ini juga — disajikan web server, bukan PHP. **Yang sudah dilakukan:**
   `berita` disalin ke lokal agar beranda bisa diuji utuh, dan
   `/public/uploads` ditambahkan ke `.gitignore` supaya 148 MB tidak pernah
   ikut ter-commit. Penyalinan sisanya = langkah cutover, bukan pekerjaan kode.

3. 🔴 **Galeri di portal yang SEKARANG LIVE hanya menampilkan 20 dari 94 foto.**
   `app/galeri/page.tsx` memanggil `/api/galeri` **tanpa parameter**, sedangkan
   endpoint itu mem-paginasi 20 baris — dan halamannya tidak punya paginasi
   maupun tombol "muat lagi". Jadi **74 foto tidak pernah bisa dilihat siapa
   pun**, tanpa tanda apa-apa di layar. Di port ini semuanya terjangkau lewat
   paginasi ber-URL (24/halaman, 4 halaman). Bug-nya ada di portal Next.js;
   perbaikan di sana keputusan user.

## 6. Yang belum — antrean Fase 7

| | Bagian | Catatan |
|---|---|---|
| 🟡 | `/produk/produk-disdukcapil` | tampilan khususnya (153 baris) belum; sekarang memakai view informasi generik. **Satu-satunya sisa Fase 7.** |
| ⛔ | **Konten Halaman** (dashboard) | merender halaman publik di iframe `?editmode=1` — prasyaratnya (situs publik) kini SUDAH ADA, jadi ini yang berikutnya |

Sisanya sudah beres: GIS & demografi, pengaduan/WBS/kritik-saran/SKM,
Hubungi Kami, Pusat Bantuan, sisa PPID, halaman ketentuan, peta situs, dan
widget aksesibilitas — lihat §8.

## 7. Hasil uji (browser, data asli)

| Uji | Hasil |
|---|---|
| `GET /` | **200**, judul & deskripsi terisi dari CMS |
| Hero | teks dari `beranda.hero` (DB), pencarian menuju `/user/pengajuan/baru?q=…` |
| Carousel | island hidup, font Cormorant Garamond termuat, slide bawaan tampil |
| Berita | **3 berita asli** dari `m_news_posts` ikut di HTML (bukan hasil fetch) |
| ProfilTabs | 5 tab berpindah; Struktur memuat bagan **dari DB** (bukan bawaan) |
| `POST /api/kunjungan` | **200** (sebelum perbaikan: 419) |
| Statistik | **177.430** penduduk · 51.813 KK · 91.926 L (52%) · 85.504 P (48%) · 121.739 wajib KTP · **11.902** permohonan · 4 layanan teratas bernama benar |
| Rincian demografi | 11 kecamatan, total **cocok** dengan angka kartu; drill-down PESISIR TENGAH → 8 desa berjumlah 21.389 (= baris kecamatannya) |
| Peta kantor | Leaflet termuat (chunk terpisah 154 KB), penanda berdenyut tampil |
| Gambar berita | `/uploads/berita/*.jpg` **200** setelah 103 berkas warisan disalin (§5.2) |
| `/media/berita` | **66 berita terbit · 8 halaman**; halaman 1 = 1 unggulan + 8 kartu, halaman 8 = 3 kartu (cocok dengan 66) |
| `/media/berita/{slug}` | 200; judul, tanggal Indonesia, gambar, isi 4.213 karakter; **JSON-LD `NewsArticle` sah** & og:image per artikel |
| Slug tak dikenal | **404** |
| `/galeri` | **94 foto · 4 halaman** (24/halaman, terakhir 22); tidak ada gambar gagal muat |
| Penampil galeri | buka dari kartu, judul & tanggal benar, tutup lewat Esc, gulir halaman terkunci lalu lepas |
| Produk & PPID | 15 alamat diuji **200**, slug tak dikenal **404**; `/produk/hukum` menampilkan dokumen HUKUM asli dari `t_produk`, `/ppid/lhkpn` menampilkan 2 paragraf + 4 butir + tautan e-LHKPN + 1 dokumen |
| Indeks PPID | 9 & 8 kartu; LHKPN berlencana **"1 dokumen"**, sisanya "belum ada"; **0 ikon jatuh ke penanda cadangan** |
| Lebar 1280 & 375 | tidak ada gulir horizontal |

Screenshot tidak diambil — tab peramban yang tidak ditampilkan berhenti
meng-*compositing* (jebakan lama di HANDOFF §6.5); verifikasi lewat `read_page`
dan `javascript_tool`.

---

## 8. Lanjutan 14 Agu — susunan & isi mengikuti SIDAKO

🔴 **Keputusan user:** portal SAIBATIN Next.js yang jadi sumber port **tertinggal**
dari SIDAKO, jadi isi & fitur situs publik mengikuti **`sidako-platform` dulu**,
bukan SAIBATIN. Ini **mengubah** target "100% sama dengan portal Next.js"
(`_analisis/06` §4) khusus untuk **situs publik** — dashboard tetap mengacu ke
SAIBATIN. Yang disalin hanya **susunan & fitur**; branding, geo, nama daerah,
dan zona waktu tetap Pesisir Barat/WIB (aturan journal induk §2 no. 2).

### 8.1 Navbar disusun ulang

| Sebelum (SAIBATIN) | Sesudah (ala SIDAKO) |
|---|---|
| Produk | **Informasi Produk** (+ SP, Alur Pelayanan, Inovasi) |
| Media Informasi | tetap |
| PPID — 3 halaman datar | **PPID — 3 grup ber-sub-tab** (Tentang 6 tab · Informasi Publik 2 tab · Layanan & Formulir 6 tab) |
| Pengaduan (dropdown 2) | **WBS** (tautan langsung) |
| — | **Pusat Bantuan** (FAQ · Pengaduan & Konsultasi · Penipuan IKD) |
| — | **Survei Kepuasan** |
| Hubungi Kami | dipindah ke **footer** |

⚠️ `Publik/Navbar.jsx` punya `IKON_MENU` yang dikunci **label** menu. Mengganti
label di `lib/navigasi.js` tanpa mengganti kunci di sana membuat ikonnya hilang
diam-diam — tidak ada galat, menunya cuma jadi teks polos di antara yang berikon.

### 8.2 Halaman baru

Semuanya lewat renderer yang sudah ada, ditambah tiga kemampuan opsional pada
`publik/info.blade.php` (`subnav`, `faq`, `formulir`):

- **Pusat Bantuan** — FAQ (buka-tutup `<details>`, isi dari blok CMS
  `pusat-bantuan.faq`), Pengaduan & Konsultasi, Penipuan IKD.
- **WBS** — Tentang WBS & Form Pengaduan, keduanya berformulir.
- **Hubungi Kami** — alamat, kontak, + tiga kartu kanal aspirasi.
- **PPID** — 5 tab "Tentang", 13 kategori Setiap Saat & 1 Berkala baru, 4 halaman
  grup "Layanan & Formulir", plus **Formulir & Register PPID** yang berbentuk
  **dua-seksi** (`config/ppid-layanan.php` + `publik/ppid-layanan.blade.php`).
- **Media** — `/media/gis` (peta sebaran) & `/media/demografi` (tabel ber-tab).
- **Ketentuan** — `/kebijakan-privasi` & `/syarat`, naskahnya dari Laravel 9 asli
  lewat registry SIDAKO (`config/ketentuan.php`).
- **`/sitemap`** — peta situs untuk manusia, **dirakit dari config yang sama**
  dengan navbar & halaman informasi. Versi tulis-tangan pasti basi.
- Pengalihan 301 untuk alamat lama: `/pengaduan`, `/privasi`, `/media/peta`,
  `/media/laporan-demografi`, `/media/survey-kepuasan`, `/riwayat`,
  `/permohonan-online` (yang terakhir meneruskan `?q=`).

### 8.3 Formulir publik

`Publik/FormAspirasi.jsx` — **satu komponen, tiga varian** (`wbs`, `pengaduan`,
`kritik-saran`). Memecahnya jadi tiga berkas berarti tiga tempat yang pelan-pelan
menyimpang padahal endpoint & tabelnya sama. Yang berbeda hanya label, subjek,
dan dua field khusus WBS.

🔴 **Bukti foto WBS** lewat `POST /api/pengaduan/upload` yang **publik tanpa
sesi** — pelapor WBS boleh anonim, dan memaksanya login meniadakan inti kanalnya.
Berkasnya masuk `storage/app/private/permohonan/pengaduan/` dengan nama berawalan
**`wbs_`**, bukan id pengunggah: `BerkasController` membaca kepemilikan dari
prefix itu, `wbs` bukan bilangan, jadi pemeriksaannya gagal untuk semua warga dan
hanya petugas yang bisa membukanya. **Terverifikasi: 404 tanpa sesi, 200 sebagai petugas.**

`Publik/FormSkm.jsx` — kuesioner 9 unsur skala 1–4.
🔴 **Tidak** menyematkan iframe skm.go.id seperti SIDAKO: alamat iframe di sana
menunjuk **instansi Tana Tidung**, jadi menyalinnya berarti jawaban warga Pesisir
Barat masuk ke rekap dinas lain. Endpoint & rekap IKM-nya sudah ada sejak Fase 3.

### 8.4 Widget aksesibilitas (14 kontrol)

Port `accessibility-widget.tsx` + `lib/a11y.ts` + blok CSS `a11y-*`.

🔴 **Filter dipasang di `<html>`, bukan `<body>`.** `filter` pada elemen biasa
membuat containing block baru untuk keturunan `position: fixed` — navbar lengket,
tombol widget, dan dialog akan ikut menggulung bersama halaman. Elemen root
dikecualikan dari aturan itu.

🔴 **Preferensi diterapkan skrip inline SEBELUM body dirender.** Menunggu React
membuat halaman berkedip dari tampilan normal ke pilihan pengguna — tepat pada
orang yang paling terganggu oleh perubahan mendadak. Logika skrip itu **kembar**
dengan `terapkanPrefs()`; kalau satu diubah, ubah juga yang lain.

⚠️ Daftar suara peramban datang **asinkron**. Memilih suara sekali saja berarti
suara Bahasa Indonesia tidak pernah terpakai — wajib mendengarkan `voiceschanged`.

## 9. Temuan baru dari sesi ini

1. 🔴 **`/profil` tidak pernah dibuat**, padahal `LoginController:110` mengarahkan
   ke `/profil?lengkapi=foto` pada login pertama warga → **login yang berhasil
   berakhir 404**, dan `/api/profil*` tidak punya pemanggil sama sekali.
   Sudah dibuat (`Pages/Profil.jsx`).
2. 🔴 **Lonceng notifikasi tidak pernah dibuat.** Backend rajin membuat notifikasi
   (permohonan, pengaduan, kritik, akun) tapi tak satu pun bisa dilihat siapa pun.
   Sudah dibuat (`Components/LoncengNotifikasi.jsx`, dipasang di dua layout).
3. **Kontrak `POST /api/profil/change-password` menyimpang** dari portal Next.js:
   memakai `lama`/`baru`/`baru2` alih-alih `passwordLama`/`passwordBaru`/
   `konfirmasi`, dan kehilangan penjaga "sandi baru tidak boleh sama dengan yang
   lama". Diluruskan; nama lama tetap diterima sebagai cadangan.
4. **`?q=` dari beranda hilang diam-diam** — `PengajuanController::pilih()` tidak
   membacanya, jadi warga yang mengetik "akta kelahiran" di hero mendarat di
   daftar penuh dengan kotak pencarian kosong. Diperbaiki.
5. 🔴 **Peta sebaran kehilangan 2 kecamatan tanpa tanda apa pun.** Rekap DKB
   menulis `PULAUPISANG` (tanpa spasi) dan `BENGKUNAT BELIMBING` (nama LAMA
   kecamatan Ngaras), sementara daftar koordinat memakai ejaan lain. Petanya
   tetap tampil rapi dengan 9 lingkaran — hanya **kurang 31.604 jiwa**. Setelah
   ditambal: **11 kecamatan · 177.430 jiwa**, cocok dengan angka beranda.
   ⚠️ **Bug yang sama masih ada di portal Next.js** (`lib/pesisir-barat-geo.ts`).

## 10. Hasil uji lanjutan (browser, data asli)

| Uji | Hasil |
|---|---|
| 40 tautan internal (navbar + footer + isi halaman) | **semuanya hidup**, 0 rusak |
| 76 tautan di `/sitemap` | **semuanya hidup**, 0 rusak |
| Sub-tab PPID | tab aktif tepat di 3 grup; label pendek dipakai di layar sempit |
| FAQ | 6 pertanyaan, yang pertama terbuka, **jawabannya ada di HTML** (bukan hasil fetch) |
| `POST /api/pengaduan` · `/api/kritik-saran` · `/api/skm` | ketiganya **200**, datanya masuk lalu dibersihkan |
| SKM setengah terisi | ditolak **422** dengan daftar unsur yang belum dinilai |
| `POST /api/pengaduan/upload` | **200**; berkasnya **404 tanpa sesi**, **200 sebagai petugas** |
| `/media/gis` | 11 lingkaran · **177.430 jiwa** (cocok dengan beranda) |
| `/media/demografi?kategori=agama` | 8 tab, 11 baris, total **177.430** |
| `/survei-kepuasan` | 9 unsur × 4 skala, penghitung "0/9 terisi" |
| Widget aksesibilitas | 14 kontrol; efek masuk ke `<html>`; **bertahan lintas halaman tanpa kedip**; reset membersihkan kelas + style + localStorage |
| Lebar 375 px | **tidak ada gulir horizontal** di halaman mana pun; tabel demografi menggulir di wadahnya sendiri |
| `/profil?lengkapi=foto` | 200, tiga kartu tampil, lonceng notifikasi hadir |
| `/user/pengajuan/baru?q=akta kelahiran` | kotak pencarian **terisi**, hasil menyempit ke 1 layanan |
