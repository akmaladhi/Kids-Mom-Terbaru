<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
redirect_if_authenticated();

$errors = [];
$nama = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
        $nama = trim(is_string($_POST['nama'] ?? null) ? $_POST['nama'] : '');
        $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $konfirmasiPassword = is_string($_POST['konfirmasi_password'] ?? null)
            ? $_POST['konfirmasi_password']
            : '';

        if ($nama === '' || strlen($nama) > 120) {
            $errors[] = 'Nama wajib diisi dan maksimal 120 karakter.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors[] = 'Masukkan alamat email yang valid.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Kata sandi harus terdiri dari minimal 8 karakter.';
        }

        if ($password !== $konfirmasiPassword) {
            $errors[] = 'Konfirmasi kata sandi tidak cocok.';
        }

        if (!$errors) {
            try {
                $pdo = db();
                $pdo->beginTransaction();

                $statement = $pdo->prepare(
                    'INSERT INTO users (nama, email, password_hash, role)
                     VALUES (:nama, :email, :password_hash, :role)'
                );
                $statement->execute([
                    'nama' => $nama,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'user',
                ]);

                $userId = (int) $pdo->lastInsertId();
                $profileStatement = $pdo->prepare(
                    'INSERT INTO profil_ibu (user_id) VALUES (:user_id)'
                );
                $profileStatement->execute(['user_id' => $userId]);

                $pdo->commit();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                redirect_to('dashboard.php');
            } catch (Throwable $exception) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if (
                    $exception instanceof PDOException
                    && isset($exception->errorInfo[1])
                    && (int) $exception->errorInfo[1] === 1062
                ) {
                    $errors[] = 'Email tersebut sudah terdaftar. Silakan masuk atau gunakan email lain.';
                } else {
                    throw $exception;
                }
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
    <title>Daftar | Mom&amp;Kids+</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">
    <header class="auth-header container">
        <a class="brand" href="index.php">
            <span class="brand-mark" aria-hidden="true">M</span>
            <span>Mom&amp;Kids<span class="brand-plus">+</span></span>
        </a>
        <a class="auth-back" href="index.php">← Kembali ke beranda</a>
    </header>

    <main class="auth-main">
        <section class="auth-card" aria-labelledby="register-title">
            <span class="eyebrow">Selamat datang di Mom&amp;Kids+</span>
            <h1 id="register-title">Buat akun ibu</h1>
            <p class="auth-intro">Mulai catat perjalanan tumbuh kembang keluarga Anda.</p>

            <?php if ($errors): ?>
                <div class="form-errors" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo escape_html($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">

                <label for="nama">Nama lengkap</label>
                <input id="nama" name="nama" type="text" maxlength="120" autocomplete="name" required
                    value="<?php echo escape_html($nama); ?>">

                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="190" autocomplete="email" required
                    value="<?php echo escape_html($email); ?>">

                <label for="password">Kata sandi</label>
                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>

                <label for="konfirmasi_password">Konfirmasi kata sandi</label>
                <input id="konfirmasi_password" name="konfirmasi_password" type="password" minlength="8"
                    autocomplete="new-password" required>

                <button class="button button-primary auth-submit" type="submit">Daftar sebagai Ibu</button>
            </form>

            <p class="auth-switch">Sudah punya akun? <a href="login.php">Masuk</a></p>
        </section>
    </main>
</body>
</html>
