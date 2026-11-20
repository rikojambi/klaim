-- ============================================================
-- DATABASE: Sistem Perekaman Klaim BPJS Ketenagakerjaan
-- Versi     : 1.0
-- Dibuat    : 2024
-- Deskripsi : Script DDL + Data Dummy untuk aplikasi klaim
-- ============================================================

CREATE DATABASE IF NOT EXISTS `db_bpjs_klaim`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db_bpjs_klaim`;

-- ============================================================
-- Tabel: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id_user`      INT(11)      NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(50)  NOT NULL,
  `password`     VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role`         ENUM('Admin','Petugas') NOT NULL DEFAULT 'Petugas',
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabel: peserta
-- ============================================================
CREATE TABLE IF NOT EXISTS `peserta` (
  `no_kpj`          VARCHAR(20)  NOT NULL,
  `nik`             VARCHAR(16)  NOT NULL,
  `nama_peserta`    VARCHAR(100) NOT NULL,
  `nama_perusahaan` VARCHAR(150) NOT NULL,
  `no_telp`         VARCHAR(15)  NOT NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`no_kpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabel: klaim
-- ============================================================
CREATE TABLE IF NOT EXISTS `klaim` (
  `id_klaim`      VARCHAR(20)  NOT NULL,
  `no_kpj`        VARCHAR(20)  NOT NULL,
  `id_user`       INT(11)      NOT NULL,
  `tgl_pengajuan` DATE         NOT NULL,
  `jenis_klaim`   ENUM('JHT','JKK','JKM','JP') NOT NULL,
  `status_klaim`  ENUM('Berkas Diterima','Verifikasi','Disetujui','Ditolak') NOT NULL DEFAULT 'Berkas Diterima',
  `keterangan`    TEXT         NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_klaim`),
  KEY `fk_klaim_peserta` (`no_kpj`),
  KEY `fk_klaim_user`    (`id_user`),
  CONSTRAINT `fk_klaim_peserta` FOREIGN KEY (`no_kpj`)  REFERENCES `peserta` (`no_kpj`) ON UPDATE CASCADE,
  CONSTRAINT `fk_klaim_user`    FOREIGN KEY (`id_user`) REFERENCES `users`   (`id_user`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabel: dokumen_syarat
-- ============================================================
CREATE TABLE IF NOT EXISTS `dokumen_syarat` (
  `id_dokumen`    INT(11)      NOT NULL AUTO_INCREMENT,
  `id_klaim`      VARCHAR(20)  NOT NULL,
  `nama_dokumen`  VARCHAR(100) NOT NULL,
  `file_path`     VARCHAR(255) NOT NULL,
  `uploaded_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dokumen`),
  KEY `fk_dok_klaim` (`id_klaim`),
  CONSTRAINT `fk_dok_klaim` FOREIGN KEY (`id_klaim`) REFERENCES `klaim` (`id_klaim`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA DUMMY: users
-- Password admin123  → password_hash('admin123',  PASSWORD_DEFAULT)
-- Password petugas123→ password_hash('petugas123',PASSWORD_DEFAULT)
-- ============================================================
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`) VALUES
('admin',    '$2y$10$1aMo9utHGdD5BUn8tOvBIu2aVdB4/GLhYqX8ngKI8FHVYlAnn8ezS', 'Administrator BPJS', 'Admin'),
('petugas1', '$2y$10$dnWrFryoVNPu80qNMC3mx.ksgaS.hGR6BjX8L.qKkilXRlyl3N67S', 'Budi Santoso', 'Petugas'),
('petugas2', '$2y$10$dnWrFryoVNPu80qNMC3mx.ksgaS.hGR6BjX8L.qKkilXRlyl3N67S', 'Siti Rahayu', 'Petugas');

-- ============================================================
-- DATA DUMMY: peserta
-- ============================================================
INSERT INTO `peserta` (`no_kpj`, `nik`, `nama_peserta`, `nama_perusahaan`, `no_telp`) VALUES
('1234567890001', '3201010101800001', 'Ahmad Fauzi',       'PT Maju Bersama Tbk',       '081234567890'),
('1234567890002', '3201010101800002', 'Dewi Lestari',      'CV Karya Mandiri',           '082345678901'),
('1234567890003', '3201010101800003', 'Rudi Hartono',      'PT Sejahtera Indonesia',     '083456789012'),
('1234567890004', '3201010101800004', 'Sari Indah',        'UD Berkah Jaya',             '084567890123'),
('1234567890005', '3201010101800005', 'Hendra Gunawan',    'PT Teknologi Nusantara',     '085678901234'),
('1234567890006', '3201010101800006', 'Rina Susanti',      'PT Global Makmur',           '086789012345'),
('1234567890007', '3201010101800007', 'Doni Prasetyo',     'CV Mitra Usaha',             '087890123456'),
('1234567890008', '3201010101800008', 'Yuli Andriani',     'PT Indah Permai',            '088901234567'),
('1234567890009', '3201010101800009', 'Bambang Setiawan',  'PT Citra Abadi',             '089012345678'),
('1234567890010', '3201010101800010', 'Fitri Handayani',   'PT Harapan Bangsa',          '081122334455');

-- ============================================================
-- DATA DUMMY: klaim
-- ============================================================
INSERT INTO `klaim` (`id_klaim`, `no_kpj`, `id_user`, `tgl_pengajuan`, `jenis_klaim`, `status_klaim`, `keterangan`) VALUES
('KLM-20240101-001', '1234567890001', 2, '2024-01-05', 'JHT', 'Disetujui',       'Klaim JHT telah disetujui dan proses pencairan.'),
('KLM-20240101-002', '1234567890002', 2, '2024-01-10', 'JKK', 'Verifikasi',      'Sedang dalam proses verifikasi dokumen.'),
('KLM-20240201-001', '1234567890003', 3, '2024-02-03', 'JKM', 'Berkas Diterima', 'Berkas sudah diterima, menunggu verifikasi.'),
('KLM-20240201-002', '1234567890004', 2, '2024-02-15', 'JP',  'Ditolak',         'Berkas tidak lengkap, silakan lengkapi dokumen.'),
('KLM-20240301-001', '1234567890005', 3, '2024-03-01', 'JHT', 'Disetujui',       'Klaim disetujui, proses transfer 3 hari kerja.'),
('KLM-20240301-002', '1234567890006', 2, '2024-03-12', 'JKK', 'Verifikasi',      'Verifikasi lapangan sedang dilakukan.'),
('KLM-20240401-001', '1234567890007', 3, '2024-04-02', 'JHT', 'Berkas Diterima', NULL),
('KLM-20240401-002', '1234567890008', 2, '2024-04-20', 'JKM', 'Disetujui',       'Santunan kematian telah diproses.'),
('KLM-20240501-001', '1234567890009', 3, '2024-05-05', 'JP',  'Verifikasi',      'Pengecekan masa iuran peserta.'),
('KLM-20240501-002', '1234567890010', 2, '2024-05-18', 'JHT', 'Berkas Diterima', NULL);

-- ============================================================
-- CATATAN PENTING:
-- Hash password di atas menggunakan PASSWORD_DEFAULT PHP.
-- Jika hash tidak cocok, jalankan script PHP berikut untuk
-- generate ulang dan UPDATE tabel users:
--
-- <?php
-- echo password_hash('admin123',   PASSWORD_DEFAULT); // untuk admin
-- echo password_hash('petugas123', PASSWORD_DEFAULT); // untuk petugas
-- ?>
--
-- Lalu UPDATE `users` SET `password`='<hash_baru>' WHERE username='admin';
-- ============================================================
