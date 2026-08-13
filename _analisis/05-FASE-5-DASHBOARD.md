# Fase 5 — Dashboard petugas

> **12 Agustus 2026.** Status: **8 halaman jadi & diuji lewat browser.**
> Sisa lingkup Fase 5 (halaman ke-9 dst) belum dikerjakan — daftarnya di §5.

## 1. Duduk perkara: sesi yang terputus

Fase 5 dibangun dalam satu sesi **12 Agu 21:27–21:51** yang berhenti mendadak:
berkas terakhir (`Dashboard/Master.jsx`) ditulis 21:51:03, lalu tidak ada apa pun
lagi — tidak ada build, tidak ada pengujian, tidak ada dokumen yang diperbarui.

Yang tertinggal saat itu:

| Terpotong | Akibat nyata |
|---|---|
| `npm run build` tidak dijalankan | `public/build` masih tertanggal 8 Agu. **Seluruh** halaman `/dashboard/*` melempar Inertia "page not found" di browser |
| Tidak ada uji browser | 35 berkas belum pernah dieksekusi sekali pun |
| `Dashboard/PengajuanBaru.jsx` + `PengajuanForm.jsx` **tidak pernah dibuat** | controller & rute & tautan sidebar sudah ada → menu "Pengajuan Baru" halaman kosong |
| HANDOFF/JOURNAL/journal induk tidak diperbarui | ketiganya masih menyatakan Fase 5 belum mulai (JOURNAL bahkan "Fase 3 berikutnya") |

Sesi 12 Agu malam (dokumen ini) menutup keempatnya.

## 2. Yang diperbaiki agar Fase 5 bisa dijalankan

### 2.1 🔴 Build gagal total — tabrakan nama komponen vs nama ikon

`resources/js/Components/LayoutDashboard.jsx` mengimpor ikon lucide bernama
`LayoutDashboard` **dan** mengekspor komponen bernama `LayoutDashboard`:

```
ERROR: The symbol "LayoutDashboard" has already been declared
```

esbuild menolak seluruh build — bukan hanya berkas itu. Artinya bukan cuma Fase 5
yang tidak terbangun: **tidak ada satu pun perubahan frontend yang bisa dibangun**
selama berkas itu ada. Diperbaiki dengan alias (`LayoutDashboard as IkonDasbor`),
pola yang sudah dipakai di `Log.jsx` (`User as UserIcon`).

Ini jebakan yang gampang berulang — nama komponen tata letak dan nama ikon lucide
memang bertabrakan secara alami (`LayoutDashboard`, `Users`, `ScrollText`).

### 2.2 Dua halaman yang hilang

`PengajuanPetugasController` merender `Dashboard/PengajuanBaru` dan
`Dashboard/PengajuanForm`; keduanya tidak ada → Inertia gagal dengan
`Cannot read properties of undefined (reading 'default')` dan **halaman putih**,
bukan 404 yang jelas. Keduanya dibuat sekarang, sebagai kembaran versi warga:

| | Warga | Petugas |
|---|---|---|
| Pemilih layanan | `Pengajuan/Pilih.jsx` | `Dashboard/PengajuanBaru.jsx` |
| Formulir | `Pengajuan/Form.jsx` | `Dashboard/PengajuanForm.jsx` |
| Renderer form | `FormLayanan` `mandiri` | `FormLayanan` `mandiri={false}` |
| Prefill | data akun yang login | **tidak ada** — datanya milik warga di loket |
| Setelah kirim | `/user/pengajuan?baru=` | `/dashboard/permohonan?sorot=` |
| Daftar layanan | disaring visibilitas | **tidak disaring** (kanal loket) |

Dua penyesuaian kecil yang menyertainya:

- `PengajuanPetugasController::tampilkan()` kini juga mengirim `kategori` —
  chip kategori tidak mungkin dirender tanpa itu.
- `FormLayanan` mendapat prop `paramBaru` (default `baru`). Halaman petugas
  memakai `sorot`, karena **itulah nama query yang sudah dibaca**
  `PermohonanAdminController::index()` untuk melompat ke halaman tempat
  permohonan itu berada. Tanpa ini, fitur lompat-ke-permohonan-baru yang sudah
  terlanjur ditulis di server tidak pernah terpanggil dari mana pun.

### 2.3 Panel detail akun tampak "tidak bereaksi" di layar < 1024 px

Panel detail di `Akun.jsx` adalah kolom `lg:col-span-2`. Di bawah breakpoint `lg`
ia turun ke **bawah** tabel yang panjangnya ratusan baris, jadi menekan "Detail"
terlihat seperti tidak melakukan apa-apa (permintaan `GET /api/admin/users/{id}`
tetap 200 — hanya panelnya yang jauh di luar layar). Ditambahkan
`scrollIntoView` yang **hanya** berjalan saat `innerWidth < 1024`; di layar lebar
panel memang sudah terlihat di sisi kanan dan tidak boleh digeser.

## 3. Hasil uji browser (login `admin`/`admin123`, port 3303)

Semua dengan data asli klon produksi di `saibatin_lv`, dibaca lewat `read_page` /
`get_page_text` (bukan tebakan dari kode):

| Halaman | Bukti yang terlihat |
|---|---|
| `/dashboard` | 11.902 permohonan · 4-status (4/352/8.696/2.850) · tren 6 bulan · 5 layanan terpopuler · 1.386 akun (1.234 warga / 140 OPD / 12 staff) · 203 responden SKM · 66 berita |
| `/dashboard/permohonan` | tabel 11.902 baris + tab status + filter periode/petugas; **panel detail** menampilkan data form, 5 berkas lampiran, jejak petugas, dan status "final & terkunci" |
| `/dashboard/pengajuan-baru` | 15 layanan + 5 kategori + pencarian |
| `/dashboard/pengajuan-baru/{slug}` | formulir skema penuh (mis. Akta Kematian: 4 seksi, 6 kolom berkas), pengantar "atas nama warga" |
| `/dashboard/users` | tab Warga/OPD/Staff × 5 status, panel detail (data diri, riwayat akun, tombol Aktifkan/Tolak) |
| `/dashboard/pengaduan` | 4 pengaduan termasuk baris migrasi portal lama |
| `/dashboard/kritik-saran` | 1 masukan |
| `/dashboard/skm` | 203 responden · rata 3,64/4 · **IKM 90,92 (A)** · 9 unsur Permenpan RB 14/2017 |
| `/dashboard/log` | 13 catatan + filter petugas/periode (khusus level 1) |
| `/dashboard/master` | formulir buka-kunci permohonan final |

Bundel hasil build: **488,9 KB JS (140 KB gzip) + 78,1 KB CSS** — naik dari
367 KB/60 KB di Fase 4, wajar untuk 10 halaman dashboard baru.

## 4. 🔴 Temuan: berkas warisan tidak bisa dibuka warga pemiliknya

Ketahuan saat panel detail permohonan menampilkan 5 berkas yang semuanya 404.
Yang 404 itu sendiri **benar** (berkas uji Fase 4 memang sudah dihapus, dan
`storage/app/private/permohonan/` lokal cuma berisi `kk-numpang`), tapi
menelusurinya membuka masalah yang lebih besar untuk cutover:

`BerkasController` menentukan kepemilikan dari **prefix nama berkas**
(`<uid>_<timestamp>.<ext>`) — konvensi yang ditulis port ini sendiri. Berkas
warisan tidak berbentuk begitu. Contoh baris nyata di `t_berkas` (1.485 baris):

```
/uploads/kkpisahkk/KKP01001.1778552208/1778552123_ngm.gedungcahyakuningan_KKP01_6a028d3b85dd9.jpg
```

Segmen pertama nama berkasnya adalah **timestamp**, bukan id pengguna. Jadi:

- **petugas** → tetap bisa membuka semua (memang melewati pemeriksaan itu);
- **warga** → mendapat **404 untuk berkasnya sendiri**, seluruh 1.485 berkas.

Sifatnya *fail-closed* (menolak, bukan membocorkan), jadi bukan lubang keamanan
dan tidak mendesak — tapi harus ditutup sebelum cutover, kalau tidak setiap warga
kehilangan akses ke riwayat berkasnya. Perbaikan yang masuk akal: untuk jalur yang
tidak berpola `<uid>_`, tentukan pemilik lewat `t_berkas.path` →
`t_permohonan.user_id`, bukan dari nama berkas. **Belum dikerjakan — menunggu
keputusan user**, karena menyentuh kontrol akses.

Catatan cutover yang menyertainya: route `/uploads/{jalur}` memetakan ke
`storage/app/private/permohonan/{jalur}` apa adanya, jadi nama foldernya harus
dipindahkan **persis** seperti di produksi (`kkpisahkk`, `kelahiran_1`,
`kematian`, …) — bukan diganti menjadi slug baru gaya port ini (`kk-pisah`).

## 5. Sisa lingkup Fase 5

Belum dibangun (dan **sengaja tidak ditautkan** dari sidebar supaya petugas tidak
menemukan menu yang rusak):

1. **Halaman Pengaturan** — `PengaturanController` sudah menyediakan 4 endpoint
   (`GET/PUT /api/admin/jam-layanan`, `GET/PUT /api/admin/pelayanan-visibilitas`)
   tapi belum punya UI dan belum punya rute `web.php`. **Backend tanpa pintu.**
2. Halaman tiket/notifikasi petugas, demografi, dan halaman CMS — sebagian besar
   memang milik Fase 6–8, bukan Fase 5.

## 6. Berkas yang disentuh sesi ini

| Berkas | Perubahan |
|---|---|
| `resources/js/Components/LayoutDashboard.jsx` | alias ikon `LayoutDashboard as IkonDasbor` (build gagal tanpa ini) |
| `resources/js/Pages/Dashboard/PengajuanBaru.jsx` | **baru** |
| `resources/js/Pages/Dashboard/PengajuanForm.jsx` | **baru** |
| `resources/js/Components/FormLayanan.jsx` | prop `paramBaru` |
| `resources/js/Pages/Dashboard/Akun.jsx` | `scrollIntoView` panel detail di layar sempit |
| `app/Http/Controllers/PengajuanPetugasController.php` | kirim `kategori` |
| `public/build/*` | dibangun ulang |
