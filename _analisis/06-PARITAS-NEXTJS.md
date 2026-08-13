# Paritas dengan portal Next.js — peta lengkap

> **12 Agustus 2026.** Permintaan user: port Laravel harus **100% sama** UI dan
> fungsinya dengan SAIBATIN Next.js; grafik yang belum ada dibuat dengan
> **Highcharts**. Dokumen ini hasil pemeriksaan berkas-per-berkas terhadap
> `../saibatin/saibatin-platform`, plus catatan apa yang sudah ditutup.

## 1. Halaman dashboard — 15 dari 16, satu dihapus atas permintaan user

| Halaman Next.js | Baris | Port Laravel | Status |
|---|---|---|---|
| `dashboard/page.tsx` (beranda) | 593 | `Dashboard/Beranda.jsx` | ✅ sama (grafik harian kini Highcharts) |
| `permohonan/AdminPermohonan.tsx` | 810 | `Dashboard/Permohonan.jsx` | 🟡 sama kecuali **unduh PDF** (§3.1) |
| `AdminUsers.tsx` | 1258 | `Dashboard/Akun.jsx` | ✅ sama |
| `pengajuan-baru/PengajuanBaruClient.tsx` | 193 | `Dashboard/PengajuanBaru.jsx` | ✅ sama (ditutup hari ini) |
| `pengaduan/AdminPengaduan.tsx` | 199 | `Dashboard/Pengaduan.jsx` | ✅ sama |
| `kritik-saran/AdminKritikSaran.tsx` | 121 | `Dashboard/KritikSaran.jsx` | ✅ sama |
| `skm/SkmDashboard.tsx` | 138 | `Dashboard/Skm.jsx` | ✅ sama |
| `log/LogAktivitasClient.tsx` | 292 | `Dashboard/Log.jsx` | ✅ sama |
| `master/page.tsx` | 127 | `Dashboard/Master.jsx` | ✅ sama |
| `berita/AdminBerita.tsx` | 263 | `Dashboard/Berita.jsx` | ✅ sama (12 Agu) |
| `media/AdminMedia.tsx` | 187 | `Dashboard/Media.jsx` | ✅ sama (12 Agu) |
| `galeri/AdminGaleri.tsx` | 185 | `Dashboard/Galeri.jsx` | ✅ sama (12 Agu) |
| `produk/AdminProduk.tsx` | 322 | `Dashboard/Produk.jsx` | ✅ sama (13 Agu) |
| `tiket/` (TiketPanel) | 499 | — | 🚫 **DIHAPUS** atas permintaan user (13 Agu) — §2.8 |
| `demografi/AdminDemografi.tsx` | 340 | `Dashboard/Demografi.jsx` + `EditorDemografi.jsx` | 🟡 sama, kurang 1 hal (§2.7) |
| `konten/AdminKonten.tsx` | 202 | — | ⛔ **menunggu situs publik** (§3.4) |

Seluruh endpoint pendukungnya sudah ada di `routes/api.php`. Yang tersisa hanya
registry blok CMS (`config/konten.php` baru memuat 3 kunci yang benar-benar
dipakai) — sisanya menyusul bersama halaman publik.

## 2. Yang ditutup hari ini

### 2.1 Grafik harian → Highcharts

`components/dashboard/chart-harian.tsx` di Next.js menggambar SVG sendiri
(kurva Catmull-Rom + crosshair + chip nilai). Port Laravel sebelumnya
menggantinya dengan **batang polos** — jelas beda. Sekarang
`Components/GrafikHarian.jsx` memakai **Highcharts 13.0.0** +
`highcharts-react-official 3.2.3` (terpasang tanpa `--force`; peer React-nya
`>=16.8`, jadi React 19 aman) dengan bentuk yang menyamai aslinya: areaspline
mulus, area gradien `#2176bd` 0,28→0, garis rata-rata putus-putus berlabel
`rata² x,x`, crosshair + tooltip gelap, penanda titik hari ini, label tanggal
tiap 5 hari, plus modul `accessibility`.

🔴 **Highcharts di-*lazy load*.** Pustakanya ± 293 KB; kalau ikut bundel utama,
setiap halaman — termasuk login warga — ikut mengunduhnya. Dengan
`lazy()` + `Suspense`, bundel utama tetap **489 KB** dan Highcharts jadi chunk
terpisah yang hanya diunduh saat beranda dashboard dibuka. Tidak menambah
kebutuhan Node di server: seluruhnya berjalan di browser, seperti komponen lain.

⚠️ **Lisensi:** Highcharts gratis hanya untuk penggunaan non-komersial
(perorangan/sekolah/nirlaba). Untuk instansi pemerintah, Highcharts menuntut
lisensi berbayar. Dipakai di sini karena diminta user — keputusan lisensinya
ada di dinas. Kalau kelak jadi masalah, penggantinya cukup satu berkas
(`GrafikHarian.jsx`), bukan seluruh dashboard.

### 2.2 Pengajuan Baru + drawer Pengaturan

Sebelumnya halaman ini memakai kartu vertikal 3 kolom + chip kategori — bentuk
yang **tidak ada** di aslinya. Sekarang menyamai `PengajuanBaruClient.tsx`:
kartu horizontal (ikon kiri, judul + keterangan, panah kanan), hanya kotak
pencarian, tombol Kembali ke `/dashboard`.

Yang lebih penting: tombol **Pengaturan** (khusus Super Admin) kini ada, membuka
drawer kanan `Components/PengaturanLayanan.jsx` dengan 2 tab — persis seperti
aslinya:

| Tab | Isi | Padanan Next.js |
|---|---|---|
| **Jam Kerja** | saklar induk, 7 hari buka/tutup + jam, terapkan massal, tanggal libur | `jam-layanan-editor.tsx` |
| **Ketersediaan Layanan** | 15 layanan per kategori, tampilkan/sembunyikan semua | `pengaturan-pelayanan.tsx` |

Keduanya **menyimpan otomatis** (800 ms / 700 ms setelah perubahan berhenti),
sama seperti aslinya. Ini menutup temuan "backend tanpa pintu": 4 endpoint
`PengaturanController` sudah ada sejak sesi lalu tapi belum bisa dijangkau
siapa pun. Diuji di browser: kedua tab memuat konfigurasi produksi yang asli
(4 dari 15 layanan sedang disembunyikan).

### 2.3 Beda kecil yang diluruskan

| Berkas | Sebelum | Sekarang |
|---|---|---|
| `Skm.jsx` | bar aspek `bg-brand` polos | gradien `#2176bd → #6cb2eb` seperti aslinya |
| `KritikSaran.jsx` | cari nama + pesan | cari nama + pesan + **email** |
| `Akun.jsx` | "Minimal 6 karakter" | "Minimal 6 karakter, bukan angka semua" |
| `Akun.jsx` | "16 digit NIK penanggung jawab instansi" | "…NIK perwakilan/penanggung jawab instansi" |
| `Log.jsx` | "Cari ringkasan aktivitas…" | "Cari aktivitas (mis. status, berita, akun)…" |

Satu kalimat **sengaja tidak** disamakan: halaman Master di Next.js menulis
*"baris berlabel Terkunci"*, padahal tabel Permohonan-nya menampilkan **ikon
gembok**, bukan label. Port ini menulis "baris bergembok" — UI-nya identik,
kalimatnya yang di sana memang keliru.

### 2.4 Pustaka media, galeri, berita (12 Agu, tahap kedua)

Tiga halaman ini satu paket karena galeri & berita berdiri di atas pustaka media.

**Sisi server**

| Berkas | Isi |
|---|---|
| `Services/PustakaMedia.php` | unggah → **konversi WebP** (GD, sisi maks 2560, kualitas 82), simpan ke `storage/app/private/media/yyyy/mm/<uuid>.<ext>` |
| `Api/Admin/MediaController.php` | `GET /api/media` (cari, filter `type=image`, 24/halaman) · `POST /api/media/upload` · `DELETE /api/media/{id}` |
| `MediaPublikController.php` | `GET /uploads/media/{jalur}` — **tanpa sesi**, cache immutable setahun |
| `Api/Admin/GaleriAdminController.php` | `POST /api/galeri` · `DELETE /api/admin/galeri/{id}` |
| `Api/Admin/BeritaAdminController.php` | daftar (termasuk draf) · buat · ubah · hapus, dengan **slug unik** yang aturannya menyalin `lib/slug.ts` |

Jalur & nama berkas dipertahankan persis (`/uploads/media/...`, kolom `path`
relatif) karena barisnya **sudah ada di produksi** — mengubah konvensi berarti
seluruh gambar berita lama menunjuk ke tempat yang salah.

🔴 Dua jebakan yang muncul saat mengerjakannya:
1. `POST /api/media/upload` cocok dengan pola catch-all `{layanan}/{aksi}` —
   harus didaftarkan **sebelum**-nya (jebakan §5 no. 12 versi baru).
2. `GET /uploads/media/...` harus didaftarkan **sebelum** `/uploads/{jalur}`
   milik `BerkasController`; pola `.*` itu menelan `media/...` juga.

**Sisi klien** — `MediaUnggah` (tarik-lepas), `PotongGambar`
(react-advanced-cropper, hasil WebP 0,92), `PemilihMedia` (dialog 2 tab),
`BidangGambar` (ubin pratinjau sekaligus tombol), `PenyuntingKaya` (tiptap 3
dengan bilah alat lengkap + sisip gambar dari pustaka).

**Juga diperbaiki:** `BerkasController::FOLDER_PUBLIK` sebelumnya cuma
`['produk']`. Konten publik warisan juga tinggal di `berita`, `galeri`, dan
`gallery` (dua ejaan, dua-duanya ada di produksi) — tanpa itu gambar berita &
galeri lama akan 404 bagi pengunjung yang belum login.

### 2.5 Halaman Inertia kini dipecah per-halaman

`app.jsx` memuat seluruh `Pages/**` dengan `eager: true` — satu bundel untuk
semuanya. Dengan bertambahnya halaman, warga yang cuma membuka `/login` ikut
mengunduh seluruh dashboard petugas. Sekarang glob-nya malas, jadi tiap halaman
punya chunk sendiri: **bundel utama 327 KB** (dari 576 KB), Highcharts (434 KB),
tiptap (422 KB), dan pemotong gambar (89 KB) masing-masing chunk terpisah yang
hanya diunduh saat dipakai.

### 2.6 Dokumen publikasi & tiket (13 Agu, tahap ketiga)

> ⚠️ Bagian **tiket** di bawah ini sudah tidak berlaku — fiturnya dihapus user
> beberapa jam kemudian. Dibiarkan tertulis sebagai catatan sejarah; lihat §2.8.

**Dokumen Publikasi** — `config/dokumen.php` (port `lib/dokumen-registry.ts`:
**20 kategori**, masing-masing tahu di halaman publik mana berkasnya muncul) +
`ProdukAdminController` + `Dashboard/Produk.jsx`. Registry dikirim dari server
ke halaman, jadi dropdown dashboard, validasi penyimpanan, dan halaman publik
nanti membaca daftar yang sama. Unggahannya lewat `/api/upload` yang sudah ada
(folder `produk`). Diuji: 85 dokumen produksi tampil, pindah kategori jalan.

🔴 `key` kategori = nilai kolom `jenis` yang sudah ada di produksi
(`SKM_LAPORAN`, `PERJANJIAN_KERJASAMA`, …). Jangan dirapikan — dokumen lama
akan lenyap dari halaman publiknya tanpa satu pun pesan galat.

**Tiket & Chat** — `PanelTiket.jsx` (port `components/tiket/tiket-panel.tsx`):
dua panel, polling 5 detik, gelembung kiri/kanan, tutup & buka kembali tiket,
dialog tiket baru dengan kategori **Internal** khusus petugas.

⚠️ **Kontrak API-nya diperbaiki**: `GET /api/tiket/{id}` di port ini sempat
membalas `{tiket:{…, pesan:[…]}}` dengan kolom `userId`, sedangkan portal
Next.js membalas `{tiket, pesan, meId}` bersaudara dengan kolom `pengirimId`.
Panel percakapan membandingkan `pengirimId` dengan `meId` untuk menentukan
gelembung kiri atau kanan — tanpa itu **semua** pesan tampil sebagai milik
orang lain. Sekarang mengikuti aslinya, plus `closedAt` yang sebelumnya tidak
dikirim (dipakai teks "Tiket ditutup · <waktu>").

### 2.7 Data Demografi (13 Agu, tahap keempat)

Terpasang **PhpSpreadsheet 5.9** (pengganti `exceljs`).

| Berkas | Isi |
|---|---|
| `config/demografi.php` | 8 kategori (slug = kolom `kategori` di DB) + petunjuk nama berkas SIAK |
| `Services/DemografiExcel.php` | baca (port `lib/demografi-import.ts`) & tulis (port `demografi-export.ts`) |
| `Api/Admin/DemografiAdminController.php` | `GET`/`PUT`/`DELETE /api/admin/demografi` · `import` · `parse` · `export` |
| `Api/DemografiController.php` | `GET /api/demografi` publik — ringkasan kecamatan = **jumlah pekonnya** |
| `Dashboard/Demografi.jsx` + `EditorDemografi.jsx` | 8 kartu kategori + editor layar penuh |

🔴 Aturan pembacaan Excel yang WAJIB dipertahankan: nomor **IDEM tidak
konsisten antar berkas** (kecamatan = IDEM 4 di berkas jenis kelamin, IDEM 3 di
berkas KK/WKTP) dan format KODE berbeda (`82.72.01` vs `827201`). Karena itu
level ditentukan dari **struktur kode** — 6 digit kecamatan, 10 digit pekon —
bukan dari IDEM. Baris kab/kota dan dusun diabaikan.

**Diuji bolak-balik di browser:** ekspor `jenis-kelamin` → 11 KB .xlsx →
diumpankan balik ke `parse` → terbaca **129 wilayah, 0 konflik, 0 berubah, 129
tetap**, kolom L/P/JML utuh. Artinya penulis dan pembaca sepakat.

⚠️ **Bug yang ketahuan dari uji itu:** PHP mengubah kunci array numerik jadi
integer, sehingga kode wilayah keluar sebagai angka `181301`, bukan string
`"181301"` — dan nol di depan (bila ada wilayah lain kelak) akan hilang diam-diam.
Sudah dikembalikan ke string di `pratinjau()`.

**Yang belum:** di aslinya, judul kolom tabel bisa diklik ⭐ untuk menjadikannya
**kartu statistik beranda**. Kartu berandanya sendiri belum ada di port ini
(bagian halaman publik), jadi tombol itu ditunda bersama Fase 7. Tombol
**Reset Kartu Beranda** sudah ada dan menulis susunan 6 kartu bawaan dari
`config/konten.php` lewat `PUT /api/admin/static-content`.

### 2.8 🚫 Tiket & Chat dihapus (13 Agu, atas permintaan user)

Fitur tiket & chat **dibatalkan**. Yang dibuang: `Dashboard/Tiket.jsx`,
`Components/PanelTiket.jsx`, `Api/TiketController.php`, `config/tiket.php`,
rute `/dashboard/tiket` dan 5 rute `/api/tiket*`.

🔴 Yang **sengaja ditinggalkan** — dan alasannya: model `Tiket`/`TiketPesan`,
kedua migrasinya, dan relasi `User::tiket()`/`tiketPesan()`. Tabel `t_tiket` &
`t_tiket_pesan` masih berisi tiket warga di produksi dan FK-nya
`ON DELETE RESTRICT`; penjaga penghapusan akun di `UserAdminController::destroy`
menghitung relasi itu untuk menolak penghapusan akun yang punya jejak. Kalau
relasinya ikut dibuang, penghapusan baru gagal di lapisan database — petugas
menerima galat 500, bukan penjelasan.

**Tabel dan datanya tidak disentuh.** Menghapusnya adalah keputusan tersendiri
yang menghilangkan riwayat percakapan warga secara permanen — belum diminta.

Konsekuensi paritas: port ini kini **sengaja berbeda** dari portal Next.js pada
satu fitur. Itu keputusan user, bukan pekerjaan yang tertinggal.

### 2.9 Menyusul perubahan baru portal Next.js (13 Agu, tahap kelima)

Portal Next.js terus dikerjakan selama port berjalan. Yang disalin masuk pada
tanggal ini — **5 commit + perubahan yang masih di working tree** — beserta
bentuknya di port:

| Sumber | Isi | Bentuk di port |
|---|---|---|
| `d8ea801` (9 Agu) | OTP dimatikan | `Otp::AKTIF = false` + `OTP_AKTIF` di `Register.jsx` — **dua sakelar kembar**, mesin OTP-nya utuh |
| `d8ea801` | field **Foto KTP** di pendaftaran | `Components/UnggahGambar.jsx` + bagian 4 di `Register.jsx` + `ktp` wajib di `RegisterController` (galat **N-19**) + `AlasanTolak::KOLOM['ktp']` |
| `a7375cc` + `8a7e66e` (11–13 Agu) | OCR KTP mengisi NIK/No.KK/Nama | `lib/ocr-ktp.js` — **di browser**, lihat §2.10 |
| `999099f` (13 Agu) | gambar dikirim base64 polos | `lib/gambar.js` + `FotoProfil` menerima dua bentuk |
| `1f435fd` (13 Agu) | validasi dari TIPE field | **sudah ada sejak Fase 4** (`Support\Layanan::periksaField`) — pesan galatnya pun sama persis |
| working tree | **ekspor Excel statistik** | `Services/StatistikExcel.php` + `/api/admin/statistik/export` + `TombolEksporStatistik` |
| working tree | penampil foto KTP di detail akun | `Components/PenampilGambar.jsx`, dipakai bersama berkas permohonan |
| working tree | alamat/telepon/email & WIB di halaman Pengaduan | ⏳ **menunggu Fase 7** — halaman publiknya belum ada (§3.3) |
| working tree | tombol edit `profile-tabs` pindah ke dalam kartu | ⏳ **menunggu Fase 7** — idem |

Dua baris terakhir bukan pekerjaan yang terlewat: keduanya menyunting halaman
publik, dan port ini belum punya satu pun. Dicatat di sini supaya ikut terpakai
saat Fase 7 dikerjakan — data kontak yang benar:
**Pasar Mulya Timur 01, Kel. Pasar Krui, Kec. Pesisir Tengah · (0728) 21XXX ·
disdukcapil@pesisirbaratkab.go.id · Senin–Jumat 08.00–16.00 WIB** (yang lama
masih menyalin alamat Tana Tidung dan menulis WITA).

### 2.10 OCR KTP: pindah ke browser, bukan disalin apa adanya

Di portal Next.js OCR berjalan di server (`/api/ocr/ktp`, tesseract.js di Node).
Di port ini **tidak ada endpoint OCR sama sekali** — pembacaan terjadi di
peramban warga. Ini keputusan user sejak port dimulai (HANDOFF §3 no. 3):
cPanel tujuan hanya menjalankan PHP.

Yang **ikut hilang bersama pindahnya, dan memang tidak lagi diperlukan**:
pembatas laju per-IP (`lib/rate-limit.ts`), penghitung `MAKS_BERSAMAAN`, dan
batas unggah 8 MB. Ketiganya melindungi CPU server dari penyalahgunaan; tidak
ada CPU server yang dipakai lagi.

Yang **tetap dibawa apa adanya**: timeout (tanpa itu wasm yang gagal muat
membuat promise tak pernah selesai — pernah mengunci endpoint di produksi
SIDAKO & TIDORE, 11 Agu), percobaan 4 orientasi + pemilihan skor terbaik, dan
seluruh logika penguraian teks (`normalkanDigit`, `nikSah`, `pilihNik`,
`uraikanTeksKtp`) sehingga hasilnya identik. Pra-proses `sharp`
(grayscale/normalize) diganti padanan canvas dengan peregangan kontras
persentil 2–98.

🔴 **Mesin & data bahasa dilayani sendiri dari `public/ocr/` (12 MB)** — tidak
mengunduh dari CDN. Selain karena jaringan dinas sering ketat, mengambil dari
CDN berarti foto KTP warga diproses kode yang sumbernya tidak kita kuasai.
Isinya 3 varian `tesseract-core-*-lstm.wasm.js` (peramban memilih satu sesuai
dukungan SIMD-nya), `worker.min.js`, dan `ind.traineddata.gz`. Varian non-LSTM
sudah dibuang — OEM yang dipakai `LSTM_ONLY`. Peramban mengunduh ± 7,7 MB
**hanya bila warga benar-benar memilih foto KTP**, lalu di-cache.

## 3. Yang masih beda — daftar kerja

### 3.1 Unduh PDF permohonan (halaman Permohonan)

Aslinya punya tombol **Unduh Dokumen** di panel detail →
`GET /api/permohonan/{id}/pdf`, dibuat `lib/permohonan-pdf.ts` (411 baris,
pdfkit + sharp): kop A4, data pemohon, seluruh isian berlabel bahasa manusia,
**dan seluruh berkas unggahan disematkan sebagai gambar** (dinormalkan ke JPEG
baseline dulu — pdfkit menolak PNG interlaced/WebP/CMYK). Port Laravel belum
punya sama sekali; padanannya `dompdf` (sudah direncanakan sebagai Fase 8).

### 3.4 ⛔ Konten Halaman TIDAK bisa dikerjakan sebelum situs publik ada

Ini bukan halaman formulir seperti dugaan awal. `AdminKonten.tsx` adalah
**site editor**: kolom kiri berisi seluruh menu navbar publik, dan bagian
tengahnya me-*render* halaman publik itu sendiri di dalam `iframe` dengan
`?editmode=1`. Penyuntingannya terjadi di halaman publik lewat tombol pensil
yang muncul saat mode edit aktif; yang disimpan lewat
`PUT /api/admin/static-content` adalah blok per-kunci.

Artinya: **12 blok CMS baru bisa disunting setelah halaman publiknya ada.**
Membangun `/dashboard/konten` lebih dulu hanya menghasilkan iframe kosong.
Urutan yang benar → situs publik (§3.3) dulu, `konten` menyusul.

### 3.3 Seluruh situs publik

Belum ada satu halaman publik pun — `/` masih redirect ke `/login`. Yang harus
dibangun: landing page, berita, galeri, produk, PPID, profil, `media/*` (peta,
GIS, demografi, laporan, survei kepuasan), WBS, hubungi kami, pengaduan publik,
permohonan online, riwayat, tiket, syarat, privasi, kebijakan privasi, sitemap.

Ini yang di rencana awal disebut Fase 7 — dan bagian terbesar dari sisa port.
