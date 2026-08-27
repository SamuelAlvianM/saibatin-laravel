-- ===========================================================================
--  SAIBATIN Laravel — menyiapkan DB PRODUKSI yang sudah ada untuk cutover
--  Disusun 18 Agustus 2026. Dijalankan OLEH USER, setelah backup.
-- ===========================================================================
--
--  🔴 BACKUP DULU. cPanel → phpMyAdmin → Export (Quick, SQL), atau
--     `mysqldump -u <user> -p <db> > backup-sebelum-cutover.sql`.
--     Berkas ini menyentuh tabel yang sedang melayani warga.
--
--  Kenapa perlu: portal Laravel memakai DB PRODUKSI yang sekarang (keputusan
--  user 18 Agu). Skema DB itu dibuat Prisma, jadi ia belum punya tabel
--  `migrations` milik Laravel, dan belum punya dua kolom yang ditambahkan
--  sesudah Fase 1:
--
--    1. `users.user_ktp`            — foto KTP pendaftaran (9 Agu, aditif)
--    2. lima kolom `t_skm_jawaban`  — identitas responden kuesioner SKM 2026
--
--  Keduanya ADITIF & NULLABLE: tidak ada satu baris data pun yang berubah,
--  dan portal Next.js yang sekarang hidup tidak terpengaruh — ia tidak pernah
--  menyebut kolom-kolom itu. Artinya SQL ini aman dijalankan SEBELUM cutover,
--  saat portal lama masih melayani warga.
--
--  ── DUA CARA, PILIH SALAH SATU ──────────────────────────────────────────
--
--  A. Punya akses Terminal/SSH di cPanel (lebih disukai):
--        cd ~/saibatin-app
--        php artisan migrate:install          # membuat tabel `migrations`
--        # lalu jalankan HANYA bagian 2 di bawah (INSERT baseline) lewat phpMyAdmin
--        php artisan migrate --force          # menjalankan 2 migrasi aditif
--     Dengan cara ini Laravel sendiri yang mencatat migrasinya.
--
--  B. Tanpa Terminal — jalankan SELURUH berkas ini di phpMyAdmin.
--     Hasil akhirnya sama; Laravel akan melihat semua migrasi sudah "Ran".
--
--  ⚠️ `php artisan migrate:fresh` dan `db:wipe` HARAM di sini — keduanya
--     menghapus seluruh tabel lebih dulu.
-- ===========================================================================


-- ── 1. Tabel pencatat migrasi (dilewati bila `migrate:install` sudah dipakai)
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2. Baseline: 23 migrasi yang tabelnya SUDAH ADA di produksi ─────────────
-- Ditandai "sudah jalan" supaya `php artisan migrate` tidak mencoba membuat
-- ulang tabel yang isinya data warga. Tanpa ini, migrate akan gagal di
-- "table already exists" — atau, lebih buruk, berhasil di DB yang salah.
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
  ('0001_01_01_000000_create_sessions_table', 1),
  ('0001_01_01_000001_create_cache_table', 1),
  ('0001_01_01_000002_create_jobs_table', 1),
  ('2026_08_07_100001_create_m_userlevels_table', 1),
  ('2026_08_07_100002_create_users_table', 1),
  ('2026_08_07_100003_create_m_jenis_permohonan_table', 1),
  ('2026_08_07_100004_create_t_permohonan_table', 1),
  ('2026_08_07_100005_create_t_berkas_table', 1),
  ('2026_08_07_100006_create_m_wilayah_table', 1),
  ('2026_08_07_100007_create_m_news_posts_table', 1),
  ('2026_08_07_100008_create_t_galleries_table', 1),
  ('2026_08_07_100009_create_t_produk_table', 1),
  ('2026_08_07_100010_create_t_pengaduanmasyarakat_table', 1),
  ('2026_08_07_100011_create_t_kritiksaran_table', 1),
  ('2026_08_07_100012_create_t_skm_jawaban_table', 1),
  ('2026_08_07_100013_create_t_static_contents_table', 1),
  ('2026_08_07_100014_create_t_media_table', 1),
  ('2026_08_07_100015_create_m_demografi_wilayah_table', 1),
  ('2026_08_07_100016_create_t_tiket_table', 1),
  ('2026_08_07_100017_create_t_tiket_pesan_table', 1),
  ('2026_08_07_100018_create_t_notifikasi_table', 1),
  ('2026_08_07_100019_create_t_kunjungan_table', 1),
  ('2026_08_07_100020_create_t_log_aktivitas_table', 1);

-- ⚠️ Tiga tabel pertama (`sessions`, `cache`, `jobs`) memang milik Laravel dan
--    BELUM tentu ada di DB produksi. Bila `SESSION_DRIVER=database` dipakai,
--    tabel `sessions` WAJIB ada — buat dengan bagian 5 di bawah.


-- ── 3. Kolom foto KTP pada `users` ──────────────────────────────────────────
-- Padanan `deploy/sql/2026-08-08_tambah-kolom-user-ktp.sql` portal Next.js.
-- Lewati bila portal Next.js sudah pernah menjalankannya (cek dulu:
--   SHOW COLUMNS FROM `users` LIKE 'user_ktp';
-- kalau sudah ada, LANGSUNG ke bagian 4 dan cukup INSERT baris migrasinya).
ALTER TABLE `users`
  ADD COLUMN `user_ktp` varchar(191) NULL AFTER `user_foto`;

INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_08_12_100001_tambah_user_ktp_ke_users', 2);


-- ── 4. Identitas responden kuesioner SKM 2026 ───────────────────────────────
-- 5 kolom aditif & nullable. `disabilitas` sengaja NULLABLE: "ya", "tidak",
-- dan "belum pernah ditanya" tiga keadaan berbeda — 203 responden kuesioner
-- lama masuk kategori ketiga, dan default 0 akan melaporkan mereka sebagai
-- "bukan penyandang disabilitas" yang tidak pernah mereka nyatakan.
ALTER TABLE `t_skm_jawaban`
  ADD COLUMN `instansi`          varchar(191) NULL AFTER `nama`,
  ADD COLUMN `pendidikan`        varchar(191) NULL AFTER `jenis_kelamin`,
  ADD COLUMN `produk_layanan`    varchar(191) NULL AFTER `pekerjaan`,
  ADD COLUMN `disabilitas`       tinyint(1)   NULL AFTER `produk_layanan`,
  ADD COLUMN `jenis_disabilitas` varchar(191) NULL AFTER `disabilitas`;

INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_08_17_100001_tambah_identitas_responden_ke_t_skm_jawaban', 2);


-- ── 5. Tabel sesi Laravel (HANYA bila `SESSION_DRIVER=database`) ────────────
-- `deploy/env-cpanel.txt` memakai driver `file`, jadi bagian ini biasanya
-- TIDAK diperlukan. Dipasang di sini supaya tidak perlu dicari-cari kalau
-- suatu saat drivernya diganti.
--
-- CREATE TABLE IF NOT EXISTS `sessions` (
--   `id` varchar(255) NOT NULL,
--   `user_id` bigint unsigned NULL,
--   `ip_address` varchar(45) NULL,
--   `user_agent` text NULL,
--   `payload` longtext NOT NULL,
--   `last_activity` int NOT NULL,
--   PRIMARY KEY (`id`),
--   KEY `sessions_user_id_index` (`user_id`),
--   KEY `sessions_last_activity_index` (`last_activity`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 6. Pemeriksaan sesudah dijalankan ───────────────────────────────────────
-- Jalankan ketiganya dan cocokkan hasilnya:
--
--   SHOW COLUMNS FROM `users` LIKE 'user_ktp';          -- 1 baris
--   SHOW COLUMNS FROM `t_skm_jawaban`;                  -- ada 5 kolom baru
--   SELECT COUNT(*) FROM `migrations`;                  -- 25
--
-- Lalu di server (bila ada Terminal):
--   php artisan migrate:status   → seluruh 25 baris "Ran"
--   php artisan db:show          → daftar tabel + jumlah barisnya
