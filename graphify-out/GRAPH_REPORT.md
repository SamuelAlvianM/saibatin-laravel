# Graph Report - saibatin-laravel  (2026-09-02)

## Corpus Check
- 296 files · ~174,355 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1455 nodes · 2475 edges · 253 communities (59 shown, 80 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 15 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `4d3c9950`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Notifikasi
- Permohonan
- Controller
- Media
- PublikController
- devDependencies
- DemografiAdminController
- Illuminate\Http\Request
- Dasbor.jsx
- LayananNonaktifTest
- User
- StatusAkun
- Wilayah
- Statistik.jsx
- Layanan
- UserLevel
- Otp
- ProfilTabs.jsx
- PeranOpdTest
- PengaturanController
- EditorMedan.jsx
- ocr-ktp.js
- scripts
- HANDOFF — SAIBATIN Laravel
- CLAUDE.md
- bootstrap/app.php
- composer.json
- Beranda.jsx
- Akun.jsx
- navigation-menu.jsx
- News
- dependencies
- a11y.js
- Navbar.jsx
- StaticContent
- kategori.js
- AppServiceProvider.php
- require-dev
- PengaturanLayanan.jsx
- geo.js
- Illuminate\Database\Eloquent\Model
- Illuminate\Support\Facades\Schema
- config
- require
- FotoProfil
- Grafik.jsx
- LoncengNotifikasi.jsx
- time-picker.jsx
- PermohonanPdfController.php
- SuntingAkunTest
- info.blade.php
- LayoutDashboard.jsx
- tabs.jsx
- mode-edit.js
- statistik-kartu.js
- Skm.jsx
- Profil.jsx
- salin-aset-ocr.mjs
- JamLayanan
- AlasanTolakPermohonan
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
- Illuminate\Database\Schema\Blueprint
- 2026_09_02_100001_tambah_wilayah_akun.php
- Illuminate\Database\Migrations\Migration
- Periode
- PengajuanPetugasController
- Berita.jsx
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
- waktu.js
- README.md
- PermohonanDetail.jsx

## God Nodes (most connected - your core abstractions)
1. `Balasan` - 112 edges
2. `User` - 73 edges
3. `Controller` - 71 edges
4. `CatatanAktivitas` - 38 edges
5. `PresisiMilidetik` - 38 edges
6. `Permohonan` - 37 edges
7. `StatistikExcel` - 32 edges
8. `Pemberitahuan` - 28 edges
9. `News` - 26 edges
10. `Layanan` - 25 edges

## Surprising Connections (you probably didn't know these)
- `SuntingAkunTest` --references--> `User`  [EXTRACTED]
  tests/Feature/SuntingAkunTest.php → app/Models/User.php
- `NotifikasiController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/NotifikasiController.php → app/Http/Controllers/Controller.php
- `ProfilController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/ProfilController.php → app/Http/Controllers/Controller.php
- `ProfilController` --references--> `FotoProfil`  [EXTRACTED]
  app/Http/Controllers/Api/ProfilController.php → app/Services/FotoProfil.php
- `OtpController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Auth/OtpController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (253 total, 80 thin omitted)

### Community 1 - "Permohonan"
Cohesion: 0.07
Nodes (22): StatistikEksporController, StatistikController, JenisPermohonan, Permohonan, DemografiExcel, Spreadsheet, StreamedResponse, Spreadsheet (+14 more)

### Community 2 - "Controller"
Cohesion: 0.15
Nodes (12): KontenStatisController, MasterController, PengaduanAdminController, PermohonanAdminController, ProdukAdminController, UserAdminController, LayananController, Controller (+4 more)

### Community 3 - "Media"
Cohesion: 0.13
Nodes (8): MediaController, Media, PustakaMedia, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Http\UploadedFile, Illuminate\Support\Str, static

### Community 4 - "PublikController"
Cohesion: 0.09
Nodes (3): PublikController, Produk, Konten

### Community 5 - "devDependencies"
Cohesion: 0.08
Nodes (25): axios, concurrently, laravel-vite-plugin, devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss (+17 more)

### Community 6 - "DemografiAdminController"
Cohesion: 0.23
Nodes (3): DemografiAdminController, DemografiController, DemografiWilayah

### Community 7 - "Illuminate\Http\Request"
Cohesion: 0.09
Nodes (10): LogAktivitasController, AspirasiController, BuktiPengaduanController, PendudukController, ProfilController, SistemController, ProfilPageController, Balasan (+2 more)

### Community 8 - "Dasbor.jsx"
Cohesion: 0.11
Nodes (8): FilterPeriode(), geser(), labelAcuan(), nomorHalaman(), Paginasi(), PERIODE, STATUS_FINAL, STATUS_PERMOHONAN

### Community 10 - "User"
Cohesion: 0.10
Nodes (6): User, DatabaseSeeder, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Database\Seeder, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 11 - "StatusAkun"
Cohesion: 0.10
Nodes (7): CekStatusController, AlasanTolak, StatusAkun, Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\Mail, Illuminate\Validation\ValidationException, Inertia\Inertia

### Community 12 - "Wilayah"
Cohesion: 0.23
Nodes (4): IsiWilayahAkun, Wilayah, Illuminate\Console\Command, Illuminate\Support\Collection

### Community 14 - "Statistik.jsx"
Cohesion: 0.14
Nodes (9): penanda, angka(), RincianDemografi(), angka(), AngkaNaik(), AWAL, KartuPelayanan(), PetaKantor (+1 more)

### Community 16 - "UserLevel"
Cohesion: 0.20
Nodes (4): UserLevel, Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 17 - "Otp"
Cohesion: 0.11
Nodes (6): OtpController, Fonnte, Otp, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 21 - "EditorMedan.jsx"
Cohesion: 0.25
Nodes (3): BarisItem(), panjang(), PenyuntingKaya

### Community 22 - "ocr-ktp.js"
Cohesion: 0.29
Nodes (12): ambilWorker(), bacaKtp(), denganTimeout(), muatGambar(), nikSah(), normalkanDigit(), OcrGagal, persentil() (+4 more)

### Community 23 - "scripts"
Cohesion: 0.17
Nodes (12): scripts, dev, post-create-project-cmd, post-root-package-install, post-update-cmd, Composer\\Config::disableProcessTimeout, npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite, @php artisan key:generate --ansi (+4 more)

### Community 24 - "HANDOFF — SAIBATIN Laravel"
Cohesion: 0.05
Nodes (36): 1. Apa ini, 2. Cara menjalankan, 3. Keputusan arsitektur yang SUDAH DIKUNCI, 4. Yang sudah jadi, 5. 🔴 Dua puluh lima jebakan yang SUDAH memakan waktu — jangan diulang, 6. Aturan keras (warisan journal workspace), 7. Temuan yang masih menunggu keputusan user, 8. Berkas sementara yang HARUS dibuang nanti (+28 more)

### Community 26 - "bootstrap/app.php"
Cohesion: 0.22
Nodes (6): HandleInertiaRequests, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets, Inertia\Middleware

### Community 27 - "composer.json"
Cohesion: 0.18
Nodes (10): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type (+2 more)

### Community 28 - "Beranda.jsx"
Cohesion: 0.24
Nodes (8): AKSI_WARNA, BarisProgres(), Beranda(), BULAN, GrafikHarian, PENGADUAN_BAR, persen(), STATUS_BAR

### Community 32 - "Akun.jsx"
Cohesion: 0.13
Nodes (8): FORM_KOSONG, GRUP, INFO_STATUS, KOLOM_TOLAK, MASUK_NIK, STATUS_AKUN, WAJIB_WILAYAH, WARNA_PERMOHONAN

### Community 34 - "News"
Cohesion: 0.13
Nodes (5): BeritaAdminController, GaleriAdminController, KontenController, Gallery, News

### Community 35 - "dependencies"
Cohesion: 0.22
Nodes (9): class-variance-authority, dependencies, class-variance-authority, @radix-ui/react-navigation-menu, react-organizational-chart, @tiptap/extension-text-align, @radix-ui/react-navigation-menu, react-organizational-chart (+1 more)

### Community 36 - "a11y.js"
Cohesion: 0.22
Nodes (5): FONT_BAWAAN_IDX, KUNCI_A11Y, LANGKAH_FONT, PREFS_BAWAAN, SPASI_MAKS

### Community 40 - "AppServiceProvider.php"
Cohesion: 0.33
Nodes (4): AppServiceProvider, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\View, Illuminate\Support\ServiceProvider

### Community 41 - "require-dev"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 44 - "geo.js"
Cohesion: 0.32
Nodes (7): ALIAS, geoWilayah(), KECAMATAN_GEO, norm(), PETA_NAMA, PUSAT_PETA, ZOOM_AWAL

### Community 45 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.06
Nodes (17): SkmAdminController, BerkasController, DashboardController, Berkas, PresisiMilidetik, KritikSaran, Kunjungan, LogAktivitas (+9 more)

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

### Community 54 - "PermohonanPdfController.php"
Cohesion: 0.12
Nodes (8): PermohonanPdfController, UnggahController, MediaPublikController, PastikanPeran, Barryvdh\DomPDF\Facade\Pdf, Closure, Illuminate\Support\Facades\Storage, Symfony\Component\HttpFoundation\Response

### Community 56 - "info.blade.php"
Cohesion: 0.33
Nodes (5): publik.partials.faq, publik.partials.berkas, publik.partials.blok-isi, publik.partials.dokumen-edit, publik.partials.subnav

### Community 57 - "LayoutDashboard.jsx"
Cohesion: 0.36
Nodes (7): aktifkan(), GRUP, GRUP_PEMOHON, grupUntuk(), ItemMenu(), KOLOM_BILAH, LayoutDashboard()

### Community 60 - "statistik-kartu.js"
Cohesion: 0.33
Nodes (3): KARTU_BAWAAN, LABEL_KOLOM, WARNA_PRESET

### Community 61 - "Skm.jsx"
Cohesion: 0.47
Nodes (3): mutu(), Skm(), warnaNilai()

### Community 63 - "salin-aset-ocr.mjs"
Cohesion: 0.33
Nodes (5): akar, berkas, hilang, tessdata, tujuan

### Community 64 - "JamLayanan"
Cohesion: 0.09
Nodes (9): App\Http\Controllers\Api\Admin\PengaturanController, PermohonanController, LoginController, RegisterController, SandiController, JamLayanan, CarbonImmutable, Recaptcha (+1 more)

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

### Community 102 - "Periode"
Cohesion: 0.43
Nodes (3): Periode, CarbonImmutable, Carbon\CarbonImmutable

### Community 103 - "PengajuanPetugasController"
Cohesion: 0.47
Nodes (4): PengajuanPetugasController, App\Services\JamLayanan, App\Support\Layanan, Controller

### Community 108 - "Berita.jsx"
Cohesion: 0.50
Nodes (4): Berita(), KOSONG, PenyuntingKaya, slugify()

### Community 251 - "README.md"
Cohesion: 0.22
Nodes (8): About Laravel, Code of Conduct, Contributing, Laravel Sponsors, Learning Laravel, License, Premium Partners, Security Vulnerabilities

### Community 253 - "PermohonanDetail.jsx"
Cohesion: 0.33
Nodes (5): ALASAN_TOLAK, perluRincian(), PermohonanDetail(), STATUS_URUT, WAJIB_RINCIAN

## Knowledge Gaps
- **219 isolated node(s):** `WARNA_KATEGORI`, `WARNA_NETRAL`, `bawaan`, `IKON`, `WARNA` (+214 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 668 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **80 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `JamLayanan`, `Notifikasi`, `Controller`, `Media`, `Permohonan`, `Illuminate\Http\Request`, `LayananNonaktifTest`, `StatusAkun`, `Wilayah`, `Illuminate\Database\Eloquent\Model`, `UserLevel`, `Otp`, `PeranOpdTest`, `PermohonanPdfController.php`, `SuntingAkunTest`?**
  _High betweenness centrality (0.054) - this node is a cross-community bridge._
- **Why does `Balasan` connect `Illuminate\Http\Request` to `JamLayanan`, `AlasanTolakPermohonan`, `News`, `Controller`, `PublikController`, `Media`, `StaticContent`, `DemografiAdminController`, `Permohonan`, `Notifikasi`, `User`, `StatusAkun`, `Wilayah`, `Illuminate\Database\Eloquent\Model`, `UserLevel`, `Otp`, `PengaturanController`, `PermohonanPdfController.php`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **Why does `Controller` connect `Controller` to `JamLayanan`, `Permohonan`, `News`, `Media`, `Notifikasi`, `PublikController`, `DemografiAdminController`, `Illuminate\Http\Request`, `StatusAkun`, `Illuminate\Database\Eloquent\Model`, `Otp`, `PengaturanController`, `PermohonanPdfController.php`?**
  _High betweenness centrality (0.028) - this node is a cross-community bridge._
- **What connects `WARNA_KATEGORI`, `WARNA_NETRAL`, `bawaan` to the rest of the system?**
  _219 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Permohonan` be split into smaller, more focused modules?**
  _Cohesion score 0.07236544549977386 - nodes in this community are weakly interconnected._
- **Should `Media` be split into smaller, more focused modules?**
  _Cohesion score 0.12615384615384614 - nodes in this community are weakly interconnected._
- **Should `PublikController` be split into smaller, more focused modules?**
  _Cohesion score 0.0907258064516129 - nodes in this community are weakly interconnected._