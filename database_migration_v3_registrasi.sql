-- Tambahan: status persetujuan akun mahasiswa + unik email (jalankan setelah backup).
USE db_himsisko;

ALTER TABLE users
  ADD COLUMN approval_status ENUM('pending','active','rejected') NOT NULL DEFAULT 'active' AFTER foto_profil;

UPDATE users SET approval_status = 'active' WHERE role = 'admin' OR role = 'mahasiswa';

-- Unik email (boleh banyak NULL di MySQL)
ALTER TABLE users ADD UNIQUE KEY uk_users_email (email);
