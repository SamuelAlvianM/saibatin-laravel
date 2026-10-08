# Graph Report - saibatin-laravel  (2026-10-08)

## Corpus Check
- 332 files · ~210,995 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1725 nodes · 2956 edges · 285 communities (73 shown, 86 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS · INFERRED: 12 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `fee5a8e4`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Http\Request
- Permohonan
- .ringkasan
- Otp
- KategoriDemografi
- devDependencies
- Balasan
- SuntingAkunTest
- Dasbor.jsx
- JenisPermohonan
- User
- StatistikKependudukan
- Illuminate\Support\Facades\Schema
- PeriodeDemografi
- Statistik.jsx
- DemografiWilayah
- LayananController.php
- TestCase
- ProfilTabs.jsx
- Illuminate\Database\Schema\Blueprint
- Pemberitahuan
- EditorMedan.jsx
- ocr-ktp.js
- scripts
- Journal — SAIBATIN-LARAVEL (`saibatin-laravel`)
- CLAUDE.md
- Wilayah
- composer.json
- Beranda.jsx
- Akun.jsx
- navigation-menu.jsx
- bangun-peta.mjs
- dependencies
- a11y.js
- Navbar.jsx
- kategori.js
- .__invoke
- require-dev
- PengaturanLayanan.jsx
- geo.js
- ApiMobileTest
- config
- require
- Illuminate\Database\Migrations\Migration
- API Aplikasi Mobile — SAIBATIN
- Grafik.jsx
- LoncengNotifikasi.jsx
- time-picker.jsx
- LayananNonaktifTest
- periode.js
- info.blade.php
- LayoutDashboard.jsx
- tabs.jsx
- mode-edit.js
- statistik-kartu.js
- Skm.jsx
- Profil.jsx
- salin-aset-ocr.mjs
- Illuminate\Database\Eloquent\Model
- psr-4
- extra
- logging.php
- GrafikTampak.jsx
- EditorDemografi.jsx
- alert.jsx
- date-picker.jsx
- toast.jsx
- api.js
- Permohonan.jsx
- Riwayat.jsx
- ppid-layanan.blade.php
- ExampleTest
- MediaUnggah.jsx
- UnggahGambar.jsx
- ikon.js
- Log.jsx
- Pengaduan.jsx
- publik.jsx
- Carousel.jsx
- PetaSebaran.jsx
- WidgetAksesibilitas.jsx
- HANDOFF — SAIBATIN Laravel
- 4. Yang sudah jadi
- 5. 🔴 Dua puluh lima jebakan yang SUDAH memakan waktu — jangan diulang
- PresisiMilidetik
- Notifikasi
- JamLayanan
- bukti-pengaduan.js
- console.php
- FooterPublik.jsx
- FormLayanan.jsx
- SearchSelect.jsx
- button.jsx
- konten-statis.js
- CekStatus.jsx
- Demografi.jsx
- Galeri.jsx
- Konten.jsx
- Media.jsx
- Produk.jsx
- FormAspirasi.jsx
- HitungPengunjung.jsx
- produk-disdukcapil.blade.php
- clsx
- peta-label.js
- PeriodeDemografiTest
- highcharts
- bootstrap/app.php
- @inertiajs/react
- leaflet
- lucide-react
- radix-ui
- @radix-ui/react-dialog
- @radix-ui/react-popover
- @radix-ui/react-slot
- react
- react-advanced-cropper
- react-day-picker
- Controller
- react-dropzone
- react-leaflet
- grafik-dasar.js
- PermohonanPdfController.php
- tesseract.js
- UserLevel
- @tiptap/extension-link
- @tiptap/extension-underline
- @tiptap/extensions
- @tiptap/pm
- @tiptap/react
- @tiptap/starter-kit
- publik.partials.footer
- navigasi.js
- ppid-indeks.blade.php
- waktu.js
- Tiket
- README.md
- News
- PermohonanDetail.jsx
- Media
- BuktiPengaduanTest
- deteksi-kategori.js
- peta-wilayah.js
- PermohonanPdfController
- 2. Login & sesi
- tabel-demografi.js
- 5. Mengajukan permohonan (warga, OPD, petugas)
- DatabaseSeeder.php
- keywords
- PilihanDropdown
- FilterStatistik
- date-fns
- framer-motion
- highcharts-react-official
- react-dom
- sonner
- tailwind-merge
- @tiptap/extension-image
- pdf.statistik-tabel

## God Nodes (most connected - your core abstractions)
1. `Balasan` - 136 edges
2. `User` - 81 edges
3. `Controller` - 78 edges
4. `CatatanAktivitas` - 41 edges
5. `Permohonan` - 40 edges
6. `PresisiMilidetik` - 38 edges
7. `StatistikExcel` - 34 edges
8. `KategoriDemografi` - 31 edges
9. `DemografiWilayah` - 30 edges
10. `StaticContent` - 28 edges

## Surprising Connections (you probably didn't know these)
- `SuntingAkunTest` --references--> `User`  [EXTRACTED]
  tests/Feature/SuntingAkunTest.php → app/Models/User.php
- `DemografiAdminController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/DemografiAdminController.php → app/Http/Controllers/Controller.php
- `DemografiAdminController` --references--> `CatatanAktivitas`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/DemografiAdminController.php → app/Services/CatatanAktivitas.php
- `DemografiAdminController` --references--> `DemografiExcel`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/DemografiAdminController.php → app/Services/DemografiExcel.php
- `KategoriDemografiController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/KategoriDemografiController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (285 total, 86 thin omitted)

### Community 0 - "Illuminate\Http\Request"
Cohesion: 0.07
Nodes (15): AuthMobileController, ProfilController, LoginController, OtpController, SandiController, ProfilPageController, KlienMobile, StatusAkun (+7 more)

### Community 1 - "Permohonan"
Cohesion: 0.07
Nodes (15): StatistikEksporController, Permohonan, DemografiExcel, Spreadsheet, StreamedResponse, Worksheet, Carbon, Spreadsheet (+7 more)

### Community 2 - ".ringkasan"
Cohesion: 0.18
Nodes (4): DashboardController, Kunjungan, PencacahKunjungan, Illuminate\Support\Facades\Cookie

### Community 3 - "Otp"
Cohesion: 0.16
Nodes (3): Fonnte, Otp, Illuminate\Support\Facades\Mail

### Community 4 - "KategoriDemografi"
Cohesion: 0.05
Nodes (5): KategoriDemografiController, PublikController, StaticContent, KategoriDemografi, Konten

### Community 5 - "devDependencies"
Cohesion: 0.08
Nodes (25): axios, concurrently, laravel-vite-plugin, devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss (+17 more)

### Community 6 - "Balasan"
Cohesion: 0.10
Nodes (5): AspirasiController, KontenController, SistemController, Balasan, Illuminate\Http\JsonResponse

### Community 8 - "Dasbor.jsx"
Cohesion: 0.11
Nodes (8): FilterPeriode(), geser(), labelAcuan(), nomorHalaman(), Paginasi(), PERIODE, STATUS_FINAL, STATUS_PERMOHONAN

### Community 9 - "JenisPermohonan"
Cohesion: 0.13
Nodes (4): PermohonanAdminController, PermohonanController, JenisPermohonan, AlasanTolakPermohonan

### Community 10 - "User"
Cohesion: 0.09
Nodes (5): User, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, Laravel\Sanctum\HasApiTokens

### Community 14 - "Statistik.jsx"
Cohesion: 0.13
Nodes (9): penanda, angka(), RincianDemografi(), angka(), AngkaNaik(), AWAL, KartuPelayanan(), PetaKantor (+1 more)

### Community 15 - "DemografiWilayah"
Cohesion: 0.20
Nodes (3): DemografiAdminController, DemografiController, DemografiWilayah

### Community 16 - "LayananController.php"
Cohesion: 0.16
Nodes (4): BuktiPengaduanController, LayananController, UnggahController, Illuminate\Support\Facades\Storage

### Community 17 - "TestCase"
Cohesion: 0.15
Nodes (7): Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, Illuminate\Foundation\Testing\TestCase, Laravel\Sanctum\Http\Middleware\AuthenticateSession, Laravel\Sanctum\Sanctum, ExampleTest, TestCase

### Community 20 - "Pemberitahuan"
Cohesion: 0.10
Nodes (7): UserAdminController, CekStatusController, RegisterController, FotoProfil, Pemberitahuan, Surel, AlasanTolak

### Community 21 - "EditorMedan.jsx"
Cohesion: 0.15
Nodes (7): BarisItem(), panjang(), PenyuntingKaya, Berita(), KOSONG, PenyuntingKaya, slugify()

### Community 22 - "ocr-ktp.js"
Cohesion: 0.29
Nodes (12): ambilWorker(), bacaKtp(), denganTimeout(), muatGambar(), nikSah(), normalkanDigit(), OcrGagal, persentil() (+4 more)

### Community 23 - "scripts"
Cohesion: 0.13
Nodes (15): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+7 more)

### Community 24 - "Journal — SAIBATIN-LARAVEL (`saibatin-laravel`)"
Cohesion: 0.17
Nodes (12): 1. Identitas project, 2. Keputusan yang sudah dikunci user — jangan dibuka ulang, 3. Kemajuan, 4. Angka acuan — **jangan diukur ulang**, 4b. Kuesioner SKM resmi dinas (18 Agu) — 9 unsur → 16 pertanyaan, 4c. Cutover ke cPanel — paket siap, server belum disentuh (18 Agu), 5. Antrean, 6. Referensi (+4 more)

### Community 26 - "Wilayah"
Cohesion: 0.27
Nodes (4): BuatAkunUji, IsiWilayahAkun, Wilayah, Illuminate\Console\Command

### Community 27 - "composer.json"
Cohesion: 0.18
Nodes (10): autoload-dev, psr-4, description, license, minimum-stability, name, prefer-stable, Tests\\ (+2 more)

### Community 28 - "Beranda.jsx"
Cohesion: 0.22
Nodes (8): AKSI_WARNA, BarisProgres(), Beranda(), BULAN, GrafikHarian, PENGADUAN_BAR, persen(), STATUS_BAR

### Community 32 - "Akun.jsx"
Cohesion: 0.13
Nodes (8): FORM_KOSONG, GRUP, INFO_STATUS, KOLOM_TOLAK, MASUK_NIK, STATUS_AKUN, WAJIB_WILAYAH, WARNA_PERMOHONAN

### Community 34 - "bangun-peta.mjs"
Cohesion: 0.22
Nodes (15): balik(), cincinDari(), jalurJs, jalurJson, KECAMATAN, keGeometri(), keJalur(), luas() (+7 more)

### Community 35 - "dependencies"
Cohesion: 0.22
Nodes (9): class-variance-authority, dependencies, class-variance-authority, @radix-ui/react-navigation-menu, react-organizational-chart, @tiptap/extension-text-align, @radix-ui/react-navigation-menu, react-organizational-chart (+1 more)

### Community 36 - "a11y.js"
Cohesion: 0.22
Nodes (5): FONT_BAWAAN_IDX, KUNCI_A11Y, LANGKAH_FONT, PREFS_BAWAAN, SPASI_MAKS

### Community 41 - "require-dev"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 44 - "geo.js"
Cohesion: 0.32
Nodes (7): ALIAS, geoWilayah(), KECAMATAN_GEO, norm(), PETA_NAMA, PUSAT_PETA, ZOOM_AWAL

### Community 47 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 48 - "require"
Cohesion: 0.25
Nodes (8): require, barryvdh/laravel-dompdf, inertiajs/inertia-laravel, laravel/framework, laravel/sanctum, laravel/tinker, php, phpoffice/phpspreadsheet

### Community 50 - "API Aplikasi Mobile — SAIBATIN"
Cohesion: 0.20
Nodes (10): 1. Dasar, 3. Pendaftaran akun warga (tanpa login), 4. Konten publik (tanpa login), 6. Warga: riwayat, profil, notifikasi, 7. Petugas (Operator & Super Admin), 8. Contoh cepat (curl), 9. Catatan untuk pengembang backend, API Aplikasi Mobile — SAIBATIN (+2 more)

### Community 51 - "Grafik.jsx"
Cohesion: 0.43
Nodes (4): dasar(), GrafikGaris(), GrafikPeringkat(), GrafikTren()

### Community 52 - "LoncengNotifikasi.jsx"
Cohesion: 0.52
Nodes (6): ambilKonteks(), bukaAudio(), bunyikan(), LoncengNotifikasi(), TIPE_IKON, waktuRelatif()

### Community 53 - "time-picker.jsx"
Cohesion: 0.38
Nodes (5): HOURS, isJam(), masker(), MINUTES, TimePicker()

### Community 55 - "periode.js"
Cohesion: 0.24
Nodes (5): gabungPeriode(), kunciPeriode(), labelPeriode(), labelPeriodePanjang(), ROMAWI

### Community 56 - "info.blade.php"
Cohesion: 0.33
Nodes (5): publik.partials.faq, publik.partials.berkas, publik.partials.blok-isi, publik.partials.dokumen-edit, publik.partials.subnav

### Community 57 - "LayoutDashboard.jsx"
Cohesion: 0.36
Nodes (7): aktifkan(), GRUP, GRUP_PEMOHON, grupUntuk(), ItemMenu(), KOLOM_BILAH, LayoutDashboard()

### Community 60 - "statistik-kartu.js"
Cohesion: 0.25
Nodes (4): KARTU_BAWAAN, LABEL_KOLOM, SINONIM_JUMLAH, WARNA_PRESET

### Community 61 - "Skm.jsx"
Cohesion: 0.47
Nodes (3): mutu(), Skm(), warnaNilai()

### Community 63 - "salin-aset-ocr.mjs"
Cohesion: 0.33
Nodes (5): akar, berkas, hilang, tessdata, tujuan

### Community 65 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.18
Nodes (6): BerkasController, Berkas, LogAktivitas, TiketPesan, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 66 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 67 - "extra"
Cohesion: 0.40
Nodes (5): dev-master, extra, branch-alias, laravel, dont-discover

### Community 68 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 70 - "EditorDemografi.jsx"
Cohesion: 0.43
Nodes (4): angka(), digit(), EditorDemografi(), PanelKartuBeranda()

### Community 73 - "date-picker.jsx"
Cohesion: 0.80
Nodes (4): DatePicker(), keTampilan(), masker(), parseValue()

### Community 76 - "api.js"
Cohesion: 0.67
Nodes (5): ambilJson(), bacaJson(), kirimBerkas(), kirimJson(), tokenCsrf()

### Community 79 - "ppid-layanan.blade.php"
Cohesion: 0.40
Nodes (4): publik.partials.berkas, publik.partials.blok-isi, publik.partials.dokumen-edit, publik.partials.subnav

### Community 82 - "UnggahGambar.jsx"
Cohesion: 0.67
Nodes (3): kecilkan(), TIPE_DITERIMA, UnggahGambar()

### Community 85 - "Log.jsx"
Cohesion: 0.67
Nodes (3): Log(), tautanData(), WARNA_AKSI

### Community 87 - "publik.jsx"
Cohesion: 0.67
Nodes (3): bacaProps(), pasang(), pulau

### Community 91 - "PetaSebaran.jsx"
Cohesion: 0.83
Nodes (3): angka(), jariJari(), PetaSebaran()

### Community 93 - "HANDOFF — SAIBATIN Laravel"
Cohesion: 0.22
Nodes (9): 1. Apa ini, 2. Cara menjalankan, 3. Keputusan arsitektur yang SUDAH DIKUNCI, 6. Aturan keras (warisan journal workspace), 7. Temuan yang masih menunggu keputusan user, 8. Berkas sementara yang HARUS dibuang nanti, 9. Rujukan, Angka acuan (hasil pembacaan penuh 7 Agu 2026 — pakai ini, jangan ukur ulang) (+1 more)

### Community 94 - "4. Yang sudah jadi"
Cohesion: 0.22
Nodes (9): 4. Yang sudah jadi, Berikutnya, Fase 1 — data (SELESAI, terverifikasi), Fase 2 — autentikasi (SELESAI, diuji di browser), Fase 3 — endpoint JSON (SELESAI untuk lapisan publik & warga), Fase 4 — 15 layanan permohonan (SELESAI), Fase 5 — dashboard petugas (SELESAI — 16 halaman, diuji di browser), Kit UI — shadcn/ui (14 Agu, keputusan user) (+1 more)

### Community 96 - "5. 🔴 Dua puluh lima jebakan yang SUDAH memakan waktu — jangan diulang"
Cohesion: 0.22
Nodes (9): 5. 🔴 Dua puluh lima jebakan yang SUDAH memakan waktu — jangan diulang, Kit UI (shadcn/Radix), Kuesioner SKM 2026 (18 Agu), Laravel / PHP, Mode Edit (17 Agu), PhpSpreadsheet, React / Inertia, Situs publik (Fase 7) (+1 more)

### Community 97 - "PresisiMilidetik"
Cohesion: 0.21
Nodes (4): PresisiMilidetik, KritikSaran, Pengaduan, SkmJawaban

### Community 107 - "JamLayanan"
Cohesion: 0.06
Nodes (10): PengaturanController, LayananMobileController, PengajuanController, PengajuanPetugasController, JamLayanan, CarbonImmutable, Layanan, Periode (+2 more)

### Community 125 - "Demografi.jsx"
Cohesion: 0.83
Nodes (3): Demografi(), unduh(), usulJudul()

### Community 134 - "peta-label.js"
Cohesion: 0.14
Nodes (8): GARIS_DARAT_TETANGGA, LABEL_KECAMATAN, LABEL_TETANGGA, SKALA_WARNA, WARNA_DARAT_TETANGGA, WARNA_LAUT, WARNA_SOROT, WARNA_TANPA_DATA

### Community 137 - "bootstrap/app.php"
Cohesion: 0.08
Nodes (17): MediaPublikController, HandleInertiaRequests, PakaiTokenMobile, PastikanPeran, AppServiceProvider, Closure, Illuminate\Auth\AuthenticationException, Illuminate\Foundation\Application (+9 more)

### Community 148 - "Controller"
Cohesion: 0.10
Nodes (11): BeritaAdminController, GaleriAdminController, KontenStatisController, LogAktivitasController, MasterController, PengaduanAdminController, ProdukAdminController, SkmAdminController (+3 more)

### Community 151 - "grafik-dasar.js"
Cohesion: 0.22
Nodes (6): BIRU, BIRU_MUDA, dasar, FONT, KUNING, REDUP

### Community 154 - "UserLevel"
Cohesion: 0.29
Nodes (3): UserLevel, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 251 - "README.md"
Cohesion: 0.22
Nodes (8): About Laravel, Code of Conduct, Contributing, Laravel Sponsors, Learning Laravel, License, Premium Partners, Security Vulnerabilities

### Community 252 - "News"
Cohesion: 0.11
Nodes (10): Gallery, News, Produk, PhpOffice\PhpSpreadsheet\Cell\Coordinate, PhpOffice\PhpSpreadsheet\Cell\DataType, PhpOffice\PhpSpreadsheet\Style\Alignment, PhpOffice\PhpSpreadsheet\Style\Border, PhpOffice\PhpSpreadsheet\Style\Fill (+2 more)

### Community 253 - "PermohonanDetail.jsx"
Cohesion: 0.33
Nodes (5): ALASAN_TOLAK, perluRincian(), PermohonanDetail(), STATUS_URUT, WAJIB_RINCIAN

### Community 254 - "Media"
Cohesion: 0.11
Nodes (8): MediaController, Media, PustakaMedia, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Http\UploadedFile, Illuminate\Support\Str, static

### Community 256 - "deteksi-kategori.js"
Cohesion: 0.60
Nodes (4): deteksiKategori(), POLA_BERKAS, slugKategori(), slugMemuat()

### Community 259 - "peta-wilayah.js"
Cohesion: 0.25
Nodes (6): BATAS_TAMPILAN, GEOJSON_KECAMATAN, GEOJSON_TETANGGA, KECAMATAN_PETA, PETA_LEBAR, PETA_TINGGI

### Community 261 - "2. Login & sesi"
Cohesion: 0.40
Nodes (5): 2. Login & sesi, `GET /auth/me` 🔒, Peran (`user.peran`), `POST /auth/login`, `POST /auth/logout` 🔒

### Community 263 - "5. Mengajukan permohonan (warga, OPD, petugas)"
Cohesion: 0.40
Nodes (5): 5. Mengajukan permohonan (warga, OPD, petugas), `GET /layanan` 🔒, `GET /layanan/{slug}` 🔒, Kirim — `POST {kirimKe}` 🔒, Unggah berkas — `POST {unggahKe}` 🔒

### Community 265 - "keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

### Community 269 - "PilihanDropdown"
Cohesion: 0.67
Nodes (3): PilihanDropdown(), pilih(), tombol()

## Knowledge Gaps
- **267 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+262 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 799 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **86 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Http\Request`, `PresisiMilidetik`, `.ringkasan`, `Permohonan`, `UserLevel`, `Notifikasi`, `DatabaseSeeder.php`, `Tiket`, `SuntingAkunTest`, `ApiMobileTest`, `LayananController.php`, `TestCase`, `Controller`, `Pemberitahuan`, `LayananNonaktifTest`, `Wilayah`, `News`, `Media`?**
  _High betweenness centrality (0.043) - this node is a cross-community bridge._
- **Why does `Balasan` connect `Balasan` to `Illuminate\Http\Request`, `Permohonan`, `PresisiMilidetik`, `.ringkasan`, `KategoriDemografi`, `Notifikasi`, `.__invoke`, `JenisPermohonan`, `User`, `JamLayanan`, `bootstrap/app.php`, `PeriodeDemografi`, `DemografiWilayah`, `LayananController.php`, `Controller`, `Pemberitahuan`, `News`, `Media`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **Why does `Controller` connect `Controller` to `Illuminate\Http\Request`, `Permohonan`, `.ringkasan`, `KategoriDemografi`, `PermohonanPdfController`, `Balasan`, `JenisPermohonan`, `bootstrap/app.php`, `PeriodeDemografi`, `DemografiWilayah`, `LayananController.php`, `Pemberitahuan`, `PermohonanPdfController.php`, `.__invoke`, `Illuminate\Database\Eloquent\Model`, `PresisiMilidetik`, `Notifikasi`, `JamLayanan`, `Media`?**
  _High betweenness centrality (0.033) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _267 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Http\Request` be split into smaller, more focused modules?**
  _Cohesion score 0.07111756168359942 - nodes in this community are weakly interconnected._
- **Should `Permohonan` be split into smaller, more focused modules?**
  _Cohesion score 0.0676056338028169 - nodes in this community are weakly interconnected._
- **Should `KategoriDemografi` be split into smaller, more focused modules?**
  _Cohesion score 0.050816696914700546 - nodes in this community are weakly interconnected._