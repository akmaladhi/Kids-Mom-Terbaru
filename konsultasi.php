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
    exit('Konsultasi hanya tersedia untuk akun Ibu.');
}

$errors = [];
$question = '';
$successMessage = is_string($_SESSION['consultation_success'] ?? null)
    ? $_SESSION['consultation_success']
    : null;
unset($_SESSION['consultation_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
        $question = trim(
            is_string($_POST['pertanyaan'] ?? null) ? $_POST['pertanyaan'] : ''
        );
        $questionLength = preg_match_all('/./us', $question);

        if ($question === '' || $questionLength === false || $questionLength > 5000) {
            $errors[] = 'Pertanyaan wajib diisi dan maksimal 5.000 karakter.';
        }

        if (!$errors) {
            $insertStatement = db()->prepare(
                'INSERT INTO konsultasi (user_id, pertanyaan)
                 VALUES (:user_id, :pertanyaan)'
            );
            $insertStatement->execute([
                'user_id' => $userId,
                'pertanyaan' => $question,
            ]);
            $_SESSION['consultation_success'] = 'Pertanyaan berhasil dikirim kepada Admin/Bidan.';
            redirect_to('konsultasi.php');
        }
    }
}

$consultationStatement = db()->prepare(
    'SELECT id, pertanyaan, jawaban, status, created_at, updated_at
     FROM konsultasi
     WHERE user_id = :user_id
     ORDER BY created_at DESC, id DESC'
);
$consultationStatement->execute(['user_id' => $userId]);
$consultations = $consultationStatement->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanya Bidan | Mom&amp;Kids+</title>
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
            <span class="eyebrow">Konsultasi keluarga</span>
            <h1>Tanya Bidan</h1>
            <p>Kirim pertanyaan secara tertulis dan lihat balasan Admin/Bidan di halaman ini.</p>
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

        <section class="management-card">
            <h2>Kirim pertanyaan</h2>
            <p class="management-help">Jangan kirim informasi darurat. Hubungi tenaga kesehatan secara langsung bila membutuhkan pertolongan segera.</p>
            <form method="post" action="konsultasi.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
                <label for="pertanyaan">Pertanyaan</label>
                <textarea id="pertanyaan" name="pertanyaan" rows="5" maxlength="5000" required><?php
                    echo escape_html($question);
                ?></textarea>
                <button class="button button-primary auth-submit" type="submit">Kirim pertanyaan</button>
            </form>
        </section>

        <section class="management-card nutrition-history">
            <h2>Riwayat konsultasi</h2>
            <?php if (!$consultations): ?>
                <p class="empty-management">Belum ada pertanyaan yang dikirim.</p>
            <?php else: ?>
                <div class="consultation-list">
                    <?php foreach ($consultations as $consultation): ?>
                        <article class="consultation-item">
                            <div class="management-card-heading">
                                <span class="status-badge status-<?php echo $consultation['status'] === 'Dijawab' ? 'answered' : 'waiting'; ?>">
                                    <?php echo escape_html($consultation['status']); ?>
                                </span>
                                <time datetime="<?php echo escape_html(date('c', strtotime($consultation['created_at']))); ?>">
                                    <?php echo escape_html(date('d-m-Y H:i', strtotime($consultation['created_at']))); ?>
                                </time>
                            </div>
                            <h3>Pertanyaan Anda</h3>
                            <div class="article-content"><?php echo nl2br(escape_html($consultation['pertanyaan'])); ?></div>
                            <?php if ($consultation['status'] === 'Dijawab' && $consultation['jawaban'] !== null): ?>
                                <h3>Balasan Admin/Bidan</h3>
                                <div class="consultation-answer article-content"><?php echo nl2br(escape_html($consultation['jawaban'])); ?></div>
                            <?php else: ?>
                                <p class="management-help">Pertanyaan Anda sedang menunggu balasan.</p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="assets/js/app.js?v=2"></script>
</body>
</html>
