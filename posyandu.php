<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$userStatement = db()->prepare(
    'SELECT role FROM users WHERE id = :id LIMIT 1'
);
$userStatement->execute(['id' => $userId]);
$currentUser = $userStatement->fetch();

if (!$currentUser) {
    $_SESSION = [];
    session_regenerate_id(true);
    redirect_to('login.php');
}

if ($currentUser['role'] !== 'user') {
    http_response_code(403);
    exit('Pendaftaran Posyandu hanya tersedia untuk akun Ibu.');
}

$errors = [];
$successMessage = is_string($_SESSION['posyandu_success'] ?? null)
    ? $_SESSION['posyandu_success']
    : null;
unset($_SESSION['posyandu_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
        $scheduleId = filter_var(
            is_string($_POST['jadwal_id'] ?? null) ? $_POST['jadwal_id'] : null,
            FILTER_VALIDATE_INT
        );

        if ($scheduleId === false || $scheduleId < 1) {
            http_response_code(404);
            exit('Jadwal Posyandu tidak ditemukan.');
        }

        $scheduleStatement = db()->prepare(
            'SELECT id FROM jadwal_posyandu
             WHERE id = :id AND tanggal >= CURRENT_DATE
             LIMIT 1'
        );
        $scheduleStatement->execute(['id' => $scheduleId]);
        if (!$scheduleStatement->fetch()) {
            http_response_code(404);
            exit('Jadwal tidak tersedia untuk pendaftaran.');
        }

        $alreadyRegisteredStatement = db()->prepare(
            'SELECT id FROM pendaftaran_posyandu
             WHERE user_id = :user_id AND jadwal_id = :jadwal_id
             LIMIT 1'
        );
        $alreadyRegisteredStatement->execute([
            'user_id' => $userId,
            'jadwal_id' => $scheduleId,
        ]);

        if ($alreadyRegisteredStatement->fetch()) {
            $errors[] = 'Anda sudah terdaftar pada jadwal ini.';
        } else {
            try {
                $insertStatement = db()->prepare(
                    'INSERT INTO pendaftaran_posyandu (user_id, jadwal_id)
                     VALUES (:user_id, :jadwal_id)'
                );
                $insertStatement->execute([
                    'user_id' => $userId,
                    'jadwal_id' => $scheduleId,
                ]);
                $_SESSION['posyandu_success'] = 'Pendaftaran berhasil. Status Anda: Terdaftar.';
                redirect_to('posyandu.php');
            } catch (PDOException $exception) {
                if (
                    isset($exception->errorInfo[1])
                    && (int) $exception->errorInfo[1] === 1062
                ) {
                    $errors[] = 'Anda sudah terdaftar pada jadwal ini.';
                } else {
                    throw $exception;
                }
            }
        }
    }
}

$scheduleStatement = db()->prepare(
    'SELECT j.id, j.tanggal, j.tempat, j.kegiatan,
            p.status AS registration_status
     FROM jadwal_posyandu j
     LEFT JOIN pendaftaran_posyandu p
       ON p.jadwal_id = j.id AND p.user_id = :user_id
     WHERE j.tanggal >= CURRENT_DATE
     ORDER BY j.tanggal ASC, j.id ASC'
);
$scheduleStatement->execute(['user_id' => $userId]);
$schedules = $scheduleStatement->fetchAll();

$historyStatement = db()->prepare(
    'SELECT j.id, j.tanggal, j.tempat, j.kegiatan, p.status
     FROM pendaftaran_posyandu p
     JOIN jadwal_posyandu j ON j.id = p.jadwal_id
     WHERE p.user_id = :user_id AND j.tanggal < CURRENT_DATE
     ORDER BY j.tanggal DESC, j.id DESC'
);
$historyStatement->execute(['user_id' => $userId]);
$pastRegistrations = $historyStatement->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Posyandu | Mom&amp;Kids+</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">
    <header class="auth-header container">
        <a class="brand" href="index.php">
            <img class="brand-logo" src="assets/img/LOGO%20Mom%26Kids%2B-3.png" alt="Mom&amp;Kids+">
        </a>
        <div class="profile-header-actions">
            <a class="button button-small button-outline page-back" href="index.php">
                <span aria-hidden="true">←</span> Kembali
            </a>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
                <button class="button button-small button-outline" type="submit">Keluar</button>
            </form>
        </div>
    </header>

    <main class="management-main container">
        <div class="management-heading">
            <span class="eyebrow">Kegiatan kesehatan keluarga</span>
            <h1>Jadwal Posyandu</h1>
            <p>Pilih jadwal yang tersedia untuk mendaftarkan kehadiran Anda.</p>
        </div>

        <?php if ($successMessage !== null): ?>
            <div class="form-success" role="status"><?php echo escape_html($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="form-errors" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo escape_html($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$schedules): ?>
            <p class="empty-management">Belum ada jadwal Posyandu yang akan datang.</p>
        <?php else: ?>
            <div class="article-grid recipe-grid">
                <?php foreach ($schedules as $schedule): ?>
                    <article class="article-card recipe-card">
                        <div class="article-card-body">
                            <span class="recipe-category">
                                <?php echo escape_html(date('d-m-Y', strtotime($schedule['tanggal']))); ?>
                            </span>
                            <h2><?php echo escape_html($schedule['kegiatan']); ?></h2>
                            <p><?php echo escape_html($schedule['tempat']); ?></p>
                            <?php if ($schedule['registration_status'] !== null): ?>
                                <span class="registration-status">
                                    Terdaftar · <?php echo escape_html($schedule['registration_status']); ?>
                                </span>
                            <?php else: ?>
                                <form method="post" action="posyandu.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
                                    <input type="hidden" name="jadwal_id" value="<?php echo (int) $schedule['id']; ?>">
                                    <button class="button button-primary button-small" type="submit">Daftar Hadir</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($pastRegistrations): ?>
            <section class="management-card nutrition-history">
                <h2>Riwayat pendaftaran</h2>
                <div class="table-wrap">
                    <table class="mothers-table">
                        <thead>
                            <tr><th>Tanggal</th><th>Kegiatan</th><th>Tempat</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pastRegistrations as $registration): ?>
                                <tr>
                                    <td><?php echo escape_html(date('d-m-Y', strtotime($registration['tanggal']))); ?></td>
                                    <td><?php echo escape_html($registration['kegiatan']); ?></td>
                                    <td><?php echo escape_html($registration['tempat']); ?></td>
                                    <td><?php echo escape_html($registration['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <script src="assets/js/app.js?v=2"></script>
</body>
</html>
