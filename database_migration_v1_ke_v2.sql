-- Cadangkan DB dulu. Untuk instalasi baru cukup impor database.sql saja.

USE db_himsisko;

ALTER TABLE users ADD nama_lengkap VARCHAR(120) DEFAULT NULL AFTER role;
ALTER TABLE users ADD email VARCHAR(150) DEFAULT NULL AFTER nama_lengkap;
ALTER TABLE users ADD foto_profil VARCHAR(255) DEFAULT NULL AFTER email;

ALTER TABLE kegiatan CHANGE nama_kegiatan judul VARCHAR(200) NOT NULL;
ALTER TABLE kegiatan ADD lokasi VARCHAR(200) DEFAULT NULL AFTER tanggal;
ALTER TABLE kegiatan MODIFY status ENUM('rencana','berlangsung','selesai','dibatalkan') NOT NULL DEFAULT 'rencana';

ALTER TABLE dokumentasi ADD jenis ENUM('foto','video') NOT NULL DEFAULT 'foto' AFTER kegiatan_id;
ALTER TABLE dokumentasi CHANGE keterangan deskripsi VARCHAR(500) DEFAULT NULL;

CREATE TABLE pengumuman (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(200) NOT NULL,
  isi TEXT NOT NULL,
  tanggal_publish DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

