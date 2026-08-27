# 08 — MODE EDIT & halaman Konten Halaman (17 Agustus 2026)

> Menutup antrean no. 1 di [`../JOURNAL.md`](../JOURNAL.md) §5 dan baris ⛔
> terakhir di [`../HANDOFF.md`](../HANDOFF.md) §4: **satu-satunya halaman
> dashboard yang belum ada**. Prasyaratnya (situs publik, Fase 7) sudah
> terpenuhi 14 Agu.

## 1. Apa yang dibangun

Dua sisi dari satu fitur:

| | Isi |
|---|---|
| **Mode Edit** (halaman publik) | tombol melayang → bagian yang bisa disunting diberi garis putus-putus + pensil → dialog berisi medan blok itu → simpan |
| **Konten Halaman** (`/dashboard/konten`) | "site editor": kolom kiri seluruh menu navbar, sub-menu di atas, dan **halaman publiknya sendiri** di dalam `iframe ?editmode=1` |

🔴 **Konten Halaman bukan halaman formulir.** Itu asumsi awal yang dikoreksi
[`06-PARITAS-NEXTJS.md`](06-PARITAS-NEXTJS.md) §3.4: penyuntingannya terjadi di
halaman publik, dashboard hanya menyediakan daftar halaman + bingkai
pratinjaunya. Karena itu ia mustahil dibangun sebelum situs publik ada —
hasilnya cuma iframe kosong.

## 2. Bedanya dari portal Next.js — dan alasannya

Portal Next.js membungkus tiap blok dengan komponen React
(`<EditableBlock kunci="beranda.hero">`), memakai Context
(`InlineEditProvider`) untuk membagi keadaan mode edit.

Halaman publik port ini **Blade yang dirender server**, dan bagian
interaktifnya island terpisah — tidak ada satu pohon React yang bisa berbagi
Context. Penggantinya:

1. **Penanda berupa atribut**, bukan komponen:
   ```blade
   <section data-blok="beranda.hero" data-blok-label="Teks Hero"> … </section>
   ```
   Tanpa mode edit atribut ini tidak melakukan apa pun, dan warga tidak
   mengunduh satu byte pun karena island-nya memang tidak dirender untuk mereka
   (`@if auth()->user()->isSuperAdmin()` di `publik/layout.blade.php`).

2. **Island `Publik/ModeEdit.jsx`** yang menyusul memasang garis + pensil lewat
   `createPortal` ke elemen ber-`data-blok`. Elemen yang muncul BELAKANGAN
   (island lain yang baru hidup) ikut tertangkap karena ada `MutationObserver` —
   sekali query saat dipasang akan melewatkan mereka.

3. **`lib/mode-edit.js`** — keadaan bersama setingkat modul + `CustomEvent`,
   pengganti Context untuk island yang butuh tahu mode edit sedang menyala
   (`ProfilTabs`, `DokumenEdit`).

Konsekuensi lain: halaman publik tidak bisa "disegarkan" dari klien seperti
`refreshStaticContent()` di portal Next.js — isinya milik server. Karena itu
sesudah menyimpan, halamannya **dimuat ulang**, dan mode edit sengaja bertahan
lewat `sessionStorage` supaya penyuntingan berikutnya tidak perlu dinyalakan
lagi.

## 3. Skema medan: sebagian tetap, sebagian dirakit

`config/konten.php` → `medan` memuat bentuk formulir blok TETAP (hero, carousel,
5 blok profil, FAQ, Produk Disdukcapil). Kunci **dinamis** tidak didaftar satu
per satu — `App\Support\Konten::skema()` merakitnya:

| Kunci | Sumber bentuknya |
|---|---|
| `info.<grup>.<slug>` | `config/info-halaman.php` (5 grup) |
| `info.ppid.<slug>` | dua seksi halaman layanan PPID di `config/ppid-layanan.php` |
| `info.kebijakan-privasi`, `info.syarat-ketentuan` | `config/ketentuan.php` → satu medan `list` per bagian bernomor |

Alasannya bukan hemat baris: halaman informasi ditambah dengan menambah satu
entri config, dan daftar terpisah **pasti tertinggal** — halaman baru akan
tampil tanpa pensil tanpa ada yang menyadarinya.

🔴 **Temuan: `PUT /api/admin/static-content` menolak seluruh kunci halaman
informasi.** Penjaganya `array_key_exists($kunci, config('konten.blok'))`,
sementara `info.produk.sop` dkk tidak pernah ada di daftar itu — padahal justru
blok itulah yang paling sering disunting petugas. Sekarang penjaganya
`Konten::skema()`, yang mengenal kunci dinamis; kunci asing tetap ditolak
(diuji: `pelayanan.jam` dan `ngawur.sekali` → 404).

**Yang SENGAJA tidak bisa disunting inline**, dan bukan kelalaian:
- `pelayanan.jam` & `pelayanan.visibilitas` — itu pengaturan, bukan konten;
  tempatnya drawer Pengaturan di `/dashboard/pengajuan-baru`.
- `beranda.statistik` — kartunya dirakit editor demografi layar penuh di
  `/dashboard/demografi`, yang sekaligus mengatur sumber datanya.
- `links`, `faq`, dan `formulir` pada blok `info.*` — ketiganya sambungan ke
  bagian lain portal (tautan navigasi, blok FAQ tersendiri, island formulir),
  dan menyuntingnya dari sini berarti halaman bisa kehilangan formulirnya karena
  satu salah ketik.

## 4. Tombol edit `profile-tabs` — sisa sinkronisasi 13 Agu yang ikut tertutup

Lima tab profil berbagi satu kotak, jadi pensil yang melayang di atas kotaknya
akan menyunting blok yang salah begitu tab berpindah. `ProfilTabs.jsx` karena
itu memasang tombolnya SENDIRI **di dalam kartu, sebaris dengan judul panel** —
persis perbaikan yang sama di `profile-tabs.tsx` portal Next.js, yang selama ini
tercatat sebagai satu-satunya perubahan sinkronisasi 13 Agu yang belum bisa
disusul ([`06`](06-PARITAS-NEXTJS.md) §2.9).

## 5. Panel dokumen (mode edit) — juga ikut

Bagian yang di Fase 7 sengaja ditunda (`publik/info.blade.php`). Island
`Publik/DokumenEdit.jsx` tampil di bawah tabel berkas **hanya saat mode edit**:
daftar dokumen halaman itu + tombol hapus, dan unggah berkas baru yang langsung
mendarat di kategori halaman tersebut — petugas tidak perlu berpindah ke
Dokumen Publikasi lalu menebak kategorinya.

⚠️ Bedanya dari aslinya: di portal Next.js tombol hapus menempel pada tiap baris
tabel berkas. Tabel di sini dirender Blade di server (justru supaya isinya
terbaca mesin pencari), jadi daftar yang bisa dihapus ditaruh di panel ini —
bukan dengan membangun ulang tabelnya di klien.

## 6. Yang diuji di browser (dev 3104, data asli `saibatin_lv`)

| Uji | Hasil |
|---|---|
| `/dashboard/konten` | 8 menu di kolom kiri, sub-menu, iframe `src="/?editmode=1"`; pensil terbaca DI DALAM iframe |
| `?editmode=1` di halaman publik | mode edit langsung menyala, garis + pensil terpasang |
| Simpan blok tetap (`beranda.hero`) | tersimpan → halaman dimuat ulang → `<h1>` server-rendered ikut berubah |
| Simpan blok dinamis (`info.produk.inovasi`) | tersimpan → halaman publik ikut berubah (dulu ditolak "Kunci konten tidak dikenal") |
| Skema 9 kunci | 7 dikenal dengan medan yang benar, `pelayanan.jam` & kunci ngawur → 404 |
| Editor `items` | FAQ 6 baris; Produk Disdukcapil 12 baris (12 ubin gambar + 12 tiptap); Struktur 5 baris + 5 pemilih "Atasan" |
| Panel dokumen | `/produk/hukum` → 38 dokumen terdaftar; unggah + hapus lewat endpoint yang sama dengan dashboard: berhasil |
| Mode edit MATI | tanpa panel, tanpa pensil, tanpa garis — hanya tombol "Mode Edit" |
| Tamu (tanpa sesi) | `data-island="ModeEdit"` **tidak ada** di HTML |
| 17 halaman publik | 200 semua |

Seluruh data uji dibersihkan lagi: 2 baris `t_static_contents`, 1 dokumen uji +
berkasnya, dan 4 baris log aktivitas.

⚠️ Screenshot tidak bisa diambil — panel browser tidak ditampilkan sehingga
halaman berhenti meng-compositing (jebakan lama, HANDOFF §6 no. 5). Verifikasi
dilakukan lewat `read_page`, `javascript_tool`, dan HTML dari server.

## 7. Berkas yang disentuh

**Baru:** `resources/js/Publik/ModeEdit.jsx` · `Publik/DokumenEdit.jsx` ·
`Components/EditorMedan.jsx` · `lib/mode-edit.js` ·
`Pages/Dashboard/Konten.jsx` · `views/publik/partials/dokumen-edit.blade.php` ·
berkas ini.

**Diubah:** `config/konten.php` (bagian `medan`) · `app/Support/Konten.php`
(`skema()`, `isiUntukEditor()`) · `KontenStatisController` (GET skema + penjaga
baru) · `routes/api.php` · `routes/web.php` · `LayoutDashboard.jsx` (menu) ·
`ProfilTabs.jsx` · `lib/ikon.js` (`NAMA_IKON`) · `PublikController` (kunci blok
+ kategori dokumen per halaman) · 7 view publik (penanda `data-blok`).

Ukuran bundel: chunk `ModeEdit` **14,7 KB** (5,3 KB gzip) + `DokumenEdit`
4,6 KB + halaman `Konten` 4,6 KB — semuanya dimuat malas dan **tidak pernah
diunduh warga**.
