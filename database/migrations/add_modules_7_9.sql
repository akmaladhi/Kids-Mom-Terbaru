USE momkids_db;

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
