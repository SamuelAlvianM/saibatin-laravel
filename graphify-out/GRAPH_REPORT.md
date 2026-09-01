# Graph Report - saibatin-laravel  (2026-09-01)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1325 nodes · 2230 edges · 250 communities (55 shown, 79 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS · INFERRED: 8 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `56511320`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Eloquent\Model
- Permohonan
- JamLayanan
- Media
- PublikController
- devDependencies
- DemografiAdminController
- Balasan
- Dasbor.jsx
- Illuminate\Http\Request
- User
- News
- UserAdminController.php
- Controller
- Statistik.jsx
- Recaptcha
- StatusAkun
- Otp
- ProfilTabs.jsx
- CatatanAktivitas
- Pemberitahuan
- EditorMedan.jsx
- ocr-ktp.js
- scripts
- AlasanTolak
- CLAUDE.md
- bootstrap/app.php
- composer.json
- Beranda.jsx
- Akun.jsx
- navigation-menu.jsx
- Gallery
- dependencies
- a11y.js
- Navbar.jsx
- api.php
- FotoProfil
- Illuminate\Support\Facades\Schema
- require-dev
- PengaturanLayanan.jsx
- geo.js
- ProdukAdminController
- LoginController
- config
- require
- Illuminate\Database\Schema\Blueprint
- Illuminate\Database\Migrations\Migration
- Grafik.jsx
- LoncengNotifikasi.jsx
- time-picker.jsx
- PermohonanPdfController
- TestCase
- info.blade.php
- LayoutDashboard.jsx
- tabs.jsx
- mode-edit.js
- statistik-kartu.js
- Skm.jsx
- Profil.jsx
- salin-aset-ocr.mjs
- CekStatusController
- Pemberitahuan.php
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
- date-fns
- framer-motion
- highcharts
- highcharts-react-official
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
- react-dom
- react-dropzone
- react-leaflet
- sonner
- tailwind-merge
- tesseract.js
- @tiptap/extension-image
- @tiptap/extension-link
- @tiptap/extension-underline
- @tiptap/extensions
- @tiptap/pm
- @tiptap/react
- @tiptap/starter-kit
- publik.partials.footer
- navigasi.js
- ppid-indeks.blade.php
- `t_skm_jawaban`
- `users`

## God Nodes (most connected - your core abstractions)
1. `Balasan` - 110 edges
2. `Controller` - 72 edges
3. `User` - 54 edges
4. `PresisiMilidetik` - 38 edges
5. `CatatanAktivitas` - 38 edges
6. `Permohonan` - 32 edges
7. `StatistikExcel` - 32 edges
8. `Pemberitahuan` - 28 edges
9. `News` - 26 edges
10. `JamLayanan` - 25 edges

## Surprising Connections (you probably didn't know these)
- `BeritaAdminController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/BeritaAdminController.php → app/Http/Controllers/Controller.php
- `BeritaAdminController` --references--> `CatatanAktivitas`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/BeritaAdminController.php → app/Services/CatatanAktivitas.php
- `DemografiAdminController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/DemografiAdminController.php → app/Http/Controllers/Controller.php
- `DemografiAdminController` --references--> `CatatanAktivitas`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/DemografiAdminController.php → app/Services/CatatanAktivitas.php
- `GaleriAdminController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/GaleriAdminController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (250 total, 79 thin omitted)

### Community 0 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.05
Nodes (18): BerkasController, MediaPublikController, PastikanPeran, Berkas, PresisiMilidetik, KritikSaran, Kunjungan, LogAktivitas (+10 more)

### Community 1 - "Permohonan"
Cohesion: 0.08
Nodes (19): StatistikController, DashboardController, JenisPermohonan, Permohonan, PencacahKunjungan, Spreadsheet, StreamedResponse, StatistikExcel (+11 more)

### Community 2 - "JamLayanan"
Cohesion: 0.05
Nodes (9): PengaturanController, PengajuanController, PengajuanPetugasController, StaticContent, JamLayanan, CarbonImmutable, Layanan, CarbonImmutable (+1 more)

### Community 3 - "Media"
Cohesion: 0.11
Nodes (9): MediaController, Media, PustakaMedia, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Cookie, Illuminate\Support\Str (+1 more)

### Community 4 - "PublikController"
Cohesion: 0.11
Nodes (3): PublikController, Produk, Konten

### Community 5 - "devDependencies"
Cohesion: 0.08
Nodes (25): axios, concurrently, laravel-vite-plugin, devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss (+17 more)

### Community 6 - "DemografiAdminController"
Cohesion: 0.14
Nodes (6): DemografiAdminController, DemografiWilayah, DemografiExcel, Spreadsheet, StreamedResponse, PhpOffice\PhpSpreadsheet\IOFactory

### Community 7 - "Balasan"
Cohesion: 0.14
Nodes (6): SistemController, OtpController, Balasan, Illuminate\Http\JsonResponse, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB

### Community 8 - "Dasbor.jsx"
Cohesion: 0.10
Nodes (8): FilterPeriode(), geser(), labelAcuan(), nomorHalaman(), Paginasi(), PERIODE, STATUS_FINAL, STATUS_PERMOHONAN

### Community 9 - "Illuminate\Http\Request"
Cohesion: 0.16
Nodes (5): NotifikasiController, PendudukController, ProfilController, ProfilPageController, Illuminate\Http\Request

### Community 10 - "User"
Cohesion: 0.14
Nodes (6): User, DatabaseSeeder, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Database\Seeder, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 11 - "News"
Cohesion: 0.18
Nodes (3): BeritaAdminController, KontenController, News

### Community 12 - "UserAdminController.php"
Cohesion: 0.15
Nodes (5): LogAktivitasController, PermohonanAdminController, UserAdminController, Surel, Periode

### Community 13 - "Controller"
Cohesion: 0.15
Nodes (6): StatistikEksporController, BuktiPengaduanController, DemografiController, Controller, Barryvdh\DomPDF\Facade\Pdf, Illuminate\Support\Facades\Storage

### Community 14 - "Statistik.jsx"
Cohesion: 0.14
Nodes (9): penanda, angka(), RincianDemografi(), angka(), AngkaNaik(), AWAL, KartuPelayanan(), PetaKantor (+1 more)

### Community 15 - "Recaptcha"
Cohesion: 0.15
Nodes (5): PermohonanController, RegisterController, SandiController, Recaptcha, Illuminate\Support\Facades\Route

### Community 16 - "StatusAkun"
Cohesion: 0.19
Nodes (5): StatusAkun, Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\Mail, Illuminate\Validation\ValidationException, Inertia\Inertia

### Community 19 - "CatatanAktivitas"
Cohesion: 0.20
Nodes (4): KontenStatisController, MasterController, LayananController, CatatanAktivitas

### Community 20 - "Pemberitahuan"
Cohesion: 0.22
Nodes (4): PengaduanAdminController, AspirasiController, Pengaduan, Pemberitahuan

### Community 21 - "EditorMedan.jsx"
Cohesion: 0.15
Nodes (7): BarisItem(), panjang(), PenyuntingKaya, Berita(), KOSONG, PenyuntingKaya, slugify()

### Community 22 - "ocr-ktp.js"
Cohesion: 0.29
Nodes (12): ambilWorker(), bacaKtp(), denganTimeout(), muatGambar(), nikSah(), normalkanDigit(), OcrGagal, persentil() (+4 more)

### Community 23 - "scripts"
Cohesion: 0.17
Nodes (12): scripts, dev, post-create-project-cmd, post-root-package-install, post-update-cmd, Composer\\Config::disableProcessTimeout, npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite, @php artisan key:generate --ansi (+4 more)

### Community 26 - "bootstrap/app.php"
Cohesion: 0.20
Nodes (6): HandleInertiaRequests, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets, Inertia\Middleware

### Community 27 - "composer.json"
Cohesion: 0.18
Nodes (10): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type (+2 more)

### Community 28 - "Beranda.jsx"
Cohesion: 0.22
Nodes (8): AKSI_WARNA, BarisProgres(), Beranda(), BULAN, GrafikHarian, PENGADUAN_BAR, persen(), STATUS_BAR

### Community 32 - "Akun.jsx"
Cohesion: 0.18
Nodes (6): FORM_KOSONG, GRUP, INFO_STATUS, KOLOM_TOLAK, STATUS_AKUN, WARNA_PERMOHONAN

### Community 35 - "dependencies"
Cohesion: 0.22
Nodes (9): class-variance-authority, dependencies, class-variance-authority, @radix-ui/react-navigation-menu, react-organizational-chart, @tiptap/extension-text-align, @radix-ui/react-navigation-menu, react-organizational-chart (+1 more)

### Community 36 - "a11y.js"
Cohesion: 0.22
Nodes (5): FONT_BAWAAN_IDX, KUNCI_A11Y, LANGKAH_FONT, PREFS_BAWAAN, SPASI_MAKS

### Community 40 - "Illuminate\Support\Facades\Schema"
Cohesion: 0.29
Nodes (5): AppServiceProvider, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Schema, Illuminate\Support\Facades\View, Illuminate\Support\ServiceProvider

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

### Community 51 - "Grafik.jsx"
Cohesion: 0.43
Nodes (4): dasar(), GrafikGaris(), GrafikPeringkat(), GrafikTren()

### Community 52 - "LoncengNotifikasi.jsx"
Cohesion: 0.52
Nodes (6): ambilKonteks(), bukaAudio(), bunyikan(), LoncengNotifikasi(), TIPE_IKON, waktuRelatif()

### Community 53 - "time-picker.jsx"
Cohesion: 0.38
Nodes (5): HOURS, isJam(), masker(), MINUTES, TimePicker()

### Community 55 - "TestCase"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 56 - "info.blade.php"
Cohesion: 0.33
Nodes (5): publik.partials.faq, publik.partials.berkas, publik.partials.blok-isi, publik.partials.dokumen-edit, publik.partials.subnav

### Community 57 - "LayoutDashboard.jsx"
Cohesion: 0.53
Nodes (5): aktifkan(), GRUP, grupUntuk(), ItemMenu(), LayoutDashboard()

### Community 60 - "statistik-kartu.js"
Cohesion: 0.33
Nodes (3): KARTU_BAWAAN, LABEL_KOLOM, WARNA_PRESET

### Community 61 - "Skm.jsx"
Cohesion: 0.47
Nodes (3): mutu(), Skm(), warnaNilai()

### Community 63 - "salin-aset-ocr.mjs"
Cohesion: 0.33
Nodes (5): akar, berkas, hilang, tessdata, tujuan

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
Cohesion: 0.60
Nodes (3): angka(), digit(), EditorDemografi()

### Community 73 - "date-picker.jsx"
Cohesion: 0.80
Nodes (4): DatePicker(), keTampilan(), masker(), parseValue()

### Community 76 - "api.js"
Cohesion: 0.60
Nodes (3): kirimBerkas(), kirimJson(), tokenCsrf()

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

## Knowledge Gaps
- **172 isolated node(s):** `graphify`, `$schema`, `name`, `type`, `description` (+167 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 610 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **79 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Controller` connect `Controller` to `Illuminate\Database\Eloquent\Model`, `Permohonan`, `JamLayanan`, `Media`, `PublikController`, `DemografiAdminController`, `Balasan`, `Illuminate\Http\Request`, `News`, `UserAdminController.php`, `Recaptcha`, `StatusAkun`, `CatatanAktivitas`, `Pemberitahuan`, `Gallery`, `api.php`, `ProdukAdminController`, `LoginController`, `PermohonanPdfController`, `CekStatusController`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **Why does `User` connect `User` to `Illuminate\Database\Eloquent\Model`, `CekStatusController`, `Permohonan`, `Pemberitahuan.php`, `Media`, `Illuminate\Http\Request`, `UserAdminController.php`, `Controller`, `LoginController`, `Recaptcha`, `StatusAkun`, `Pemberitahuan`, `AlasanTolak`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **Why does `Balasan` connect `Balasan` to `Illuminate\Database\Eloquent\Model`, `Permohonan`, `Gallery`, `Media`, `JamLayanan`, `CekStatusController`, `DemografiAdminController`, `api.php`, `Illuminate\Http\Request`, `News`, `UserAdminController.php`, `ProdukAdminController`, `Controller`, `Recaptcha`, `StatusAkun`, `CatatanAktivitas`, `Pemberitahuan`, `AlasanTolak`?**
  _High betweenness centrality (0.025) - this node is a cross-community bridge._
- **What connects `graphify`, `$schema`, `name` to the rest of the system?**
  _172 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Eloquent\Model` be split into smaller, more focused modules?**
  _Cohesion score 0.05336951605608322 - nodes in this community are weakly interconnected._
- **Should `Permohonan` be split into smaller, more focused modules?**
  _Cohesion score 0.08461131676361713 - nodes in this community are weakly interconnected._
- **Should `JamLayanan` be split into smaller, more focused modules?**
  _Cohesion score 0.054426705370101594 - nodes in this community are weakly interconnected._