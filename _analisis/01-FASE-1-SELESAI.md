# Fase 1 — Kerangka, 20 Migration, 20 Model · SELESAI & TERVERIFIKASI

> 2026-08-07 · Laravel **12.65.0** · PHP **8.3.28** · MySQL **8.0.36**
> Belum menyentuh server. Semua di laptop.

---

## 1. Yang sudah berdiri

| | |
|---|---|
| Kerangka | Laravel 12.65.0 di `saibatin-laravel/` |
| DB kerja | **`saibatin_lv`** — klon `saibatin` (DB dev Next.js): 1.386 akun · 11.902 permohonan · 1.485 berkas · 1.032 baris demografi |
| Migration | **23** (3 infrastruktur Laravel + **20 tabel domain**) |
| Model Eloquent | **20**, semuanya terbaca dari data asli |
| Konfigurasi | `.env`, `config/hashing.php`, `AppServiceProvider` |

DB `saibatin` yang asli **tidak disentuh** — portal Next.js tetap bisa dijalankan
berdampingan sebagai pembanding.

---

## 2. Bukti, bukan klaim

### 2.1 Migration menghasilkan skema yang PERSIS sama

Migration dijalankan ke DB kosong (`saibatin_lv_test`), lalu skemanya dibandingkan
dengan DB asli:

```
A (DB asli)   : 295 baris
B (migration) : 295 baris
>>> SKEMA IDENTIK — 0 baris beda di 20 tabel <<<
```

Yang ikut terverifikasi sama: tipe kolom, `datetime(3)`, kolom `json`, nilai
default, nullability, **nama index**, **nama constraint FK**, dan aksi
`ON DELETE`/`ON UPDATE`. Artinya aplikasi Laravel ini bisa diarahkan ke DB yang
sudah ada tanpa drift, dan instalasi baru menghasilkan bentuk yang sama.

### 2.2 Eloquent membaca data warisan

```
20/20 model OK
payload JSON       → array, 19 kunci, pemohonnama=Samuel
konten/data JSON   → array
datetime(3)        → 2026-07-28 09:08:08.771 (milidetik utuh)
relasi             → permohonan #35835 → user 'Administrator' (level 1), 5 berkas
                     kecamatan 'BENGKUNAT' → 9 pekon/kelurahan
```

---

## 3. 🔴 Empat temuan yang mengoreksi asumsi

### 3.1 Hash bcrypt `$2a$` membuat Laravel melempar 500 — bukan sekadar gagal login

Tabel `users` berisi dua varian hash:

| prefix | jumlah | asal |
|---|---|---|
| `$2y$` | **1.378** | portal Laravel 9 (`password_hash` PHP) |
| `$2a$` | **7** | portal Next.js (paket `bcryptjs`) |
| `!arsip-tidak-bisa-login` | 1 | akun arsip sentinel — memang tak boleh masuk |

`password_verify()` PHP memverifikasi **keduanya** dengan benar (diuji: cocok untuk
sandi benar, menolak sandi salah). Yang bermasalah adalah penjaga bawaan Laravel:
`BcryptHasher::check()` memanggil `password_get_info()` lebih dulu, dan PHP hanya
mengenali `$2y$` sebagai `PASSWORD_BCRYPT` — untuk `$2a$` ia mengembalikan
`algoName = "unknown"`, lalu Laravel **melempar**:

```
RuntimeException: This password does not use the Bcrypt algorithm.
```

Akibatnya HTTP 500, bukan "sandi salah". Akun sentinel `!arsip…` kena hal yang sama.

**Perbaikan:** `config/hashing.php` dibuat khusus dengan `'verify' => false`,
sehingga pengecekan diserahkan ke `password_verify()`.

> Menulis ulang prefix `$2a$` → `$2y$` di database **sengaja tidak dipakai**: itu
> menyentuh hash sandi warga secara massal untuk masalah yang selesai dengan satu
> baris konfigurasi.

⚠️ Di DB dev ini yang ber-`$2a$` kebetulan hanya 7 akun uji. **Di produksi jumlahnya
kemungkinan jauh lebih besar** — setiap warga yang mendaftar lewat portal Next.js
sejak live memiliki hash `$2a$`. Perlu dihitung ulang di DB produksi sebelum cutover.

### 3.2 Panjang string bawaan Laravel ≠ Prisma

`$table->string()` Laravel menghasilkan `varchar(255)`; seluruh tabel ini dibuat
Prisma dengan `varchar(191)`. Inilah **satu-satunya** perbedaan yang tersisa saat
skema pertama kali dibandingkan. Diperbaiki dengan `Schema::defaultStringLength(191)`
di `AppServiceProvider`. Kolom yang memang beda (`t_produk.judul` 255,
`t_kunjungan.visitor_id` 64) menuliskan panjangnya eksplisit sehingga tidak terpengaruh.

### 3.3 Ada EMPAT level user, bukan tiga

Komentar di `schema.prisma` hanya menyebut `1=superadmin, 2=operator, 3=warga`.
Isi tabel `m_userlevels`:

| level | nama | akun |
|---|---|---|
| 1 | Super Admin | 7 |
| 2 | Operator | 5 |
| 3 | Warga | 1.234 |
| **4** | **Operator OPD** | **140** |

Sebaran status: **1.297 aktif · 89 menunggu**.

### 3.4 Dua hal yang mudah salah baca di master data

- **`m_jenis_permohonan` berisi 17 baris, formulirnya hanya 15.** `SAKINAH` dan
  `PENCETAKAN_KTP` ada di master tanpa form. Daftar layanan yang bisa diajukan
  harus bersumber dari config formulir, bukan dari jumlah baris tabel.
- **`t_static_contents` juga menampung konfigurasi**, bukan hanya konten:
  `pelayanan.jam` (jam layanan) dan `pelayanan.visibilitas` (layanan yang tampil).
  Jangan menyaring isinya hanya dengan daftar blok konten.

---

### 3.5 🔴 `config/app.php` Laravel 12 mem-hardcode `'UTC'` dan mengabaikan `APP_TIMEZONE`

Ketahuan **dari tampilan**, bukan dari terminal: badge zona waktu di halaman
diagnostik menulis `UTC` padahal `.env` sudah berisi `APP_TIMEZONE=Asia/Jakarta`.
Kerangka Laravel 12 menulis `'timezone' => 'UTC'` sebagai nilai mati — tidak ada
`env()`, dan tidak ada peringatan apa pun bahwa setelan `.env` diabaikan.

Ini berbahaya di project ini secara khusus: seluruh kolom waktu di DB warisan
disimpan sebagai **waktu lokal Jakarta** oleh portal Laravel 9 maupun Next.js.
Kalau aplikasi membacanya sebagai UTC, setiap tanggal yang ditampilkan dan setiap
perhitungan "hari ini" (kartu pengunjung, filter periode, rekap harian) meleset
7 jam. Bandingkan dengan insiden di project saudara: MySQL yang restart tanpa
`default-time-zone` terkunci membuat 17 kolom `created_at` melompat +9 jam.

**Perbaikan:** `'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta')`.

### 3.6 Menempelkan Laravel ke DB yang sudah berisi tabel

`saibatin_lv` adalah klon, jadi 20 tabel domainnya sudah ada tapi tabel
infrastruktur Laravel (`sessions`, `cache`, `jobs`, `migrations`) belum. Halaman
pertama langsung 500: *Table 'saibatin_lv.sessions' doesn't exist*.

`php artisan migrate` polos tidak bisa dipakai — ia akan mencoba membuat ulang
20 tabel yang sudah ada. Urutan yang benar:

```bash
php artisan migrate:install                     # buat tabel `migrations`
# tandai 20 migration domain sebagai SUDAH jalan (tabelnya memang sudah ada)
# INSERT INTO migrations (migration,batch) VALUES ('2026_08_07_1000xx_…',1), …;
php artisan migrate --force                     # hanya 3 migration infrastruktur yang jalan
```

Urutan yang sama nanti berlaku saat aplikasi ini pertama kali diarahkan ke DB
mana pun yang skemanya sudah dibuat Prisma.

---

## 4. Catatan teknis kecil yang sempat menggigit

`trait PresisiMilidetik` semula mendeklarasikan `protected $dateFormat = '…'`.
PHP menolaknya: *"Model and PresisiMilidetik define the same property (\$dateFormat)
… considered incompatible"* — sebuah trait tidak boleh mendeklarasikan ulang
properti induk dengan default berbeda. Diganti memakai **trait initializer**
Eloquent (`initializePresisiMilidetik()`), yang dipanggil di konstruktor.

---

## 5. Berkas yang dihasilkan

```
app/Models/                 20 model + Concerns/PresisiMilidetik.php
app/Providers/AppServiceProvider.php   defaultStringLength(191) + preventLazyLoading
config/hashing.php          BARU — verify=false (lihat §3.1)
database/migrations/        23 migration
.env                        DB saibatin_lv, zona Asia/Jakarta, sesi database,
                            QUEUE=sync, cookie `saibatin_session`
```

---

## 6. Berikutnya (Fase 2 — Auth)

1. Guard sesi dengan kolom identitas **`user_id`** (NIK atau username), bukan `email`.
2. Middleware peran untuk **empat** level, dengan "petugas" = level 1 & 2.
3. Alur 4-status akun + `/cek-status` + Ajukan Ulang.
4. OTP (email / Fonnte WA), reset sandi lewat `forgotten_code`, reCAPTCHA v3 server-side.

**Masih menunggu dari user:** hasil `php -v && php -m` di cPanel, dan keputusan
lokasi uji (subdomain atau subfolder) karena SAIBATIN Next.js masih live di sana.
