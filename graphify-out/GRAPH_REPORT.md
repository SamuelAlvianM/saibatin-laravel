# Graph Report - saibatin-laravel  (2026-09-01)

## Corpus Check
- 305 files · ~207,790 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1330 nodes · 2229 edges · 254 communities (60 shown, 73 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS · INFERRED: 8 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `863c3d92`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Permohonan
- News
- Media
- devDependencies
- DemografiAdminController
- PermohonanPdfController.php
- PermohonanAdminController.php
- Dasbor.jsx
- Pemberitahuan
- CatatanAktivitas
- Illuminate\Database\Eloquent\Model
- Illuminate\Http\Request
- User
- api.php
- Controller
- Statistik.jsx
- Balasan
- StatusAkun
- Tiket
- Otp
- ProfilTabs.jsx
- Berita.jsx
- EditorMedan.jsx
- JamLayanan
- DatabaseSeeder.php
- CLAUDE.md
- Illuminate\Database\Eloquent\Relations\BelongsTo
- ocr-ktp.js
- FotoProfil
- bootstrap/app.php
- scripts
- composer.json
- Beranda.jsx
- Akun.jsx
- SkmJawaban
- navigation-menu.jsx
- Notifikasi
- dependencies
- a11y.js
- Navbar.jsx
- Illuminate\Support\Facades\Schema
- require-dev
- PengaturanLayanan.jsx
- geo.js
- config
- require
- Illuminate\Database\Schema\Blueprint
- Illuminate\Database\Migrations\Migration
- Grafik.jsx
- LoncengNotifikasi.jsx
- time-picker.jsx
- TestCase
- info.blade.php
- LayoutDashboard.jsx
- tabs.jsx
- mode-edit.js
- statistik-kartu.js
- Skm.jsx
- Profil.jsx
- salin-aset-ocr.mjs
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
- 2026-08-18_siapkan-db-produksi.sql
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
- `StatistikController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/StatistikController.php → app/Http/Controllers/Controller.php
- `DashboardController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/DashboardController.php → app/Http/Controllers/Controller.php
- `Permohonan` --mixes_in--> `PresisiMilidetik`  [EXTRACTED]
  app/Models/Permohonan.php → app/Models/Concerns/PresisiMilidetik.php
- `SistemController` --references--> `PencacahKunjungan`  [EXTRACTED]
  app/Http/Controllers/Api/SistemController.php → app/Services/PencacahKunjungan.php
- `StatistikEksporController` --references--> `StatistikExcel`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/StatistikEksporController.php → app/Services/StatistikExcel.php

## Import Cycles
- None detected.

## Communities (254 total, 73 thin omitted)

### Community 0 - "Permohonan"
Cohesion: 0.08
Nodes (20): StatistikController, DashboardController, JenisPermohonan, Permohonan, PencacahKunjungan, Spreadsheet, StreamedResponse, StatistikExcel (+12 more)

### Community 1 - "News"
Cohesion: 0.08
Nodes (5): BeritaAdminController, PublikController, News, Produk, Konten

### Community 2 - "Media"
Cohesion: 0.12
Nodes (8): MediaController, Media, PustakaMedia, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Http\UploadedFile, Illuminate\Support\Str, static

### Community 3 - "devDependencies"
Cohesion: 0.08
Nodes (25): axios, concurrently, laravel-vite-plugin, devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss (+17 more)

### Community 4 - "DemografiAdminController"
Cohesion: 0.14
Nodes (6): DemografiAdminController, DemografiWilayah, DemografiExcel, Spreadsheet, StreamedResponse, PhpOffice\PhpSpreadsheet\IOFactory

### Community 5 - "PermohonanPdfController.php"
Cohesion: 0.12
Nodes (8): PermohonanPdfController, BerkasController, MediaPublikController, PastikanPeran, Barryvdh\DomPDF\Facade\Pdf, Closure, Illuminate\Support\Facades\Storage, Symfony\Component\HttpFoundation\Response

### Community 6 - "PermohonanAdminController.php"
Cohesion: 0.21
Nodes (4): LogAktivitasController, PermohonanAdminController, Surel, Periode

### Community 7 - "Dasbor.jsx"
Cohesion: 0.10
Nodes (8): FilterPeriode(), geser(), labelAcuan(), nomorHalaman(), Paginasi(), PERIODE, STATUS_FINAL, STATUS_PERMOHONAN

### Community 8 - "Pemberitahuan"
Cohesion: 0.13
Nodes (8): PengaduanAdminController, AspirasiController, PermohonanController, RegisterController, KritikSaran, Pengaduan, Pemberitahuan, Recaptcha

### Community 9 - "CatatanAktivitas"
Cohesion: 0.13
Nodes (5): KontenStatisController, MasterController, ProdukAdminController, LayananController, CatatanAktivitas

### Community 10 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.21
Nodes (5): PresisiMilidetik, Kunjungan, LogAktivitas, UserLevel, Illuminate\Database\Eloquent\Model

### Community 11 - "Illuminate\Http\Request"
Cohesion: 0.18
Nodes (4): ProfilController, SandiController, ProfilPageController, Illuminate\Http\Request

### Community 12 - "User"
Cohesion: 0.12
Nodes (5): User, Wilayah, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 13 - "api.php"
Cohesion: 0.14
Nodes (5): GaleriAdminController, KontenController, UnggahController, Gallery, Illuminate\Support\Facades\Route

### Community 14 - "Controller"
Cohesion: 0.13
Nodes (6): SkmAdminController, StatistikEksporController, BuktiPengaduanController, DemografiController, PendudukController, Controller

### Community 15 - "Statistik.jsx"
Cohesion: 0.14
Nodes (9): penanda, angka(), RincianDemografi(), angka(), AngkaNaik(), AWAL, KartuPelayanan(), PetaKantor (+1 more)

### Community 16 - "Balasan"
Cohesion: 0.15
Nodes (5): NotifikasiController, SistemController, Balasan, Illuminate\Http\JsonResponse, Illuminate\Support\Facades\DB

### Community 17 - "StatusAkun"
Cohesion: 0.10
Nodes (7): CekStatusController, LoginController, StatusAkun, Illuminate\Support\Facades\Mail, Illuminate\Validation\ValidationException, Inertia\Inertia, Inertia\Response

### Community 19 - "Otp"
Cohesion: 0.14
Nodes (4): OtpController, Fonnte, Otp, Illuminate\Support\Facades\Cache

### Community 21 - "Berita.jsx"
Cohesion: 0.50
Nodes (4): Berita(), KOSONG, PenyuntingKaya, slugify()

### Community 22 - "EditorMedan.jsx"
Cohesion: 0.25
Nodes (3): BarisItem(), panjang(), PenyuntingKaya

### Community 23 - "JamLayanan"
Cohesion: 0.05
Nodes (9): PengaturanController, PengajuanController, PengajuanPetugasController, StaticContent, JamLayanan, CarbonImmutable, Layanan, CarbonImmutable (+1 more)

### Community 26 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.24
Nodes (3): Berkas, TiketPesan, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 27 - "ocr-ktp.js"
Cohesion: 0.29
Nodes (12): ambilWorker(), bacaKtp(), denganTimeout(), muatGambar(), nikSah(), normalkanDigit(), OcrGagal, persentil() (+4 more)

### Community 28 - "FotoProfil"
Cohesion: 0.13
Nodes (4): UserAdminController, FotoProfil, AlasanTolak, Illuminate\Support\Facades\Hash

### Community 29 - "bootstrap/app.php"
Cohesion: 0.18
Nodes (6): HandleInertiaRequests, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets, Inertia\Middleware

### Community 30 - "scripts"
Cohesion: 0.17
Nodes (12): scripts, dev, post-create-project-cmd, post-root-package-install, post-update-cmd, Composer\\Config::disableProcessTimeout, npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite, @php artisan key:generate --ansi (+4 more)

### Community 31 - "composer.json"
Cohesion: 0.18
Nodes (10): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type (+2 more)

### Community 32 - "Beranda.jsx"
Cohesion: 0.24
Nodes (8): AKSI_WARNA, BarisProgres(), Beranda(), BULAN, GrafikHarian, PENGADUAN_BAR, persen(), STATUS_BAR

### Community 36 - "Akun.jsx"
Cohesion: 0.18
Nodes (6): FORM_KOSONG, GRUP, INFO_STATUS, KOLOM_TOLAK, STATUS_AKUN, WARNA_PERMOHONAN

### Community 39 - "Notifikasi"
Cohesion: 0.20
Nodes (3): Notifikasi, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 40 - "dependencies"
Cohesion: 0.22
Nodes (9): class-variance-authority, dependencies, class-variance-authority, @radix-ui/react-navigation-menu, react-organizational-chart, @tiptap/extension-text-align, @radix-ui/react-navigation-menu, react-organizational-chart (+1 more)

### Community 41 - "a11y.js"
Cohesion: 0.22
Nodes (5): FONT_BAWAAN_IDX, KUNCI_A11Y, LANGKAH_FONT, PREFS_BAWAAN, SPASI_MAKS

### Community 44 - "Illuminate\Support\Facades\Schema"
Cohesion: 0.29
Nodes (5): AppServiceProvider, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Schema, Illuminate\Support\Facades\View, Illuminate\Support\ServiceProvider

### Community 45 - "require-dev"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 48 - "geo.js"
Cohesion: 0.32
Nodes (7): ALIAS, geoWilayah(), KECAMATAN_GEO, norm(), PETA_NAMA, PUSAT_PETA, ZOOM_AWAL

### Community 51 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 52 - "require"
Cohesion: 0.29
Nodes (7): require, barryvdh/laravel-dompdf, inertiajs/inertia-laravel, laravel/framework, laravel/tinker, php, phpoffice/phpspreadsheet

### Community 55 - "Grafik.jsx"
Cohesion: 0.43
Nodes (4): dasar(), GrafikGaris(), GrafikPeringkat(), GrafikTren()

### Community 56 - "LoncengNotifikasi.jsx"
Cohesion: 0.52
Nodes (6): ambilKonteks(), bukaAudio(), bunyikan(), LoncengNotifikasi(), TIPE_IKON, waktuRelatif()

### Community 57 - "time-picker.jsx"
Cohesion: 0.38
Nodes (5): HOURS, isJam(), masker(), MINUTES, TimePicker()

### Community 60 - "TestCase"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 61 - "info.blade.php"
Cohesion: 0.33
Nodes (5): publik.partials.faq, publik.partials.berkas, publik.partials.blok-isi, publik.partials.dokumen-edit, publik.partials.subnav

### Community 62 - "LayoutDashboard.jsx"
Cohesion: 0.53
Nodes (5): aktifkan(), GRUP, grupUntuk(), ItemMenu(), LayoutDashboard()

### Community 65 - "statistik-kartu.js"
Cohesion: 0.33
Nodes (3): KARTU_BAWAAN, LABEL_KOLOM, WARNA_PRESET

### Community 66 - "Skm.jsx"
Cohesion: 0.47
Nodes (3): mutu(), Skm(), warnaNilai()

### Community 68 - "salin-aset-ocr.mjs"
Cohesion: 0.33
Nodes (5): akar, berkas, hilang, tessdata, tujuan

### Community 69 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 70 - "extra"
Cohesion: 0.40
Nodes (5): dev-master, extra, branch-alias, laravel, dont-discover

### Community 71 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 73 - "EditorDemografi.jsx"
Cohesion: 0.60
Nodes (3): angka(), digit(), EditorDemografi()

### Community 76 - "date-picker.jsx"
Cohesion: 0.80
Nodes (4): DatePicker(), keTampilan(), masker(), parseValue()

### Community 79 - "api.js"
Cohesion: 0.60
Nodes (3): kirimBerkas(), kirimJson(), tokenCsrf()

### Community 82 - "ppid-layanan.blade.php"
Cohesion: 0.40
Nodes (4): publik.partials.berkas, publik.partials.blok-isi, publik.partials.dokumen-edit, publik.partials.subnav

### Community 85 - "UnggahGambar.jsx"
Cohesion: 0.67
Nodes (3): kecilkan(), TIPE_DITERIMA, UnggahGambar()

### Community 88 - "Log.jsx"
Cohesion: 0.67
Nodes (3): Log(), tautanData(), WARNA_AKSI

### Community 90 - "publik.jsx"
Cohesion: 0.67
Nodes (3): bacaProps(), pasang(), pulau

### Community 94 - "PetaSebaran.jsx"
Cohesion: 0.83
Nodes (3): angka(), jariJari(), PetaSebaran()

### Community 96 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 97 - "post-autoload-dump"
Cohesion: 0.67
Nodes (3): post-autoload-dump, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan package:discover --ansi

## Knowledge Gaps
- **173 isolated node(s):** `graphify`, `IKON`, `bawaan`, `WARNA`, `KATEGORI` (+168 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 615 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **73 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Controller` connect `Controller` to `Permohonan`, `News`, `Media`, `DemografiAdminController`, `PermohonanPdfController.php`, `PermohonanAdminController.php`, `Pemberitahuan`, `CatatanAktivitas`, `Illuminate\Http\Request`, `api.php`, `Balasan`, `StatusAkun`, `Otp`, `JamLayanan`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `FotoProfil`?**
  _High betweenness centrality (0.034) - this node is a cross-community bridge._
- **Why does `User` connect `User` to `Permohonan`, `Media`, `PermohonanPdfController.php`, `Notifikasi`, `Pemberitahuan`, `Illuminate\Database\Eloquent\Model`, `Illuminate\Http\Request`, `Controller`, `StatusAkun`, `Tiket`, `DatabaseSeeder.php`, `FotoProfil`?**
  _High betweenness centrality (0.019) - this node is a cross-community bridge._
- **Why does `Balasan` connect `Balasan` to `Permohonan`, `News`, `Media`, `DemografiAdminController`, `SkmJawaban`, `PermohonanAdminController.php`, `PermohonanPdfController.php`, `Pemberitahuan`, `CatatanAktivitas`, `Illuminate\Http\Request`, `User`, `api.php`, `Controller`, `StatusAkun`, `Otp`, `JamLayanan`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `FotoProfil`?**
  _High betweenness centrality (0.018) - this node is a cross-community bridge._
- **What connects `graphify`, `IKON`, `bawaan` to the rest of the system?**
  _173 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Permohonan` be split into smaller, more focused modules?**
  _Cohesion score 0.08294930875576037 - nodes in this community are weakly interconnected._
- **Should `News` be split into smaller, more focused modules?**
  _Cohesion score 0.08013937282229965 - nodes in this community are weakly interconnected._
- **Should `Media` be split into smaller, more focused modules?**
  _Cohesion score 0.11965811965811966 - nodes in this community are weakly interconnected._