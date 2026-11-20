-- ===== DATABASE: import di phpMyAdmin (tab "Import") atau biarkan dibuat otomatis oleh config.php =====
CREATE DATABASE IF NOT EXISTS absensi_hamka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE absensi_hamka;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  nim VARCHAR(30) NOT NULL UNIQUE,
  telepon VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin','admin_utama') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS absensi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  kegiatan VARCHAR(150) NOT NULL,
  departemen VARCHAR(80) NOT NULL,
  tanggal DATE NOT NULL,
  keterangan ENUM('Hadir','Sakit','Izin') NOT NULL,
  foto VARCHAR(255) NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  akurasi INT NULL,
  lokasi VARCHAR(255) NULL,
  pdf VARCHAR(255) NULL,
  pdf_hash CHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY satu_kegiatan_satu_absen (user_id, kegiatan, tanggal),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Admin utama (sekum1 / Sekum#123) dibuat otomatis oleh config.php
