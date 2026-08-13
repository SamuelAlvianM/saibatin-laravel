# Fase 3 — Endpoint JSON · SELESAI (lapisan publik & warga)

> 2026-08-08 · **36 rute** terdaftar, semuanya diuji lewat browser.
> Kontrak warisan `{ error[], success[], data, html[] }` dipertahankan.

---

## 1. Keputusan lingkup — dan alasannya

Rencana awal menyebut "90 handler di Fase 3". Setelah dikerjakan, pembagiannya
diubah: **endpoint yang hanya melayani satu halaman dashboard dipindah ke fase
yang membangun halaman itu.**

Alasannya bukan menghindari pekerjaan, melainkan menghindari mengerjakannya dua
kali: bentuk respons `/api/admin/permohonan` baru bisa dipastikan benar saat
tabelnya ada, dan menebaknya lebih dulu berarti menulis ulang begitu UI-nya jadi.

| Kelompok | Nasib |
|---|---|
| Publik + warga berizin (**36 handler**) | ✅ selesai di sini |
| `/api/admin/**` (± 30) | → Fase 5 & 6, bersama halaman dashboardnya |
| `/api/{layanan}/{action}` | → Fase 4 (15 formulir) |
| `/api/demografi/**`, `/api/media/**` | → Fase 6 |
| `/api/permohonan/{id}/pdf` | → Fase 8 |
| `/api/ocr/ktp` | → Fase 9, **dihapus** (pindah ke browser) |

Yang ditunda semuanya membalas **404** sekarang — bukan setengah jadi.

---

## 2. Yang selesai

**Publik (11):** `health` · `auth/session` · `jenis-permohonan` · `wilayah` ·
`jam-layanan` · `static-content` · `kunjungan` (GET/POST) · `berita` (+detail) ·
`galeri` · `stats` · `skm/unsur`

**Publik menulis (4, ber-reCAPTCHA + throttle 10/menit):** `pengaduan` ·
`kritik-saran` · `skm` · `auth/check-nik`

**Berizin (21):** `profil` (GET/PUT) · `profil/change-password` ·
`profil/foto` (PUT/DELETE) · `notifikasi` (GET/PATCH, PATCH `{id}`) ·
`tiket` (GET/POST, `{id}` GET/POST/PATCH) · `permohonan` (GET/POST) ·
`penduduk/check` · `upload` · daftar `pengaduan` & `kritik-saran` (petugas)

Pendukungnya: `JamLayanan`, `PencacahKunjungan`, `CatatanAktivitas`,
`config/skm.php`, `config/tiket.php`.

---

## 3. Hasil uji (browser, data asli)

| Uji | Hasil |
|---|---|
| 11 endpoint publik | **200** semua, bentuk data sesuai |
| `/api/stats` | 11.902 permohonan · 8.696 selesai · **356 aktif** (=352 diproses + 4 menunggu) · 4 layanan teratas bernama benar · tren Mar–Agu |
| `/api/berita` | 66 terbit (dari 67 baris — satu belum publish) |
| `/api/jenis-permohonan` | 17 aktif |
| `/api/profil` · `notifikasi` · `tiket` · `permohonan` tanpa sesi | **401** |
| `/api/permohonan` (admin) | 5 item, `counts` = 3 menunggu / 1 diproses / 1 selesai |
| `/api/penduduk/check` NIK **milik orang lain** | `terdaftar: true`, **`autofill: null`** — data pribadi ditahan |
| `/api/skm` isian separuh | 422 `Belum dinilai: unsur 2,3,4,5,6,7,8,9` |
| `/api/auth/check-nik` NIK terpakai | 400 `… (C-03)` — kode galat asli utuh |
| `/api/profil/change-password` sandi lama salah | 400 ditolak |
| Endpoint yang ditunda | **404**, bukan setengah jadi |
| Log Laravel | **0 galat baru** (26 entri lama semuanya sudah diperbaiki) |

---

## 4. 🔴 Bug CSRF yang ketahuan — dan koreksi catatan Fase 2

Seluruh POST membalas **419** setelah login. Penyebabnya bukan token yang salah,
melainkan token yang **basi**:

`<meta name="csrf-token">` hanya dirender saat pemuatan halaman penuh. Login
memanggil `session()->regenerate()` yang menerbitkan token baru — tapi Inertia
**tidak pernah merender ulang `<head>`**. Jadi halaman yang masih hidup terus
memakai token lama, dan setiap POST sesudah login gagal.

**Ini mengoreksi catatan Fase 2 §5.4 no. 1.** Di sana tertulis "yang benar
`X-CSRF-TOKEN`". Lebih tepatnya: **kedua header sah, tapi isinya berbeda** —

| Header | Isi yang diharapkan Laravel |
|---|---|
| `X-CSRF-TOKEN` | token **mentah** (dari `<meta>`) |
| `X-XSRF-TOKEN` | nilai cookie `XSRF-TOKEN`, yang **terenkripsi** — didekripsi dulu |

Kesalahan awal adalah mengisi header yang satu dengan nilai milik yang lain.

**Perbaikan:** `resources/js/lib/api.js` sekarang memakai **cookie
`XSRF-TOKEN`** (disegarkan di setiap respons, jadi tahan regenerasi sesi), dengan
`<meta>` sebagai cadangan bila cookie tak terbaca.

---

## 5. Perubahan disengaja dari portal Next.js

**`/api/upload` menulis ke `storage/app/private/`, bukan `public/uploads/`.**
URL publiknya tetap `/uploads/<folder>/<berkas>` dan dilayani `BerkasController`
yang memeriksa izin. Menulis ke `public/` persis yang membuat 560 MB scan warga
terekspos di produksi sekarang; kontraknya tidak berubah, hanya letak fisiknya.

**Tren 6 bulan dihitung di SQL,** bukan dengan menarik seluruh baris ke PHP.
Portal Next.js mengambil semua permohonan 6 bulan lalu menghitungnya di memori —
dengan 11.902 baris dan terus bertambah, itu beban yang tidak perlu.

**Auto-close tiket memakai `update()` massal,** bukan simpan per-model:
menyentuh `updated_at` lewat Eloquent justru memperpanjang umur tiket yang
hendak ditutup.

**Pencarian berita hanya menyentuh `judul` + `ringkasan`,** tidak `konten`.
Kolom itu `LONGTEXT` tanpa indeks fulltext; `LIKE` di sana masih murah untuk 67
baris, tapi akan jadi beban begitu beritanya ribuan.

---

## 6. Catatan

- `stats.kartuDemografi` masih `[]` karena kunci `beranda.statistik` belum ada di
  `t_static_contents` (produksi punya 7 kunci, bukan itu). Editornya dibangun di
  Fase 6 — endpointnya sendiri sudah benar.
- `/api/static-content` baru mengembalikan override DB; **registri nilai bawaan
  12 blok** (port `lib/static-content-registry.ts`, 32 KB) juga Fase 6.
- Pengujian sempat menandai 2 notifikasi `admin` sebagai sudah dibaca
  (`PATCH /api/notifikasi`). Tidak dikembalikan — statusnya tidak bermakna di DB dev.
