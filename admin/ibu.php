<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login();

$statement = db()->prepare(
    'SELECT role FROM users WHERE id = :id LIMIT 1'
);
$statement->execute(['id' => $_SESSION['user_id']]);
$currentUser = $statement->fetch();

if (!$currentUser) {
    $_SESSION = [];
    session_regenerate_id(true);
    redirect_to('../login.php');
}

if ($currentUser['role'] !== 'admin') {
    http_response_code(403);
    exit('Halaman ini hanya tersedia untuk Admin.');
}

$mothers = db()->query(
    'SELECT u.nama, u.email, u.created_at, p.usia_tahun, p.golongan_darah,
            p.usia_kehamilan_minggu, p.jumlah_anak
     FROM users u
     LEFT JOIN profil_ibu p ON p.user_id = u.id
     WHERE u.role = \'user\'
     ORDER BY u.created_at DESC, u.id DESC'
)->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Ibu | Mom&amp;Kids+</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-page">
    <header class="auth-header container">
        <a class="brand" href="../dashboard.php">
            <span class="brand-mark" aria-hidden="true">M</span>
            <span>Mom&amp;Kids<span class="brand-plus">+</span></span>
        </a>
        <div class="profile-header-actions">
            <a class="button button-small button-outline page-back" href="../dashboard.php">
                <span aria-hidden="true">←</span> Kembali
            </a>
            <form method="post" action="../logout.php">
                <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
                <button class="button button-small button-outline" type="submit">Keluar</button>
            </form>
        </div>
    </header>

    <main class="admin-main container">
        <div class="admin-heading">
            <span class="eyebrow">Pemantauan akun</span>
            <h1>Daftar ibu terdaftar</h1>
            <p>Informasi profil untuk akun Ibu yang sudah terdaftar.</p>
        </div>

        <div class="table-wrap">
            <table class="mothers-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Usia</th>
                        <th>Gol. darah</th>
                        <th>Kehamilan</th>
                        <th>Jumlah anak</th>
                        <th>Terdaftar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$mothers): ?>
                        <tr>
                            <td class="empty-state" colspan="7">Belum ada akun Ibu yang terdaftar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mothers as $mother): ?>
                            <tr>
                                <td><?php echo escape_html($mother['nama']); ?></td>
                                <td><?php echo escape_html($mother['email']); ?></td>
                                <td><?php echo $mother['usia_tahun'] === null
                                    ? '-'
                                    : escape_html((string) $mother['usia_tahun']); ?></td>
                                <td><?php echo escape_html($mother['golongan_darah'] ?? 'Tidak diketahui'); ?></td>
                                <td><?php echo $mother['usia_kehamilan_minggu'] === null
                                    ? '-'
                                    : escape_html((string) $mother['usia_kehamilan_minggu']) . ' minggu'; ?></td>
                                <td><?php echo $mother['jumlah_anak'] === null
                                    ? '-'
                                    : escape_html((string) $mother['jumlah_anak']); ?></td>
                                <td><?php echo escape_html(date('d-m-Y', strtotime($mother['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script src="../assets/js/app.js"></script>
</body>
</html>
