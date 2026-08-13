# Fase 4 — 15 Layanan Permohonan · SELESAI

> 2026-08-08 · Satu config + satu renderer + satu endpoint, menggantikan
> ± 19.800 baris `*Modal.tsx` yang sudah mati di portal Next.js.

---

## 1. 🔴 Temuan utama: layanan `kk-numpang` RUSAK di portal yang sekarang live

Endpoint catch-all portal Next.js memvalidasi tipe kolom dengan **menebak dari
akhiran namanya** (`app/api/[layanan]/[action]/route.ts:77`):

```js
if (/(nik|nokk|kk)$/i.test(k) && !/^\d{16}$/.test(s)) → tolak "harus 16 digit angka"
```

Regex itu ikut mencocoki dua field milik layanan **`kk-numpang`**:

| Field | Tipe sebenarnya | Isi |
|---|---|---|
| `alasannumpangkk` | **select** | "Pekerjaan" / "Pendidikan" / "Perawatan Kesehatan" / "Lainnya" |
| `nikygnumpangkk` | **textarea** | banyak NIK, satu per baris |

Keduanya **wajib** di layanan itu. Jadi setiap pengiriman kk-numpang pasti kena
422 dengan dua pesan sekaligus — **layanannya tidak bisa dipakai sama sekali**
lewat jalur itu.

**Statusnya:** terbukti dari kode (regex deterministik, kedua nama field ada di
skema, keduanya `required`). Data mendukung tapi lemah sebagai bukti mandiri:
dari 11.902 baris, hanya **5** yang dikirim lewat formulir baru (punya kunci
`pemohonnama`) — tersebar di KONSOLIDASI (2), AKTA_KELAHIRAN_NIK_ADA (1),
AKTA_KELAHIRAN_NIK_BLM_ADA (1), KK_PISAH (1). **Nol** di KK_NUMPANG. Sampelnya
terlalu kecil untuk menyimpulkan sendiri, tapi konsisten.

`nikygpisah` (textarea multi-NIK di `kk-pisah`) lolos **hanya karena kebetulan**
akhirannya "pisah", bukan karena aturannya benar.

> Ini **tidak diperbaiki di portal Next.js** — di luar lingkup, dan portal itu
> sedang melayani warga. Diserahkan ke user sebagai temuan.

**Di port ini validasi digerakkan SKEMA, bukan nama** (`App\Support\Layanan::periksaField`)
— tipe dibaca dari definisi fieldnya, sehingga masalah ini tidak bisa terjadi.

---

## 2. Yang dibangun

```
config/layanan.php                       15 skema formulir + peta slug + peta kode
app/Support/Layanan.php                  akses skema, validasi, ekstraksi berkas
app/Http/Controllers/Api/LayananController.php   catch-all POST /api/{layanan}/{aksi}
app/Http/Controllers/PengajuanController.php     halaman pilih/form/riwayat
resources/js/Components/FormLayanan.jsx  SATU renderer untuk 15 layanan
resources/js/Components/LayoutPengguna.jsx
resources/js/lib/ikon.js                 peta ikon eksplisit (lihat §5)
resources/js/Pages/Pengajuan/{Pilih,Form,Riwayat}.jsx
```

Rute halaman: `/user/pengajuan`, `/user/pengajuan/baru`,
`/user/pengajuan/baru/{slug}` — tiap layanan punya URL sendiri sehingga bisa
di-bookmark (di portal lama semuanya modal tanpa URL).

---

## 3. Hasil uji end-to-end (browser + DB, data asli)

Sengaja memakai **`kk-numpang`** — layanan yang rusak di portal lama.

| Uji | Hasil |
|---|---|
| Halaman pemilih | **15 dari 15 layanan** tampil, filter kategori & pencarian jalan |
| Formulir `kk-numpang` | 4 seksi (Data Pemohon · Kelengkapan Data · Dokumen Syarat · Catatan), 10 isian, 3 dropzone |
| Prefill | `pemohonnama` + `pemohonemail` terisi; **`pemohonnik` sengaja kosong** karena `user_id` akun ini "admin", bukan 16 digit |
| Unggah 2 berkas (PNG asli via canvas) | 200, URL `/uploads/kk-numpang/1_…png` |
| Kirim dengan `alasannumpangkk='Pekerjaan'` + `nikygnumpangkk` multi-baris | **200** — `REG1786196723155` (portal lama menolak ini) |
| `t_permohonan` | jenis `KK_NUMPANG`, status `MENUNGGU`, payload 12 kunci utuh |
| `t_berkas` | 2 baris terdaftar, berkas fisik **ada di storage** |
| 🔴 Bocor ke `public/`? | **tidak** — keduanya |
| Notifikasi | **11 petugas** (dari 12; pembuatnya dikecualikan) |
| Log aktivitas | tercatat — "Membuat permohonan KK - Numpang KK (REG…) atas nama warga" |
| Ambil berkas sebagai petugas | 200 `image/png`, header `Cache-Control: no-store, private` |
| Ambil berkas milik uid lain | **404** |

Data uji dibersihkan: 1 permohonan + 2 berkas + 11 notifikasi + 1 log dihapus,
berkas fisik ikut dihapus. **Total kembali 11.902**, nol berkas sisa.

---

## 4. Perubahan disengaja dari portal Next.js

**Validasi digerakkan skema** (§1).

**Unggahan masuk `storage/app/private/permohonan/{layanan}/`, bukan `public/`.**
URL-nya tetap `/uploads/{layanan}/{berkas}` dan dilayani `BerkasController`
ber-Gate. Nama berkas diawali id pengunggah — itulah dasar kontrol aksesnya.

**Status jam layanan dikirim dari server sebagai props**, bukan diambil ulang
klien lewat endpoint terpisah. Sumbernya jadi sama persis dengan yang
menggerbang endpoint pengirimannya — tidak ada celah antara "yang ditampilkan"
dan "yang ditegakkan".

**Catch-all didaftarkan PALING AKHIR** di `routes/api.php`. Polanya
`{layanan}/{aksi}` akan menelan rute dua-segmen mana pun sesudahnya
(`auth/session`, `profil/foto`, `skm/unsur`) — Laravel memakai rute pertama yang
cocok, jadi urutannya menentukan.

---

## 5. 🔴 Jebakan yang kena: `import * as` dari lucide-react

`Pilih.jsx` semula memakai `import * as Ikon from 'lucide-react'` supaya ikon
bisa diambil dinamis dari nama di config. Akibatnya **seluruh set ikon** masuk
bundel — Vite tidak bisa menyingkirkan yang tak terpakai karena aksesnya dinamis.

**Diukur: 367 KB → 1.280 KB (3,5×).** Diganti peta eksplisit di
`resources/js/lib/ikon.js` (12 ikon) → kembali **392,82 KB**.

Pola yang sama dipakai portal aslinya (`lib/icon-map.ts`) — dan sekarang jelas
kenapa: bukan gaya, tapi ukuran bundel.

---

## 6. Belum termasuk di fase ini

- **Tombol OCR** di sebelah field NIK/KK → Fase 9 (pindah ke browser)
- **Penampil berkas layar penuh** dengan zoom/putar/maju-mundur antar dokumen →
  sekarang baru klik-untuk-perbesar sederhana; versi penuhnya ikut Fase 5
  (dipakai bersama halaman detail permohonan petugas)
- **Form petugas "Pengajuan Baru"** (`mandiri=false`) → Fase 5; renderer-nya
  sudah menerima prop itu, tinggal dipasang halamannya
- **Detail permohonan** `/riwayat/{id}` beserta kamus `kode-options` (agama "1"
  → "Islam") → Fase 5
