<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (!is_localhost_request()) {
    http_response_code(403);
    exit('Setup Admin hanya dapat dibuka dari komputer server (localhost).');
}

$pdo = db();
$adminExists = (int) $pdo->query(
    'SELECT COUNT(*) FROM users WHERE role = \'admin\''
)->fetchColumn() > 0;
$errors = [];
$form = [
    'nama' => '',
    'email' => '',
];
$created = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adminExists) {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
        $form['nama'] = trim(
            is_string($_POST['nama'] ?? null) ? $_POST['nama'] : ''
        );
        $form['email'] = trim(
            is_string($_POST['email'] ?? null) ? $_POST['email'] : ''
        );
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirmation = is_string($_POST['konfirmasi_password'] ?? null)
            ? $_POST['konfirmasi_password']
            : '';
        $nameLength = preg_match_all('/./us', $form['nama']);

        if ($form['nama'] === '' || $nameLength === false || $nameLength > 120) {
            $errors[] = 'Nama wajib diisi dan maksimal 120 karakter.';
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 190) {
            $errors[] = 'Masukkan alamat email yang valid.';
        }
        if (strlen($password) < 12) {
            $errors[] = 'Kata sandi Admin harus terdiri dari minimal 12 karakter.';
        }
        if ($password !== $confirmation) {
            $errors[] = 'Konfirmasi kata sandi tidak cocok.';
        }

        if (!$errors) {
            $lockName = 'momkids_first_admin_setup';
            $lockStatement = $pdo->prepare('SELECT GET_LOCK(:lock_name, 5)');
            $lockStatement->execute(['lock_name' => $lockName]);
            $lockAcquired = (int) $lockStatement->fetchColumn() === 1;

            if (!$lockAcquired) {
                $errors[] = 'Setup Admin sedang digunakan. Tunggu sebentar lalu coba kembali.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $adminExists = (int) $pdo->query(
                        'SELECT COUNT(*) FROM users WHERE role = \'admin\''
                    )->fetchColumn() > 0;

                    if ($adminExists) {
                        $pdo->rollBack();
                        http_response_code(409);
                        $errors[] = 'Admin pertama sudah dibuat. Setup ini telah dikunci.';
                    } else {
                        $insertStatement = $pdo->prepare(
                            'INSERT INTO users (nama, email, password_hash, role)
                             VALUES (:nama, :email, :password_hash, :role)'
                        );
                        $insertStatement->execute([
                            'nama' => $form['nama'],
                            'email' => $form['email'],
                            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                            'role' => 'admin',
                        ]);
                        $pdo->commit();
                        $created = true;
                    }
                } catch (PDOException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    if (
                        isset($exception->errorInfo[1])
                        && (int) $exception->errorInfo[1] === 1062
                    ) {
                        $errors[] = 'Email tersebut sudah digunakan. Gunakan email lain.';
                    } else {
                        throw $exception;
                    }
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    throw $exception;
                } finally {
                    $releaseStatement = $pdo->prepare(
                        'SELECT RELEASE_LOCK(:lock_name)'
                    );
                    $releaseStatement->execute(['lock_name' => $lockName]);
                }
            }
        }
    }

    if ($created) {
        $_SESSION['setup_admin_success'] = 'Akun Admin pertama berhasil dibuat. Silakan masuk.';
        redirect_to('login.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adminExists) {
    http_response_code(409);
    $errors[] = 'Admin pertama sudah dibuat. Setup ini telah dikunci.';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin Pertama | Mom&amp;Kids+</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">
    <header class="auth-header container">
        <a class="brand" href="index.php">
            <img class="brand-logo" src="assets/img/LOGO%20Mom%26Kids%2B-3.png" alt="Mom&amp;Kids+">
        </a>
        <a class="button button-small button-outline page-back" href="login.php">
            <span aria-hidden="true">←</span> Kembali
        </a>
    </header>

    <main class="auth-main">
        <section class="auth-card" aria-labelledby="setup-title">
            <span class="eyebrow">Pengaturan awal lokal</span>
            <h1 id="setup-title">Setup Admin pertama</h1>
            <?php if ($adminExists): ?>
                <p class="auth-intro">Akun Admin sudah tersedia. Halaman setup ini terkunci.</p>
                <a class="button button-primary auth-submit" href="login.php">Masuk sebagai Admin</a>
            <?php else: ?>
                <p class="auth-intro">
                    Buat akun Admin pertama untuk mengelola jadwal, konsultasi, artikel, resep, dan laporan.
                    Halaman ini hanya berfungsi dari localhost dan terkunci setelah Admin dibuat.
                </p>

                <?php if ($errors): ?>
                    <div class="form-errors" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo escape_html($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="setup_admin.php" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">

                    <label for="nama">Nama Admin</label>
                    <input id="nama" name="nama" type="text" maxlength="120" autocomplete="name" required
                        value="<?php echo escape_html($form['nama']); ?>">

                    <label for="email">Email Admin</label>
                    <input id="email" name="email" type="email" maxlength="190" autocomplete="email" required
                        value="<?php echo escape_html($form['email']); ?>">

                    <label for="password">Kata sandi (minimal 12 karakter)</label>
                    <input id="password" name="password" type="password" minlength="12"
                        autocomplete="new-password" required>

                    <label for="konfirmasi_password">Konfirmasi kata sandi</label>
                    <input id="konfirmasi_password" name="konfirmasi_password" type="password" minlength="12"
                        autocomplete="new-password" required>

                    <button class="button button-primary auth-submit" type="submit">Buat Admin pertama</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
    <script src="assets/js/app.js?v=2"></script>
</body>
</html>
