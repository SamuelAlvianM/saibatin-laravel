# Graph Report - saibatin-laravel  (2026-09-01)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1333 nodes · 2233 edges · 260 communities (59 shown, 79 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS · INFERRED: 8 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `4d6c08fb`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Community 0
- Community 1
- Community 2
- Community 3
- Community 4
- Community 5
- Community 6
- Community 7
- Community 8
- Community 9
- Community 10
- Community 11
- Community 12
- Community 13
- Community 14
- Community 15
- Community 16
- Community 17
- Community 18
- Community 19
- Community 20
- Community 21
- Community 22
- Community 23
- Community 24
- Community 25
- Community 26
- Community 27
- Community 28
- Community 29
- Community 30
- Community 31
- Community 32
- Community 36
- Community 37
- Community 38
- Community 39
- Community 40
- Community 41
- Community 42
- Community 43
- Community 44
- Community 45
- Community 46
- Community 48
- Community 49
- Community 50
- Community 51
- Community 52
- Community 53
- Community 54
- Community 55
- Community 56
- Community 57
- Community 58
- Community 59
- Community 60
- Community 61
- Community 62
- Community 63
- Community 64
- Community 65
- Community 66
- Community 67
- Community 68
- Community 69
- Community 70
- Community 71
- Community 72
- Community 73
- Community 75
- Community 76
- Community 78
- Community 79
- Community 80
- Community 81
- Community 82
- Community 83
- Community 84
- Community 85
- Community 86
- Community 88
- Community 89
- Community 90
- Community 91
- Community 94
- Community 95
- Community 96
- Community 97
- Community 119
- Community 120
- Community 121
- Community 123
- Community 124
- Community 125
- Community 127
- Community 128
- Community 129
- Community 130
- Community 131
- Community 132
- Community 133
- Community 134
- Community 135
- Community 136
- Community 137
- Community 139
- Community 140
- Community 141
- Community 142
- Community 143
- Community 144
- Community 145
- Community 146
- Community 147
- Community 148
- Community 149
- Community 150
- Community 151
- Community 152
- Community 153
- Community 154
- Community 155
- Community 156
- Community 157
- Community 158
- Community 159
- Community 160
- Community 161
- Community 162
- Community 163
- Community 164
- Community 165
- Community 166
- Community 183
- Community 196
- Community 256
- Community 258

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

## Communities (260 total, 79 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.08
Nodes (20): StatistikController, DashboardController, JenisPermohonan, Permohonan, PencacahKunjungan, Spreadsheet, StreamedResponse, StatistikExcel (+12 more)

### Community 1 - "Community 1"
Cohesion: 0.11
Nodes (3): PublikController, Produk, Konten

### Community 2 - "Community 2"
Cohesion: 0.12
Nodes (8): MediaController, Media, PustakaMedia, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Http\UploadedFile, Illuminate\Support\Str, static

### Community 3 - "Community 3"
Cohesion: 0.08
Nodes (25): axios, concurrently, laravel-vite-plugin, devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss (+17 more)

### Community 4 - "Community 4"
Cohesion: 0.14
Nodes (6): DemografiAdminController, DemografiWilayah, DemografiExcel, Spreadsheet, StreamedResponse, PhpOffice\PhpSpreadsheet\IOFactory

### Community 5 - "Community 5"
Cohesion: 0.13
Nodes (7): BerkasController, MediaPublikController, PastikanPeran, FotoProfil, Closure, Illuminate\Support\Facades\Storage, Symfony\Component\HttpFoundation\Response

### Community 6 - "Community 6"
Cohesion: 0.13
Nodes (6): LogAktivitasController, PermohonanAdminController, UserAdminController, UnggahController, Surel, Periode

### Community 7 - "Community 7"
Cohesion: 0.10
Nodes (8): FilterPeriode(), geser(), labelAcuan(), nomorHalaman(), Paginasi(), PERIODE, STATUS_FINAL, STATUS_PERMOHONAN

### Community 8 - "Community 8"
Cohesion: 0.17
Nodes (6): PengaduanAdminController, AspirasiController, PermohonanController, Pengaduan, Pemberitahuan, Recaptcha

### Community 9 - "Community 9"
Cohesion: 0.15
Nodes (5): GaleriAdminController, KontenStatisController, MasterController, LayananController, CatatanAktivitas

### Community 10 - "Community 10"
Cohesion: 0.16
Nodes (6): PresisiMilidetik, Gallery, KritikSaran, Kunjungan, LogAktivitas, Illuminate\Database\Eloquent\Model

### Community 11 - "Community 11"
Cohesion: 0.16
Nodes (5): NotifikasiController, PendudukController, ProfilController, ProfilPageController, Illuminate\Http\Request

### Community 12 - "Community 12"
Cohesion: 0.14
Nodes (6): User, DatabaseSeeder, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Database\Seeder, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 13 - "Community 13"
Cohesion: 0.17
Nodes (3): BeritaAdminController, KontenController, News

### Community 14 - "Community 14"
Cohesion: 0.14
Nodes (6): StatistikEksporController, BuktiPengaduanController, DemografiController, OtpController, Controller, Illuminate\Support\Facades\Cache

### Community 15 - "Community 15"
Cohesion: 0.14
Nodes (9): penanda, angka(), RincianDemografi(), angka(), AngkaNaik(), AWAL, KartuPelayanan(), PetaKantor (+1 more)

### Community 16 - "Community 16"
Cohesion: 0.20
Nodes (4): SistemController, Balasan, Illuminate\Http\JsonResponse, Illuminate\Support\Facades\DB

### Community 17 - "Community 17"
Cohesion: 0.19
Nodes (5): StatusAkun, Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\Mail, Illuminate\Validation\ValidationException, Inertia\Inertia

### Community 18 - "Community 18"
Cohesion: 0.14
Nodes (3): Notifikasi, Tiket, Illuminate\Database\Eloquent\Builder

### Community 21 - "Community 21"
Cohesion: 0.14
Nodes (4): RegisterController, SandiController, Wilayah, Illuminate\Support\Facades\Route

### Community 22 - "Community 22"
Cohesion: 0.15
Nodes (7): BarisItem(), panjang(), PenyuntingKaya, Berita(), KOSONG, PenyuntingKaya, slugify()

### Community 24 - "Community 24"
Cohesion: 0.21
Nodes (3): PengajuanPetugasController, JamLayanan, CarbonImmutable

### Community 26 - "Community 26"
Cohesion: 0.19
Nodes (3): Berkas, TiketPesan, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 27 - "Community 27"
Cohesion: 0.29
Nodes (12): ambilWorker(), bacaKtp(), denganTimeout(), muatGambar(), nikSah(), normalkanDigit(), OcrGagal, persentil() (+4 more)

### Community 29 - "Community 29"
Cohesion: 0.18
Nodes (6): HandleInertiaRequests, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets, Inertia\Middleware

### Community 30 - "Community 30"
Cohesion: 0.17
Nodes (12): scripts, dev, post-create-project-cmd, post-root-package-install, post-update-cmd, Composer\\Config::disableProcessTimeout, npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite, @php artisan key:generate --ansi (+4 more)

### Community 31 - "Community 31"
Cohesion: 0.18
Nodes (10): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type (+2 more)

### Community 32 - "Community 32"
Cohesion: 0.22
Nodes (8): AKSI_WARNA, BarisProgres(), Beranda(), BULAN, GrafikHarian, PENGADUAN_BAR, persen(), STATUS_BAR

### Community 36 - "Community 36"
Cohesion: 0.18
Nodes (6): FORM_KOSONG, GRUP, INFO_STATUS, KOLOM_TOLAK, STATUS_AKUN, WARNA_PERMOHONAN

### Community 39 - "Community 39"
Cohesion: 0.25
Nodes (3): UserLevel, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 40 - "Community 40"
Cohesion: 0.22
Nodes (9): class-variance-authority, dependencies, class-variance-authority, @radix-ui/react-navigation-menu, react-organizational-chart, @tiptap/extension-text-align, @radix-ui/react-navigation-menu, react-organizational-chart (+1 more)

### Community 41 - "Community 41"
Cohesion: 0.22
Nodes (5): FONT_BAWAAN_IDX, KUNCI_A11Y, LANGKAH_FONT, PREFS_BAWAAN, SPASI_MAKS

### Community 44 - "Community 44"
Cohesion: 0.29
Nodes (5): AppServiceProvider, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Schema, Illuminate\Support\Facades\View, Illuminate\Support\ServiceProvider

### Community 45 - "Community 45"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 48 - "Community 48"
Cohesion: 0.32
Nodes (7): ALIAS, geoWilayah(), KECAMATAN_GEO, norm(), PETA_NAMA, PUSAT_PETA, ZOOM_AWAL

### Community 51 - "Community 51"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 52 - "Community 52"
Cohesion: 0.29
Nodes (7): require, barryvdh/laravel-dompdf, inertiajs/inertia-laravel, laravel/framework, laravel/tinker, php, phpoffice/phpspreadsheet

### Community 55 - "Community 55"
Cohesion: 0.43
Nodes (4): dasar(), GrafikGaris(), GrafikPeringkat(), GrafikTren()

### Community 56 - "Community 56"
Cohesion: 0.52
Nodes (6): ambilKonteks(), bukaAudio(), bunyikan(), LoncengNotifikasi(), TIPE_IKON, waktuRelatif()

### Community 57 - "Community 57"
Cohesion: 0.38
Nodes (5): HOURS, isJam(), masker(), MINUTES, TimePicker()

### Community 60 - "Community 60"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 61 - "Community 61"
Cohesion: 0.33
Nodes (5): publik.partials.faq, publik.partials.berkas, publik.partials.blok-isi, publik.partials.dokumen-edit, publik.partials.subnav

### Community 62 - "Community 62"
Cohesion: 0.53
Nodes (5): aktifkan(), GRUP, grupUntuk(), ItemMenu(), LayoutDashboard()

### Community 65 - "Community 65"
Cohesion: 0.33
Nodes (3): KARTU_BAWAAN, LABEL_KOLOM, WARNA_PRESET

### Community 66 - "Community 66"
Cohesion: 0.47
Nodes (3): mutu(), Skm(), warnaNilai()

### Community 68 - "Community 68"
Cohesion: 0.33
Nodes (5): akar, berkas, hilang, tessdata, tujuan

### Community 69 - "Community 69"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 70 - "Community 70"
Cohesion: 0.40
Nodes (5): dev-master, extra, branch-alias, laravel, dont-discover

### Community 71 - "Community 71"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 73 - "Community 73"
Cohesion: 0.60
Nodes (3): angka(), digit(), EditorDemografi()

### Community 76 - "Community 76"
Cohesion: 0.80
Nodes (4): DatePicker(), keTampilan(), masker(), parseValue()

### Community 79 - "Community 79"
Cohesion: 0.60
Nodes (3): kirimBerkas(), kirimJson(), tokenCsrf()

### Community 82 - "Community 82"
Cohesion: 0.40
Nodes (4): publik.partials.berkas, publik.partials.blok-isi, publik.partials.dokumen-edit, publik.partials.subnav

### Community 85 - "Community 85"
Cohesion: 0.67
Nodes (3): kecilkan(), TIPE_DITERIMA, UnggahGambar()

### Community 88 - "Community 88"
Cohesion: 0.67
Nodes (3): Log(), tautanData(), WARNA_AKSI

### Community 90 - "Community 90"
Cohesion: 0.67
Nodes (3): bacaProps(), pasang(), pulau

### Community 94 - "Community 94"
Cohesion: 0.83
Nodes (3): angka(), jariJari(), PetaSebaran()

### Community 96 - "Community 96"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 97 - "Community 97"
Cohesion: 0.67
Nodes (3): post-autoload-dump, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan package:discover --ansi

## Knowledge Gaps
- **172 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+167 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 617 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **79 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Controller` connect `Community 14` to `Community 0`, `Community 1`, `Community 2`, `Community 4`, `Community 5`, `Community 6`, `Community 8`, `Community 9`, `Community 11`, `Community 13`, `Community 16`, `Community 17`, `Community 21`, `Community 23`, `Community 24`, `Community 26`, `Community 28`, `Community 37`, `Community 43`, `Community 49`, `Community 50`, `Community 58`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **Why does `Balasan` connect `Community 16` to `Community 0`, `Community 2`, `Community 4`, `Community 37`, `Community 6`, `Community 39`, `Community 8`, `Community 9`, `Community 10`, `Community 11`, `Community 5`, `Community 13`, `Community 14`, `Community 49`, `Community 17`, `Community 23`, `Community 26`, `Community 28`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **Why does `User` connect `Community 12` to `Community 0`, `Community 2`, `Community 5`, `Community 6`, `Community 39`, `Community 8`, `Community 10`, `Community 11`, `Community 17`, `Community 50`, `Community 18`, `Community 21`, `Community 26`, `Community 28`?**
  _High betweenness centrality (0.021) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _172 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.08294930875576037 - nodes in this community are weakly interconnected._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.10846560846560846 - nodes in this community are weakly interconnected._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.11965811965811966 - nodes in this community are weakly interconnected._