-- ============================================================================
--  RuangKuliah - Sistem Informasi Jadwal Perkuliahan (UNISKA)
--  Database : ruangkuliah
--  Kompatibel dengan MariaDB 10.4+ / MySQL 5.7+ (bawaan XAMPP)
--
--  Cara import:
--    1. Jalankan Apache + MySQL di XAMPP Control Panel
--    2. Buka http://localhost/phpmyadmin  ->  tab "Import"
--    3. Pilih file ini  ->  klik "Import"
--       (database "ruangkuliah" akan dibuat otomatis)
--
--  PERINGATAN: file ini menghapus tabel lama (DROP TABLE) lalu membuatnya ulang.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `ruangkuliah`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ruangkuliah`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `notifikasi_dibaca`;
DROP TABLE IF EXISTS `notifikasi`;
DROP TABLE IF EXISTS `jadwal`;
DROP TABLE IF EXISTS `mata_kuliah`;
DROP TABLE IF EXISTS `mahasiswa`;
DROP TABLE IF EXISTS `dosen`;
DROP TABLE IF EXISTS `admin`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
--  1. admin  (super admin)
-- ----------------------------------------------------------------------------
CREATE TABLE `admin` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`          VARCHAR(120) NOT NULL,
  `username`      VARCHAR(50)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  2. dosen  (login memakai nama + NIP)
-- ----------------------------------------------------------------------------
CREATE TABLE `dosen` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(120) NOT NULL,
  `nip`        VARCHAR(30)  NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dosen_nip` (`nip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  3. mahasiswa  (login memakai nama + NPM)
-- ----------------------------------------------------------------------------
CREATE TABLE `mahasiswa` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(120) NOT NULL,
  `npm`        VARCHAR(30)  NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mahasiswa_npm` (`npm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  4. mata_kuliah  (dosen pengampu diatur oleh super admin)
--     Jika dosen dihapus, dosen_id otomatis menjadi NULL (belum ditentukan).
-- ----------------------------------------------------------------------------
CREATE TABLE `mata_kuliah` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(150) NOT NULL,
  `dosen_id`   INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mk_dosen` (`dosen_id`),
  CONSTRAINT `fk_mk_dosen` FOREIGN KEY (`dosen_id`)
    REFERENCES `dosen` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  5. jadwal  (sesi perkuliahan; hari & jam dapat diubah dosen pengampu/admin)
-- ----------------------------------------------------------------------------
CREATE TABLE `jadwal` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mata_kuliah_id` INT UNSIGNED NOT NULL,
  `hari`           ENUM('Senin','Selasa','Rabu','Kamis','Jumat') NOT NULL,
  `jam_mulai`      TIME         NOT NULL,
  `jam_selesai`    TIME         NOT NULL,
  `kelas`          VARCHAR(10)  NOT NULL,
  `ruang`          VARCHAR(20)  NOT NULL,
  `jenis`          ENUM('Kuliah','Praktikum') NOT NULL DEFAULT 'Kuliah',
  `diubah_pada`    DATETIME     NULL DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jadwal_mk` (`mata_kuliah_id`),
  KEY `idx_jadwal_hari` (`hari`, `jam_mulai`),
  CONSTRAINT `fk_jadwal_mk` FOREIGN KEY (`mata_kuliah_id`)
    REFERENCES `mata_kuliah` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  6. notifikasi  (riwayat pergantian jadwal, dapat dilihat semua pengguna)
--     Nama mata kuliah disimpan sebagai salinan agar riwayat tetap utuh.
-- ----------------------------------------------------------------------------
CREATE TABLE `notifikasi` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jadwal_id`     INT UNSIGNED NULL DEFAULT NULL,
  `mata_kuliah`   VARCHAR(150) NOT NULL,
  `kelas`         VARCHAR(10)  NOT NULL,
  `hari_lama`     ENUM('Senin','Selasa','Rabu','Kamis','Jumat') NOT NULL,
  `mulai_lama`    TIME         NOT NULL,
  `selesai_lama`  TIME         NOT NULL,
  `hari_baru`     ENUM('Senin','Selasa','Rabu','Kamis','Jumat') NOT NULL,
  `mulai_baru`    TIME         NOT NULL,
  `selesai_baru`  TIME         NOT NULL,
  `pengubah_role` ENUM('dosen','admin') NOT NULL,
  `pengubah_nama` VARCHAR(120) NOT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_created` (`created_at`),
  KEY `idx_notif_jadwal` (`jadwal_id`),
  CONSTRAINT `fk_notif_jadwal` FOREIGN KEY (`jadwal_id`)
    REFERENCES `jadwal` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
--  7. notifikasi_dibaca  (penanda notifikasi terakhir yang sudah dilihat
--     tiap pengguna, dipakai untuk lencana "baru")
-- ----------------------------------------------------------------------------
CREATE TABLE `notifikasi_dibaca` (
  `role`         ENUM('mahasiswa','dosen','admin') NOT NULL,
  `user_id`      INT UNSIGNED NOT NULL,
  `terakhir_id`  INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`role`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
--  DATA AWAL (DUMMY)
-- ============================================================================

-- Super admin dummy ---------------------------------------------------------
--   username : admin
--   password : admin123      (disimpan sebagai hash bcrypt)
INSERT INTO `admin` (`id`, `nama`, `username`, `password_hash`) VALUES
(1, 'Super Admin', 'admin', '$2y$10$WVJA0JdXQVcUY1gktoI1j.icD/RRdxg0/HLKfkdLS36AySYt1NZ5G');

-- Mata kuliah : jadwal kuliah Kelas A2 Semester 5 ---------------------------
-- dosen_id sengaja NULL: super admin yang menambahkan dosen lalu
-- memasangkannya ke mata kuliah lewat menu Administrasi > Dosen pengampu.
INSERT INTO `mata_kuliah` (`id`, `nama`, `dosen_id`) VALUES
(1, 'Pengolahan Citra Digital',      NULL),
(2, 'Decision Support System',       NULL),
(3, 'AGAMA 5',                       NULL),
(4, 'Teamwork Building',             NULL),
(5, 'e-Learning',                    NULL),
(6, 'Komunikasi Data',               NULL),
(7, 'Prak Komunikasi Data',          NULL),
(8, 'Pem Dasar Berbasis Obyek',      NULL),
(9, 'Prak Pem Dasar Berbasis Obyek', NULL);

INSERT INTO `jadwal` (`mata_kuliah_id`, `hari`, `jam_mulai`, `jam_selesai`, `kelas`, `ruang`, `jenis`) VALUES
(1, 'Senin',  '08:00:00', '10:00:00', 'A2',  'G3',   'Kuliah'),
(2, 'Senin',  '10:50:00', '13:50:00', 'A1',  'Lab',  'Kuliah'),
(3, 'Senin',  '14:40:00', '16:50:00', 'A13', 'C3.1', 'Kuliah'),
(4, 'Selasa', '09:10:00', '10:50:00', 'A2',  'G3',   'Kuliah'),
(5, 'Rabu',   '07:30:00', '10:00:00', 'A1',  'Lab',  'Kuliah'),
(6, 'Rabu',   '13:50:00', '16:00:00', 'A2',  'G2',   'Kuliah'),
(7, 'Rabu',   '16:00:00', '16:50:00', 'A2',  'Lab',  'Praktikum'),
(8, 'Jumat',  '10:00:00', '12:30:00', 'A2',  'G2',   'Kuliah'),
(9, 'Jumat',  '13:00:00', '13:50:00', 'A2',  'Lab',  'Praktikum');
