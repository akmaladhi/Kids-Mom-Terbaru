<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$statement = db()->prepare(
    'SELECT nama, email, role FROM users WHERE id = :id LIMIT 1'
);
$statement->execute(['id' => $_SESSION['user_id']]);
$user = $statement->fetch();

if (!$user) {
    $_SESSION = [];
    session_regenerate_id(true);
    redirect_to('login.php');
}

$roleLabel = $user['role'] === 'admin' ? 'Admin' : 'Ibu';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda Akun | Mom&amp;Kids+</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">
    <header class="auth-header container">
        <a class="brand" href="index.php">
            <span class="brand-mark" aria-hidden="true">M</span>
            <span>Mom&amp;Kids<span class="brand-plus">+</span></span>
        </a>
        <div class="profile-header-actions">
            <a class="button button-small button-outline page-back page-back-static" href="index.php">
                <span aria-hidden="true">←</span> Kembali
            </a>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
                <button class="button button-small button-outline" type="submit">Keluar</button>
            </form>
        </div>
    </header>

    <main class="auth-main">
        <section class="auth-card dashboard-card">
            <span class="eyebrow">Berhasil masuk sebagai <?php echo escape_html($roleLabel); ?></span>
            <h1>Halo, <?php echo escape_html($user['nama']); ?>!</h1>
            <p class="auth-intro">
                Kelola informasi keluarga Anda dan ikuti perjalanan tumbuh kembang si kecil.
            </p>
            <div class="dashboard-actions">
                <a class="button button-primary" href="anak.php">Data anak</a>
                <a class="button button-outline" href="cek_gizi.php">Cek gizi</a>
                <a class="button button-outline" href="resep.php">Resep MPASI</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a class="button button-primary" href="admin/ibu.php">Daftar ibu</a>
                    <a class="button button-outline" href="admin/artikel.php">Kelola artikel</a>
                    <a class="button button-outline" href="admin/resep.php">Kelola resep</a>
                    <a class="button button-outline" href="admin/posyandu.php">Kelola Posyandu</a>
                    <a class="button button-outline" href="admin/konsultasi.php">Konsultasi masuk</a>
                    <a class="button button-outline" href="admin/laporan.php">Laporan data</a>
                <?php else: ?>
                    <a class="button button-primary" href="profile.php">Profil ibu</a>
                    <a class="button button-outline" href="posyandu.php">Jadwal Posyandu</a>
                    <a class="button button-outline" href="konsultasi.php">Tanya Bidan</a>
                <?php endif; ?>
                <a class="button button-outline" href="artikel.php">Artikel edukasi</a>
                <a class="button button-outline" href="index.php">Kembali ke beranda</a>
            </div>
        </section>
    </main>
    <script src="assets/js/app.js"></script>
</body>
</html>
