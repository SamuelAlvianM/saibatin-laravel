# API Aplikasi Mobile — SAIBATIN

Panduan untuk pengembang aplikasi mobile (Android/iOS/Flutter/React Native) yang
tersambung ke portal SAIBATIN — Disdukcapil Kabupaten Pesisir Barat.

Datanya **satu** dengan website: permohonan yang diajukan dari aplikasi langsung
muncul di dashboard petugas web, dan sebaliknya.

---

## 1. Dasar

| | |
|---|---|
| Base URL produksi | `https://saibatin.pesisirbaratkab.go.id/api/mobile/v1` |
| Base URL lokal | `http://localhost:3104/api/mobile/v1` (`php artisan serve --port=3104`) |
| Format kirim | `application/json` **atau** `multipart/form-data` (wajib multipart untuk unggah berkas) |
| Format balas | selalu JSON |
| Autentikasi | token Bearer — `Authorization: Bearer <token>` |
| Zona waktu | Asia/Jakarta; tanggal dikirim ISO-8601 |

Tidak perlu cookie, sesi, maupun token CSRF.

### Bentuk balasan (SEMUA endpoint, sukses maupun gagal)

```json
{
  "error":   [],          // pesan galat — tampilkan ke pengguna apa adanya
  "success": [],          // pesan sukses — boleh ditampilkan sebagai toast
  "data":    { ... },     // isi balasan; null bila tidak ada
  "html":    []           // warisan portal lama — abaikan
}
```

Aturan membacanya: **cek kode HTTP**, lalu tampilkan `error` bila ada.

| Kode | Arti |
|---|---|
| 200 / 201 | berhasil |
| 400 | permintaan ditolak (lihat `error`) |
| 401 | belum login / token kedaluwarsa / token dicabut → arahkan ke layar login |
| 403 | login, tapi tidak berwenang (mis. warga membuka endpoint petugas) |
| 404 | tidak ditemukan — **termasuk** data milik orang lain (sengaja bukan 403) |
| 409 | konflik (mis. NIK sudah pernah mendaftar) |
| 422 | isian tidak valid — `data.kolom` berisi galat per kolom |
| 423 | permohonan sudah final (Selesai/Ditolak) dan terkunci |
| 429 | terlalu sering mencoba — tunggu ±1 menit |
| 500 | galat di server — `error[0]` berisi pesan umum; laporkan ke pengelola beserta jamnya |

Contoh 422:

```json
{
  "error": ["NIK/User ID harus diisi", "Password harus diisi"],
  "success": [],
  "data": { "kolom": { "user_id": ["NIK/User ID harus diisi"], "password": ["Password harus diisi"] } },
  "html": []
}
```

---

## 2. Login & sesi

### `POST /auth/login`

| Kolom | Wajib | Isi |
|---|---|---|
| `user_id` | ya | NIK 16 digit (warga) atau username (petugas/OPD) |
| `password` | ya | |
| `perangkat` | tidak | nama perangkat, mis. `"Samsung A15 — Android 14"` |

Batas: 5 percobaan per menit **per akun** (bukan per IP — warga yang berbagi IP
operator seluler tidak saling mengunci), plus 60 per menit per IP.

```json
// 200
{
  "data": {
    "token": "12|Ab3x...",
    "tipeToken": "Bearer",
    "kedaluwarsaMenit": 129600,
    "lengkapiFoto": false,
    "user": {
      "id": 49, "userId": "1806...", "nama": "Budi Santoso",
      "level": 3, "peran": "warga", "namaPeran": "Warga", "petugas": false,
      "email": "...", "hp": "...", "kecamatan": "PESISIR TENGAH", "foto": "/uploads/..."
    }
  },
  "success": ["Selamat datang, Budi."]
}
```

- Simpan `token` di penyimpanan aman (Keychain / EncryptedSharedPreferences / `flutter_secure_storage`).
- Token berlaku **90 hari**. Setelah itu semua permintaan dibalas 401 → login ulang.
- `lengkapiFoto: true` → warga baru pertama login dan belum punya foto; sarankan ke layar foto profil.

Galat login memakai kode yang biasa disebut petugas dinas:

| Kode | HTTP | Arti |
|---|---|---|
| L-02 | 401 | NIK/username belum terdaftar |
| L-03 | 403 | akun belum aktif / ditolak / dinonaktifkan. Untuk warga, `data.cekStatus = {status, nik}` → arahkan ke Cek Status |
| L-04 | 401 | password salah |

### Peran (`user.peran`)

| `peran` | `level` | Yang bisa dilakukan |
|---|---|---|
| `warga` | 3 | ajukan & pantau permohonan sendiri, profil, notifikasi, pengaduan |
| `operator_opd` | 4 | ajukan permohonan atas nama instansi, lihat permohonannya sendiri |
| `operator` | 2 | petugas: proses permohonan, akun warga, pengaduan, SKM |
| `super_admin` | 1 | semua, termasuk konten, master, log |

Gunakan `user.peran` untuk menentukan menu aplikasi. **Pagar sebenarnya tetap di
server** — menyembunyikan menu bukan pengaman.

### `GET /auth/me` 🔒
Data pengguna dari token (bentuk sama dengan `user` di atas). Panggil saat
aplikasi dibuka untuk memastikan token masih berlaku.

### `POST /auth/logout` 🔒
Mencabut token perangkat ini saja.

> 🔒 = wajib header `Authorization: Bearer <token>`.

---

## 3. Pendaftaran akun warga (tanpa login)

Alurnya sama dengan website: isi formulir → verifikasi OTP (bila aktif) → kirim →
akun **menunggu verifikasi petugas** → setelah aktif baru bisa login.

| Langkah | Endpoint |
|---|---|
| 1. Bahan formulir | `GET /auth/register` → `{kecamatan[], otpWajib, otpKanal ("email"/"wa"), prefill, perbaiki[]}` |
| 2. Kirim OTP | `POST /auth/otp/send` `{email}` (kanal email) atau `{hp}` (kanal wa) → `{kanal, challenge, ttlDetik}`; bila `data.dinonaktifkan`, lewati langkah 2–3 |
| 3. Verifikasi OTP | `POST /auth/otp/verify` `{email \| hp, kode, challenge}` → `data.bukti` |
| 4. Daftar | `POST /auth/register` |
| 5. Pantau status | `POST /auth/cek-status` `{nik}` |
| 6. Ajukan ulang (bila ditolak) | `POST /auth/ajukan-ulang` `{nik}` |

`GET /auth/register?nik=<NIK>` — bila NIK itu pernah **ditolak**, `prefill` berisi
data lamanya dan `perbaiki` berisi kolom yang perlu dibetulkan.

**`POST /auth/register`**

| Kolom | Isi |
|---|---|
| `nama`, `email`, `hp`, `kecamatan` | teks; `kecamatan` salah satu dari daftar langkah 1 |
| `nik`, `kk` | 16 digit |
| `pass`, `pass2` | min. 6 karakter, tidak boleh angka semua, harus sama |
| `foto` | foto wajah/selfie — **JPEG/PNG/WebP**, base64 polos (tanpa awalan `data:image/...;base64,`), maks. 4 MB |
| `ktp` | foto KTP — ketentuan sama |
| `otpBukti` | dari langkah 3 (wajib bila `otpWajib`) |

> Kirim base64 **tanpa** awalan `data:`. Firewall hosting (mod_security) memblokir
> badan permintaan yang memuat awalan itu.
>
> ⚠️ **iPhone menyimpan foto sebagai HEIC** — format itu ditolak (422, kode N-18/N-19).
> Konversi ke JPEG dulu di aplikasi (mis. `image_picker` dengan `imageQuality`,
> atau kompres ulang ke JPEG). Sekalian perkecil ke lebar ±1000 px supaya unggah cepat.

Balasan: `201` `{nik, status: 0}` (0 = menunggu verifikasi), atau `409` bila NIK
sudah pernah mendaftar dan masih diproses (`data.cekStatus.nik`).

**Lupa password** — `POST /auth/forgot-password` `{nik}`. Balasannya selalu sama
(ada atau tidak ada akunnya). Tautan reset dikirim ke email dan dibuka di
**website**.

---

## 4. Konten publik (tanpa login)

| Endpoint | Isi |
|---|---|
| `GET /berita?limit=&kategori=&q=` | daftar berita |
| `GET /berita/{slug}` | detail berita |
| `GET /galeri?limit=&kategori=` | galeri |
| `GET /demografi?kategori=jenis-kelamin` | data kependudukan per kecamatan; `&parent=<kode kecamatan>` → per pekon; `&tahun=&semester=` → periode tertentu. `kategori` wajib |
| `GET /stats` | statistik ringkas |
| `GET /jam-layanan` | jam buka layanan |
| `GET /jenis-permohonan` | jenis permohonan |
| `GET /wilayah` | kecamatan/desa |
| `GET /static-content` | isi halaman statis |
| `POST /pengaduan` | kirim pengaduan `{nama, isi, nik?, email?, hp?, subjek?}` |
| `POST /pengaduan/upload` | unggah bukti pengaduan (multipart `file`) |
| `POST /kritik-saran` | `{nama, pesan, email?, hp?}` |
| `GET /skm/unsur` · `POST /skm` | survei kepuasan masyarakat |

Endpoint publik yang menulis dibatasi 10 kiriman per menit.

Gambar berita/galeri (`/uploads/media/...`, `/uploads/berita/...` dsb.) bersifat
publik — buka dari domain website langsung:
`https://saibatin.pesisirbaratkab.go.id/uploads/...`.

---

## 5. Mengajukan permohonan (warga, OPD, petugas)

```
GET  /layanan                       → daftar layanan + jam layanan
GET  /layanan/{slug}                → skema formulir satu layanan
POST /{form.slug}/upload            → unggah tiap berkas lampiran → dapat url
POST /{form.slug}/create            → kirim permohonan
```

### `GET /layanan` 🔒
`{daftar[], kategori[], jam: {open, message}, tersembunyi[]}`. Tiap item `daftar`:
`{slug, kunci, title, description, icon, category}`. Bila `jam.open = false`,
tampilkan `jam.message` dan nonaktifkan tombol kirim.

### `GET /layanan/{slug}` 🔒
`slug` = `slug` dari item `daftar`.

```json
{
  "layanan": {
    "slug": "akta-kelahiran-nik-ada",          // ← slug FORMULIR, dipakai di URL kirim
    "title": "...",
    "sections": [
      { "title": "Data Pemohon",
        "fields": [
          { "name": "pemohonnik", "label": "NIK Pemohon", "type": "nik", "required": true, "half": true },
          ...
        ] }
    ]
  },
  "prefill": { "pemohonnik": "...", "pemohonnama": "..." },   // hanya untuk warga
  "jam": { "open": true, "message": "" },
  "kirimKe": "/api/mobile/v1/akta-kelahiran-nik-ada/create",
  "unggahKe": "/api/mobile/v1/akta-kelahiran-nik-ada/upload"
}
```

> ⚠️ Slug di daftar (`akta-kelahiran-ada-nik`) bisa berbeda dari slug formulir
> (`akta-kelahiran-nik-ada`). Untuk mengirim, pakai `kirimKe`/`unggahKe` dari
> balasan ini — jangan merangkai URL sendiri.

Tipe field (`type`): `text`, `textarea`, `nik` (16 digit), `kk` (16 digit),
`phone`, `email`, `number`, `date`, `time`, `select` (pilihannya di `options`), `file`.
Render formulir **dari skema ini**, jangan di-hardcode — dinas bisa mengubahnya.

### Unggah berkas — `POST {unggahKe}` 🔒
`multipart/form-data`, kolom `file`. Hanya **gambar** JPG/PNG/WebP, maks. **5 MB**.

```json
{ "data": { "url": "/uploads/akta-kelahiran-nik-ada/49_1791430840573.png" } }
```

Masukkan `url` itu sebagai nilai field bertipe `file` (berdasarkan `name`-nya).

### Kirim — `POST {kirimKe}` 🔒
Badan = objek `{ <name>: <nilai>, ... }` untuk semua field.

- `200` → `{noregister, id}` + pesan "Permohonan berhasil diajukan — No. Registrasi REG…"
- `422` → `error[]` berisi semua kekurangan sekaligus
- `403` → layanan dimatikan atau di luar jam layanan

---

## 6. Warga: riwayat, profil, notifikasi

| Endpoint | Isi |
|---|---|
| `GET /permohonan?status=&limit=&cursor=` 🔒 | riwayat permohonan milik sendiri (paginasi cursor) |
| `GET /permohonan/{id}/pdf` 🔒 | tanda terima PDF |
| `GET /profil` 🔒 | biodata |
| `PUT /profil` 🔒 | `{nama, hp?, email?, alamat?}` |
| `POST /profil/change-password` 🔒 | `{passwordLama, passwordBaru, konfirmasi}` |
| `PUT /profil/foto` 🔒 | `{foto}` base64 polos JPEG/PNG/WebP |
| `DELETE /profil/foto` 🔒 | hapus foto |
| `GET /notifikasi?limit=` 🔒 | `{items[], unread}` |
| `PATCH /notifikasi` 🔒 | tandai semua dibaca |
| `PATCH /notifikasi/{id}` 🔒 | tandai satu dibaca |

### Membuka berkas lampiran (KTP, KK, scan dokumen)

URL berkas dari API berbentuk `/uploads/...`. Berkas pribadi ini **tidak publik**
— buka lewat API dengan token:

```
GET /api/mobile/v1/uploads/<sisa path>     Authorization: Bearer <token>
```

Petugas boleh membuka semua berkas, warga hanya miliknya; selain itu 404. Untuk
menampilkan gambar, pakai image loader yang bisa membawa header (Glide/Coil
dengan header, `Image.network(url, headers: {...})` di Flutter).

---

## 7. Petugas (Operator & Super Admin)

| Endpoint | Isi |
|---|---|
| `GET /dashboard` 🔒 | ringkasan: permohonan per status, tren 6 bulan, 30 hari, layanan terpopuler, aspirasi, akun, kunjungan, konten |
| `GET /admin/permohonan` 🔒 | daftar permohonan — `status, q, jenis, wilayah, petugas, periode, acuan, limit (10–100), page` |
| `GET /admin/permohonan/{id}` 🔒 | detail + lampiran + `alasanPilihan`, `alasanWajibRincian`, `rincianPilihan` |
| `PATCH /admin/permohonan/{id}` 🔒 | ubah status/catatan (lihat bawah) |
| `GET /admin/users` · `GET /admin/users/{id}` 🔒 | manajemen akun warga |
| `PATCH /admin/users` 🔒 | verifikasi / aktivasi / tolak akun |
| `GET /admin/pengaduan` · `PATCH /admin/pengaduan/{id}` 🔒 | pengaduan |
| `GET /admin/skm` 🔒 | rekap survei |
| `GET /admin/statistik/export` 🔒 | unduhan Excel |

Status permohonan: `MENUNGGU` → `DIPROSES` → `SELESAI` / `DITOLAK`.
**SELESAI dan DITOLAK final** — setelahnya `PATCH` dibalas `423`.

**Proses** — `PATCH /admin/permohonan/{id}` `{"status": "DIPROSES", "catatan": "..."}`

**Tolak** — penolakan wajib lengkap:

```json
{
  "status": "DITOLAK",
  "alasan": "<salah satu dari alasanPilihan>",
  "rincian": ["<label dari rincianPilihan>", "..."],
  "keterangan": "teks bebas, wajib"
}
```

`rincian` wajib bila `alasan` termasuk `alasanWajibRincian`.

Operator OPD hanya bisa `GET /admin/permohonan` (miliknya sendiri) dan
`GET /admin/permohonan/{id}`. Endpoint petugas lain membalas 403 untuknya.

Endpoint khusus Super Admin (berita, galeri, produk, demografi, konten,
pengaturan jam & visibilitas layanan, log aktivitas, media) juga tersedia di
bawah `/admin/...` dengan nama yang sama seperti di website — lihat
`routes/api.php`. Untuk aplikasi mobile biasanya cukup bagian di atas.

---

## 8. Contoh cepat (curl)

```bash
B=https://saibatin.pesisirbaratkab.go.id/api/mobile/v1

# login
curl -X POST $B/auth/login -H "Content-Type: application/json" \
     -d '{"user_id":"<NIK>","password":"<sandi>","perangkat":"Uji"}'

# pakai token
T="12|Ab3x..."
curl $B/auth/me          -H "Authorization: Bearer $T"
curl $B/layanan          -H "Authorization: Bearer $T"
curl $B/permohonan       -H "Authorization: Bearer $T"
curl -F file=@scan.jpg   -H "Authorization: Bearer $T" $B/akta-kelahiran-nik-ada/upload
curl -X POST $B/auth/logout -H "Authorization: Bearer $T"
```

---

## 9. Catatan untuk pengembang backend

- Prefix mobile memuat ulang **seluruh** `routes/api.php`, jadi setiap endpoint
  website `/api/x` otomatis tersedia sebagai `/api/mobile/v1/x`. Endpoint baru
  di `api.php` langsung ikut ke aplikasi — pagar `peran:*` dan pemeriksaan di
  controller tetap berlaku.
- Endpoint khusus mobile ada di `routes/mobile.php`; mekanismenya dijelaskan di
  `app/Http/Middleware/PakaiTokenMobile.php`.
- Uji: `tests/Feature/ApiMobileTest.php`.
- Mencabut semua token seseorang (mis. ponsel hilang):
  `php artisan tinker` → `App\Models\User::find(<id>)->tokens()->delete();`
