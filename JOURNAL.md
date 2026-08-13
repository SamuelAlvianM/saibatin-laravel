# Journal — SAIBATIN-LARAVEL (`saibatin-laravel`)

> **Terakhir diperbarui: 2026-08-13** · anak dari [`../journal.md`](../journal.md) §3.5
>
> 🔴 **Sebelum melanjutkan, baca [`HANDOFF.md`](HANDOFF.md).** Berkas ini cuma ringkasan
> keadaan; panduan kerja, cara menjalankan, dan 13 jebakan ada di sana.

## 1. Identitas project

| | |
|---|---|
| Apa ini | **port SAIBATIN kembali ke PHP** — Laravel 12.65 + Inertia + React, MySQL |
| Tujuan | **mengganti total** portal SAIBATIN Next.js |
| Umur | baru — dimulai **7 Agustus 2026** |
| Port dev | **3303** (`saibatin-laravel-dev`) · login uji `admin`/`admin123` |
| Git | **belum di-git** |
| Server | **belum menyentuh server sama sekali** |

Ini **bukan** fork codebase Next.js. Project terpisah. Jebakan keluarga Next.js
(journal induk §5) **tidak berlaku** di sini — jebakan Laravel ada di `HANDOFF.md` §5.

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
| **5** | **dashboard petugas** — 15 halaman + rute `/api/admin/*`, `/api/media/*`, `/api/demografi` di 14 controller | 🟢 **selesai kecuali `konten`**, yang memang menunggu situs publik |

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
Dua perubahan tidak bisa disusul karena menyunting **halaman publik** yang di
port ini belum ada — data kontak Pengaduan & posisi tombol edit profil; keduanya
dicatat untuk Fase 7 di [`_analisis/06`](_analisis/06-PARITAS-NEXTJS.md) §2.9.

🔴 OCR-nya **di browser**, bukan di server (keputusan user sejak awal: cPanel
hanya PHP). Mesin tesseract + data bahasa Indonesia dilayani sendiri dari
`public/ocr/` — **12 MB berkas statis**, tidak menyentuh CDN, dan CPU server
tidak terpakai sama sekali.

🚫 **Fitur Tiket & Chat dihapus user (13 Agu).** Kode, rute, dan config-nya
dibuang; model + migrasi + tabelnya **tetap** karena penjaga penghapusan akun
bergantung padanya dan produksi masih menyimpan tiket warga. Rincian:
[`_analisis/06`](_analisis/06-PARITAS-NEXTJS.md) §2.8.

## 5. Antrean

1. **Fase 7 — situs publik.** Belum ada satu halaman publik pun, dan ini
   prasyarat halaman **Konten** (yang me-render halaman publik di dalam iframe,
   bukan formulir tersendiri). Sisa Fase 5 sudah habis 12–13 Agu: Pengaturan,
   berita, pustaka media, galeri, dokumen publikasi, tiket & chat, demografi.
2. 🔴 **Belum di-git.** Makin mendesak: seluruh pekerjaan Fase 3–5 (± 45 berkas)
   hanya ada di working tree, tanpa satu pun titik pulih. Sesi 12 Agu yang
   terputus adalah contoh persis kenapa ini berisiko.
3. **Menunggu keputusan user:** berkas warisan tidak terbuka untuk warga
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
| [`HANDOFF.md`](HANDOFF.md) | **panduan melanjutkan** — status, cara jalan, 13 jebakan, berkas sementara |
| `_analisis/00…` | kelayakan & peta port |
| `_analisis/01…` | bukti Fase 1 (perbandingan skema) |
| `_analisis/02…` | hasil uji auth (Fase 2) |
| `_analisis/03…` · `04…` | endpoint JSON (Fase 3) · 15 layanan (Fase 4) |
| `_analisis/05…` | dashboard petugas (Fase 5) + sesi yang terputus & apa yang ditutup |
| `_analisis/06…` | **peta paritas dengan portal Next.js** — dipakai sebagai daftar kerja |
| [`../saibatin/saibatin-platform/JOURNAL.md`](../saibatin/saibatin-platform/JOURNAL.md) | portal yang sedang digantikan — **masih melayani warga** |
| `../app.pesbar.002_debug_20260708/` | Laravel 9 asli SAIBATIN — rujukan "dulu fitur ini bentuknya bagaimana" |
| [`../journal.md`](../journal.md) · [`../HISTORY.md`](../HISTORY.md) §L, §O | keadaan workspace · detail langkah port · sinkronisasi 13 Agu |
