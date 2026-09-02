# Graph Report - saibatin-laravel  (2026-09-02)

## Corpus Check
- 292 files · ~168,435 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1409 nodes · 2354 edges · 251 communities (65 shown, 72 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 48 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `5b94de5a`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Http\Request
- Permohonan
- CatatanAktivitas
- Media
- News
- devDependencies
- DemografiAdminController
- Balasan
- Dasbor.jsx
- Pemberitahuan
- User
- Illuminate\Database\Eloquent\Relations\BelongsTo
- Layanan
- Notifikasi
- Statistik.jsx
- FotoProfil
- AlasanTolak
- Otp
- ProfilTabs.jsx
- App\Services\Pemberitahuan
- PeranOpdTest
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
- Berita.jsx
- dependencies
- a11y.js
- Navbar.jsx
- JamLayanan
- SkmJawaban
- AppServiceProvider.php
- require-dev
- PengaturanLayanan.jsx
- geo.js
- Illuminate\Database\Eloquent\Model
- App\Models\Wilayah
- config
- require
- Illuminate\Database\Schema\Blueprint
- Grafik.jsx
- LoncengNotifikasi.jsx
- time-picker.jsx
- App\Http\Controllers\Api\Admin\PermohonanAdminController
- api.php
- info.blade.php
- LayoutDashboard.jsx
- tabs.jsx
- mode-edit.js
- statistik-kartu.js
- Skm.jsx
- Profil.jsx
- salin-aset-ocr.mjs
- App\Http\Controllers\Auth\RegisterController
- Periode
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
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Migrations\Migration
- App\Models\Permohonan
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
- README.md
- PermohonanDetail.jsx
- 2026_09_02_100001_tambah_wilayah_akun.php

## God Nodes (most connected - your core abstractions)
1. `Balasan` - 107 edges
2. `Controller` - 64 edges
3. `User` - 60 edges
4. `PresisiMilidetik` - 34 edges
5. `Permohonan` - 32 edges
6. `StatistikExcel` - 32 edges
7. `CatatanAktivitas` - 32 edges
8. `News` - 25 edges
9. `Layanan` - 24 edges
10. `PublikController` - 22 edges

## Surprising Connections (you probably didn't know these)
- `ProfilController` --references--> `FotoProfil`  [EXTRACTED]
  app/Http/Controllers/Api/ProfilController.php → app/Services/FotoProfil.php
- `StatistikEksporController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/StatistikEksporController.php → app/Http/Controllers/Controller.php
- `StatistikController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/StatistikController.php → app/Http/Controllers/Controller.php
- `Permohonan` --mixes_in--> `PresisiMilidetik`  [EXTRACTED]
  app/Models/Permohonan.php → app/Models/Concerns/PresisiMilidetik.php
- `DemografiAdminController` --references--> `DemografiExcel`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/DemografiAdminController.php → app/Services/DemografiExcel.php

## Import Cycles
- None detected.

## Communities (251 total, 72 thin omitted)

### Community 0 - "Illuminate\Http\Request"
Cohesion: 0.11
Nodes (13): BuktiPengaduanController, DemografiController, App\Http\Controllers\Api\NotifikasiController, NotifikasiController, App\Http\Controllers\Api\PendudukController, PendudukController, App\Http\Controllers\Api\ProfilController, ProfilController (+5 more)

### Community 1 - "Permohonan"
Cohesion: 0.07
Nodes (25): App\Http\Controllers\Api\Admin\StatistikEksporController, StatistikEksporController, App\Http\Controllers\Api\StatistikController, StatistikController, JenisPermohonan, Permohonan, DemografiExcel, Spreadsheet (+17 more)

### Community 2 - "CatatanAktivitas"
Cohesion: 0.10
Nodes (10): App\Http\Controllers\Api\Admin\GaleriAdminController, GaleriAdminController, App\Http\Controllers\Api\Admin\KontenStatisController, KontenStatisController, App\Http\Controllers\Api\Admin\MasterController, MasterController, PengaturanController, Gallery (+2 more)

### Community 3 - "Media"
Cohesion: 0.13
Nodes (9): App\Http\Controllers\Api\Admin\MediaController, MediaController, Media, PustakaMedia, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Http\UploadedFile, Illuminate\Support\Str (+1 more)

### Community 4 - "News"
Cohesion: 0.06
Nodes (10): App\Http\Controllers\Api\Admin\BeritaAdminController, BeritaAdminController, App\Http\Controllers\Api\Admin\ProdukAdminController, ProdukAdminController, App\Http\Controllers\Api\KontenController, KontenController, PublikController, News (+2 more)

### Community 5 - "devDependencies"
Cohesion: 0.08
Nodes (25): axios, concurrently, laravel-vite-plugin, devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss (+17 more)

### Community 6 - "DemografiAdminController"
Cohesion: 0.30
Nodes (3): App\Http\Controllers\Api\Admin\DemografiAdminController, DemografiAdminController, DemografiWilayah

### Community 7 - "Balasan"
Cohesion: 0.13
Nodes (6): AspirasiController, App\Http\Controllers\Api\SistemController, SistemController, Wilayah, Balasan, Illuminate\Http\JsonResponse

### Community 8 - "Dasbor.jsx"
Cohesion: 0.10
Nodes (8): FilterPeriode(), geser(), labelAcuan(), nomorHalaman(), Paginasi(), PERIODE, STATUS_FINAL, STATUS_PERMOHONAN

### Community 9 - "Pemberitahuan"
Cohesion: 0.15
Nodes (6): App\Http\Controllers\Api\Admin\PengaduanAdminController, PengaduanAdminController, App\Http\Controllers\Auth\CekStatusController, CekStatusController, Pemberitahuan, StatusAkun

### Community 10 - "User"
Cohesion: 0.10
Nodes (9): App\Models\Concerns\PresisiMilidetik, App\Models\User, User, UserLevel, DatabaseSeeder, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Database\Seeder, Illuminate\Foundation\Auth\User (+1 more)

### Community 11 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.16
Nodes (6): App\Http\Controllers\Api\Admin\LogAktivitasController, LogAktivitasController, App\Models\LogAktivitas, LogAktivitas, TiketPesan, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 13 - "Notifikasi"
Cohesion: 0.14
Nodes (4): App\Models\News, Notifikasi, Tiket, Illuminate\Database\Eloquent\Builder

### Community 14 - "Statistik.jsx"
Cohesion: 0.14
Nodes (9): penanda, angka(), RincianDemografi(), angka(), AngkaNaik(), AWAL, KartuPelayanan(), PetaKantor (+1 more)

### Community 15 - "FotoProfil"
Cohesion: 0.11
Nodes (9): PermohonanPdfController, BerkasController, MediaPublikController, PastikanPeran, FotoProfil, Barryvdh\DomPDF\Facade\Pdf, Closure, Illuminate\Support\Facades\Storage (+1 more)

### Community 17 - "Otp"
Cohesion: 0.13
Nodes (5): App\Http\Controllers\Auth\OtpController, OtpController, Fonnte, Otp, Illuminate\Support\Facades\Cache

### Community 19 - "App\Services\Pemberitahuan"
Cohesion: 0.36
Nodes (8): PermohonanAdminController, UserAdminController, App\Services\CatatanAktivitas, App\Services\FotoProfil, App\Services\Pemberitahuan, App\Services\Surel, Surel, App\Support\StatusAkun

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
Cohesion: 0.25
Nodes (6): HandleInertiaRequests, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets, Inertia\Middleware

### Community 27 - "composer.json"
Cohesion: 0.18
Nodes (10): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type (+2 more)

### Community 28 - "Beranda.jsx"
Cohesion: 0.24
Nodes (8): AKSI_WARNA, BarisProgres(), Beranda(), BULAN, GrafikHarian, PENGADUAN_BAR, persen(), STATUS_BAR

### Community 32 - "Akun.jsx"
Cohesion: 0.18
Nodes (6): FORM_KOSONG, GRUP, INFO_STATUS, KOLOM_TOLAK, STATUS_AKUN, WARNA_PERMOHONAN

### Community 34 - "Berita.jsx"
Cohesion: 0.50
Nodes (4): Berita(), KOSONG, PenyuntingKaya, slugify()

### Community 35 - "dependencies"
Cohesion: 0.22
Nodes (9): class-variance-authority, dependencies, class-variance-authority, @radix-ui/react-navigation-menu, react-organizational-chart, @tiptap/extension-text-align, @radix-ui/react-navigation-menu, react-organizational-chart (+1 more)

### Community 36 - "a11y.js"
Cohesion: 0.22
Nodes (5): FONT_BAWAAN_IDX, KUNCI_A11Y, LANGKAH_FONT, PREFS_BAWAAN, SPASI_MAKS

### Community 38 - "JamLayanan"
Cohesion: 0.17
Nodes (4): LayananController, PengajuanPetugasController, JamLayanan, CarbonImmutable

### Community 39 - "SkmJawaban"
Cohesion: 0.31
Nodes (4): App\Http\Controllers\Api\Admin\SkmAdminController, SkmAdminController, App\Models\SkmJawaban, SkmJawaban

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
Cohesion: 0.17
Nodes (14): App\Http\Controllers\Api\AspirasiController, DashboardController, Berkas, PresisiMilidetik, App\Models\Gallery, App\Models\KritikSaran, KritikSaran, Kunjungan (+6 more)

### Community 46 - "App\Models\Wilayah"
Cohesion: 0.36
Nodes (4): IsiWilayahAkun, App\Models\Wilayah, Illuminate\Console\Command, Illuminate\Support\Collection

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

### Community 54 - "App\Http\Controllers\Api\Admin\PermohonanAdminController"
Cohesion: 0.23
Nodes (10): App\Http\Controllers\Api\Admin\PermohonanAdminController, App\Http\Controllers\Api\PermohonanController, PermohonanController, App\Http\Controllers\Controller, App\Models\JenisPermohonan, App\Services\Recaptcha, App\Support\Balasan, App\Support\Layanan (+2 more)

### Community 55 - "api.php"
Cohesion: 0.22
Nodes (5): App\Http\Controllers\Api\Admin\PengaturanController, PengajuanController, App\Services\JamLayanan, Controller, Illuminate\Support\Facades\Route

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

### Community 64 - "App\Http\Controllers\Auth\RegisterController"
Cohesion: 0.13
Nodes (13): App\Http\Controllers\Auth\LoginController, LoginController, App\Http\Controllers\Auth\RegisterController, RegisterController, App\Http\Controllers\Auth\SandiController, SandiController, App\Http\Controllers\PengajuanPetugasController, Recaptcha (+5 more)

### Community 65 - "Periode"
Cohesion: 0.43
Nodes (4): App\Support\Periode, Periode, CarbonImmutable, Carbon\CarbonImmutable

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

### Community 104 - "App\Models\Permohonan"
Cohesion: 0.29
Nodes (5): App\Models\Permohonan, Illuminate\Foundation\Testing\TestCase, ExampleTest, Tests\TestCase, TestCase

### Community 251 - "README.md"
Cohesion: 0.22
Nodes (8): About Laravel, Code of Conduct, Contributing, Laravel Sponsors, Learning Laravel, License, Premium Partners, Security Vulnerabilities

### Community 253 - "PermohonanDetail.jsx"
Cohesion: 0.33
Nodes (5): ALASAN_TOLAK, perluRincian(), PermohonanDetail(), STATUS_URUT, WAJIB_RINCIAN

## Knowledge Gaps
- **214 isolated node(s):** `bawaan`, `IKON`, `WARNA`, `KATEGORI`, `GRUP` (+209 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 652 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **72 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Http\Request`, `App\Http\Controllers\Auth\RegisterController`, `Permohonan`, `Media`, `App\Models\Permohonan`, `Pemberitahuan`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Illuminate\Database\Eloquent\Model`, `App\Models\Wilayah`, `Notifikasi`, `AlasanTolak`, `App\Services\Pemberitahuan`, `PeranOpdTest`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **Why does `Balasan` connect `Balasan` to `Illuminate\Http\Request`, `Permohonan`, `CatatanAktivitas`, `Media`, `News`, `DemografiAdminController`, `SkmJawaban`, `JamLayanan`, `Pemberitahuan`, `User`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Layanan`, `Illuminate\Database\Eloquent\Model`, `AlasanTolak`, `Otp`, `App\Http\Controllers\Api\Admin\PermohonanAdminController`, `api.php`?**
  _High betweenness centrality (0.021) - this node is a cross-community bridge._
- **Why does `Controller` connect `Illuminate\Http\Request` to `App\Http\Controllers\Auth\RegisterController`, `Permohonan`, `CatatanAktivitas`, `Media`, `News`, `DemografiAdminController`, `SkmJawaban`, `Balasan`, `Pemberitahuan`, `JamLayanan`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Illuminate\Database\Eloquent\Model`, `FotoProfil`, `Otp`, `App\Http\Controllers\Api\Admin\PermohonanAdminController`, `api.php`?**
  _High betweenness centrality (0.019) - this node is a cross-community bridge._
- **Are the 10 inferred relationships involving `Balasan` (e.g. with `.index()` and `.show()`) actually correct?**
  _`Balasan` has 10 INFERRED edges - model-reasoned connections that need verification._
- **What connects `bawaan`, `IKON`, `WARNA` to the rest of the system?**
  _214 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Http\Request` be split into smaller, more focused modules?**
  _Cohesion score 0.10695187165775401 - nodes in this community are weakly interconnected._
- **Should `Permohonan` be split into smaller, more focused modules?**
  _Cohesion score 0.06690140845070422 - nodes in this community are weakly interconnected._