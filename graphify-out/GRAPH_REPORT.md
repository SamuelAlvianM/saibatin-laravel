# Graph Report - saibatin-laravel  (2026-10-08)

## Corpus Check
- 332 files · ~212,118 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1729 nodes · 2970 edges · 292 communities (80 shown, 89 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 15 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `0502aa25`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Gallery
- StatistikExcel
- Illuminate\Http\Request
- Otp
- KategoriDemografi
- devDependencies
- Balasan
- SuntingAkunTest
- Dasbor.jsx
- Permohonan
- User
- StatistikKependudukan
- Illuminate\Database\Migrations\Migration
- Layanan
- Statistik.jsx
- DemografiExcel
- CatatanAktivitas
- Illuminate\Database\Eloquent\Model
- ProfilTabs.jsx
- Illuminate\Database\Schema\Blueprint
- StatusAkun
- EditorMedan.jsx
- ocr-ktp.js
- scripts
- Journal — SAIBATIN-LARAVEL (`saibatin-laravel`)
- CLAUDE.md
- RegisterController.php
- composer.json
- Beranda.jsx
- Akun.jsx
- navigation-menu.jsx
- bangun-peta.mjs
- dependencies
- a11y.js
- Navbar.jsx
- kategori.js
- Wilayah
- require-dev
- PengaturanLayanan.jsx
- geo.js
- News
- config
- require
- Illuminate\Database\Eloquent\Relations\BelongsTo
- API Aplikasi Mobile — SAIBATIN
- Grafik.jsx
- LoncengNotifikasi.jsx
- time-picker.jsx
- UserLevel
- periode.js
- info.blade.php
- LayoutDashboard.jsx
- tabs.jsx
- mode-edit.js
- statistik-kartu.js
- Skm.jsx
- Profil.jsx
- salin-aset-ocr.mjs
- Illuminate\Support\Facades\Schema
- DemografiAdminController
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
- Konten
- Notifikasi
- Periode
- JamLayanan
- .__invoke
- Illuminate\Database\Eloquent\Relations\HasMany
- PublikController
- PeranOpdTest
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
- DemografiWilayah
- highcharts
- TestCase
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
- Produk
- react-dropzone
- react-leaflet
- grafik-dasar.js
- PublikController.php
- tesseract.js
- AppServiceProvider.php
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
- PermohonanPdfController
- README.md
- Controller
- PermohonanDetail.jsx
- Media
- FotoProfil
- deteksi-kategori.js
- peta-wilayah.js
- LoginController
- 2. Login & sesi
- tabel-demografi.js
- 5. Mengajukan permohonan (warga, OPD, petugas)
- web.php
- keywords
- PilihanDropdown
- FilterStatistik
- date-fns
- framer-motion
- highcharts-react-official
- react-dom
- Berita.jsx
- sonner
- tailwind-merge
- @tiptap/extension-image
- pdf.statistik-tabel
- Tiket
- .ringkasan
- StatistikExcel.php

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
- `GaleriAdminController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/GaleriAdminController.php → app/Http/Controllers/Controller.php
- `GaleriAdminController` --references--> `CatatanAktivitas`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/GaleriAdminController.php → app/Services/CatatanAktivitas.php
- `Gallery` --mixes_in--> `PresisiMilidetik`  [EXTRACTED]
  app/Models/Gallery.php → app/Models/Concerns/PresisiMilidetik.php
- `StatistikEksporController` --references--> `StatistikExcel`  [EXTRACTED]
  app/Http/Controllers/Api/Admin/StatistikEksporController.php → app/Services/StatistikExcel.php

## Import Cycles
- None detected.

## Communities (292 total, 89 thin omitted)

### Community 1 - "StatistikExcel"
Cohesion: 0.24
Nodes (5): Carbon, Spreadsheet, Worksheet, StatistikExcel, Illuminate\Support\Carbon

### Community 2 - "Illuminate\Http\Request"
Cohesion: 0.17
Nodes (8): ProfilController, MediaPublikController, PakaiTokenMobile, PastikanPeran, Closure, Illuminate\Http\Request, Illuminate\Support\Facades\Auth, Symfony\Component\HttpFoundation\Response

### Community 3 - "Otp"
Cohesion: 0.15
Nodes (4): Fonnte, Otp, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 4 - "KategoriDemografi"
Cohesion: 0.11
Nodes (3): KategoriDemografiController, StaticContent, KategoriDemografi

### Community 5 - "devDependencies"
Cohesion: 0.08
Nodes (25): axios, concurrently, laravel-vite-plugin, devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss (+17 more)

### Community 6 - "Balasan"
Cohesion: 0.12
Nodes (5): AspirasiController, AuthMobileController, SistemController, Balasan, Illuminate\Http\JsonResponse

### Community 7 - "SuntingAkunTest"
Cohesion: 0.18
Nodes (3): HandleInertiaRequests, Inertia\Middleware, SuntingAkunTest

### Community 8 - "Dasbor.jsx"
Cohesion: 0.11
Nodes (8): FilterPeriode(), geser(), labelAcuan(), nomorHalaman(), Paginasi(), PERIODE, STATUS_FINAL, STATUS_PERMOHONAN

### Community 9 - "Permohonan"
Cohesion: 0.13
Nodes (5): PermohonanAdminController, PermohonanController, JenisPermohonan, Permohonan, AlasanTolakPermohonan

### Community 10 - "User"
Cohesion: 0.10
Nodes (7): UserAdminController, User, AlasanTolak, DatabaseSeeder, Illuminate\Database\Seeder, Illuminate\Foundation\Auth\User, Illuminate\Support\Facades\Hash

### Community 13 - "Layanan"
Cohesion: 0.11
Nodes (3): LayananController, Layanan, Illuminate\Support\Facades\Storage

### Community 14 - "Statistik.jsx"
Cohesion: 0.13
Nodes (9): penanda, angka(), RincianDemografi(), angka(), AngkaNaik(), AWAL, KartuPelayanan(), PetaKantor (+1 more)

### Community 15 - "DemografiExcel"
Cohesion: 0.13
Nodes (7): DemografiExcel, Spreadsheet, StreamedResponse, Worksheet, StreamedResponse, PhpOffice\PhpSpreadsheet\IOFactory, PhpOffice\PhpSpreadsheet\Spreadsheet

### Community 16 - "CatatanAktivitas"
Cohesion: 0.19
Nodes (5): MasterController, PengaduanAdminController, CatatanAktivitas, Pemberitahuan, Surel

### Community 17 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.20
Nodes (5): PresisiMilidetik, KritikSaran, Kunjungan, Pengaduan, Illuminate\Database\Eloquent\Model

### Community 21 - "EditorMedan.jsx"
Cohesion: 0.25
Nodes (3): BarisItem(), panjang(), PenyuntingKaya

### Community 22 - "ocr-ktp.js"
Cohesion: 0.29
Nodes (12): ambilWorker(), bacaKtp(), denganTimeout(), muatGambar(), nikSah(), normalkanDigit(), OcrGagal, persentil() (+4 more)

### Community 23 - "scripts"
Cohesion: 0.13
Nodes (15): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+7 more)

### Community 24 - "Journal — SAIBATIN-LARAVEL (`saibatin-laravel`)"
Cohesion: 0.14
Nodes (14): 1. Identitas project, 2. Keputusan yang sudah dikunci user — jangan dibuka ulang, 3. Kemajuan, 4. Angka acuan — **jangan diukur ulang**, 4b. Kuesioner SKM resmi dinas (18 Agu) — 9 unsur → 16 pertanyaan, 4c. Cutover ke cPanel — paket siap, server belum disentuh (18 Agu), 5. Antrean, 6. Referensi (+6 more)

### Community 26 - "RegisterController.php"
Cohesion: 0.14
Nodes (9): KlienMobile, Illuminate\Auth\AuthenticationException, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets, Illuminate\Support\Facades\Mail, Illuminate\Validation\ValidationException (+1 more)

### Community 27 - "composer.json"
Cohesion: 0.18
Nodes (10): autoload-dev, psr-4, description, license, minimum-stability, name, prefer-stable, Tests\\ (+2 more)

### Community 28 - "Beranda.jsx"
Cohesion: 0.24
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

### Community 40 - "Wilayah"
Cohesion: 0.20
Nodes (5): BuatAkunUji, IsiWilayahAkun, Wilayah, Illuminate\Console\Command, Illuminate\Support\Collection

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

### Community 49 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.17
Nodes (4): BerkasController, Berkas, TiketPesan, Illuminate\Database\Eloquent\Relations\BelongsTo

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

### Community 54 - "UserLevel"
Cohesion: 0.18
Nodes (4): ProfilPageController, LogAktivitas, UserLevel, Inertia\Inertia

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

### Community 105 - "Periode"
Cohesion: 0.25
Nodes (4): LogAktivitasController, Periode, CarbonImmutable, Carbon\CarbonImmutable

### Community 107 - "JamLayanan"
Cohesion: 0.11
Nodes (6): PengaturanController, LayananMobileController, PengajuanController, PengajuanPetugasController, JamLayanan, CarbonImmutable

### Community 112 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.18
Nodes (3): Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Notifications\Notifiable, Laravel\Sanctum\HasApiTokens

### Community 125 - "Demografi.jsx"
Cohesion: 0.83
Nodes (3): Demografi(), unduh(), usulJudul()

### Community 134 - "peta-label.js"
Cohesion: 0.14
Nodes (8): GARIS_DARAT_TETANGGA, LABEL_KECAMATAN, LABEL_TETANGGA, SKALA_WARNA, WARNA_DARAT_TETANGGA, WARNA_LAUT, WARNA_SOROT, WARNA_TANPA_DATA

### Community 135 - "DemografiWilayah"
Cohesion: 0.11
Nodes (5): DemografiController, DemografiWilayah, PeriodeDemografi, Illuminate\Support\Facades\DB, PeriodeDemografiTest

### Community 137 - "TestCase"
Cohesion: 0.07
Nodes (10): Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, Illuminate\Foundation\Testing\TestCase, Laravel\Sanctum\Http\Middleware\AuthenticateSession, Laravel\Sanctum\Sanctum, ApiMobileTest, BuktiPengaduanTest, ExampleTest (+2 more)

### Community 151 - "grafik-dasar.js"
Cohesion: 0.22
Nodes (6): BIRU, BIRU_MUDA, dasar, FONT, KUNING, REDUP

### Community 154 - "AppServiceProvider.php"
Cohesion: 0.29
Nodes (5): AppServiceProvider, Illuminate\Cache\RateLimiting\Limit, Illuminate\Support\Facades\RateLimiter, Illuminate\Support\Facades\View, Illuminate\Support\ServiceProvider

### Community 251 - "README.md"
Cohesion: 0.22
Nodes (8): About Laravel, Code of Conduct, Contributing, Laravel Sponsors, Learning Laravel, License, Premium Partners, Security Vulnerabilities

### Community 252 - "Controller"
Cohesion: 0.09
Nodes (8): KontenStatisController, SkmAdminController, StatistikEksporController, BuktiPengaduanController, KontenController, PendudukController, UnggahController, Controller

### Community 253 - "PermohonanDetail.jsx"
Cohesion: 0.33
Nodes (5): ALASAN_TOLAK, perluRincian(), PermohonanDetail(), STATUS_URUT, WAJIB_RINCIAN

### Community 254 - "Media"
Cohesion: 0.10
Nodes (9): MediaController, Media, PustakaMedia, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Http\UploadedFile, Illuminate\Support\Str, RuntimeException (+1 more)

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

### Community 264 - "web.php"
Cohesion: 0.16
Nodes (4): OtpController, SandiController, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\Route

### Community 265 - "keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

### Community 269 - "PilihanDropdown"
Cohesion: 0.67
Nodes (3): PilihanDropdown(), pilih(), tombol()

### Community 275 - "Berita.jsx"
Cohesion: 0.50
Nodes (4): Berita(), KOSONG, PenyuntingKaya, slugify()

### Community 288 - ".ringkasan"
Cohesion: 0.22
Nodes (3): DashboardController, PencacahKunjungan, Illuminate\Support\Facades\Cookie

### Community 301 - "StatistikExcel.php"
Cohesion: 0.14
Nodes (8): SkmJawaban, PhpOffice\PhpSpreadsheet\Cell\Coordinate, PhpOffice\PhpSpreadsheet\Cell\DataType, PhpOffice\PhpSpreadsheet\Style\Alignment, PhpOffice\PhpSpreadsheet\Style\Border, PhpOffice\PhpSpreadsheet\Style\Fill, PhpOffice\PhpSpreadsheet\Worksheet\Drawing, PhpOffice\PhpSpreadsheet\Worksheet\PageSetup

## Knowledge Gaps
- **268 isolated node(s):** `1. Identitas project`, `2. Keputusan yang sudah dikunci user — jangan dibuka ulang`, `3. Kemajuan`, `4. Angka acuan — **jangan diukur ulang**`, `4b. Kuesioner SKM resmi dinas (18 Agu) — 9 unsur → 16 pertanyaan` (+263 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 799 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **89 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `StatistikExcel`, `Illuminate\Http\Request`, `Otp`, `LoginController`, `Balasan`, `SuntingAkunTest`, `web.php`, `TestCase`, `Permohonan`, `CatatanAktivitas`, `Illuminate\Database\Eloquent\Model`, `StatusAkun`, `RegisterController.php`, `Tiket`, `.ringkasan`, `Wilayah`, `StatistikExcel.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `UserLevel`, `Notifikasi`, `Illuminate\Database\Eloquent\Relations\HasMany`, `PeranOpdTest`, `Controller`, `Media`, `FotoProfil`?**
  _High betweenness centrality (0.044) - this node is a cross-community bridge._
- **Why does `Balasan` connect `Balasan` to `Gallery`, `Illuminate\Http\Request`, `KategoriDemografi`, `DemografiWilayah`, `web.php`, `Permohonan`, `User`, `Layanan`, `CatatanAktivitas`, `Illuminate\Database\Eloquent\Model`, `Produk`, `StatusAkun`, `RegisterController.php`, `.ringkasan`, `News`, `StatistikExcel.php`, `UserLevel`, `DemografiAdminController`, `Notifikasi`, `Periode`, `JamLayanan`, `.__invoke`, `Controller`, `Media`, `FotoProfil`?**
  _High betweenness centrality (0.028) - this node is a cross-community bridge._
- **Why does `Controller` connect `Controller` to `Gallery`, `Illuminate\Http\Request`, `KategoriDemografi`, `LoginController`, `Balasan`, `DemografiWilayah`, `web.php`, `Permohonan`, `User`, `Layanan`, `CatatanAktivitas`, `Illuminate\Database\Eloquent\Model`, `Produk`, `StatusAkun`, `PublikController.php`, `RegisterController.php`, `.ringkasan`, `News`, `StatistikExcel.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `UserLevel`, `DemografiAdminController`, `Notifikasi`, `Periode`, `JamLayanan`, `.__invoke`, `PublikController`, `PermohonanPdfController`, `Media`, `FotoProfil`?**
  _High betweenness centrality (0.019) - this node is a cross-community bridge._
- **What connects `1. Identitas project`, `2. Keputusan yang sudah dikunci user — jangan dibuka ulang`, `3. Kemajuan` to the rest of the system?**
  _268 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Otp` be split into smaller, more focused modules?**
  _Cohesion score 0.14736842105263157 - nodes in this community are weakly interconnected._
- **Should `KategoriDemografi` be split into smaller, more focused modules?**
  _Cohesion score 0.11375661375661375 - nodes in this community are weakly interconnected._
- **Should `devDependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07692307692307693 - nodes in this community are weakly interconnected._