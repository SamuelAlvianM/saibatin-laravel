# Runbook cutover — SAIBATIN Laravel ke cPanel

> Disusun 17 Agustus 2026, **ditulis ulang 18 Agustus 2026** mengikuti rencana
> user. **Belum pernah dijalankan.**
>
> 🔴 Ini **cutover**, bukan deploy biasa. Portal SAIBATIN Next.js yang sekarang
> menempati cPanel itu **sedang melayani warga**. Seluruh langkah di server
> dikerjakan **user sendiri** — keputusan yang dikunci sejak awal port
> (HANDOFF §3 no. 5).

**Rencana yang disepakati (18 Agu):**

| Hal | Keputusan |
|---|---|
| Sasaran | **Cutover sekarang** — portal utama diganti, bukan subdomain uji |
| `public_html` lama | **Di-rename jadi `public_html-old`**, lalu dibuat `public_html` baru |
| Application Manager (Passenger/Node) | **Dimatikan user** sebelum pengujian |
| Database | **DB produksi yang sekarang**; backup sudah ada (`saibatinpesibar_saibatin.sql`) |
| Kuesioner SKM 16 pertanyaan | **Belum dibuka untuk warga** (`SKM_TERBUKA=false`) sampai dinas menyetujui |

Susunan akhirnya:

```
/home/<akun>/saibatin-app/     app bootstrap config database lang routes
                               resources storage vendor .env artisan
/home/<akun>/public_html/      index.php + .htaccess + isi folder public/
                               + uploads/{berita,galeri,gallery,produk}
/home/<akun>/public_html-old/  portal lama — DIBIARKAN sebagai jalan mundur
```

---

## Peta langkah

| Fase | Isi | Portal lama |
|---|---|---|
| 0 | Prasyarat & backup | tetap hidup |
| 1 | Unggah dua ZIP ke home | tetap hidup |
| 2 | Ekstrak aplikasi + `.env` + kunci | tetap hidup |
| 3 | Database | tetap hidup |
| 4 | Uji dari baris perintah | tetap hidup |
| **5** | **CUTOVER** — matikan Passenger, tukar `public_html`, pindahkan berkas | **mati** |
| 6 | Uji lewat peramban | — |
| 7 | Kunci cache | — |
| 8 | Cara mundur | — |
| 9 | Sesudah stabil | — |

Fase 0–4 **tidak mengubah apa pun** yang sedang dipakai warga. Boleh dikerjakan
kapan saja, tidak harus sekali duduk. Yang perlu jendela pemeliharaan hanya
fase 5–7.

---

## Fase 0 — Prasyarat & backup

**0.1 Backup.** Sudah ada: `saibatinpesibar_saibatin.sql`.
🔴 Simpan **satu salinan di luar server** (unduh ke laptop). Backup yang hanya
ada di server yang sama tidak menolong kalau akunnya yang bermasalah.

**0.2 Cocokkan hosting.**

| Syarat | Kebutuhan | Cara cek |
|---|---|---|
| PHP | ≥ 8.2 | cPanel → Select PHP Version (`ea-php82`) |
| Ekstensi | `pdo_mysql`, `mbstring`, `openssl`, `gd`, `zip`, `fileinfo` | halaman yang sama → Extensions |
| `memory_limit` | 512M (ekspor Excel ± 170 MB) | sudah dinaikkan lewat `.htaccess`; kalau host memakai `php_admin_value`/LVE, kenaikannya diabaikan |
| `max_execution_time` | ≥ 120 | idem (di-set 180) |
| Node.js | **tidak dibutuhkan** | aset sudah dibangun di laptop |

`gd` wajib — dipakai memperkecil lampiran PDF & logo kop. Tanpa `gd` PDF tetap
terbit, lampirannya saja tanpa gambar.

**0.3 Sisa kuota disk.** Fase 5 menyalin 560 MB berkas warga (aslinya tetap ada
di `public_html-old` sebagai jalan mundur), jadi butuh **± 700 MB bebas**.
Kalau mepet: pakai `mv` untuk scan warga, dan pastikan backup berkasnya ada.

---

## Fase 1 — Unggah paket

Dibuat di laptop dengan:

```bash
cd C:/sam/WORK/SAM-AMANDA-GALANG/saibatin-laravel
npm run build
powershell -ExecutionPolicy Bypass -File deploy\buat-paket.ps1
```

| Berkas | Taruh di | Ukuran |
|---|---|---|
| `deploy/dist/saibatin-app.zip` | `/home/<akun>/` | 40,8 MB |
| `deploy/dist/saibatin-public.zip` | `/home/<akun>/` | 47,3 MB |

cPanel → File Manager → **home, bukan `public_html`** → Upload.

🔴 **Jangan ekstrak `saibatin-public.zip` sekarang.** Isinya untuk `public_html`
yang baru; `public_html` yang sekarang masih melayani warga.

---

## Fase 2 — Aplikasi & `.env`

**2.1 Ekstrak aplikasi** (di luar `public_html`, jadi aman):

```bash
cd ~ && unzip -q saibatin-app.zip -d saibatin-app && chmod -R 775 saibatin-app/storage saibatin-app/bootstrap/cache && ls saibatin-app
```

Harus muncul: `app bootstrap config database lang resources routes storage vendor artisan`.

**2.2 Buat `.env`.** Salin isi `deploy/env-cpanel.txt` menjadi
`/home/<akun>/saibatin-app/.env` (File Manager → Edit), lalu isi:

- `APP_URL` — alamat portal, **dengan `https://`**
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — kredensial DB produksi
  (`DB_HOST` tetap `127.0.0.1`)
- surel pengirim (notifikasi & reset sandi)
- `APP_ENV=production`, `APP_DEBUG=false` — biarkan
- `SKM_TERBUKA=false` — biarkan sampai dinas menyetujui kuesionernya

**2.3 Kunci aplikasi:**

```bash
cd ~/saibatin-app && php artisan key:generate && php artisan about | head -20
```

Tanpa `APP_KEY` setiap halaman menjawab 500 dengan "No application encryption
key has been specified". Kalau Terminal tidak tersedia, kuncinya bisa dibuat di
laptop lalu ditempel ke `.env`.

---

## Fase 3 — Database

🔴 Aditif seluruhnya. Boleh dijalankan **selagi portal lama masih hidup** —
portal Next.js tidak pernah menyebut kolom-kolom baru ini.

1. phpMyAdmin → pilih DB produksi → **Import** →
   `deploy/sql/2026-08-18_siapkan-db-produksi.sql`
2. Periksa dengan tiga kueri di bagian 6 berkas itu:
   `users.user_ktp` ada · `t_skm_jawaban` punya 5 kolom baru · `migrations` = 25 baris

**Kalau ingin lewat DB salinan dulu** (lebih aman, dan Anda bisa membuat DB
sendiri): impor `saibatinpesibar_saibatin.sql` ke DB baru, jalankan SQL di atas
ke DB itu, lalu tulis nama DB salinan pada `DB_DATABASE`. Setelah semua uji
hijau, ganti ke DB produksi dan jalankan `php artisan config:cache` lagi.

🔴 **HARAM di server:** `migrate:fresh`, `migrate:reset`, `db:wipe`, dan seluruh
seeder. Ketiga perintah pertama menghapus tabel lebih dulu — tabel itu berisi
11.902 permohonan dan 1.386 akun warga.

---

## Fase 4 — Uji dari baris perintah

```bash
cd ~/saibatin-app && php artisan db:show && php artisan migrate:status | tail -5
```

- `db:show` menampilkan daftar tabel + jumlah baris → koneksi & kredensial benar
- `migrate:status` menampilkan 25 baris **Ran**

Kalau dua ini hijau, sisa risikonya tinggal soal web server. **Berhenti di sini
kalau belum siap ada jeda layanan** — sampai titik ini belum ada yang berubah
bagi warga.

---

## Fase 5 — CUTOVER

Mulai di sini portal lama berhenti melayani. Umumkan dulu ke dinas.

**5.1 Matikan Application Manager / entri Node.js** portal lama di cPanel.
🔴 Selama entri itu hidup, Apache meneruskan permintaan ke aplikasi Node dan
`.htaccess` Laravel **tidak akan pernah dibaca** — inilah sebabnya `.htaccess`
terasa "mati" waktu dicoba dulu.

**5.2 Tukar `public_html`:**

```bash
cd ~ && mv public_html public_html-old && mkdir public_html && chmod 750 public_html
```

**5.3 Ekstrak isi publik + salin `.well-known`:**

```bash
cd ~ && unzip -q saibatin-public.zip -d public_html && cp -a public_html-old/.well-known public_html/ 2>/dev/null; ls public_html | head
```

`.well-known` dipakai AutoSSL; tanpa itu perpanjangan sertifikat bisa gagal
diam-diam beberapa bulan kemudian.

**5.4 Berkas unggahan — DUA tujuan berbeda.** Ini langkah yang paling mudah
salah, dan akibatnya paling luas.

| Isi `public_html-old/uploads/` | Tujuan | Kenapa |
|---|---|---|
| `berita`, `galeri`, `gallery`, `produk` (147,7 MB) | `public_html/uploads/` | Path di DB `/uploads/berita/x.jpg`; `.htaccess` hanya melempar ke `index.php` bila berkasnya TIDAK ada (`!-f`), jadi ini disajikan Apache langsung |
| Sisanya — scan KTP/KK/akta warga (560,2 MB: `kelahiran_1`, `kematian`, `kk*`, `kkpisahkk`, …) | `saibatin-app/storage/app/private/permohonan/` | Route `/uploads/{jalur}` → `BerkasController` mencarinya di sana, memetakan **nama folder apa adanya** |

```bash
cd ~ && mkdir -p public_html/uploads saibatin-app/storage/app/private/permohonan && for d in berita galeri gallery produk; do [ -d "public_html-old/uploads/$d" ] && cp -a "public_html-old/uploads/$d" public_html/uploads/; done
```

```bash
cd ~/public_html-old/uploads && for d in */; do case "${d%/}" in berita|galeri|gallery|produk) ;; *) cp -a "$d" ~/saibatin-app/storage/app/private/permohonan/ ;; esac; done
```

Cocokkan angkanya:

```bash
find ~/saibatin-app/storage/app/private/permohonan -type f | wc -l && du -sh ~/saibatin-app/storage/app/private/permohonan ~/public_html/uploads
```

Harapan: **± 1.467 berkas / ± 560 MB** di storage, **± 148 MB** di
`public_html/uploads`.

🔴 `cp -a`, bukan `mv` — selama `public_html-old` utuh, jalan mundur tetap ada.
`-a` menjaga tanggal & izin berkas.

**5.5 Izin:**

```bash
chmod -R 775 ~/saibatin-app/storage ~/saibatin-app/bootstrap/cache && chmod 750 ~/public_html
```

---

## Fase 6 — Uji lewat peramban

Kerjakan berurutan; yang bertanda 🔴 paling sering menyingkap masalah nyata.

- [ ] Beranda tampil, angka statistik **tidak 0**
- [ ] Login `admin` → dashboard; login warga → Pengajuan Saya
- [ ] Kirim satu permohonan lengkap dengan unggah gambar
- [ ] Buka berkas yang baru diunggah (200) & berkas milik akun lain (**404**)
- [ ] 🔴 **Berkas WARISAN warga** — masuk sebagai akun warga lama, buka lampiran
      permohonan lamanya (pola `<timestamp>_<desa>_…`). Harus **200**. Ini uji
      jalur cadangan `t_berkas.path`; hanya di produksi polanya bisa dipastikan
- [ ] Gambar berita & foto galeri tampil (menguji fase 5.4 kolom pertama)
- [ ] 🔴 **Ekspor Excel statistik** — paling mungkin gagal (`memory_limit`).
      Layar putih = memory; minta host menaikkannya, atau pakai ekspor per-bagian
- [ ] Unduh PDF permohonan
- [ ] Kirim pengaduan dari halaman WBS (menguji CSRF di halaman Blade)
- [ ] **Mode Edit** — Super Admin → beranda → "Mode Edit" → sunting satu blok →
      simpan → muat ulang. Lalu Dashboard → **Konten Halaman**: pratinjau tampil
      di dalam iframe
- [ ] **Survei Kepuasan** — sebagai tamu: "Survei sedang disiapkan";
      sebagai petugas: formulir 16 pertanyaan + spanduk pratinjau

---

## Fase 7 — Kunci cache

**Paling akhir, bukan di awal.**

```bash
cd ~/saibatin-app && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

🔴 `config:cache` **membekukan isi `.env` saat itu**. Mengubah `.env` sesudahnya
tidak berpengaruh apa pun sampai perintah ini diulang — dan gejalanya
membingungkan (kredensial sudah benar tapi tetap "Access denied").

⚠️ `route:cache` menuntut tidak ada closure di berkas rute. `routes/web.php`
memang sudah ditulis begitu; kalau kelak ada yang menambah closure, perintah ini
gagal dengan `LogicException: Unable to prepare route for serialization`.

---

## Fase 8 — Cara mundur

Selama `public_html-old` belum dihapus, portal lama bisa dihidupkan lagi:

```bash
cd ~ && mv public_html public_html-baru && mv public_html-old public_html
```

lalu hidupkan kembali entri Application Manager portal lama.

🔴 **Jangan hapus `public_html-old`** sampai portal baru terbukti stabil
beberapa hari — di dalamnya ada satu-satunya salinan asli 560 MB berkas warga.

Kalau yang bermasalah hanya database, pulihkan dari
`saibatinpesibar_saibatin.sql`. Dua migrasi fase 3 aditif, jadi backup itu tetap
bisa dipulihkan tanpa menyesuaikan apa pun.

---

## Fase 9 — Sesudah stabil

- [ ] **Buka kuesioner SKM** setelah dinas menyetujui: `SKM_TERBUKA=true` di
      `.env` → **`php artisan config:cache`** (tanpa itu tidak berubah sama
      sekali). Jawaban percobaan dinas ikut terhitung di rekap IKM — hapus dari
      Dashboard → SKM bila hanya mencoba
- [ ] Daftarkan `sitemap.xml` di Google Search Console
- [ ] Cek `saibatin-app/storage/logs/laravel.log` hari pertama —
      `LOG_LEVEL=error` membuatnya sepi kalau semuanya sehat
- [ ] Beri peringatan di `deploy/README.md` portal Next.js supaya tidak ada yang
      menjalankan deploy lama secara tidak sengaja
- [ ] Setelah stabil beberapa hari: arsipkan `public_html-old` (unduh, lalu
      hapus dari server) untuk mengosongkan kuota

---

## Kalau macet

| Gejala | Penyebab yang paling sering |
|---|---|
| Semua halaman 500 | `APP_KEY` kosong · `.env` belum dibuat · izin `storage` |
| "Folder aplikasi tidak ditemukan di: …" | nama folder ≠ `saibatin-app` → ubah `$app` di `public_html/index.php` |
| Halaman putih tanpa galat | bundel Vite tidak ikut / `public_html/build` kosong |
| Semua POST dijawab 419 | `.htaccess` tidak terbaca (Application Manager masih hidup) atau baris `X-XSRF-Token` hilang |
| Login berhasil lalu balik ke login | cookie `Secure` di `http://` — pastikan HTTPS & `APP_URL` diawali `https://` |
| Lampiran permohonan lama 404 | fase 5.4 kolom kedua terlewat, atau nama folder berubah saat menyalin |
| Gambar berita hilang | fase 5.4 kolom pertama terlewat |
| Ekspor Excel layar putih | `memory_limit` ditolak host |
| Ubah `.env` tapi tidak berpengaruh | konfigurasi masih di-cache → `php artisan config:cache` lagi |
