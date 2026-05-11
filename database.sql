-- SIM HIMSISKO — instalasi baru (buat database + tabel + data awal)
-- MySQL 8 / MariaDB

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS dokumentasi;
DROP TABLE IF EXISTS keuangan;
DROP TABLE IF EXISTS pengumuman;
DROP TABLE IF EXISTS kegiatan;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE DATABASE IF NOT EXISTS db_himsisko
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_himsisko;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','mahasiswa') NOT NULL DEFAULT 'mahasiswa',
  nama_lengkap VARCHAR(120) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  foto_profil VARCHAR(255) DEFAULT NULL,
  approval_status ENUM('pending','active','rejected') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE kegiatan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(200) NOT NULL,
  deskripsi TEXT DEFAULT NULL,
  tanggal DATE NOT NULL,
  lokasi VARCHAR(200) DEFAULT NULL,
  status ENUM('rencana','berlangsung','selesai','dibatalkan') NOT NULL DEFAULT 'rencana',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_kegiatan_status (status),
  INDEX idx_kegiatan_tgl (tanggal)
) ENGINE=InnoDB;

CREATE TABLE dokumentasi (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kegiatan_id INT UNSIGNED NOT NULL,
  jenis ENUM('foto','video') NOT NULL DEFAULT 'foto',
  file_path VARCHAR(255) NOT NULL,
  deskripsi VARCHAR(500) DEFAULT NULL,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_documentasi_kegiatan
    FOREIGN KEY (kegiatan_id) REFERENCES kegiatan(id) ON DELETE CASCADE,
  INDEX idx_dok_kegiatan (kegiatan_id)
) ENGINE=InnoDB;

CREATE TABLE keuangan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tipe ENUM('masuk','keluar') NOT NULL,
  nominal BIGINT UNSIGNED NOT NULL,
  keterangan VARCHAR(255) DEFAULT NULL,
  tanggal DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_keuangan_tgl (tanggal),
  INDEX idx_keuangan_tipe (tipe)
) ENGINE=InnoDB;

CREATE TABLE pengumuman (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(200) NOT NULL,
  isi TEXT NOT NULL,
  tanggal_publish DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pengu_tgl (tanggal_publish)
) ENGINE=InnoDB;

CREATE TABLE feedback (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kegiatan_id INT UNSIGNED DEFAULT NULL,
  mhs_name VARCHAR(100) NOT NULL,
  komentar TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fb_kegiatan
    FOREIGN KEY (kegiatan_id) REFERENCES kegiatan(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  user_role ENUM('admin','mahasiswa') NOT NULL,
  username VARCHAR(100) DEFAULT NULL,
  action VARCHAR(120) NOT NULL,
  details TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_activity_user (user_id),
  INDEX idx_activity_role (user_role),
  INDEX idx_activity_created_at (created_at),
  CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO users (username, password, role, nama_lengkap, email, approval_status) VALUES
(
  'admin',
  '$2y$10$AZV3kJu2/OfDKzk42HbYJOXQH5tAHOxCjfkGiWHmDD1hzjyazGfTS',
  'admin',
  'Administrator SIM',
  'admin@himsisko.local',
  'active'
);

INSERT INTO users (username, password, role, nama_lengkap, email, approval_status) VALUES
(
  'mahasiswa1',
  '$2y$10$m1yl8nX0CacQYQu1F2EJeOISuV5mBCmaWWi9EZaAtc37QJjeb0b3S',
  'mahasiswa',
  'Demo Mahasiswa',
  'mahasiswa@himsisko.local',
  'active'
);

INSERT INTO pengumuman (judul, isi, tanggal_publish) VALUES
(
  'Selamat Datang di SIM HIMSISKO',
  'Gunakan portal untuk melihat kegiatan, transparansi keuangan, dan pengumuman organisasi.',
  CURDATE()
);
