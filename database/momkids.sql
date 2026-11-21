CREATE DATABASE IF NOT EXISTS momkids_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE momkids_db;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS profil_ibu (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    usia_tahun TINYINT UNSIGNED NULL,
    golongan_darah ENUM('A', 'B', 'AB', 'O', 'Tidak diketahui') NOT NULL DEFAULT 'Tidak diketahui',
    usia_kehamilan_minggu TINYINT UNSIGNED NULL,
    jumlah_anak TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profil_ibu_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS anak (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    nama VARCHAR(120) NOT NULL,
    tanggal_lahir DATE NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_anak_user (user_id),
    CONSTRAINT fk_anak_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS riwayat_ukur (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    anak_id INT UNSIGNED NOT NULL,
    tanggal_ukur DATE NOT NULL,
    berat_kg DECIMAL(5, 2) NOT NULL,
    tinggi_cm DECIMAL(5, 2) NOT NULL,
    catatan VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_riwayat_ukur_anak_tanggal (anak_id, tanggal_ukur),
    CONSTRAINT fk_riwayat_ukur_anak
        FOREIGN KEY (anak_id) REFERENCES anak (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS riwayat_cek_gizi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    anak_id INT UNSIGNED NULL,
    umur_bulan SMALLINT UNSIGNED NOT NULL,
    berat_kg DECIMAL(5, 2) NOT NULL,
    tinggi_cm DECIMAL(5, 2) NOT NULL,
    status ENUM('Gizi Baik', 'Kurang Gizi', 'Indikasi Stunting', 'Perlu Penilaian') NOT NULL,
    saran TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_riwayat_cek_gizi_user (user_id, created_at),
    INDEX idx_riwayat_cek_gizi_anak (anak_id),
    CONSTRAINT fk_riwayat_cek_gizi_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_riwayat_cek_gizi_anak
        FOREIGN KEY (anak_id) REFERENCES anak (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS artikel (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    ringkasan VARCHAR(500) NOT NULL,
    konten MEDIUMTEXT NOT NULL,
    gambar_path VARCHAR(255) NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_artikel_status_tanggal (status, created_at),
    CONSTRAINT fk_artikel_created_by
        FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resep (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(180) NOT NULL,
    ringkasan VARCHAR(500) NOT NULL,
    kategori ENUM('Ibu Hamil', '6-8 Bulan', '9-12 Bulan') NOT NULL,
    bahan TEXT NOT NULL,
    langkah MEDIUMTEXT NOT NULL,
    gambar_path VARCHAR(255) NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_resep_kategori_status (kategori, status),
    CONSTRAINT fk_resep_created_by
        FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jadwal_posyandu (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    tempat VARCHAR(255) NOT NULL,
    kegiatan VARCHAR(180) NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jadwal_posyandu_tanggal (tanggal),
    CONSTRAINT fk_jadwal_posyandu_created_by
        FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pendaftaran_posyandu (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jadwal_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    status ENUM('Terdaftar', 'Hadir', 'Tidak Hadir') NOT NULL DEFAULT 'Terdaftar',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pendaftaran_posyandu_user_jadwal (user_id, jadwal_id),
    INDEX idx_pendaftaran_posyandu_jadwal (jadwal_id),
    CONSTRAINT fk_pendaftaran_posyandu_jadwal
        FOREIGN KEY (jadwal_id) REFERENCES jadwal_posyandu (id) ON DELETE CASCADE,
    CONSTRAINT fk_pendaftaran_posyandu_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS konsultasi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    pertanyaan TEXT NOT NULL,
    jawaban TEXT NULL,
    status ENUM('Menunggu', 'Dijawab') NOT NULL DEFAULT 'Menunggu',
    answered_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_konsultasi_user_tanggal (user_id, created_at),
    INDEX idx_konsultasi_status_tanggal (status, created_at),
    CONSTRAINT fk_konsultasi_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_konsultasi_answered_by
        FOREIGN KEY (answered_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;
