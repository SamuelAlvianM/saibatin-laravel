# Graph Report - saibatin-laravel  (2026-09-08)

## Corpus Check
- 306 files · ~193,640 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1551 nodes · 2671 edges · 265 communities (64 shown, 84 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS · INFERRED: 10 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `de1fb7cd`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Http\Request
- Permohonan
- PeriodeDemografi
- Pemberitahuan
- PublikController
- devDependencies
- Balasan
- CatatanAktivitas
- Dasbor.jsx
- Layanan
- User
- Pengaduan
- Illuminate\Support\Facades\Schema
- Statistik.jsx
- KategoriDemografi
- PeranOpdTest
- Otp
- ProfilTabs.jsx
- Illuminate\Database\Migrations\Migration
- DemografiExcel
- EditorMedan.jsx
- ocr-ktp.js
- scripts
- HANDOFF — SAIBATIN Laravel
- CLAUDE.md
- AppServiceProvider.php
- composer.json
- Beranda.jsx
- Akun.jsx
- navigation-menu.jsx
- Gallery
- dependencies
- a11y.js
- Navbar.jsx
- kategori.js
- DemografiAdminController
- require-dev
- PengaturanLayanan.jsx
- geo.js
- SuntingAkunTest
- config
- require
- StatusAkun
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
- JamLayanan
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
- autoload-dev
- post-autoload-dump
- Wilayah
- AlasanTolakPermohonan
- bootstrap/app.php
- Illuminate\Database\Schema\Blueprint
- DemografiWilayah
- .__invoke
- FotoProfil
- Carbon\CarbonImmutable
- PengaturanController
- PermohonanPdfController.php
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
- BuktiPengaduanTest
- highcharts
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
- react-dropzone
- react-leaflet
- date-fns
- framer-motion
- tesseract.js
- highcharts-react-official
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
- UserLevel
- README.md
- react-dom
- PermohonanDetail.jsx
- Media
- deteksi-kategori.js
- sonner
- tailwind-merge
- @tiptap/extension-image
- Controller
- DemografiAdminController.php

## God Nodes (most connected - your core abstractions)
1. `Balasan` - 121 edges
2. `User` - 75 edges
3. `Controller` - 74 edges
4. `CatatanAktivitas` - 41 edges
5. `PresisiMilidetik` - 38 edges
6. `Permohonan` - 37 edges
7. `StatistikExcel` - 34 edges
8. `KategoriDemografi` - 29 edges
9. `DemografiWilayah` - 28 edges
10. `StaticContent` - 28 edges

## Surprising Connections (you probably didn't know these)
- `SuntingAkunTest` --references--> `User`  [EXTRACTED]
  tests/Feature/SuntingAkunTest.php → app/Models/User.php
- `BeritaAdminController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/BeritaAdminController.php → app/Http/Controllers/Controller.php
- `DemografiAdminController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/DemografiAdminController.php → app/Http/Controllers/Controller.php
- `DemografiAdminController` --references--> `CatatanAktivitas`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/DemografiAdminController.php → app/Services/CatatanAktivitas.php
- `DemografiAdminController` --references--> `DemografiExcel`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/DemografiAdminController.php → app/Services/DemografiExcel.php

## Import Cycles
- None detected.

## Communities (265 total, 84 thin omitted)

### Community 0 - "Illuminate\Http\Request"
Cohesion: 0.11
Nodes (8): ProfilController, LoginController, OtpController, SandiController, ProfilPageController, Illuminate\Http\Request, Illuminate\Support\Facades\Cache, Inertia\Response

### Community 1 - "Permohonan"
Cohesion: 0.09
Nodes (18): StatistikEksporController, DashboardController, Permohonan, PencacahKunjungan, Carbon, Spreadsheet, StreamedResponse, Worksheet (+10 more)

### Community 3 - "Pemberitahuan"
Cohesion: 0.11
Nodes (8): LogAktivitasController, PermohonanAdminController, LayananController, PermohonanController, CekStatusController, JenisPermohonan, Pemberitahuan, Periode

### Community 4 - "PublikController"
Cohesion: 0.09
Nodes (3): PublikController, Produk, Konten

### Community 5 - "devDependencies"
Cohesion: 0.08
Nodes (25): axios, concurrently, laravel-vite-plugin, devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss (+17 more)

### Community 6 - "Balasan"
Cohesion: 0.14
Nodes (5): NotifikasiController, PendudukController, SistemController, Balasan, Illuminate\Http\JsonResponse

### Community 7 - "CatatanAktivitas"
Cohesion: 0.10
Nodes (7): BeritaAdminController, KontenStatisController, MasterController, ProdukAdminController, News, CatatanAktivitas, Illuminate\Support\Facades\Route

### Community 8 - "Dasbor.jsx"
Cohesion: 0.11
Nodes (8): FilterPeriode(), geser(), labelAcuan(), nomorHalaman(), Paginasi(), PERIODE, STATUS_FINAL, STATUS_PERMOHONAN

### Community 10 - "User"
Cohesion: 0.13
Nodes (6): User, DatabaseSeeder, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Database\Seeder, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 11 - "Pengaduan"
Cohesion: 0.16
Nodes (4): PengaduanAdminController, AspirasiController, KritikSaran, Pengaduan

### Community 14 - "Statistik.jsx"
Cohesion: 0.13
Nodes (9): penanda, angka(), RincianDemografi(), angka(), AngkaNaik(), AWAL, KartuPelayanan(), PetaKantor (+1 more)

### Community 15 - "KategoriDemografi"
Cohesion: 0.13
Nodes (3): KategoriDemografiController, StaticContent, KategoriDemografi

### Community 20 - "DemografiExcel"
Cohesion: 0.17
Nodes (4): DemografiExcel, Spreadsheet, StreamedResponse, Worksheet

### Community 21 - "EditorMedan.jsx"
Cohesion: 0.15
Nodes (7): BarisItem(), panjang(), PenyuntingKaya, Berita(), KOSONG, PenyuntingKaya, slugify()

### Community 22 - "ocr-ktp.js"
Cohesion: 0.29
Nodes (12): ambilWorker(), bacaKtp(), denganTimeout(), muatGambar(), nikSah(), normalkanDigit(), OcrGagal, persentil() (+4 more)

### Community 23 - "scripts"
Cohesion: 0.17
Nodes (12): scripts, dev, post-create-project-cmd, post-root-package-install, post-update-cmd, Composer\\Config::disableProcessTimeout, npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite, @php artisan key:generate --ansi (+4 more)

### Community 24 - "HANDOFF — SAIBATIN Laravel"
Cohesion: 0.05
Nodes (36): 1. Apa ini, 2. Cara menjalankan, 3. Keputusan arsitektur yang SUDAH DIKUNCI, 4. Yang sudah jadi, 5. 🔴 Dua puluh lima jebakan yang SUDAH memakan waktu — jangan diulang, 6. Aturan keras (warisan journal workspace), 7. Temuan yang masih menunggu keputusan user, 8. Berkas sementara yang HARUS dibuang nanti (+28 more)

### Community 26 - "AppServiceProvider.php"
Cohesion: 0.33
Nodes (4): AppServiceProvider, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\View, Illuminate\Support\ServiceProvider

### Community 27 - "composer.json"
Cohesion: 0.18
Nodes (10): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type (+2 more)

### Community 28 - "Beranda.jsx"
Cohesion: 0.22
Nodes (8): AKSI_WARNA, BarisProgres(), Beranda(), BULAN, GrafikHarian, PENGADUAN_BAR, persen(), STATUS_BAR

### Community 32 - "Akun.jsx"
Cohesion: 0.13
Nodes (8): FORM_KOSONG, GRUP, INFO_STATUS, KOLOM_TOLAK, MASUK_NIK, STATUS_AKUN, WAJIB_WILAYAH, WARNA_PERMOHONAN

### Community 34 - "Gallery"
Cohesion: 0.19
Nodes (3): GaleriAdminController, KontenController, Gallery

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
Cohesion: 0.29
Nodes (7): require, barryvdh/laravel-dompdf, inertiajs/inertia-laravel, laravel/framework, laravel/tinker, php, phpoffice/phpspreadsheet

### Community 50 - "StatusAkun"
Cohesion: 0.18
Nodes (5): StatusAkun, Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\Mail, Illuminate\Validation\ValidationException, Inertia\Inertia

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

### Community 64 - "JamLayanan"
Cohesion: 0.29
Nodes (3): PengajuanPetugasController, JamLayanan, CarbonImmutable

### Community 65 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.05
Nodes (17): SkmAdminController, BerkasController, MediaPublikController, PastikanPeran, Berkas, PresisiMilidetik, Kunjungan, LogAktivitas (+9 more)

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

### Community 93 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 94 - "post-autoload-dump"
Cohesion: 0.67
Nodes (3): post-autoload-dump, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan package:discover --ansi

### Community 95 - "Wilayah"
Cohesion: 0.22
Nodes (5): BuatAkunUji, IsiWilayahAkun, Wilayah, Illuminate\Console\Command, Illuminate\Support\Collection

### Community 97 - "bootstrap/app.php"
Cohesion: 0.22
Nodes (6): HandleInertiaRequests, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets, Inertia\Middleware

### Community 99 - "DemografiWilayah"
Cohesion: 0.25
Nodes (3): DemografiController, DemografiWilayah, PhpOffice\PhpSpreadsheet\IOFactory

### Community 103 - "FotoProfil"
Cohesion: 0.10
Nodes (5): UserAdminController, RegisterController, FotoProfil, Surel, AlasanTolak

### Community 125 - "Demografi.jsx"
Cohesion: 0.83
Nodes (3): Demografi(), unduh(), usulJudul()

### Community 250 - "UserLevel"
Cohesion: 0.14
Nodes (6): UserLevel, Illuminate\Foundation\Testing\TestCase, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log, ExampleTest, TestCase

### Community 251 - "README.md"
Cohesion: 0.22
Nodes (8): About Laravel, Code of Conduct, Contributing, Laravel Sponsors, Learning Laravel, License, Premium Partners, Security Vulnerabilities

### Community 253 - "PermohonanDetail.jsx"
Cohesion: 0.33
Nodes (5): ALASAN_TOLAK, perluRincian(), PermohonanDetail(), STATUS_URUT, WAJIB_RINCIAN

### Community 254 - "Media"
Cohesion: 0.10
Nodes (9): MediaController, Media, PustakaMedia, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Cookie, Illuminate\Support\Str (+1 more)

### Community 256 - "deteksi-kategori.js"
Cohesion: 0.60
Nodes (4): deteksiKategori(), POLA_BERKAS, slugKategori(), slugMemuat()

### Community 268 - "Controller"
Cohesion: 0.24
Nodes (4): BuktiPengaduanController, UnggahController, Controller, Illuminate\Support\Facades\Storage

## Knowledge Gaps
- **222 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+217 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 705 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **84 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Http\Request`, `Permohonan`, `Illuminate\Database\Eloquent\Model`, `Pemberitahuan`, `Balasan`, `FotoProfil`, `Pengaduan`, `Controller`, `SuntingAkunTest`, `PeranOpdTest`, `StatusAkun`, `LayananNonaktifTest`, `UserLevel`, `Media`, `Wilayah`?**
  _High betweenness centrality (0.054) - this node is a cross-community bridge._
- **Why does `Controller` connect `Controller` to `Illuminate\Http\Request`, `Permohonan`, `Pemberitahuan`, `PublikController`, `Balasan`, `CatatanAktivitas`, `Pengaduan`, `KategoriDemografi`, `DemografiAdminController.php`, `Gallery`, `DemografiAdminController`, `StatusAkun`, `JamLayanan`, `Illuminate\Database\Eloquent\Model`, `DemografiWilayah`, `.__invoke`, `FotoProfil`, `PengaturanController`, `PermohonanPdfController.php`, `Media`?**
  _High betweenness centrality (0.033) - this node is a cross-community bridge._
- **Why does `Balasan` connect `Balasan` to `AlasanTolakPermohonan`, `Illuminate\Database\Eloquent\Model`, `Gallery`, `DemografiWilayah`, `.__invoke`, `Pemberitahuan`, `Permohonan`, `CatatanAktivitas`, `DemografiAdminController`, `FotoProfil`, `Illuminate\Http\Request`, `Pengaduan`, `PengaturanController`, `Controller`, `KategoriDemografi`, `StatusAkun`, `DemografiAdminController.php`, `Media`?**
  _High betweenness centrality (0.028) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _222 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Http\Request` be split into smaller, more focused modules?**
  _Cohesion score 0.11083743842364532 - nodes in this community are weakly interconnected._
- **Should `Permohonan` be split into smaller, more focused modules?**
  _Cohesion score 0.09147869674185463 - nodes in this community are weakly interconnected._
- **Should `Pemberitahuan` be split into smaller, more focused modules?**
  _Cohesion score 0.10591133004926108 - nodes in this community are weakly interconnected._