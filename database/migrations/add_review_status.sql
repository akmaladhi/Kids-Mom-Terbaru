ALTER TABLE riwayat_cek_gizi
    MODIFY status ENUM(
        'Gizi Baik',
        'Kurang Gizi',
        'Indikasi Stunting',
        'Perlu Penilaian'
    ) NOT NULL;
