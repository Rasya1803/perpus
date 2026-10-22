-- Import lewat phpMyAdmin / CLI: mysql -u root < database.sql
CREATE DATABASE IF NOT EXISTS perpus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE perpus;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS peminjaman;
DROP TABLE IF EXISTS buku;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(100) NOT NULL,
  role ENUM('admin','petugas','operator','pengguna') NOT NULL DEFAULT 'pengguna',
  identitas VARCHAR(30) DEFAULT NULL,
  kelas VARCHAR(30) DEFAULT NULL,
  email VARCHAR(100) DEFAULT NULL,
  no_telp VARCHAR(20) DEFAULT NULL,
  alamat TEXT DEFAULT NULL,
  dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE kategori (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE buku (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(200) NOT NULL,
  pengarang VARCHAR(120) DEFAULT NULL,
  penerbit VARCHAR(120) DEFAULT NULL,
  tahun YEAR DEFAULT NULL,
  isbn VARCHAR(30) DEFAULT NULL,
  kategori_id INT DEFAULT NULL,
  stok_total INT NOT NULL DEFAULT 1,
  stok_tersedia INT NOT NULL DEFAULT 1,
  CONSTRAINT fk_buku_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE peminjaman (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  buku_id INT NOT NULL,
  tgl_pinjam DATE DEFAULT NULL,
  tgl_jatuh_tempo DATE DEFAULT NULL,
  tgl_kembali DATE DEFAULT NULL,
  status ENUM('menunggu','dipinjam','dikembalikan','ditolak','dibatalkan') NOT NULL DEFAULT 'menunggu',
  denda INT NOT NULL DEFAULT 0,
  dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pinjam_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_pinjam_buku FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Password sengaja plaintext sesuai permintaan.
INSERT INTO users (nama, username, password, role, identitas, kelas, email, no_telp, alamat) VALUES
('Administrator', 'admin', 'admin123', 'admin', 'NIP001', NULL, 'admin@sekolah.sch.id', '0811111111', 'Ruang TU'),
('Petugas Sirkulasi', 'petugas', 'petugas123', 'petugas', 'NIP002', NULL, 'petugas@sekolah.sch.id', '0811111112', 'Perpustakaan'),
('Operator Buku', 'operator', 'operator123', 'operator', 'NIP003', NULL, 'operator@sekolah.sch.id', '0811111113', 'Perpustakaan'),
('Siswa Contoh', 'siswa', 'siswa123', 'pengguna', '2024001', 'X IPA 1', 'siswa@sekolah.sch.id', '0811111114', 'Jl. Pendidikan No. 1');

INSERT INTO kategori (nama) VALUES ('Fiksi'), ('Sains'), ('Sejarah'), ('Teknologi');

INSERT INTO buku (judul, pengarang, penerbit, tahun, isbn, kategori_id, stok_total, stok_tersedia) VALUES
('Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, '9789793062792', 1, 5, 5),
('Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, '9789799731234', 1, 3, 3),
('Sapiens', 'Yuval Noah Harari', 'Kepustakaan Populer Gramedia', 2017, '9786024243241', 3, 2, 2),
('Fisika Dasar', 'Halliday & Resnick', 'Erlangga', 2010, '9789790331122', 2, 4, 4),
('Kimia Organik', 'Paula Yurkanis Bruice', 'Pearson', 2016, '9780134042282', 2, 2, 2),
('Sejarah Indonesia Modern', 'M.C. Ricklefs', 'Serambi', 2008, '9789791114001', 3, 3, 3),
('Belajar PHP', 'Abdul Kadir', 'Andi Publisher', 2019, '9789792951777', 4, 4, 4),
('Jaringan Komputer', 'Andrew S. Tanenbaum', 'Pearson', 2011, '9780132126953', 4, 2, 2);
