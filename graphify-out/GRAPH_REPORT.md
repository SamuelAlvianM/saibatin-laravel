# Graph Report - saibatin-laravel  (2026-09-06)

## Corpus Check
- 304 files · ~185,232 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1517 nodes · 2574 edges · 260 communities (63 shown, 77 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS · INFERRED: 10 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `473d9175`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Balasan
- Permohonan
- UserAdminController
- PustakaMedia
- PublikController
- devDependencies
- Illuminate\Database\Eloquent\Model
- StatistikExcel.php
- Dasbor.jsx
- Notifikasi
- SuntingAkunTest
- User
- Statistik.jsx
- News
- Illuminate\Database\Schema\Blueprint
- Otp
- ProfilTabs.jsx
- JamLayanan
- EditorMedan.jsx
- ocr-ktp.js
- scripts
- HANDOFF — SAIBATIN Laravel
- CLAUDE.md
- Illuminate\Support\Facades\Schema
- composer.json
- Beranda.jsx
- Akun.jsx
- navigation-menu.jsx
- Pemberitahuan
- dependencies
- a11y.js
- Navbar.jsx
- Illuminate\Http\Request
- kategori.js
- StaticContent
- require-dev
- PengaturanLayanan.jsx
- geo.js
- DemografiWilayah
- config
- require
- StatusAkun
- Grafik.jsx
- LoncengNotifikasi.jsx
- time-picker.jsx
- BerkasController.php
- periode.js
- info.blade.php
- LayoutDashboard.jsx
- tabs.jsx
- mode-edit.js
- statistik-kartu.js
- Skm.jsx
- Profil.jsx
- salin-aset-ocr.mjs
- Layanan
- Illuminate\Database\Migrations\Migration
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
- PeranOpdTest
- Wilayah
- Controller
- PencacahKunjungan
- BuktiPengaduanTest
- PermohonanPdfController
- UserLevel
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
- CatatanAktivitas
- deteksi-kategori.js

## God Nodes (most connected - your core abstractions)
1. `Balasan` - 113 edges
2. `User` - 75 edges
3. `Controller` - 72 edges
4. `PresisiMilidetik` - 38 edges
5. `CatatanAktivitas` - 38 edges
6. `Permohonan` - 37 edges
7. `StatistikExcel` - 32 edges
8. `Pemberitahuan` - 28 edges
9. `News` - 26 edges
10. `Layanan` - 26 edges

## Surprising Connections (you probably didn't know these)
- `SuntingAkunTest` --references--> `User`  [EXTRACTED]
  tests/Feature/SuntingAkunTest.php → app/Models/User.php
- `BeritaAdminController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/BeritaAdminController.php → app/Http/Controllers/Controller.php
- `BeritaAdminController` --references--> `CatatanAktivitas`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/BeritaAdminController.php → app/Services/CatatanAktivitas.php
- `DemografiAdminController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/DemografiAdminController.php → app/Http/Controllers/Controller.php
- `DemografiAdminController` --references--> `CatatanAktivitas`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/DemografiAdminController.php → app/Services/CatatanAktivitas.php

## Import Cycles
- None detected.

## Communities (260 total, 77 thin omitted)

### Community 0 - "Balasan"
Cohesion: 0.10
Nodes (6): KontenController, ProfilController, SistemController, JenisPermohonan, Balasan, Illuminate\Http\JsonResponse

### Community 1 - "Permohonan"
Cohesion: 0.12
Nodes (12): Permohonan, DemografiExcel, Spreadsheet, StreamedResponse, Carbon, Spreadsheet, StreamedResponse, StatistikExcel (+4 more)

### Community 2 - "UserAdminController"
Cohesion: 0.14
Nodes (3): UserAdminController, Surel, AlasanTolak

### Community 3 - "PustakaMedia"
Cohesion: 0.21
Nodes (4): PustakaMedia, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Cookie, Illuminate\Support\Str

### Community 4 - "PublikController"
Cohesion: 0.11
Nodes (3): PublikController, Produk, Konten

### Community 5 - "devDependencies"
Cohesion: 0.08
Nodes (25): axios, concurrently, laravel-vite-plugin, devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss (+17 more)

### Community 6 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.14
Nodes (8): Berkas, PresisiMilidetik, Kunjungan, LogAktivitas, Media, TiketPesan, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 7 - "StatistikExcel.php"
Cohesion: 0.11
Nodes (11): Gallery, KritikSaran, Pengaduan, SkmJawaban, PhpOffice\PhpSpreadsheet\Cell\Coordinate, PhpOffice\PhpSpreadsheet\Cell\DataType, PhpOffice\PhpSpreadsheet\Style\Alignment, PhpOffice\PhpSpreadsheet\Style\Border (+3 more)

### Community 8 - "Dasbor.jsx"
Cohesion: 0.11
Nodes (8): FilterPeriode(), geser(), labelAcuan(), nomorHalaman(), Paginasi(), PERIODE, STATUS_FINAL, STATUS_PERMOHONAN

### Community 9 - "Notifikasi"
Cohesion: 0.14
Nodes (3): Notifikasi, Tiket, Illuminate\Database\Eloquent\Builder

### Community 11 - "User"
Cohesion: 0.13
Nodes (6): User, DatabaseSeeder, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Database\Seeder, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 14 - "Statistik.jsx"
Cohesion: 0.13
Nodes (9): penanda, angka(), RincianDemografi(), angka(), AngkaNaik(), AWAL, KartuPelayanan(), PetaKantor (+1 more)

### Community 17 - "Otp"
Cohesion: 0.09
Nodes (5): RegisterController, Fonnte, FotoProfil, Otp, Illuminate\Support\Facades\Mail

### Community 20 - "JamLayanan"
Cohesion: 0.13
Nodes (5): PengaturanController, LayananController, PengajuanController, PengajuanPetugasController, JamLayanan

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

### Community 26 - "Illuminate\Support\Facades\Schema"
Cohesion: 0.29
Nodes (5): AppServiceProvider, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Schema, Illuminate\Support\Facades\View, Illuminate\Support\ServiceProvider

### Community 27 - "composer.json"
Cohesion: 0.18
Nodes (10): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type (+2 more)

### Community 28 - "Beranda.jsx"
Cohesion: 0.22
Nodes (8): AKSI_WARNA, BarisProgres(), Beranda(), BULAN, GrafikHarian, PENGADUAN_BAR, persen(), STATUS_BAR

### Community 32 - "Akun.jsx"
Cohesion: 0.13
Nodes (8): FORM_KOSONG, GRUP, INFO_STATUS, KOLOM_TOLAK, MASUK_NIK, STATUS_AKUN, WAJIB_WILAYAH, WARNA_PERMOHONAN

### Community 34 - "Pemberitahuan"
Cohesion: 0.25
Nodes (3): PengaduanAdminController, PermohonanController, Pemberitahuan

### Community 35 - "dependencies"
Cohesion: 0.22
Nodes (9): class-variance-authority, dependencies, class-variance-authority, @radix-ui/react-navigation-menu, react-organizational-chart, @tiptap/extension-text-align, @radix-ui/react-navigation-menu, react-organizational-chart (+1 more)

### Community 36 - "a11y.js"
Cohesion: 0.22
Nodes (5): FONT_BAWAAN_IDX, KUNCI_A11Y, LANGKAH_FONT, PREFS_BAWAAN, SPASI_MAKS

### Community 38 - "Illuminate\Http\Request"
Cohesion: 0.12
Nodes (7): AspirasiController, NotifikasiController, OtpController, SandiController, ProfilPageController, Illuminate\Http\Request, Illuminate\Support\Facades\Cache

### Community 40 - "StaticContent"
Cohesion: 0.06
Nodes (11): HandleInertiaRequests, StaticContent, CarbonImmutable, CarbonImmutable, Carbon\CarbonImmutable, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware (+3 more)

### Community 41 - "require-dev"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 44 - "geo.js"
Cohesion: 0.32
Nodes (7): ALIAS, geoWilayah(), KECAMATAN_GEO, norm(), PETA_NAMA, PUSAT_PETA, ZOOM_AWAL

### Community 45 - "DemografiWilayah"
Cohesion: 0.07
Nodes (11): DemografiAdminController, DemografiController, Carbon, StatistikController, DemografiWilayah, PeriodeDemografi, UserFactory, Illuminate\Database\Eloquent\Factories\Factory (+3 more)

### Community 47 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 48 - "require"
Cohesion: 0.29
Nodes (7): require, barryvdh/laravel-dompdf, inertiajs/inertia-laravel, laravel/framework, laravel/tinker, php, phpoffice/phpspreadsheet

### Community 50 - "StatusAkun"
Cohesion: 0.12
Nodes (8): CekStatusController, LoginController, StatusAkun, Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\Route, Illuminate\Validation\ValidationException, Inertia\Inertia, Inertia\Response

### Community 51 - "Grafik.jsx"
Cohesion: 0.43
Nodes (4): dasar(), GrafikGaris(), GrafikPeringkat(), GrafikTren()

### Community 52 - "LoncengNotifikasi.jsx"
Cohesion: 0.52
Nodes (6): ambilKonteks(), bukaAudio(), bunyikan(), LoncengNotifikasi(), TIPE_IKON, waktuRelatif()

### Community 53 - "time-picker.jsx"
Cohesion: 0.38
Nodes (5): HOURS, isJam(), masker(), MINUTES, TimePicker()

### Community 54 - "BerkasController.php"
Cohesion: 0.23
Nodes (5): BerkasController, MediaPublikController, PastikanPeran, Closure, Symfony\Component\HttpFoundation\Response

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

### Community 64 - "Layanan"
Cohesion: 0.08
Nodes (4): PermohonanAdminController, AlasanTolakPermohonan, Layanan, Periode

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
Cohesion: 0.53
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

### Community 101 - "Wilayah"
Cohesion: 0.22
Nodes (5): BuatAkunUji, IsiWilayahAkun, Wilayah, Illuminate\Console\Command, Illuminate\Support\Collection

### Community 102 - "Controller"
Cohesion: 0.11
Nodes (9): LogAktivitasController, SkmAdminController, StatistikEksporController, BuktiPengaduanController, PendudukController, UnggahController, Controller, Barryvdh\DomPDF\Facade\Pdf (+1 more)

### Community 109 - "UserLevel"
Cohesion: 0.14
Nodes (6): UserLevel, Illuminate\Foundation\Testing\TestCase, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log, ExampleTest, TestCase

### Community 251 - "README.md"
Cohesion: 0.22
Nodes (8): About Laravel, Code of Conduct, Contributing, Laravel Sponsors, Learning Laravel, License, Premium Partners, Security Vulnerabilities

### Community 253 - "PermohonanDetail.jsx"
Cohesion: 0.33
Nodes (5): ALASAN_TOLAK, perluRincian(), PermohonanDetail(), STATUS_URUT, WAJIB_RINCIAN

### Community 254 - "CatatanAktivitas"
Cohesion: 0.09
Nodes (6): GaleriAdminController, KontenStatisController, MasterController, MediaController, ProdukAdminController, CatatanAktivitas

## Knowledge Gaps
- **222 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+217 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 695 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **77 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Balasan`, `Permohonan`, `Pemberitahuan`, `UserAdminController`, `PustakaMedia`, `Wilayah`, `Controller`, `Illuminate\Http\Request`, `StatistikExcel.php`, `Notifikasi`, `Illuminate\Database\Eloquent\Model`, `StaticContent`, `PeranOpdTest`, `UserLevel`, `SuntingAkunTest`, `Otp`, `StatusAkun`, `CatatanAktivitas`?**
  _High betweenness centrality (0.056) - this node is a cross-community bridge._
- **Why does `Controller` connect `Controller` to `Layanan`, `Balasan`, `Pemberitahuan`, `UserAdminController`, `Permohonan`, `PublikController`, `Illuminate\Http\Request`, `PencacahKunjungan`, `PermohonanPdfController`, `DemografiWilayah`, `News`, `Otp`, `StatusAkun`, `JamLayanan`, `BerkasController.php`, `CatatanAktivitas`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **Why does `Balasan` connect `Balasan` to `Layanan`, `Permohonan`, `Pemberitahuan`, `UserAdminController`, `Controller`, `Illuminate\Http\Request`, `DemografiWilayah`, `News`, `StatusAkun`, `JamLayanan`, `CatatanAktivitas`?**
  _High betweenness centrality (0.025) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _222 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Balasan` be split into smaller, more focused modules?**
  _Cohesion score 0.09848484848484848 - nodes in this community are weakly interconnected._
- **Should `Permohonan` be split into smaller, more focused modules?**
  _Cohesion score 0.11655874190564293 - nodes in this community are weakly interconnected._
- **Should `UserAdminController` be split into smaller, more focused modules?**
  _Cohesion score 0.13725490196078433 - nodes in this community are weakly interconnected._