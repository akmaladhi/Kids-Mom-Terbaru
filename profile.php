<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$statement = db()->prepare(
    'SELECT u.nama, u.email, u.role, p.usia_tahun, p.golongan_darah,
            p.usia_kehamilan_minggu, p.jumlah_anak
     FROM users u
     LEFT JOIN profil_ibu p ON p.user_id = u.id
     WHERE u.id = :id
     LIMIT 1'
);
$statement->execute(['id' => $userId]);
$profile = $statement->fetch();

if (!$profile) {
    $_SESSION = [];
    session_regenerate_id(true);
    redirect_to('login.php');
}

if ($profile['role'] !== 'user') {
    http_response_code(403);
    exit('Halaman ini hanya tersedia untuk akun Ibu.');
}

$form = [
    'nama' => $profile['nama'],
    'usia_tahun' => $profile['usia_tahun'] === null ? '' : (string) $profile['usia_tahun'],
    'golongan_darah' => $profile['golongan_darah'],
    'usia_kehamilan_minggu' => $profile['usia_kehamilan_minggu'] === null
        ? ''
        : (string) $profile['usia_kehamilan_minggu'],
    'jumlah_anak' => (string) $profile['jumlah_anak'],
];
$errors = [];
$success = isset($_SESSION['profile_saved']);
unset($_SESSION['profile_saved']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
        foreach (array_keys($form) as $field) {
            $form[$field] = trim(
                is_string($_POST[$field] ?? null) ? $_POST[$field] : ''
            );
        }

        $nameLength = preg_match_all('/./us', $form['nama']);
        if ($form['nama'] === '' || $nameLength === false || $nameLength > 120) {
            $errors[] = 'Nama wajib diisi dan maksimal 120 karakter.';
        }

        $allowedBloodTypes = ['A', 'B', 'AB', 'O', 'Tidak diketahui'];
        if (!in_array($form['golongan_darah'], $allowedBloodTypes, true)) {
            $errors[] = 'Pilih golongan darah yang tersedia.';
        }

        $usiaTahun = null;
        if ($form['usia_tahun'] !== '') {
            $usiaTahun = filter_var($form['usia_tahun'], FILTER_VALIDATE_INT);
            if ($usiaTahun === false || $usiaTahun < 1 || $usiaTahun > 120) {
                $errors[] = 'Usia ibu harus berupa angka antara 1 dan 120 tahun.';
            }
        }

        $usiaKehamilan = null;
        if ($form['usia_kehamilan_minggu'] !== '') {
            $usiaKehamilan = filter_var(
                $form['usia_kehamilan_minggu'],
                FILTER_VALIDATE_INT
            );
            if ($usiaKehamilan === false || $usiaKehamilan < 1 || $usiaKehamilan > 45) {
                $errors[] = 'Usia kehamilan harus berupa angka antara 1 dan 45 minggu.';
            }
        }

        $jumlahAnak = filter_var($form['jumlah_anak'], FILTER_VALIDATE_INT);
        if ($jumlahAnak === false || $jumlahAnak < 0 || $jumlahAnak > 99) {
            $errors[] = 'Jumlah anak harus berupa angka antara 0 dan 99.';
        }

        if (!$errors) {
            $pdo = db();
            $pdo->beginTransaction();

            try {
                $userStatement = $pdo->prepare(
                    'UPDATE users SET nama = :nama WHERE id = :id'
                );
                $userStatement->execute([
                    'nama' => $form['nama'],
                    'id' => $userId,
                ]);

                $profileStatement = $pdo->prepare(
                    'UPDATE profil_ibu
                     SET usia_tahun = :usia_tahun,
                         golongan_darah = :golongan_darah,
                         usia_kehamilan_minggu = :usia_kehamilan_minggu,
                         jumlah_anak = :jumlah_anak
                     WHERE user_id = :user_id'
                );
                $profileStatement->execute([
                    'usia_tahun' => $usiaTahun,
                    'golongan_darah' => $form['golongan_darah'],
                    'usia_kehamilan_minggu' => $usiaKehamilan,
                    'jumlah_anak' => $jumlahAnak,
                    'user_id' => $userId,
                ]);

                $pdo->commit();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $exception;
            }

            $_SESSION['profile_saved'] = true;
            redirect_to('profile.php');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Ibu | Mom&amp;Kids+</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">
    <header class="auth-header container">
        <a class="brand" href="dashboard.php">
            <span class="brand-mark" aria-hidden="true">M</span>
            <span>Mom&amp;Kids<span class="brand-plus">+</span></span>
        </a>
        <div class="profile-header-actions">
            <a class="button button-small button-outline page-back" href="dashboard.php">
                <span aria-hidden="true">←</span> Kembali
            </a>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
                <button class="button button-small button-outline" type="submit">Keluar</button>
            </form>
        </div>
    </header>

    <main class="auth-main">
        <section class="auth-card" aria-labelledby="profile-title">
            <span class="eyebrow">Informasi keluarga</span>
            <h1 id="profile-title">Profil ibu</h1>
            <p class="auth-intro">Perbarui biodata dasar Anda. Email akun tidak dapat diubah di halaman ini.</p>

            <?php if ($success): ?>
                <div class="form-success" role="status">Profil berhasil disimpan.</div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="form-errors" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo escape_html($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="profile.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">

                <label for="nama">Nama lengkap</label>
                <input id="nama" name="nama" type="text" maxlength="120" autocomplete="name" required
                    value="<?php echo escape_html($form['nama']); ?>">

                <label for="email">Email akun</label>
                <input id="email" type="email" value="<?php echo escape_html($profile['email']); ?>" disabled>

                <label for="usia_tahun">Usia ibu (tahun)</label>
                <input id="usia_tahun" name="usia_tahun" type="number" min="1" max="120" inputmode="numeric"
                    value="<?php echo escape_html($form['usia_tahun']); ?>">

                <label for="golongan_darah">Golongan darah</label>
                <select id="golongan_darah" name="golongan_darah" required>
                    <?php foreach (['Tidak diketahui', 'A', 'B', 'AB', 'O'] as $bloodType): ?>
                        <option value="<?php echo escape_html($bloodType); ?>"
                            <?php echo $form['golongan_darah'] === $bloodType ? 'selected' : ''; ?>>
                            <?php echo escape_html($bloodType); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="usia_kehamilan_minggu">Usia kehamilan (minggu, opsional)</label>
                <input id="usia_kehamilan_minggu" name="usia_kehamilan_minggu" type="number"
                    min="1" max="45" inputmode="numeric"
                    value="<?php echo escape_html($form['usia_kehamilan_minggu']); ?>">

                <label for="jumlah_anak">Jumlah anak</label>
                <input id="jumlah_anak" name="jumlah_anak" type="number" min="0" max="99"
                    inputmode="numeric" required value="<?php echo escape_html($form['jumlah_anak']); ?>">

                <button class="button button-primary auth-submit" type="submit">Simpan profil</button>
            </form>
        </section>
    </main>
    <script src="assets/js/app.js"></script>
</body>
</html>
