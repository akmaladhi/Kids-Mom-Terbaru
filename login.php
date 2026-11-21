<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
redirect_if_authenticated();

$adminSetupAvailable = is_localhost_request()
    && (int) db()->query(
        'SELECT COUNT(*) FROM users WHERE role = \'admin\''
    )->fetchColumn() === 0;
$setupSuccess = is_string($_SESSION['setup_admin_success'] ?? null)
    ? $_SESSION['setup_admin_success']
    : null;
unset($_SESSION['setup_admin_success']);

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
        $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $errors[] = 'Email atau kata sandi tidak benar.';
        } else {
            $statement = db()->prepare(
                'SELECT id, password_hash FROM users WHERE email = :email LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Email atau kata sandi tidak benar.';
            } else {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    $updateStatement = db()->prepare(
                        'UPDATE users SET password_hash = :password_hash WHERE id = :id'
                    );
                    $updateStatement->execute([
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => $user['id'],
                    ]);
                }

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                redirect_to('dashboard.php');
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | Mom&amp;Kids+</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">
    <header class="auth-header container">
        <a class="brand" href="index.php">
            <span class="brand-mark" aria-hidden="true">M</span>
            <span>Mom&amp;Kids<span class="brand-plus">+</span></span>
        </a>
        <a class="button button-small button-outline page-back page-back-static" href="index.php">
            <span aria-hidden="true">←</span> Kembali
        </a>
    </header>

    <main class="auth-main">
        <section class="auth-card" aria-labelledby="login-title">
            <span class="eyebrow">Senang bertemu kembali</span>
            <h1 id="login-title">Masuk ke akun</h1>
            <p class="auth-intro">Lanjutkan perjalanan tumbuh kembang keluarga Anda.</p>

            <?php if ($setupSuccess !== null): ?>
                <div class="form-success" role="status"><?php echo escape_html($setupSuccess); ?></div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="form-errors" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo escape_html($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">

                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="190" autocomplete="email" required
                    value="<?php echo escape_html($email); ?>">

                <label for="password">Kata sandi</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>

                <button class="button button-primary auth-submit" type="submit">Masuk</button>
            </form>

            <p class="auth-switch">Belum punya akun? <a href="register.php">Daftar sebagai Ibu</a></p>
            <?php if ($adminSetupAvailable): ?>
                <p class="auth-switch">Admin pertama? <a href="setup_admin.php">Buat akun Admin lokal</a></p>
            <?php endif; ?>
        </section>
    </main>
    <script src="assets/js/app.js"></script>
</body>
</html>
