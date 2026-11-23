CREATE DATABASE IF NOT EXISTS sistem_keuangan_mahasiswa;
USE sistem_keuangan_mahasiswa;

-- Tabel Pengguna
CREATE TABLE pengguna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'mahasiswa') NOT NULL DEFAULT 'mahasiswa',
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Kategori
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    jenis ENUM('pemasukan', 'pengeluaran') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Transaksi
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kategori_id INT NOT NULL,
    jenis ENUM('pemasukan', 'pengeluaran') NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    tanggal DATE NOT NULL,
    catatan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES pengguna(id) ON DELETE CASCADE,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Anggaran
CREATE TABLE anggaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bulan TINYINT NOT NULL,
    tahun SMALLINT NOT NULL,
    batas_nominal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES pengguna(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Target Tabungan
CREATE TABLE target_tabungan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nama_target VARCHAR(100) NOT NULL,
    target_nominal DECIMAL(15,2) NOT NULL,
    terkumpul_nominal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tenggat_tanggal DATE NULL,
    status ENUM('berjalan', 'tercapai') NOT NULL DEFAULT 'berjalan',
    FOREIGN KEY (user_id) REFERENCES pengguna(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Tips Keuangan
CREATE TABLE tips_keuangan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(150) NOT NULL,
    konten TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- SEEDER: Data Awal
-- =============================================

-- Akun Admin (email: admin@keuangan.test, password: admin123)
INSERT INTO pengguna (nama, email, password, role) VALUES
('Administrator', 'admin@keuangan.test', '$2y$10$fFYfavwBcTKInG7u63M7Du84cDKIlggG9w/GpI1vOJSW75cOS1Ug.', 'admin');

-- Kategori Pemasukan
INSERT INTO kategori (nama_kategori, jenis) VALUES
('Uang Saku', 'pemasukan'),
('Beasiswa', 'pemasukan'),
('Kerja Paruh Waktu', 'pemasukan'),
('Lainnya', 'pemasukan');

-- Kategori Pengeluaran
INSERT INTO kategori (nama_kategori, jenis) VALUES
('Makan & Minum', 'pengeluaran'),
('Kos & Listrik', 'pengeluaran'),
('Transportasi', 'pengeluaran'),
('Tugas & Kuliah', 'pengeluaran'),
('Hiburan', 'pengeluaran'),
('Lainnya', 'pengeluaran');
