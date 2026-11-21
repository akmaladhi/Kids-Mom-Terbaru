# Mom&Kids+

Website pendamping ibu dan anak berbasis PHP, MySQL, HTML, CSS, dan JavaScript untuk XAMPP.

## Menjalankan dengan XAMPP

1. Salin folder proyek ke `D:\xampp\htdocs\webRES`.
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Buat database `momkids_db`, lalu impor `database/momkids.sql` melalui phpMyAdmin.
4. Jika database sudah dipakai, impor migrasi `database/migrations/add_modules_7_9.sql` melalui phpMyAdmin.
5. Buka `http://localhost/webRES/`.
6. Sebelum Admin pertama tersedia, buka `http://localhost/webRES/login.php` dari komputer yang menjalankan XAMPP, lalu pilih **Buat akun Admin lokal**. Setup ini hanya tersedia dari localhost, mensyaratkan kata sandi minimal 12 karakter, dan terkunci setelah Admin pertama dibuat.
7. Daftar akun Ibu dari halaman **Daftar**, lalu masuk untuk menggunakan fitur keluarga.

Koneksi database lokal menggunakan `localhost`, database `momkids_db`, user `root`, dan password kosong. Atur environment `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASSWORD` bila konfigurasi XAMPP berbeda.

## Pembagian role

- **Admin/Bidan:** melihat daftar Ibu, mengelola seluruh data anak, artikel, resep, jadwal Posyandu, menjawab konsultasi, dan melihat/mencetak laporan.
- **Ibu:** mengelola profil dan data anak sendiri, mencatat pengukuran, melakukan skrining gizi, mendaftar ke jadwal Posyandu, dan mengirim/melihat konsultasi sendiri.
- Pendaftaran publik selalu membuat akun Ibu. Role Admin tidak dapat dipilih melalui form pendaftaran.

## Modul 7–9

- **Jadwal Posyandu:** Admin membuat/mengubah/menghapus jadwal dan melihat peserta; Ibu dapat mendaftar satu kali untuk setiap jadwal mendatang.
- **Tanya Bidan:** Ibu mengirim pertanyaan dan melihat status/balasan; Admin/Bidan membalas melalui dashboard Admin.
- **Laporan:** Admin melihat jumlah Ibu, anak, anak dengan hasil skrining terbaru berindikasi stunting, serta 20 pemeriksaan terbaru. Tombol cetak memakai fitur bawaan browser.

Konsultasi di website bukan layanan darurat dan tidak menggantikan pemeriksaan langsung oleh tenaga kesehatan.
