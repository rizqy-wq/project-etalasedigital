CREATE DATABASE IF NOT EXISTS db_etalase_stella CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_etalase_stella;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'siswa') DEFAULT 'siswa'
);

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nama_project VARCHAR(150) NOT NULL,
    deskripsi TEXT NOT NULL,
    gambar VARCHAR(255) NOT NULL,
    link_project VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Akun demo (password semua akun: password123)
INSERT IGNORE INTO users (nama, email, password, role) VALUES
('Super Admin Stella', 'superadmin@stella.sch.id', '$2y$10$TARps.N..ZyDpEji67Y7HO//gtT3E.Kb8E3KSn.it811CETWcA.JK', 'super_admin'),
('Admin Stella', 'admin@stella.sch.id', '$2y$10$TARps.N..ZyDpEji67Y7HO//gtT3E.Kb8E3KSn.it811CETWcA.JK', 'admin'),
('Siswa Contoh', 'siswa@stella.sch.id', '$2y$10$TARps.N..ZyDpEji67Y7HO//gtT3E.Kb8E3KSn.it811CETWcA.JK', 'siswa');
