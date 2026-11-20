<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$recipeId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($recipeId === false || $recipeId < 1) {
    http_response_code(404);
    exit('Resep tidak ditemukan.');
}

$statement = db()->prepare(
    'SELECT judul, ringkasan, kategori, bahan, langkah, created_at
     FROM resep
     WHERE id = :id AND status = :status
     LIMIT 1'
);
$statement->execute(['id' => $recipeId, 'status' => 'published']);
$recipe = $statement->fetch();

if (!$recipe) {
    http_response_code(404);
    exit('Resep tidak ditemukan.');
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape_html($recipe['judul']); ?> | Mom&amp;Kids+</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">
    <header class="auth-header container">
        <a class="brand" href="index.php">
            <span class="brand-mark" aria-hidden="true">M</span>
            <span>Mom&amp;Kids<span class="brand-plus">+</span></span>
        </a>
        <a class="auth-back" href="resep.php">← Kembali ke katalog</a>
    </header>

    <main class="article-reader container recipe-reader">
        <article>
            <span class="recipe-category"><?php echo escape_html($recipe['kategori']); ?></span>
            <span class="article-date"><?php echo escape_html(date('d-m-Y', strtotime($recipe['created_at']))); ?></span>
            <h1><?php echo escape_html($recipe['judul']); ?></h1>
            <p class="article-lead"><?php echo escape_html($recipe['ringkasan']); ?></p>

            <section class="recipe-section">
                <h2>Bahan</h2>
                <div class="article-content"><?php echo nl2br(escape_html($recipe['bahan'])); ?></div>
            </section>

            <section class="recipe-section">
                <h2>Langkah pembuatan</h2>
                <div class="article-content"><?php echo nl2br(escape_html($recipe['langkah'])); ?></div>
            </section>

            <p class="recipe-note">
                Sesuaikan bahan dan tekstur makanan dengan kebutuhan keluarga. Untuk kondisi kesehatan
                khusus atau alergi, konsultasikan pilihan menu dengan tenaga kesehatan.
            </p>
        </article>
    </main>
</body>
</html>
