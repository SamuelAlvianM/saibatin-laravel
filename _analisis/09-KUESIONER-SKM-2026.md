# 09 — Kuesioner SKM resmi dinas (17–18 Agustus 2026)

> Sumber: berkas **Word milik user** — "Daftar Pertanyaan Survei Kepuasan
> Masyarakat, Dinas Kependudukan dan Pencatatan Sipil Kabupaten Pesisir Barat".
> Berkasnya tidak ada di disk workspace (sudah disisir: yang ada hanya dua Word
> SIDAKO di `Downloads`); isinya dibaca dari tangkapan layar yang dikirim user.

## 1. Apa yang berubah

| | Sebelum | Sesudah |
|---|---|---|
| Pertanyaan | **9 unsur** Permenpan RB 14/2017 | **16 pertanyaan** kuesioner dinas |
| Skala | 1–4, satu ragam label | 1–4, **dua ragam label**: "setuju" & "sesuai" |
| Identitas | nama (wajib), umur, jenis kelamin, pekerjaan | + **instansi, pendidikan, produk layanan, disabilitas, jenis disabilitas**; **seluruhnya opsional** |
| Saran | opsional | tetap (label jadi "Keluhan / Saran Perbaikan") |

Keputusan user (18 Agu): **ganti total** ke 16 pertanyaan, identitas baru
disimpan sebagai **kolom** (bukan JSON), dan **hanya 16 penilaian yang wajib** —
identitas boleh dikosongkan seluruhnya, persis catatan pada berkas dinas ("boleh
inisial atau tidak diisi"). Menyusul: jenis kelamin, pendidikan, dan pekerjaan
memakai **dropdown**; jenis disabilitas dapat opsi tambahan **"Tidak ada"**; dan
baris kosong dropdown berbunyi **"Pilih Opsi Berikut"**.

## 2. 🔴 Kunci jawaban `p1`–`p16`, bukan `0`–`15`

**204 responden** yang sudah ada menyimpan jawabannya sebagai
`{ "0": nilai, …, "8": nilai }` (di produksi bentuknya `u0`–`u8`). Kalau
kuesioner baru ikut memakai indeks angka, jawaban lama akan terbaca sebagai
jawaban **9 pertanyaan pertama kuesioner baru** — padahal isinya beda sama
sekali (unsur lama no. 1 "kesesuaian persyaratan" vs pertanyaan baru no. 1
"informasi pelayanan tersedia melalui media elektronik"). Rekapnya akan tampak
wajar dan diam-diam salah; tidak ada satu pun yang terlihat rusak.

Pembacaan ketiga bentuk kunci dipusatkan di `SkmJawaban::nilaiTerbaca()` —
jangan menguraikan bentuk kunci lagi di controller, karena bentuk keempat pasti
muncul.

## 3. Rekap: dua generasi hidup bersamaan

- **Rata-rata per pertanyaan dipisah** — 16 pertanyaan 2026 dan 9 unsur warisan
  punya kartunya sendiri di `/dashboard/skm`. Menyandingkannya berarti
  menjejerkan pertanyaan yang berbeda isi.
- **Nilai IKM dihitung dari rata-rata TIAP RESPONDEN**, lalu dirata-rata lagi.
  Dengan begitu responden kuesioner 16 pertanyaan tidak berbobot 16/9 kali
  responden lama hanya karena pertanyaannya lebih banyak, dan 204 responden lama
  tetap ikut. Rumusnya tetap **NRR × 25**.
- Sebaran identitas (pendidikan, pekerjaan, produk layanan, disabilitas) ikut
  direkap. Responden lama muncul sebagai "Tidak diisi" — bukan dipaksa masuk
  salah satu kategori.

🔴 **Bug yang ketahuan saat menguji rekap**: `$rows->where('disabilitas', false)`
memakai perbandingan longgar, dan `null == false` bernilai true di PHP → **204
responden lama yang tidak pernah ditanya ikut terhitung "bukan penyandang
disabilitas"** sekaligus masuk hitungan "tidak ditanya". Angkanya dobel dan
laporan disabilitas jadi salah. Sudah ditutup dengan `where(..., '===', false)`.

## 4. Berkas yang disentuh

**Diubah:** `config/skm.php` (ditulis ulang: 16 pertanyaan + dua ragam skala +
pilihan identitas + daftar `warisan`) · `app/Models/SkmJawaban.php`
(`nilaiTerbaca()`, `rataSkor()`, fillable & cast baru) ·
`Api/AspirasiController.php` (`kirimSkm`, `unsurSkm`) ·
`Api/Admin/SkmAdminController.php` (rekap dua generasi + demografi) ·
`PublikController::survei()` · `views/publik/survei-kepuasan.blade.php` ·
`resources/js/Publik/FormSkm.jsx` · `resources/js/Pages/Dashboard/Skm.jsx`.

**Baru:** migrasi
`2026_08_17_100001_tambah_identitas_responden_ke_t_skm_jawaban` — 5 kolom
**aditif & nullable** (`instansi`, `pendidikan`, `produk_layanan`,
`disabilitas`, `jenis_disabilitas`). `disabilitas` sengaja **boolean nullable**:
"ya", "tidak", dan "belum pernah ditanya" tiga keadaan berbeda.

⚠️ Migrasi ini menambah pengecualian KEDUA pada klaim Fase 1 *"0 baris beda"*
(yang pertama `users.user_ktp`).

## 5. Yang diuji (dev 3104, data asli `saibatin_lv`)

| Uji | Hasil |
|---|---|
| Formulir publik | 16 pertanyaan tampil; label skala benar per tipe ("Sangat tidak setuju" vs "Sangat tidak sesuai") |
| Identitas | 3 dropdown (jenis kelamin, pendidikan, pekerjaan) + dropdown jenis disabilitas; pilihan tersimpan (diuji: SLTA) |
| Jenis disabilitas | hanya muncul saat menjawab "Ya" |
| Penjaga kelengkapan | kirim dengan 15/16 → ditolak "Masih ada 1 pertanyaan yang belum dinilai (nomor 16)", barisnya disorot merah |
| Kirim lengkap | tersimpan (id 210): 16 kunci `p1`–`p16`, `produk_layanan` & `disabilitas` terisi, `generasi = baru`, rata 3,50 |
| Rekap dashboard | 205 responden = **1 baru + 204 warisan**; IKM **90,90 / mutu A**; dua kartu rata-rata terpisah; sebaran pendidikan/pekerjaan/produk; kartu disabilitas |
| Jebakan Blade | `@json([...])` bertingkat di view → **halaman 500 "Unclosed '['"**; props dirakit di controller (HANDOFF §5 no. 21 — terjadi lagi) |

⚠️ Baris uji **id 210 ("UJI SKM 16") sengaja DIBIARKAN** supaya user bisa
melihat bentuknya di dashboard. Hapus dengan:
`App\Models\SkmJawaban::find(210)?->delete();`

⚠️ Ada pula baris uji dari sesi lain: **"RESPONDEN UJI OTOMATIS"** (17 Agu,
sarannya berbunyi *"UJI OTOMATIS — mohon hapus data ini"*) — bukan dari sesi ini,
tapi layak ikut dibersihkan sebelum cutover.

## 6. Belum dikerjakan

- **Ekspor Excel** hanya memuat jumlah responden SKM (`StatistikExcel` §
  "Responden Survei Kepuasan Masyarakat"); rincian 16 pertanyaan + sebaran
  identitas belum jadi sheet tersendiri. Belum diminta.
- **Portal Next.js yang live masih memakai 9 unsur.** Kuesioner baru ini hanya
  ada di port Laravel — kalau dinas memakainya sebelum cutover, dua portal akan
  mengumpulkan kuesioner berbeda.
