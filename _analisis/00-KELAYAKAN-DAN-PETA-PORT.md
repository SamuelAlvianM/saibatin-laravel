# SAIBATIN: Next.js → Laravel 12 / PHP 8.2+ — Analisis Kelayakan & Peta Port

> Dibuat 2026-08-07 · sumber: pembacaan penuh `saibatin/` (journal.md, HISTORY.md,
> HANDOFF.md, CATATAN-UTAMA-SAIBATIN.md, dan seluruh berkas kode `saibatin-platform/`).
> **Status: dokumen diskusi. Belum ada satu baris kode pun yang ditulis.**

## 0. Keputusan yang sudah diambil user (2026-08-07)

| Perkara | Keputusan |
|---|---|
| **OCR KTP/KK** | **Pindah ke browser** (tesseract.js WASM sisi klien). `tessdata/ind.traineddata.gz` = **3,67 MB**, sekali unduh lalu di-cache. Server tidak perlu binary `tesseract` → risiko #1 di §5 **hilang**. |
| **Database** | **DB dev baru** hasil impor `saibatin/saibatinpesibar_db_pesbar_002.sql` (13,6 MB). Produksi tidak disentuh selama pengembangan. |
| **Tujuan** | **Pengganti total** SAIBATIN Next.js. Perlu rencana uji berdampingan + cutover sebelum flip. |
| **Arsitektur** | Ditanyakan balik: *"yang cocok masuk cPanel strict apa?"* → lihat **§4.5** di bawah. Usulan: **Inertia + React (hibrida dengan Blade untuk halaman publik ber-SEO)**. Menunggu konfirmasi. |

---

## 1. Jawaban singkat

**Mungkin — dan secara teknis ini pulang kampung, bukan lompatan.**

Aplikasi ini **aslinya Laravel 9** (`app.pesbar.002_debug_20260708/`, PHP ^8.0.2,
200 blade view). Next.js adalah hasil migrasi dari sana. Tiga hal yang biasanya
bikin port lintas-stack berdarah-darah, di sini justru sudah beres duluan:

| | Keadaan sekarang | Artinya untuk Laravel |
|---|---|---|
| Database | **sudah MySQL** (Prisma hanya kulit di atasnya) | tidak ada migrasi data sama sekali |
| Kontrak API | `{ error:[], success:[], data:{}, html:[] }` — **kontrak Laravel yang sengaja dipertahankan** (`lib/api-response.ts` menulisnya terang-terangan) | controller Laravel tinggal mengembalikan bentuk yang sama |
| Password | bcrypt (`bcryptjs`, cost 10) | `Hash::check()` Laravel baca `$2a$`/`$2y$` — **semua akun lama tetap bisa login** |

Nama kolom di Prisma juga sudah di-`@map` ke nama tabel/kolom gaya Laravel
(`users`, `m_userlevels`, `t_permohonan`, `created_at`, `updated_at`, …). Model
Eloquent bisa menempel ke tabel yang sudah ada **tanpa mengubah apa pun**.

⚠️ **Koreksi satu premis:** Laravel 12 butuh **PHP ≥ 8.2** — PHP 8.0/8.1 tidak
jalan. Dan Laravel sejak v6 tidak lagi memakai skema "LTS"; v12 dapat perbaikan
bug ± 1 tahun dan perbaikan keamanan ± 2 tahun sejak rilis (Feb 2025). Kalau yang
dicari umur panjang, **versi Laravel terbaru saat mulai** lebih masuk akal
daripada mengejar label LTS yang sudah tidak ada. Wajib dicek dulu: **versi PHP
yang tersedia di cPanel "damar"** (MultiPHP Manager).

---

## 2. Yang sudah dibaca — ukuran sebenarnya

### 2.1 Inventaris

| | Jumlah |
|---|---|
| Berkas kode (di luar `node_modules`, `.next`, `public`) | **380** |
| `.tsx` | 179 · `.ts` 121 |
| Baris kode (ts/tsx/css/prisma) | **59.475** |
| Halaman (`page.tsx`) | **52** |
| Berkas route API | **66** → **90 handler HTTP** |
| Model Prisma / tabel MySQL | **20** |
| Komponen `'use client'` | **116 dari 177** |
| Blok konten CMS (`STATIC_BLOCKS`) | **12** |
| Jenis layanan permohonan | **15** |
| Aset `public/` | **712 MB** (berita, galeri, produk, uploads warisan) |

### 2.2 🔴 Temuan terbesar: **20.206 baris (± 770 KB) sudah jadi kode mati**

15 modal permohonan (`components/permohonan-online/*Modal.tsx`, 655–2.001 baris
masing-masing) **tidak diimpor oleh apa pun lagi**. Diverifikasi dengan grep:
satu-satunya yang menyebutnya adalah `form-map.tsx`, dan `form-map.tsx` sendiri
tidak diimpor siapa pun. `app/permohonan-online/page.tsx` sekarang cuma
`redirect()`. Pulau mati itu: 15 modal + `form-map` + `form-shell` +
`pemohon-nik-field` + `kk-scan-field`.

Penggantinya jauh lebih ringkas dan **inilah yang harus di-port**:

```
lib/layanan-forms.ts            667 baris  ← skema 15 layanan (deklaratif, data murni)
components/dashboard/staff-pengajuan-form.tsx   ± 470 baris  ← SATU renderer untuk semuanya
app/api/[layanan]/[action]/route.ts            234 baris  ← SATU endpoint untuk semuanya
```

**Konsekuensi: permukaan port yang nyata ± 39.300 baris, bukan 59.500.**
Dan bagian tersulit (15 formulir raksasa) sudah berubah jadi **satu berkas data
667 baris** yang di PHP tinggal jadi array config. Ini hadiah besar.

### 2.3 Sebaran baris yang benar-benar perlu di-port

| Bagian | Baris | Catatan |
|---|---|---|
| `app/dashboard/**` | 5.558 | 18 halaman admin, tabel + panel detail |
| `lib/**` | 5.795 | logika domain — **paling mudah dipindah, hampir semuanya data & fungsi murni** |
| `components/shared/**` | 5.333 | navbar (33 KB), sidebar, a11y widget (20 KB), notif bell, viewer gambar |
| `app/api/**` | 4.764 | 90 handler |
| `components/landingpage/**` | 3.530 | carousel, hero, stats, peta, struktur organisasi |
| `components/dashboard/**` | 1.981 | editor demografi (35 KB), jam layanan, form pengajuan |
| `components/ui/**` | 1.619 | shadcn/ui (Radix) |
| lain-lain (konten/media/ppid/produk/tiket) | 2.746 | |
| `store/**` | 553 | Redux auth — **hilang total di Laravel**, diganti sesi server |
| `prisma/` | 217 | jadi migration + seeder |

---

## 3. Peta modul — 20 tabel & fungsinya

```
users · m_userlevels                      akun & peran (1 superadmin, 2 operator, 3 warga)
m_jenis_permohonan · t_permohonan · t_berkas    15 layanan, payload JSON, lampiran
t_pengaduanmasyarakat · t_kritiksaran     aspirasi warga
m_news_posts · t_galleries · t_produk     konten publik (berita, galeri, dokumen)
t_skm_jawaban                             survei kepuasan (SKM/IKM)
t_static_contents                         CMS 12 blok (visi-misi, hero, carousel, …)
t_media                                   pustaka media terpusat (uuid, storage/uploads/yyyy/mm)
m_wilayah                                 kecamatan/pekon Pesisir Barat
t_tiket · t_tiket_pesan                   tiket bantuan + chat, auto-close
t_notifikasi                              lonceng in-app
m_demografi_wilayah                       agregat demografi hasil impor Excel SIAK
t_kunjungan                               pencacah pengunjung (online / hari ini / total)
t_log_aktivitas                           audit ringan petugas
```

Kolom JSON yang perlu `$casts`: `t_permohonan.payload`, `t_static_contents.konten`,
`m_demografi_wilayah.data`, `t_skm_jawaban.jawaban`.

---

## 4. Persimpangan arsitektur — **ini pertanyaan sesungguhnya**

"Replika 100% persis" punya dua arti yang sangat berbeda ongkosnya.

### Opsi A — Laravel 12 + Blade + Livewire 3 (+ Alpine)

- Runtime **PHP murni**. Tidak ada Node di server sama sekali.
- 116 komponen client (± 30.000 baris TSX) **ditulis ulang** jadi Blade/Livewire.
- Risiko: tampilan melenceng dari aslinya di seratus tempat kecil.
- Paling ramah cPanel, paling ringan diurus jangka panjang, paling lama dikerjakan.

### Opsi B — Laravel 12 + Inertia.js + React ⭐ *paling cocok untuk kata "100% persis"*

- Backend Laravel/Eloquent penuh; **179 berkas React dipakai hampir apa adanya**.
  Yang diganti hanya lapisan routing/data: `page.tsx` → komponen halaman Inertia,
  `fetch('/api/...')` → props dari controller, Redux → `usePage().props`.
- Tailwind v4 + shadcn/ui + Radix + framer-motion + Leaflet + tiptap **semuanya
  jalan tanpa disentuh** (Vite, bukan Next).
- Node tetap dibutuhkan **saat build saja** (`npm run build` di laptop) — persis
  seperti sekarang, tapi tanpa Passenger, tanpa Prisma engine, tanpa cold-start.
- Tampilan bisa benar-benar identik karena komponennya memang komponen yang sama.

### Opsi C — Laravel jadi API saja, frontend tetap Next.js

Tidak memenuhi permintaan ("pakai PHP"), dan justru menambah satu proses Node
yang selama ini jadi sumber masalah. **Tidak disarankan.**

> Rekomendasi: **Opsi B**, dengan catatan halaman publik yang butuh SEO
> (beranda, berita, PPID, produk) boleh dibuat Blade biasa agar HTML-nya penuh
> di sisi server — campuran ini sah di Inertia dan justru memperbaiki SEO
> dibanding sekarang.

### 4.5 Mana yang cocok untuk **cPanel strict**?

Salah paham yang umum: **Inertia bukan Node di server.** Inertia hanya protokol —
Laravel membalas JSON, browser yang me-render React. Node dipakai **hanya saat
`npm run build` di laptop**; hasilnya berkas statis di `public/build/`.
**Ketiga opsi sama-sama PHP murni di runtime.** Jadi pembandingnya bukan Node:

| | Blade + Alpine | **Inertia + React** | Blade + Livewire |
|---|---|---|---|
| Request PHP per interaksi | paling sedikit | 1 per navigasi + AJAX saat submit | **paling boros** — tiap update komponen = 1 POST + re-render server |
| Risiko kena batas LVE (entry process / CPU) saat ramai | rendah | rendah | **tertinggi** |
| Perlu `exec` / `proc_open` | tidak | tidak | tidak |
| Perlu proses hidup terus-menerus | tidak | tidak — **asal Inertia SSR TIDAK dipakai** (SSR butuh daemon Node) | tidak |
| Ongkos tulis ulang | ± 30.000 baris dari nol | **± 179 berkas React dipakai apa adanya** | ± 30.000 baris dari nol |
| Peluang hasil "100% persis" | rendah | **tinggi** | sedang |

**Kesimpulan: Inertia + React** — paling cocok cPanel strict *sekaligus* satu-satunya
yang realistis mencapai "100% persis".

**Livewire justru paling berisiko di sini.** `ulimit -u=35` yang tercatat di
`CATATAN-UTAMA-SAIBATIN.md` menandakan hosting ini pelit proses; Livewire mengubah
tiap ketikan/klik jadi request PHP baru. Halaman seperti `AdminUsers.tsx` (47 KB,
tabel + panel detail + filter) atau `demografi-editor.tsx` (35 KB) akan jadi ladang
request.

**Blade+Alpine** paling ringan, tapi berarti membangun ulang editor demografi,
media picker, cropper, tiptap, Leaflet, dan widget aksesibilitas dari nol — dan
hasilnya dijamin **tidak** persis.

**Bentuk yang diusulkan — hibrida:**
- **Blade biasa** untuk halaman publik ber-SEO: beranda, berita, PPID, produk,
  galeri. HTML penuh dari server (lebih baik daripada sekarang).
- **Inertia + React** untuk dashboard, formulir 15 layanan, dan semua yang interaktif.
- 🔴 **Tanpa Inertia SSR** — satu-satunya bagian Inertia yang butuh daemon Node.

Keputusan OCR-di-browser sangat pas dengan bentuk ini: logika `ocr-upload-button.tsx`
tinggal dipindah ke sisi klien, di Inertia nyaris salin-tempel.

### 4.6 Yang wajib dicek di cPanel "damar" sebelum fase 1

```bash
php -v && php -m | sort | tr '\n' ' '
```

- **PHP ≥ 8.2** (syarat Laravel 12) — via MultiPHP Manager.
- Ekstensi wajib: `pdo_mysql mbstring openssl tokenizer xml ctype json bcmath fileinfo curl zip`
- Ekstensi tambahan: **`gd` atau `imagick`** (Intervention Image, pengganti `sharp`).
  `zip` + `gd` juga syarat **PhpSpreadsheet** (impor/ekspor demografi).
- **Kuota inode** — `vendor/` Laravel ± 15.000–25.000 berkas.
- **Kuota disk** — aset sekarang sudah 708 MB.
- Apakah **cPanel Terminal bisa menjalankan `composer`**. Kalau tidak: `vendor/`
  dibangun di laptop lalu ikut diunggah (pola tarball seperti sekarang, tapi jauh
  lebih kecil dari `node_modules`).
- **Antrean & penjadwal:** tidak ada proses hidup terus di cPanel → `QUEUE_CONNECTION=sync`,
  atau driver `database` yang ditarik **cPanel Cron Job** (`queue:work --stop-when-empty`).
  Cron di server ini sudah terbukti ada (CATATAN §5).
- **Sesi & cache:** driver `file` atau `database` (tidak ada Redis di shared).

---

## 5. Titik sulit — sudah diukur, bukan ditebak

| # | Sekarang (Node) | Di Laravel | Tingkat |
|---|---|---|---|
| 1 | **`tesseract.js`** OCR KTP/KK, `app/api/ocr/ktp/route.ts` (243 baris) + `tessdata/ind.traineddata.gz` (3,67 MB) | ~~butuh binary `tesseract` di server~~ → **DIPUTUSKAN: pindah ke browser (WASM).** Endpoint `/api/ocr/ktp` dihapus; `parseKtpText()`, `isValidNik()`, `normalizeDigits()`, `pickNik()` dipindah ke sisi klien apa adanya. Praproses `sharp` (rotate/grayscale/normalize/sharpen) diganti Canvas API. Gambar tak pernah naik ke server untuk OCR → **lebih privat**. Konsekuensi: HP lemah akan berat. | ✅ **risiko hilang** |
| 2 | **`pdfkit`** — `lib/permohonan-pdf.ts` 14,4 KB menggambar PDF secara imperatif (logo, tipografi, gambar tersemat) | ditulis ulang sebagai template HTML+CSS → **dompdf** (Laravel 9 lama sudah pakai `barryvdh/laravel-dompdf`) atau **mPDF**. Hasilnya tidak akan byte-identik; harus dirancang ulang lalu dibandingkan visual. | 🟠 sedang-berat |
| 3 | **`sharp`** — normalisasi WebP→JPEG (HP Android), resize, praproses OCR | **Intervention Image** (GD/Imagick). Perlu ekstensi `gd` atau `imagick` aktif. Setara fungsi. | 🟢 mudah |
| 4 | **`exceljs`** — impor/ekspor demografi | **PhpSpreadsheet**. Matang, setara. | 🟢 mudah |
| 5 | **`jose`** JWT di cookie `saibatin_session`, 7 hari | sesi Laravel biasa (paling sederhana & paling aman). Kalau nama cookie mau dipertahankan, tinggal atur `SESSION_COOKIE`. Redux auth di client jadi tidak perlu. | 🟢 mudah |
| 6 | Penyaji berkas berkontrol akses `/uploads/[...path]` (staff = semua, warga = miliknya) | route Laravel + `Storage::disk('private')` + Gate. **Lebih rapi di Laravel.** | 🟢 mudah |
| 7 | `nodemailer` + `fonnte` (OTP WhatsApp) | `Mail::` + `Http::post()`. | 🟢 mudah |
| 8 | reCAPTCHA v3 verifikasi server | satu panggilan `Http::asForm()`. | 🟢 mudah |
| 9 | Prisma `Json` + `@updatedAt` + `@default(now())` | `$casts` + timestamps Eloquent. Nama kolom sudah cocok. | 🟢 mudah |
| 10 | Leaflet/GIS, carousel, framer-motion, widget aksesibilitas (20 KB), tiptap | Opsi B: **tidak disentuh**. Opsi A: ditulis ulang dengan Alpine/vanilla. | B 🟢 / A 🔴 |
| 11 | `t_kunjungan` + ping pengunjung | job/middleware ringan. | 🟢 mudah |
| 12 | Auto-close tiket (`TIKET_AUTO_CLOSE_DAYS`, dicek malas) | tetap malas, atau scheduler Laravel (cron cPanel sudah ada). | 🟢 mudah |

> Dengan OCR pindah ke browser, **satu-satunya titik berat yang tersisa adalah PDF (#2).**

---

## 6. Hadiah tak terduga: **deploy jadi jauh lebih mudah**

Ini mungkin alasan terkuat migrasi ini, di luar bahasa. Semua penderitaan yang
tercatat di `CATATAN-UTAMA-SAIBATIN.md` **hilang begitu saja** di Laravel:

| Masalah hari ini | Nasibnya di Laravel |
|---|---|
| cPanel tak sanggup `next build` (LVE `ulimit -u=35`) | tidak ada build di server; PHP tidak perlu di-build |
| Wajib `next build --webpack`, Turbopack merusak tracing | lenyap |
| Prisma harus `engineType="client"` (WASM, Rust-free) agar tak tembus batas proses | lenyap — PDO/MySQL biasa |
| `PassengerSpawnMethod direct` wajib, kalau tidak 500 berulang | lenyap — tidak ada Passenger |
| Restart hanya lewat toggle Application Manager | lenyap — PHP-FPM tak perlu di-restart |
| Cold start → `ChunkLoadError` → butuh cron keep-warm tiap 2 menit | lenyap |
| Tukar `sharp` win32 → linux-x64 di setiap deploy | lenyap |
| Tarball 34 MB berisi `node_modules` | deploy = kirim kode + `composer install` |

Deploy jadi: `git pull` (atau upload) → `composer install --no-dev` →
`php artisan migrate` → `php artisan optimize`. Itu saja.

---

## 7. Sisa yang belum diputuskan

| # | Perkara | Status |
|---|---|---|
| 1 | Arsitektur A / B / Blade+Alpine | **usulan: B hibrida** (§4.5) — menunggu konfirmasi |
| 2 | Versi PHP + ekstensi di cPanel "damar" | **belum dicek** (§4.6) |
| 3 | Nasib OCR KTP | ✅ pindah ke browser |
| 4 | Database | ✅ DB dev baru dari dump 13,6 MB |
| 5 | Tujuan akhir | ✅ mengganti total Next.js |
| 6 | **Aset `public/` 707,9 MB** | belum diputuskan — lihat §7.1, ada temuan penting |
| 7 | Kode mati 20.206 baris | usulan: **dilewati** (sudah tidak dipanggil siapa pun) |

### 7.1 🔴 Temuan: 560 MB scan warga masih terekspos di `public/`

`public/uploads/` = **707,9 MB**. Setelah dipisah menurut isinya:

| | Ukuran | Berkas |
|---|---|---|
| Sah publik (`berita`, `gallery`, `galeri`, `produk`) | **147,7 MB** | 284 |
| 🔴 **Scan KTP/KK/akta warga** (`kelahiran_1`, `kelahiran_2`, `kematian`, `kia`, `kk*`, `kedatangan`, `konsolidasi*`, `akta-*`) | **560,2 MB** | **1.467** |

Ini pelanggaran aturan journal §2.4 yang **masih hidup**: Next menyajikan `public/`
sebagai aset statis tanpa cek sesi. `app/uploads/[...path]/route.ts` bahkan punya
`.catch(() => readFile(join(ROOT_PUBLIK, rel)))` sebagai jaring warisan, dengan
komentar yang mengakui: *"berkas lama itu masih terekspos sebagai aset statis"*.

**Migrasi ke Laravel adalah momen paling wajar untuk menutup ini sekali untuk
selamanya:** 1.467 berkas itu pindah ke `storage/app/private/permohonan/`, dilayani
controller ber-Gate (staff = semua, warga = miliknya sendiri, sisanya 404).
Yang 147,7 MB tetap di `public/`.

> Catatan: ini menyentuh **server produksi yang sedang melayani warga**. Pemindahan
> berkasnya harus jadi langkah cutover tersendiri yang dikonfirmasi user, bukan
> disisipkan diam-diam ke deploy.

---

## 8. Aturan yang tetap berlaku di project baru ini

Diturunkan dari `journal.md §2` dan `§5` — melanggar ini sudah pernah memakan korban:

1. 🔴 **Berkas warga (KTP/KK/selfie) tidak pernah boleh masuk `public/`.**
   Di Laravel: `storage/app/private/…` + route berkontrol akses. Aturan ini sudah
   dilanggar **dua kali** di project Next.js.
2. 🔴 **Jangan sentuh sistem deploy SIDAKO / TIDORE / SAIBATIN-Next.** Project ini
   berdiri sendiri.
3. 🔴 **Tidak ada rahasia di berkas markdown mana pun** — tulis nama variabelnya saja.
4. Migrasi DB pada sistem yang live wajib dikonfirmasi user lebih dulu.
5. `tsc`/`composer` bersih ≠ aplikasi jalan. Ukur di jalur yang benar-benar dipakai produksi.

---

## 9. Perkiraan urutan kerja (kalau lampu hijau)

| Fase | Isi | Bergantung pada |
|---|---|---|
| 0 | Konfirmasi arsitektur (§4.5) + cek PHP/ekstensi cPanel (§4.6) + impor dump 13,6 MB ke MySQL lokal | **jawaban user** |
| 1 | Kerangka Laravel 12 + 20 migration + 20 model Eloquent + seeder master (jenis permohonan, wilayah, userlevel) | fase 0 |
| 2 | Auth (login/register/OTP/lupa sandi/status akun) + middleware peran 1/2/3 | fase 1 |
| 3 | 90 endpoint → controller, kontrak `{error,success,data}` dipertahankan | fase 1 |
| 4 | 15 layanan permohonan (config array + satu renderer + satu controller) + upload berkas + berkas berkontrol akses | fase 2,3 |
| 5 | Dashboard petugas: permohonan, akun, pengaduan, kritik, SKM, log | fase 3 |
| 6 | Konten & media: CMS 12 blok, berita, galeri, dokumen, pustaka media, demografi (impor/ekspor Excel) | fase 3 |
| 7 | Halaman publik + navbar/footer + widget aksesibilitas + beranda (carousel/stats/peta/struktur) | fase 6 |
| 8 | PDF permohonan (dompdf) + notifikasi + tiket + pencacah kunjungan | fase 4 |
| 9 | OCR KTP sisi browser (tesseract.js WASM + praproses Canvas) | fase 4 |
| 10 | Uji berdampingan dengan yang live | semua |
| 11 | **Cutover**: pindahkan 1.467 scan warga (560,2 MB) dari `public/` ke `storage/app/private/` + flip domain | fase 10 + **konfirmasi user** |

Fase 4 dan 6 yang paling berat. Fase 1–3 sebagian besar mekanis.
Fase 11 menyentuh produksi — langkahnya diserahkan ke user, bukan dieksekusi sendiri.

---

## 10. Rujukan yang dipakai saat porting

| Berkas | Kegunaan |
|---|---|
| `app.pesbar.002_debug_20260708/app.pesbar.002/` | **Laravel 9 asli** — 200 blade view, `routes/web.php` (495 baris), controller asli. Rujukan penamaan & bentuk form lama. |
| `saibatin/saibatinpesibar_db_pesbar_002.sql` | dump DB lama 13,6 MB |
| `saibatin/saibatin-platform/lib/kode-options.ts` | kamus `m_options` portal lama (agama "1", pekerjaan "88", …) — wajib ikut, data migrasi menyimpan kode angka |
| `saibatin/saibatin-platform/lib/layanan-forms.ts` | skema 15 layanan — **sumber kebenaran formulir** |
| `saibatin/saibatin-platform/prisma/schema.prisma` | 20 tabel + komentar alasan tiap keputusan |
| `saibatin/saibatin-platform/CATATAN-UTAMA-SAIBATIN.md` | seluk-beluk cPanel "damar" |
| `saibatin/HANDOFF.md` | sejarah migrasi Laravel→Next & palet warna brand |
| `saibatin/REPORT.md` | laporan migrasi asli — **pola route Laravel lama** & pemetaan `.env` |
| `saibatin/saibatin-platform/PROMPT-DISABILITAS.md` | spesifikasi lengkap 14 kontrol widget aksesibilitas |
| `saibatin/saibatin-platform/HANDOFF-UPDATE-2026-07-28.md` | model 4-status akun + alur Cek Status / Ajukan Ulang |

### 10.1 Pola route Laravel lama (dari `REPORT.md`) — pakai lagi kalau cocok

Portal lama memakai sufiks fungsi yang konsisten di ±150 route:

```
…/index      render awal          …/fetchdata  ambil satu record (edit)
…/getdata    data tabel           …/procdata   proses (approve/reject/delete)
…/postdata   simpan (+reCAPTCHA)  …/upload     unggah berkas
…/images     galeri bukti         …/downloadPDF  cetak PDF (DOMPDF)
…/delfile    hapus berkas
```

Endpoint catch-all Next sekarang (`/api/<layanan>/<action>`) **masih menerima
`postdata` & `insertdata`** sebagai alias — jejak kompatibilitas itu sengaja
dipertahankan. Artinya kontrak lama masih hidup dan bisa dipulihkan apa adanya.

### 10.2 Palet brand (jangan diubah)

| Token | Hex |
|---|---|
| Brand Blue | `#2176bd` |
| Brand Blue Dark | `#1b4b72` |
| Brand Blue Light | `#6cb2eb` |
| Brand Yellow | `#ffed4a` |
| Brand Yellow Dark | `#e77817` |

Di `app/globals.css` warnanya sudah dalam **oklch** + token Tailwind v4
(`@theme inline`). Opsi B: berkas CSS ini dipakai apa adanya lewat Vite.

### 10.3 Fitur yang mudah terlupakan saat porting

- **Widget aksesibilitas** — 14 kontrol (perbesar/perkecil teks, spasi, font disleksia,
  kontras tinggi, invert, grayscale, latar terang, sorot tautan, garis bantu baca,
  kursor besar, jeda animasi, **text-to-speech Web Speech API `id-ID`**, reset).
  Preferensi di `localStorage` + init script anti-flicker di `<head>`.
  Spek penuh: `PROMPT-DISABILITAS.md`. **Semua sisi klien — di Opsi B tidak disentuh.**
- **Model 4-status akun**: 0 Menunggu · 1 Aktif · 2 Ditolak · 3 Nonaktif, plus alur
  `/cek-status` → **Ajukan Ulang** (2→0). Alasan penolakan wajib, disimpan di `users.ket`
  dan diurai `lib/akun-tolak.ts`.
- **`lib/kode-options.ts`** — data migrasi menyimpan **kode angka** (agama `1`,
  pekerjaan `88`). Tanpa kamus ini, detail permohonan lama tampil sebagai angka.
- **Jam layanan** menggerbang pembuatan permohonan untuk **semua** peran
  (`cekJamLayananSekarang()` dipanggil di endpoint, bukan hanya di UI).
- **Notifikasi in-app** + **log aktivitas** (hanya level 1/2) + **pencacah kunjungan**
  ikut terpasang di banyak endpoint sebagai efek samping — jangan sampai hilang.
