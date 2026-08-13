# HANDOFF — SAIBATIN Laravel

> Untuk sesi/developer berikutnya. **Baca ini dulu sebelum menyentuh kode.**
> Terakhir diperbarui: **2026-08-13** · Fase 1–5 selesai (16 halaman dashboard
> jadi & diuji; hanya `konten` yang menunggu situs publik) — lihat §4,
> `_analisis/05-…` & `06-…`. Perubahan baru portal Next.js sudah disusul
> (§4 "Sinkronisasi", `_analisis/06` §2.9–2.10).
> Riwayat langkahnya: [`../HISTORY.md` §L & §O](../HISTORY.md) · ringkasan: [`../journal.md` §3.5](../journal.md)

---

## 1. Apa ini

Port **SAIBATIN** (portal Disdukcapil Kab. Pesisir Barat) dari **Next.js 16 →
Laravel 12**, atas permintaan user. Tujuan akhirnya **mengganti total** portal
Next.js yang sekarang live di cPanel.

Sumber yang di-port: `../saibatin/saibatin-platform` (jangan diubah — itu yang
melayani warga sekarang).

| | |
|---|---|
| Folder | `C:\sam\SAM-AMANDA-GALANG\saibatin-laravel` (root workspace, **bukan** di dalam `saibatin/` agar tidak mengotori working tree git-nya) |
| Stack | Laravel **12.65.0** · PHP **8.3.28** · MySQL **8.0.36** · Inertia **v3.3.1** · React 19 · Tailwind v4 · Vite 6 · Highcharts **13** · tiptap **3** · react-advanced-cropper · react-dropzone (tiga terakhir *lazy*) |
| DB kerja | **`saibatin_lv`** — klon dari DB dev Next.js `saibatin`. 1.386 akun · 11.902 permohonan · 1.485 berkas |
| Dev server | **port 3104** (🔴 dipaku), entri `saibatin-laravel-dev` di `../.claude/launch.json` |
| Login uji | `admin` / `admin123` |
| Belum di-git | folder ini belum jadi repo. Belum ada apa pun yang menyentuh server. |

**Bukan** repo git, **bukan** di-deploy, **tidak** menyentuh produksi.

---

## 2. Cara menjalankan

```bash
cd C:/sam/SAM-AMANDA-GALANG/saibatin-laravel
npm run build          # WAJIB setelah mengubah apa pun di resources/js atau resources/css
php artisan serve --port=3104
```

Atau lewat preview: konfigurasi `saibatin-laravel-dev` sudah ada di `.claude/launch.json`.

> **Kenapa `npm run build`, bukan `npm run dev`?** Sengaja memakai jalur
> produksi: aset dikompilasi jadi berkas statis di `public/build`, lalu PHP
> sendirian yang melayaninya. Ini persis bentuk yang nanti diunggah ke cPanel —
> jadi kalau ada yang rusak, rusaknya ketahuan sekarang, bukan saat deploy.

---

## 3. Keputusan arsitektur yang SUDAH DIKUNCI

Diputuskan user, jangan diubah tanpa membicarakannya lagi:

1. **Inertia + React**, hibrida:
   - Halaman **publik ber-SEO** (beranda, berita, PPID, produk, galeri) → **Blade**
     yang dirender server, dengan widget interaktif dipasang sebagai **React island**.
   - **Dashboard & formulir** → Inertia + React penuh (komponen aslinya dipakai).
2. 🔴 **TANPA Inertia SSR.** Itu satu-satunya bagian Inertia yang butuh daemon
   Node, dan cPanel user **tidak punya Application Manager / Node.js selector**.
   Di server hanya PHP yang berjalan. Node cuma dipakai saat build di laptop.
3. **OCR KTP pindah ke browser** (tesseract.js WASM). Endpoint server dihapus;
   server tidak perlu binary `tesseract`. `tessdata/ind.traineddata.gz` 3,67 MB.
4. **DB dev sendiri** (`saibatin_lv`). Produksi tidak disentuh.
5. **User yang mengunggah manual ke cPanel.** Jangan bahas deployment sampai
   memang siap di-deploy — permintaan eksplisit user.

---

## 4. Yang sudah jadi

### Fase 1 — data (SELESAI, terverifikasi)
20 migration + 20 model Eloquent menempel ke DB warisan.
**Dibuktikan:** migration dijalankan ke DB kosong lalu skemanya dibandingkan
dengan DB asli → **0 baris beda di 20 tabel** (tipe, `datetime(3)`, kolom `json`,
default, nama index, nama FK, aksi ON DELETE/UPDATE semuanya sama).

### Fase 2 — autentikasi (SELESAI, diuji di browser)
Login/logout, alur 4-status + Cek Status + Ajukan Ulang, pendaftaran + OTP +
kamera selfie, lupa sandi & reset, middleware peran 4 level, penyaji berkas
berkontrol akses.

### Fase 3 — endpoint JSON (SELESAI untuk lapisan publik & warga)
**36 rute** di `routes/api.php`, kontrak `{error,success,data,html}` dipertahankan.
Publik, aspirasi (pengaduan/kritik/SKM), profil, notifikasi, permohonan, unggah,
cek NIK. Semua diuji di browser; endpoint yang ditunda membalas 404.
(Endpoint tiket yang dibangun di fase ini sudah dihapus lagi — lihat Fase 5.)

🔴 **Lingkupnya sengaja diubah:** endpoint yang hanya melayani satu halaman
dashboard dipindah ke fase yang membangun halaman itu — bentuk responsnya baru
bisa dipastikan benar saat UI-nya ada, dan menebaknya lebih dulu berarti menulis
ulang. Lihat `_analisis/03-FASE-3-ENDPOINT.md` §1.

### Fase 4 — 15 layanan permohonan (SELESAI)
`config/layanan.php` (skema 15 formulir) + `FormLayanan.jsx` (SATU renderer) +
`LayananController` (catch-all `POST /api/{layanan}/{aksi}`). Diuji end-to-end:
unggah 2 berkas → kirim → permohonan + `t_berkas` + notifikasi 11 petugas + log,
berkas **tidak** bocor ke `public/`. Data uji sudah dibersihkan.

🔴 **Ditemukan: layanan `kk-numpang` RUSAK di portal Next.js yang sekarang live** —
validasi servernya menebak tipe kolom dari akhiran nama, sehingga `alasannumpangkk`
(select) dan `nikygnumpangkk` (textarea multi-NIK) selalu ditolak "harus 16 digit".
Tidak diperbaiki di sana (di luar lingkup, portal itu melayani warga) — diserahkan
ke user. Di port ini validasi digerakkan skema. Lihat `_analisis/04-FASE-4-LAYANAN.md` §1.

### Fase 5 — dashboard petugas (SELESAI kecuali 1 halaman — 15 halaman, diuji di browser)

`/dashboard` (statistik rekap), `/permohonan` (+ panel detail & proses),
`/pengajuan-baru` (+ formulir per layanan + **drawer Pengaturan**), `/users`,
`/pengaduan`, `/kritik-saran`, `/skm`, `/log`, `/master`, `/berita`, `/media`,
`/galeri`, `/produk`, `/demografi` — semuanya di atas data asli `saibatin_lv`
(11.902 permohonan · 1.386 akun · 203 responden SKM · 67 berita · 94 foto
galeri · 85 dokumen publikasi).
Dilayani rute `/api/admin/*`, `/api/media/*`, `/api/demografi` di 14 controller.

🔴 **Fitur Tiket & Chat DIHAPUS atas permintaan user (13 Agu 2026).** Halaman,
panel, controller, rute, dan `config/tiket.php` sudah dibuang. Yang **sengaja
ditinggalkan**: model `Tiket`/`TiketPesan`, migrasinya, dan relasi
`User::tiket()`/`tiketPesan()` — tabelnya masih berisi tiket warga di produksi
dengan FK `ON DELETE RESTRICT`, dan penjaga penghapusan akun menghitungnya.
Tanpa itu, menghapus akun bertiket gagal di lapisan database (galat 500) alih-alih
ditolak dengan penjelasan. **Tabel & datanya tidak disentuh.**

⚠️ Migrasi baru `2026_08_12_100001_tambah_user_ktp_ke_users` (kolom
`users.user_ktp`, aditif & nullable, **sudah dijalankan**) membuat klaim Fase 1
*"0 baris beda"* punya satu pengecualian yang disengaja — padanannya di portal
Next.js adalah `deploy/sql/2026-08-08_tambah-kolom-user-ktp.sql`.

Detail: `_analisis/01-FASE-1-SELESAI.md`, `02-FASE-2-AUTENTIKASI.md`,
`03-FASE-3-ENDPOINT.md`, `04-FASE-4-LAYANAN.md`, `05-FASE-5-DASHBOARD.md`.

🔴 **Target yang dikunci user (12 Agu): port ini harus 100% sama UI & fungsinya
dengan portal Next.js.** Peta paritas lengkap + sisa pekerjaannya ada di
`_analisis/06-PARITAS-NEXTJS.md` — pakai itu sebagai daftar kerja.
Grafik yang belum ada dibuat dengan **Highcharts** (juga keputusan user);
sudah terpasang & di-*lazy load* di `Components/GrafikHarian.jsx`.

Halaman **Pengaturan sudah ada** — bukan halaman tersendiri, melainkan drawer
di `/dashboard/pengajuan-baru` (khusus level 1), persis seperti aslinya.

### Sinkronisasi dengan portal Next.js (13 Agu, tahap kelima)

Portal Next.js terus dikerjakan selama port berjalan; 5 commit terakhir +
perubahan yang masih di working tree-nya sudah disalin ke sini. Petanya lengkap
di `_analisis/06` §2.9. Ringkasnya:

1. **OTP dimatikan** — `Otp::AKTIF = false` (server) + `OTP_AKTIF` di
   `Register.jsx` (klien). **Dua sakelar kembar, ubah keduanya bersamaan.**
   Mesin OTP-nya tidak dihapus sama sekali.
2. **Foto KTP wajib di pendaftaran** — `Components/UnggahGambar.jsx`
   (pilih berkas → dikecilkan canvas 1600 px/0,85 → data URL). Berkas masuk
   `storage/app/private/profil/ktp`, bukan `public/`. Kode galat baru **N-19**.
3. **OCR KTP di BROWSER** (`lib/ocr-ktp.js` + `public/ocr/` 12 MB) — mengisi
   NIK / No. KK / Nama, hanya kolom yang masih kosong, dengan cincin amber +
   badge "dari scan — periksa" yang hilang begitu warga menyuntingnya.
   Kenapa di browser & apa yang ikut hilang: `_analisis/06` §2.10.
4. **Gambar dikirim base64 POLOS** (`lib/gambar.js`) — mod_security cPanel
   memblokir badan permintaan yang memuat tanda tangan data URI, dan
   penolakannya muncul sebagai **halaman 404**, bukan galat JSON.
   `FotoProfil` menerima dua-duanya + memeriksa angka ajaib berkasnya.
5. **Ekspor Excel statistik dashboard** — tombol Excel di tiap kartu +
   "Export Excel" untuk seluruhnya. `Services/StatistikExcel.php` (kop surat +
   logo + 9 bagian + 4 sheet rincian) → `GET /api/admin/statistik/export`.
6. **Penampil gambar layar penuh** (`Components/PenampilGambar.jsx`) —
   zoom/putar/geser/unduh + ←/→, dipakai detail akun (KTP & foto profil).
7. `1f435fd` (validasi dari TIPE field) **sudah ada sejak Fase 4** di
   `Support\Layanan::periksaField` — pesan galatnya pun sama persis.

### Berikutnya
| Fase | Isi |
|---|---|
| 7 | **Seluruh situs publik** — pekerjaan terbesar yang tersisa, dan prasyarat halaman Konten |
| ⛔ | **Konten Halaman** BUKAN halaman formulir: ia me-render halaman publik di dalam iframe (`?editmode=1`) dan disunting di sana. **Baru bisa dibuat setelah situs publik ada** — lihat `_analisis/06` §3.4 |
| 8 (sebagian) | Unduh PDF permohonan — satu-satunya fungsi yang kurang di halaman yang sudah jadi |
| 9 | OCR sisi browser (tesseract.js) |
| 10 | Paket unggah manual cPanel |

---

## 5. 🔴 Lima belas jebakan yang SUDAH memakan waktu — jangan diulang

### Laravel / PHP

1. **`Schema::defaultStringLength(191)`** wajib ada di `AppServiceProvider`.
   `$table->string()` Laravel menghasilkan `varchar(255)`, seluruh tabel ini
   dibuat Prisma dengan `varchar(191)`. Tanpa itu skema meleset dari produksi.

2. **`config/hashing.php` dengan `'verify' => false` JANGAN DIHAPUS.**
   Berkas itu tidak ada di kerangka Laravel 12 — dibuat khusus. `users` memuat
   hash `$2y$` (dari Laravel 9) DAN `$2a$` (dari `bcryptjs` portal Next.js).
   PHP hanya mengenali `$2y$` sebagai PASSWORD_BCRYPT, sehingga penjaga bawaan
   Laravel **melempar RuntimeException** untuk `$2a$` → **HTTP 500, bukan
   "sandi salah"**. Jangan menulis ulang prefix hash di database.

3. **`config/app.php` mem-hardcode `'timezone' => 'UTC'`** dan TIDAK membaca
   `APP_TIMEZONE`. Sudah diubah jadi `env('APP_TIMEZONE','Asia/Jakarta')`.
   Semua kolom waktu DB warisan adalah waktu lokal Jakarta; kalau dibaca sebagai
   UTC, setiap tanggal dan setiap hitungan "hari ini" meleset 7 jam.

4. **Menempelkan Laravel ke DB yang tabelnya sudah ada** tidak bisa dengan
   `php artisan migrate` polos. Urutannya:
   ```bash
   php artisan migrate:install
   # INSERT INTO migrations (migration,batch) VALUES ('2026_08_07_1000xx_…',1), …;
   php artisan migrate --force
   ```

5. **Trait tidak boleh mendeklarasikan ulang properti induk** dengan default
   berbeda (`$dateFormat` di `PresisiMilidetik`). Pakai *trait initializer*
   Eloquent (`initializeNamaTrait()`).

### Toolchain

6. **`inertiajs/inertia-laravel:^2.0` menolak Laravel 12** — pin itu mengunci ke
   v2.0.0. Tanpa pin, Composer memilih v3.3.1 yang benar.

7. **`@vitejs/plugin-react` terbaru (v6) menuntut Vite 8**, Laravel 12 mengirim
   Vite 6. Terpasang **`^4.3.4`** (→ 4.7.0). Jangan `--force`.

8. 🔴 **JANGAN menambahkan `build: { manifest: true }` ke `vite.config.js`.**
   Sejak Vite 5, opsi itu menaruh manifest di `public/build/.vite/manifest.json`
   sedangkan Laravel mencarinya di `public/build/manifest.json` → **setiap
   halaman 500** dengan `ViteManifestNotFoundException`. `laravel-vite-plugin`
   sudah mengaturnya.

### React / Inertia

9. 🔴 **CSRF: dua header, dua isi berbeda — tertukar = 419 tanpa penjelasan.**

   | Header | Isi yang diharapkan Laravel |
   |---|---|
   | `X-CSRF-TOKEN` | token **mentah** (dari `<meta name="csrf-token">`) |
   | `X-XSRF-TOKEN` | nilai cookie `XSRF-TOKEN`, yang **terenkripsi** |

   Dan **jangan memakai `<meta>` di halaman Inertia**: meta hanya dirender saat
   pemuatan halaman penuh, sedangkan Inertia tidak pernah merender ulang `<head>`.
   Login memanggil `session()->regenerate()` → token di meta jadi basi → SEMUA
   POST sesudah login gagal 419. Pakai **cookie `XSRF-TOKEN`** (disegarkan tiap
   respons). Sudah diurus `resources/js/lib/api.js` — pakai itu, jangan menulis
   `fetch` sendiri.

   Bonus: **prop Inertia bernama `key` ditelan React** sebagai penanda internal
   dan tidak pernah sampai ke komponen. Namai apa pun selain `key`.

10. 🔴 **JANGAN `import * as X from 'lucide-react'`.** Impor bintang menarik
    SELURUH set ikon ke bundel — Vite tidak bisa menyingkirkan yang tak terpakai
    karena aksesnya dinamis. **Diukur: 367 KB → 1.280 KB (3,5×).** Pakai peta
    eksplisit di `resources/js/lib/ikon.js`.

11. 🔴 **Nama komponen tata letak bertabrakan dengan nama ikon lucide.**
    `LayoutDashboard.jsx` sempat mengimpor ikon `LayoutDashboard` sekaligus
    mengekspor komponen bernama sama → `The symbol "LayoutDashboard" has already
    been declared`, dan **seluruh `npm run build` gagal**, bukan cuma berkas itu.
    Akibatnya seluruh Fase 5 tak pernah masuk bundel selama 4 hari tanpa ada yang
    sadar. Beri alias ikonnya (`LayoutDashboard as IkonDasbor`), seperti
    `User as UserIcon` di `Log.jsx`. Nama rawan lain: `Users`, `ScrollText`.
    **Selalu jalankan `npm run build` sebelum menutup sesi** — Inertia yang tidak
    menemukan komponen halaman memberi **halaman putih**, bukan pesan galat.

12. 🔴 **Dua rute yang gampang tertelan rute lain — urutannya menentukan.**
    - `POST /api/media/upload` cocok dengan pola catch-all `{layanan}/{aksi}`
      di bawah, jadi grup `/api/media` harus **di atas**-nya.
    - `GET /uploads/media/{jalur}` harus **di atas** `/uploads/{jalur}` milik
      `BerkasController` — pola `.*` itu menelan `media/...` juga, dan berkas
      media akan dicari di folder permohonan lalu 404 tanpa penjelasan.

13. **Rute catch-all `POST /api/{layanan}/{aksi}` HARUS didaftarkan paling akhir**
    di `routes/api.php`. Polanya menelan rute dua-segmen mana pun sesudahnya
    (`auth/session`, `profil/foto`, `skm/unsur`) — Laravel memakai rute pertama
    yang cocok.

### PhpSpreadsheet

14. 🔴 **JANGAN memberi gaya per SEL pada tabel besar.** Versi pertama
    `StatistikExcel` memanggil `$sel->getStyle()` untuk tiap sel; pada sheet
    rincian permohonan (11.902 baris × 9 kolom = **107 ribu sel**) satu berkas
    memakan **475 detik**. Dengan gaya per RENTANG (`getStyle("A11:I11913")
    ->applyFromArray(...)` + satu rentang per kolom) berkas yang sama selesai
    **± 12 detik**. Nilainya boleh ditulis per sel; yang mahal adalah objek
    gayanya. Belang selang-seling dilewati di atas 2.000 baris.

    Ikutannya: ekspor penuh butuh **± 170 MB** memori — di atas `memory_limit`
    bawaan 128 MB. Controller-nya menaikkan sendiri ke 512 MB; kalau cPanel
    tidak mengizinkan, yang dipangkas adalah sheet rincian, bukan angkanya.

15. **`setValue()` menebak tipe — NIK & No. KK 16 digit jadi bilangan** lalu
    tampil `1,80123E+15` dan kehilangan presisi. Kolom bukan-angka wajib
    `setValueExplicit($v, DataType::TYPE_STRING)`. (exceljs di portal Next.js
    tidak kena karena JS string memang disimpan sebagai teks.)

---

## 6. Aturan keras (warisan journal workspace)

1. 🔴 **Berkas warga (KTP/KK/akta/selfie) TIDAK PERNAH boleh di `public/`.**
   Tempatnya `storage/app/private/`, disajikan `BerkasController` dengan Gate:
   petugas = semua, warga = miliknya sendiri, sisanya **404** (bukan 403 —
   403 mengonfirmasi berkasnya ada). Aturan ini sudah dilanggar **dua kali** di
   portal Next.js.
2. 🔴 **Jangan sentuh sistem deploy SAIBATIN-Next / SIDAKO / TIDORE.**
   Project ini berdiri sendiri.
3. **Migrasi/seed ke DB produksi wajib dikonfirmasi user dulu.** Seed ke
   produksi tetap haram — server itu pernah datanya tertimpa seed.
4. **Tidak ada rahasia di berkas markdown mana pun.** Tulis nama variabelnya saja.
5. **Tab browser yang tidak ditampilkan berhenti meng-compositing** →
   screenshot gagal & klik tidak sampai. Verifikasi lewat `read_page` /
   `javascript_tool` / log server.

---

## 7. Temuan yang masih menunggu keputusan user

1. **Status akun terbaca tanpa sandi.** Login memeriksa status **sebelum**
   sandi — mengikuti portal lama apa adanya. Akibatnya siapa pun yang mengetik
   NIK dengan sandi asal tetap tahu NIK itu terdaftar dan berstatus apa.
   Bukan regresi, tapi tetap kebocoran informasi. Belum diputuskan.

2. 🔴 **560,2 MB / 1.467 scan KTP-KK-akta warga masih terekspos di
   `public/uploads/` PRODUKSI** (`kelahiran_1`, `kematian`, `kk*`, dst).
   Yang sah publik hanya 147,7 MB (`berita`/`gallery`/`galeri`/`produk`).
   Pemindahannya menyentuh server yang sedang melayani warga → harus jadi
   langkah cutover tersendiri yang dikonfirmasi, bukan disisipkan ke deploy.

3. **Versi PHP & ekstensi di cPanel belum dicek** (butuh PHP ≥ 8.2 +
   `gd`/`imagick`, `zip`). Belum dibahas karena user minta deployment ditunda.
   Ditambah sejak 13 Agu: **`memory_limit` harus boleh dinaikkan ke 512 MB**
   (ekspor Excel penuh memakai ± 170 MB, lihat §5 no. 14), dan paket unggahan
   bertambah **12 MB** oleh mesin OCR di `public/ocr/`.

4. 🔴 **Warga tidak akan bisa membuka berkas WARISAN miliknya sendiri.**
   `BerkasController` membaca kepemilikan dari prefix nama berkas
   (`<uid>_<timestamp>.<ext>`) — konvensi port ini. 1.485 berkas warisan
   berbentuk lain: `/uploads/kkpisahkk/KKP01001.1778552208/1778552123_ngm.
   gedungcahyakuningan_KKP01_….jpg`, segmen pertamanya **timestamp**, bukan id.
   Petugas tetap bisa membuka semuanya; warga dapat **404 untuk berkasnya
   sendiri**. Sifatnya menolak (bukan membocorkan), jadi tidak mendesak — tapi
   wajib ditutup sebelum cutover. Perbaikan yang disarankan: untuk jalur yang
   tidak berpola `<uid>_`, tentukan pemilik lewat `t_berkas.path` →
   `t_permohonan.user_id`. **Belum dikerjakan** — menyentuh kontrol akses, jadi
   menunggu keputusan. Rinciannya `_analisis/05-FASE-5-DASHBOARD.md` §4.

   Menyertainya: saat cutover, folder di `public/uploads/` harus dipindah ke
   `storage/app/private/permohonan/` dengan **nama folder persis seperti di
   produksi** (`kkpisahkk`, `kelahiran_1`, `kematian`, …) — route
   `/uploads/{jalur}` memetakannya apa adanya, bukan lewat slug baru port ini.

---

## 8. Berkas sementara yang HARUS dibuang nanti

**Sudah bersih — tidak ada lagi berkas sementara.** Yang pernah ada di sini dan
kini sudah dibuang:

| Berkas | Dibuang |
|---|---|
| `Pages/Dashboard.jsx` + blok `ringkas` di rute `/dashboard` | 12 Agu — digantikan `DashboardController` + `Pages/Dashboard/Beranda.jsx` |
| `routes/_diagnostik.php` + `resources/views/status.blade.php` (rute `/_status`) | 13 Agu — dashboard sungguhan sudah memeriksa hal yang sama |
| `resources/views/welcome.blade.php` | 13 Agu — halaman bawaan Laravel, tidak pernah dirutekan |

---

## 9. Rujukan

| Berkas | Isi |
|---|---|
| `_analisis/00-KELAYAKAN-DAN-PETA-PORT.md` | analisis kelayakan, inventaris terukur, peta 20 tabel, 12 titik sulit, 11 fase |
| `_analisis/01-FASE-1-SELESAI.md` | bukti skema identik + 4 temuan yang mengoreksi asumsi |
| `_analisis/02-FASE-2-AUTENTIKASI.md` | hasil uji auth + keputusan yang beda dari portal lama |
| `../saibatin/saibatin-platform/` | **sumber port** — jangan diubah |
| `../app.pesbar.002_debug_20260708/` | Laravel 9 asli, 200 blade view, `routes/web.php` 495 baris |
| `../saibatin/REPORT.md` | pola route Laravel lama (`index/getdata/postdata/fetchdata/procdata/upload/downloadPDF`) |
| `../saibatin/saibatin-platform/PROMPT-DISABILITAS.md` | spek 14 kontrol widget aksesibilitas |
| `../journal.md` | keadaan seluruh workspace |

### Angka acuan (hasil pembacaan penuh 7 Agu 2026 — pakai ini, jangan ukur ulang)

- Sumber Next.js: **380 berkas · 59.475 baris**, tapi **20.206 baris di antaranya
  KODE MATI** (15 `*Modal.tsx` + `form-map`/`form-shell`/`pemohon-nik-field`/
  `kk-scan-field` — tidak diimpor siapa pun) → permukaan port nyata **± 39.300 baris**.
- **52 halaman · 66 berkas route = 90 handler HTTP · 20 tabel · 12 blok CMS · 15 layanan**.
- **4 userlevel**, bukan 3: 1 Super Admin (7) · 2 Operator (5) · 3 Warga (1.234) ·
  **4 Operator OPD (140)**.
- `m_jenis_permohonan` **17 baris tapi hanya 15 punya formulir** (SAKINAH &
  PENCETAKAN_KTP tanpa form).
- `t_static_contents` juga menampung **konfigurasi** (`pelayanan.jam`,
  `pelayanan.visibilitas`), bukan cuma konten.
- Bundel hasil build sekarang: **488,9 KB JS (140 KB gzip) + 78,1 KB CSS**
  (12 Agu, sesudah 10 halaman dashboard Fase 5; sebelumnya 367 KB + 60 KB).
  Bundel inti tidak berubah berarti sesudah sinkronisasi 13 Agu: tesseract.js
  hanya **15,8 KB** chunk terpisah yang dimuat saat foto KTP dipilih; mesin
  wasm + data bahasanya berkas statis di `public/ocr/` (**12 MB**, di luar bundel).
- Ekspor Excel di atas data asli: satu bagian ± 8,5 detik, seluruh bagian
  **± 12 detik / 150 MB / berkas 858 KB** (13 sheet).
