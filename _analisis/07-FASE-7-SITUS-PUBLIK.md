# Fase 7 — Situs publik

> **14 Agustus 2026.** Status: **kerangka, beranda, berita, dan galeri jadi &
> diuji di browser.** Halaman publik lainnya belum dibangun — antreannya di §6,
> temuan yang butuh keputusan user di §5.

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

### 3.6 Pendukung baru
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
| | `/produk/{slug}` (4) · `/ppid/{slug}` (3) | halaman indeks + konten |
| | `/media/gis` · `/media/demografi` | peta sebaran & laporan demografi |
| | `/pengaduan` · `/hubungi-kami` (+ kritik-saran, SKM) | formulir publik — endpointnya sudah ada sejak Fase 3 |
| | Widget aksesibilitas | spek 14 kontrol di `PROMPT-DISABILITAS.md` |
| ⛔ | **Konten Halaman** (dashboard) | merender halaman publik di iframe `?editmode=1` — baru bisa setelah halaman publiknya ada |

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
| Lebar 1280 & 375 | tidak ada gulir horizontal |

Screenshot tidak diambil — tab peramban yang tidak ditampilkan berhenti
meng-*compositing* (jebakan lama di HANDOFF §6.5); verifikasi lewat `read_page`
dan `javascript_tool`.
