-- Tambahan: tabel log aktivitas pengguna
USE db_himsisko;

CREATE TABLE IF NOT EXISTS activity_logs (
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
