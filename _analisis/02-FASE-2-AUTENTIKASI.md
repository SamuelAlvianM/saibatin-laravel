# Fase 2 — Autentikasi · SELESAI

> 2026-08-07 · Inertia **v3.3.1** · React 19 · Tailwind v4 · Vite 6
> Diuji langsung di browser (localhost:3303), bukan hanya di terminal.

---

## 1. Sudah jalan & terbukti dari browser

| Uji | Hasil |
|---|---|
| Halaman `/login` tampil | seluruh teks, tautan, dan catatan asli dipertahankan |
| Sandi salah | `Info: Password salah (L-04)` |
| Sandi benar (`admin`/`admin123`, hash `$2a$`) | masuk → Dashboard |
| Data nyata setelah login | **11.902** permohonan · 1.234 akun warga · 89 menunggu |
| Akun berstatus **Menunggu** | `Info: Pendaftaran akun Anda sedang diproses… (L-03)` + kartu **Cek Status** dengan tautan `/cek-status?nik=…` |
| Logout | sesi hangus → `/login` |
| Buka `/dashboard` tanpa sesi | dialihkan ke `/login` |

Berkas statis: **341 KB JS (107 KB gzip)** + 58 KB CSS di `public/build`.
Server hanya menjalankan PHP — tidak ada Node.

## 2. Yang dibangun

```
app/Http/Controllers/Auth/LoginController.php   login/logout, 4-status, redirect aman
app/Http/Middleware/PastikanPeran.php           peran:petugas | peran:1 | peran:3,4
app/Http/Middleware/HandleInertiaRequests.php   pengganti Redux authSlice
app/Support/StatusAkun.php                      0 Menunggu · 1 Aktif · 2 Ditolak · 3 Nonaktif
app/Support/AlasanTolak.php                     penyandian alasan tolak ke users.ket
app/Support/Balasan.php                         kontrak {error,success,data,html}
app/Services/Recaptcha.php                      verifikasi v3 + peringatan fail-open
config/situs.php, config/services.php           identitas situs, kunci reCAPTCHA & Fonnte
resources/js/Pages/Auth/Login.jsx               port dari LoginContent.tsx
resources/js/Pages/{Dashboard,Segera}.jsx       SEMENTARA — diganti di Fase 5
```

**Redux hilang, dan itu memang tujuannya.** `store/` (553 baris) beserta
`GET /api/auth/session` tidak diperlukan lagi: keadaan sesi menempel pada setiap
respons Inertia, jadi tidak ada permintaan tambahan dan tidak ada jeda "belum
tahu siapa yang login" saat render pertama.

---

## 3. Keputusan yang diambil, beserta alasannya

**`Auth::attempt()` tidak dipakai.** Akun yang sandinya benar tapi statusnya
belum aktif harus mendapat pesan berbeda-beda beserta jalan keluarnya; itu hanya
bisa dibedakan bila user diambil lebih dulu.

**Kode galat `L-00`…`L-99` dipertahankan** — petugas dinas terbiasa menyebut
kodenya saat melapor.

**Pembatas percobaan `throttle:5,1` DITAMBAHKAN** (tidak ada di portal lama).
Identitas login warga adalah NIK, dan daftar NIK bersifat semi-publik, sehingga
tebak-sandi massal sangat murah dilakukan tanpa pembatas.

---

## 4. ⚠️ Temuan yang perlu keputusan: status akun terbaca tanpa sandi

Urutan pemeriksaan login **mengikuti portal lama apa adanya**: status akun dicek
**sebelum** sandi diverifikasi. Akibatnya siapa pun yang mengetik NIK dengan
sandi asal-asalan tetap bisa mengetahui apakah NIK itu terdaftar dan berstatus
apa (Menunggu / Ditolak / Nonaktif). Ini terbukti saat pengujian di atas: akun
`157107…0041` memunculkan pesan L-03 padahal sandinya sengaja diisi ngawur.

Ini **bukan regresi** — perilakunya sama persis di portal Next.js maupun Laravel 9.
Dan ada alasannya: warga yang lupa sudah pernah mendaftar memang perlu diberi
tahu keadaannya, dan tidak semua warga ingat sandinya.

Tetap saja itu kebocoran informasi. Tiga pilihan:

1. **Biarkan** (paling setia pada perilaku sekarang, warga paling tertolong).
2. **Cek sandi dulu**, baru tampilkan status. Aman, tapi warga yang lupa sandi
   sekaligus lupa status jadi buntu — padahal itu justru kasus tersering.
3. **Biarkan, tapi diperketat**: turunkan throttle khusus untuk balasan berstatus,
   dan jangan bocorkan apa pun untuk akun non-warga.

Belum diputuskan; sementara ini pilihan 1 (perilaku asli) yang berjalan.

---

## 5. Bagian kedua — pendaftaran, OTP, lupa sandi, cek status

### 5.1 Diuji end-to-end di browser

| Uji | Hasil |
|---|---|
| `/register` tampil | 11 kecamatan dari DB, termasuk ejaan resmi **NGARAS** & **PULAU PISANG** |
| Kirim OTP (tanpa SMTP) | kode dev muncul di layar (`210092`) — alur tetap bisa diuji |
| Verifikasi OTP | lencana **Terverifikasi** |
| Cek Status — akun **Menunggu** | label + pesan status yang benar |
| Cek Status — akun **Ditolak** | chip **NIK** & **Foto selfie**, alasan petugas, dua tombol tindakan |
| **Perbaiki Data Dulu** | `/register?nik=…` → data lama terisi, NIK & bagian foto **tersorot merah** |
| **Ajukan Ulang** | status 2 → 0, dan **12 petugas aktif** menerima notifikasi |
| Penyandian alasan tolak | `susun()` → `uraikan()` bolak-balik utuh (kolom + alasan) |
| 5 halaman auth | semuanya 200 |
| `/dashboard` tanpa sesi | dialihkan |
| `/uploads/selfie/…` tanpa sesi | **404** (bukan 403 — keberadaan berkas tidak dibocorkan) |

Keadaan DB dikembalikan persis seperti semula setelah pengujian: **89 menunggu ·
1.297 aktif**, dan 12 notifikasi uji dihapus.

### 5.2 Yang ditambahkan

```
app/Services/Otp.php             OTP stateless berbasis HMAC (tanpa tabel)
app/Services/Fonnte.php          pengirim WhatsApp
app/Services/Pemberitahuan.php   notifikasi in-app, best-effort
app/Services/FotoProfil.php      simpan selfie DI LUAR public/
app/Http/Controllers/Auth/{Register,CekStatus,Otp,Sandi}Controller.php
app/Http/Controllers/BerkasController.php   penyaji berkas berkontrol akses
resources/js/Components/{KartuAuth,AmbilSelfie}.jsx
resources/js/lib/api.js          pemanggil JSON + penjelasan jebakan CSRF
resources/js/Pages/Auth/{Register,CekStatus,LupaSandi,ResetSandi}.jsx
resources/views/emails/*.blade.php   4 templat surel
```

### 5.3 Keputusan yang berbeda dari portal lama — dan alasannya

**OTP tetap stateless (HMAC), bukan tabel DB.** Kode tidak pernah disimpan;
yang beredar hanya `challenge` bertanda tangan. Hosting target tidak punya
Redis, dan menyimpan OTP di MySQL berarti satu tabel + pembersihan berkala untuk
data yang umurnya 5 menit.

**Pembatas kirim-ulang OTP pindah dari memori ke Cache.** Portal Next.js memakai
`Map` in-memory. Di PHP itu tidak akan pernah berfungsi — setiap request adalah
proses baru, jadi penghitungnya selalu kosong dan pembatasnya lumpuh diam-diam.

**Lupa sandi memakai `forgotten_code` di tabel `users`, bukan
`password_reset_tokens` bawaan Laravel.** Mekanisme bawaan berkunci pada alamat
email, sedangkan portal ini berkunci pada NIK — dan dalam data warisan satu email
bisa dipakai beberapa NIK.

**Data lama hanya dikeluarkan untuk akun berstatus DITOLAK.** `/cek-status` dan
`/register?nik=` terbuka tanpa login; kalau status lain ikut dilayani, siapa pun
yang tahu NIK seseorang bisa memanen nama, KK, nomor HP, dan emailnya.

**Selfie tetap tanpa opsi unggah berkas.** Foto ini dipakai petugas untuk
mencocokkan pemohon dengan foto KTP-nya; kalau boleh diunggah, verifikasinya
kehilangan arti.

**`passwordnote` Laravel 9 tidak dihidupkan kembali.** Kolom itu dulu menyimpan
sandi dalam bentuk plainteks.

### 5.4 Tiga bug yang ketahuan hanya karena diuji lewat browser

1. **419 di Cek Status.** Header CSRF-nya `X-XSRF-TOKEN` berisi token mentah,
   padahal Laravel memperlakukan header itu sebagai nilai cookie TERENKRIPSI dan
   mencoba mendekripsinya. Yang benar `X-CSRF-TOKEN`. Pesannya tidak menyebut
   penyebabnya sama sekali. Diperbaiki dengan menyatukan semua pemanggilan JSON
   ke `resources/js/lib/api.js` supaya kesalahan ini tidak terulang per halaman.
2. **500 di Cek Status.** `$tolak` bernilai null untuk status selain DITOLAK,
   tapi tetap diakses sebagai array.
3. **Pesan "Pengajuan ulang terkirim" hilang seketika.** Pemuatan ulang otomatis
   sesudahnya menghapus pesannya sebelum sempat terbaca.

Plus satu yang dicegat sebelum sempat jalan: prop Inertia bernama **`key`** akan
ditelan React sebagai penanda internal dan tidak pernah sampai ke komponen —
halaman reset sandi akan selalu mengira tautannya tidak memuat kode. Dinamai
`kunci`.

### 5.5 Masih tersisa

- Endpoint JSON `/api/auth/*` untuk paritas kontrak penuh → **Fase 3**
- Halaman profil warga (`/profil`) tempat foto dilengkapi setelah login pertama

---

## 6. Jebakan toolchain yang kena di fase ini

1. **`inertiajs/inertia-laravel:^2.0` menolak Laravel 12.** Pin itu mengunci ke
   v2.0.0 yang hanya mendukung Laravel 10/11. Tanpa pin, Composer memilih
   **v3.3.1** yang benar.
2. **`@vitejs/plugin-react` terbaru (v6) menuntut Vite 8**, sedangkan Laravel 12
   mengirim Vite 6 → konflik peer. Dipasang **`^4.3.4`** (resolusi ke 4.7.0),
   bukan dipaksa dengan `--force`.
3. 🔴 **`build: { manifest: true }` di `vite.config.js` MERUSAK build.** Sejak
   Vite 5 opsi itu menaruh manifest di `public/build/.vite/manifest.json`,
   sedangkan Laravel mencarinya di `public/build/manifest.json` → setiap halaman
   500 dengan `ViteManifestNotFoundException`. `laravel-vite-plugin` sudah
   mengaturnya; jangan ditimpa. (Ini kesalahan sendiri, sudah dicatat sebagai
   komentar peringatan di berkas config.)
