# Journal — SAIBATIN-LARAVEL (`saibatin-laravel`)

> **Terakhir diperbarui: 2026-08-17** · anak dari [`../journal.md`](../journal.md) §3.5
>
> 🔴 **Sebelum melanjutkan, baca [`HANDOFF.md`](HANDOFF.md).** Berkas ini cuma ringkasan
> keadaan; panduan kerja, cara menjalankan, dan 17 jebakan ada di sana.

## 1. Identitas project

| | |
|---|---|
| Apa ini | **port SAIBATIN kembali ke PHP** — Laravel 12.65 + Inertia + React, MySQL |
| Tujuan | **mengganti total** portal SAIBATIN Next.js |
| Umur | baru — dimulai **7 Agustus 2026** |
| Port dev | **3104** (`saibatin-laravel-dev`) — 🔴 DIPATENKAN · login uji `admin`/`admin123` |
| Git | ✅ **sudah di-git** — `a992920` (port Fase 1–5) · `7fc49f6` (fokus input lepas) · `df76489` (kit shadcn/ui) · `a02cb7e` (Fase 7: kerangka, beranda, berita, galeri) · `db70038` (`/profil`, lonceng, situs publik ala SIDAKO) · `3d6f6e6` (widget aksesibilitas) |
| Server | **belum menyentuh server sama sekali** |

Ini **bukan** fork codebase Next.js. Project terpisah. Jebakan keluarga Next.js
(journal induk §5) **tidak berlaku** di sini — jebakan Laravel ada di `HANDOFF.md` §5.

**Kit UI (keputusan user 14 Agu 2026).** Kontrol formulir memakai **shadcn/ui**,
17 komponen disalin dari portal Next.js ke `resources/js/Components/ui/`
(+ `SearchSelect`). Alasannya bukan selera: portal yang sekarang live memang
memakai kit itu, jadi menyalinnya = memulangkan desain aslinya, bukan mendesain
ulang. Keputusan lama "hindari shadcn demi ukuran bundel" (dulu tertulis di
`Components/Dasbor.jsx`) **sudah dicabut** — harganya CSS 78 → 132 KB
(20,6 KB gzip) dan JS inti praktis tetap (328 → 329 KB).

**Turbine UI** (`brandymedia/turbine-ui-core`) yang ditanyakan user itu **nyata**
tapi komponen **Blade**, jadi tidak bisa dipakai di halaman Inertia+React.
Tempatnya kalau mau dipakai adalah **Fase 7 (situs publik)** yang memang Blade.
Catatan pemasangannya: dokumennya menyuruh menambah path ke `content` di
`tailwind.config.js` (gaya Tailwind v3); project ini Tailwind v4, padanannya
satu baris `@source '../../vendor/brandymedia/turbine-ui-core/**/*.php';`
di `resources/css/app.css`.

## 2. Keputusan yang sudah dikunci user — jangan dibuka ulang

- **Inertia + React hibrida**: halaman publik ber-SEO pakai **Blade + React island**;
  dashboard & formulir pakai **Inertia**.
- 🔴 **Tanpa Inertia SSR.** Itu satu-satunya bagian yang butuh daemon Node, dan cPanel
  user **tidak punya Application Manager / Node.js selector**.
- **OCR KTP pindah ke browser** (tidak lagi di server).
- **User yang mengunggah manual.**
- **Pembahasan deployment ditunda** sampai memang siap.

## 3. Kemajuan

| Fase | Isi | Status |
|---|---|---|
| **1** | 20 tabel + 20 model | ✅ selesai — skema hasil migration **0 baris beda** dari DB asli (satu pengecualian disengaja: kolom `users.user_ktp`, 12 Agu) |
| **2** | login, 4-status, Cek Status, Ajukan Ulang, pendaftaran + OTP + kamera selfie, lupa/reset sandi, penyaji berkas berkontrol akses | ✅ selesai — **semuanya diuji lewat browser** |
| **3** | endpoint JSON lapisan publik & warga (36 rute), kontrak `{error, success, data, html}` **dipertahankan** | ✅ selesai |
| **4** | 15 layanan permohonan: `config/layanan.php` + **satu** renderer `FormLayanan.jsx` + catch-all `LayananController` | ✅ selesai — diuji end-to-end |
| **5** | **dashboard petugas** — 16 halaman + rute `/api/admin/*`, `/api/media/*`, `/api/demografi` di 14 controller | ✅ **selesai** — `konten` menyusul 17 Agu bersama Mode Edit |
| **7** | **situs publik** — beranda, berita, galeri, Produk/PPID, Pusat Bantuan, WBS, Hubungi Kami, SKM, GIS & demografi, halaman ketentuan, peta situs, widget aksesibilitas | 🟢 **selesai** kecuali tampilan khusus `/produk/produk-disdukcapil` |
| **6** | **Mode Edit + Konten Halaman** — penyuntingan isi situs dari halamannya sendiri | ✅ selesai 17 Agu, diuji di browser |

**Mode Edit & Konten Halaman (17 Agu) — halaman dashboard terakhir.**
`/dashboard/konten` bukan formulir melainkan *site editor*: kolom kiri seluruh
menu navbar, dan bagian tengahnya me-render halaman publiknya sendiri di dalam
iframe `?editmode=1`. Penyuntingannya terjadi di halaman publik — Super Admin
menyalakan mode edit, tiap bagian yang bisa disunting diberi garis putus-putus +
pensil, dan pensilnya membuka dialog berisi medan blok itu.

Karena halaman publik port ini **Blade**, bukan React seperti portal Next.js,
penandanya atribut (`data-blok="beranda.hero"`) dan bukan komponen
`<EditableBlock>`; island `Publik/ModeEdit.jsx` yang menyusul memasang garis &
pensilnya. Tiga hal ikut tertutup bersama ini:
🔴 `PUT /api/admin/static-content` selama ini **menolak semua kunci halaman
informasi** (`info.produk.sop` dkk) karena penjaganya cuma melihat
`config('konten.blok')`; **tombol edit `profile-tabs`** — sisa sinkronisasi
13 Agu yang memang baru bisa dikerjakan bersama mode edit; dan **panel unggah
dokumen** dari halaman publik yang sengaja ditunda di Fase 7.
Detail + hasil ujinya: [`_analisis/08`](_analisis/08-MODE-EDIT-DAN-KONTEN.md).

🔴 **Situs publik mengikuti SIDAKO, bukan SAIBATIN** (keputusan user 14 Agu).
Portal SAIBATIN Next.js yang jadi sumber port tertinggal; SIDAKO sudah menerima
permintaan dinas dan susunan menunya satu generasi lebih maju. Ini **mengubah**
target "100% sama dengan portal Next.js" **khusus situs publik** — dashboard
tetap mengacu ke SAIBATIN. Yang disalin hanya susunan & fitur; branding, geo,
nama daerah, dan zona waktu tetap Pesisir Barat/WIB. Peta lengkapnya:
[`_analisis/07`](_analisis/07-FASE-7-SITUS-PUBLIK.md) §8.

**Sanity check input → backend (14 Agu).** Seluruh panggilan `lib/api.js` +
formulir Inertia ditelusuri dan dicocokkan dengan rute & pembacaan controller.
Lima puluh lebih panggilan cocok; **empat tidak**, dan keempatnya sudah ditutup:

1. 🔴 **`/profil` tidak pernah dibuat** padahal `LoginController` mengarahkan ke
   sana pada login pertama warga → **login yang berhasil berakhir 404**, dan
   `/api/profil*` tidak punya pemanggil sama sekali.
2. 🔴 **Lonceng notifikasi tidak pernah dibuat** — backend rajin membuat
   notifikasi, tapi tak satu pun bisa dilihat.
3. Kontrak `change-password` menyimpang dari portal Next.js (nama field) dan
   kehilangan penjaga "sandi baru ≠ sandi lama".
4. `?q=` dari pencarian beranda hilang diam-diam di pemilih layanan.

🔴 Fase 5 dibangun dalam sesi 12 Agu yang **terputus sebelum sempat di-build atau
diuji**: `npm run build` tak pernah jalan (bundel tertinggal 4 hari), 2 halaman
(`PengajuanBaru`, `PengajuanForm`) tak pernah dibuat padahal controller, rute, dan
tautan sidebar-nya sudah ada, dan build-nya sendiri **gagal total** karena nama
komponen bertabrakan dengan nama ikon lucide. Ketiganya sudah ditutup 12 Agu
malam — riwayatnya di [`_analisis/05-FASE-5-DASHBOARD.md`](_analisis/05-FASE-5-DASHBOARD.md).

## 4. Angka acuan — **jangan diukur ulang**

- Sumber Next.js: **380 berkas / 59.475 baris**
- Di antaranya **20.206 baris kode mati** (15 `*Modal.tsx` dkk, tidak diimpor siapa pun)
- → **permukaan port nyata ± 39.300 baris**
- **52 halaman · 66 route file = 90 handler · 20 tabel · 12 blok CMS · 15 layanan**
- 🔴 **4 userlevel, bukan 3** — level 4 = **Operator OPD**, 140 akun.

🔴 **Target dikunci user (12 Agu): 100% sama UI & fungsi dengan portal Next.js**,
grafik baru memakai **Highcharts**. Peta paritas & daftar kerjanya:
[`_analisis/06-PARITAS-NEXTJS.md`](_analisis/06-PARITAS-NEXTJS.md) — **15 dari 16**
halaman dashboard sudah setara; situs publik belum dimulai.

**Sinkronisasi dengan portal Next.js (13 Agu).** Portal Next.js terus
dikerjakan selama port berjalan; 5 commit terakhirnya (9–13 Agu) + perubahan
yang masih di working tree-nya sudah disusul: OTP dimatikan, **Foto KTP wajib**
di pendaftaran, **OCR KTP** yang mengisi NIK/No.KK/Nama otomatis, gambar dikirim
**base64 polos** (WAF cPanel memblokir data URI), **ekspor Excel statistik
dashboard** berkop surat, dan penampil foto layar penuh di detail akun.
Dua perubahan sempat tertunda karena menyunting **halaman publik** yang waktu itu
belum ada ([`_analisis/06`](_analisis/06-PARITAS-NEXTJS.md) §2.9). Keadaannya
sekarang, sesudah Fase 7:

- **Data kontak & zona waktu Pengaduan — sudah beres.** Alamat, email, dan jam
  layanan ada di `config/info-halaman.php` grup `hubungi-kami`, memakai data
  Pesisir Barat dan **WIB**. (Portal Next.js sempat menulis **WITA** karena
  disalin mentah dari SIDAKO — kesalahan itu tidak ikut ke sini.)
- **Posisi tombol edit `profile-tabs` — sudah beres (17 Agu).** Tombolnya kini
  duduk di dalam kartu, sebaris dengan judul panel, dan kuncinya ikut tab yang
  sedang tampil. Ini memang baru bisa dikerjakan bersama **mode edit**.

🔴 OCR-nya **di browser**, bukan di server (keputusan user sejak awal: cPanel
hanya PHP). Mesin tesseract + data bahasa Indonesia dilayani sendiri dari
`public/ocr/` — **12 MB berkas statis**, tidak menyentuh CDN, dan CPU server
tidak terpakai sama sekali.

🚫 **Fitur Tiket & Chat dihapus user (13 Agu).** Kode, rute, dan config-nya
dibuang; model + migrasi + tabelnya **tetap** karena penjaga penghapusan akun
bergantung padanya dan produksi masih menyimpan tiket warga. Rincian:
[`_analisis/06`](_analisis/06-PARITAS-NEXTJS.md) §2.8.

## 4b. Kuesioner SKM resmi dinas (18 Agu) — 9 unsur → 16 pertanyaan

Berkas Word dari user ("Daftar Pertanyaan Survei Kepuasan Masyarakat Disdukcapil
Kabupaten Pesisir Barat") **mengganti** kuesioner 9 unsur: 16 pertanyaan skala
1–4 dengan dua ragam label (setuju / sesuai), identitas responden yang lebih
lengkap (instansi, pendidikan, produk layanan, **disabilitas & jenisnya**), dan
kolom keluhan. Seluruh identitas **opsional**; yang wajib hanya 16 penilaian.

🔴 Kunci jawabannya **`p1`–`p16`, bukan `0`–`15`**: 204 responden lama menyimpan
jawaban dengan kunci angka, dan memakai indeks angka lagi membuat jawaban mereka
terbaca sebagai 9 pertanyaan pertama kuesioner baru — rekap yang tampak wajar
tapi salah. Rekap dashboard menampilkan **dua generasi terpisah**, sementara
**IKM dihitung dari rata-rata tiap responden** sehingga keduanya tetap ikut
(205 responden → IKM 90,90 / mutu A).

Identitas barunya jadi **kolom** (migrasi aditif & nullable), bukan JSON, supaya
bisa direkap: sebaran pendidikan, pekerjaan, produk layanan, dan jumlah
responden penyandang disabilitas. Detail & hasil uji:
[`_analisis/09`](_analisis/09-KUESIONER-SKM-2026.md).

## 4c. Cutover ke cPanel — paket siap, server belum disentuh (18 Agu)

Keputusan user: **cutover sekarang** (bukan subdomain uji), **memakai DB
produksi yang sekarang**, dan **kuesioner SKM ditunjukkan ke dinas dulu**
sebelum warga boleh mengisi.

- Paket unggah dibangun ulang berisi seluruh pekerjaan 17–18 Agu:
  `deploy/dist/saibatin-app.zip` (40,8 MB) + `saibatin-public.zip` (47,3 MB),
  diperiksa isinya (chunk ModeEdit/Konten/FormSkm ada, mesin OCR ada, `.env`
  **tidak** ikut, tidak ada satu pun scan warga di paket publik).
- 🔴 **DB produksi tidak diimpor dari laptop.** Yang dijalankan hanya dua
  migrasi aditif lewat `deploy/sql/2026-08-18_siapkan-db-produksi.sql`
  (`users.user_ktp` + 5 kolom identitas SKM) — keduanya nullable, tidak
  menyentuh satu baris data pun, dan aman dijalankan **sebelum** cutover saat
  portal lama masih melayani warga.
- **Sakelar `SKM_TERBUKA`** (bawaan `false`): warga melihat "survei sedang
  disiapkan", petugas yang login melihat formulir 16 pertanyaan lengkap dengan
  spanduk pratinjau. Penjaganya di dua lapis — halaman **dan** `POST /api/skm`.
- Blocker lama "warga tidak bisa membuka berkas warisannya" **sudah tertutup**
  (jalur cadangan `t_berkas.path` di `BerkasController`), tapi tetap masuk
  daftar uji cutover karena hanya di produksi pola nama berkasnya bisa
  dipastikan.
- 🔴 **Penghalang yang tersisa**: 560,2 MB scan warga di `public_html/uploads/`
  harus dipindah ke `storage/app/private/permohonan/` dengan nama folder persis
  seperti di produksi — kalau `public_html` ditimpa sebelum itu, berkasnya
  hilang.

Langkah demi langkahnya: [`deploy/RUNBOOK-CPANEL.md`](deploy/RUNBOOK-CPANEL.md).
Seluruh langkah server dikerjakan user sendiri; sesi ini tidak menyentuh server.

## 5. Antrean

1. ~~Halaman Konten (dashboard)~~ — **selesai 17 Agu** bersama Mode Edit
   ([`_analisis/08`](_analisis/08-MODE-EDIT-DAN-KONTEN.md)). Dengan ini seluruh
   16 halaman dashboard sudah ada.
2. 🟡 **`/produk/produk-disdukcapil`** masih memakai view informasi generik;
   portal Next.js punya tampilan khusus (153 baris). Isinya sama, tata letaknya
   belum. Satu-satunya sisa Fase 7.
3. ~~Belum di-git~~ — **beres 14 Agu.** Lima commit, ada titik pulih.
4. **Menunggu keputusan user:** berkas warisan tidak terbuka untuk warga
   pemiliknya (HANDOFF §7 no. 4) — wajib ditutup sebelum cutover.
4. Deployment: **sengaja ditunda** atas keputusan user. Dua syarat baru yang
   muncul 13 Agu dan harus dicek saat deployment dibahas: cPanel harus
   mengizinkan **`memory_limit` 512 MB** (ekspor Excel penuh ± 170 MB) dan
   paket unggahan bertambah **12 MB** oleh mesin OCR di `public/ocr/`.
5. **Portal Next.js masih terus berubah.** Sinkronisasi 13 Agu menyusul sampai
   commit `1f435fd` + working tree-nya. Perubahan sesudah itu perlu disusul lagi;
   yang menyentuh halaman publik baru bisa ikut setelah Fase 7.

## 6. Referensi

| Berkas | Isi |
|---|---|
| [`HANDOFF.md`](HANDOFF.md) | **panduan melanjutkan** — status, cara jalan, 17 jebakan, berkas sementara |
| `_analisis/00…` | kelayakan & peta port |
| `_analisis/01…` | bukti Fase 1 (perbandingan skema) |
| `_analisis/02…` | hasil uji auth (Fase 2) |
| `_analisis/03…` · `04…` | endpoint JSON (Fase 3) · 15 layanan (Fase 4) |
| `_analisis/05…` | dashboard petugas (Fase 5) + sesi yang terputus & apa yang ditutup |
| `_analisis/06…` | **peta paritas dengan portal Next.js** — dipakai sebagai daftar kerja |
| `_analisis/07…` | situs publik (Fase 7) |
| `_analisis/08…` | **Mode Edit + halaman Konten Halaman** (17 Agu) |
| [`../saibatin/saibatin-platform/JOURNAL.md`](../saibatin/saibatin-platform/JOURNAL.md) | portal yang sedang digantikan — **masih melayani warga** |
| `../app.pesbar.002_debug_20260708/` | Laravel 9 asli SAIBATIN — rujukan "dulu fitur ini bentuknya bagaimana" |
| [`../journal.md`](../journal.md) · [`../HISTORY.md`](../HISTORY.md) §L, §O | keadaan workspace · detail langkah port · sinkronisasi 13 Agu |
