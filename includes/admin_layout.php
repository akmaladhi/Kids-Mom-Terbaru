<?php

declare(strict_types=1);

function render_admin_sidebar_start(string $activeKey, string $adminName, string $basePath = ''): void
{
    $menuGroups = [
        'Utama' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => $basePath . 'dashboard.php'],
            ['key' => 'laporan', 'label' => 'Laporan data', 'href' => $basePath . 'admin/laporan.php'],
        ],
        'Data keluarga' => [
            ['key' => 'ibu', 'label' => 'Daftar ibu', 'href' => $basePath . 'admin/ibu.php'],
            ['key' => 'anak', 'label' => 'Data anak', 'href' => $basePath . 'anak.php'],
            ['key' => 'cek_gizi', 'label' => 'Cek gizi', 'href' => $basePath . 'cek_gizi.php'],
        ],
        'Layanan & konten' => [
            ['key' => 'konsultasi', 'label' => 'Konsultasi masuk', 'href' => $basePath . 'admin/konsultasi.php'],
            ['key' => 'posyandu', 'label' => 'Kelola Posyandu', 'href' => $basePath . 'admin/posyandu.php'],
            ['key' => 'artikel', 'label' => 'Kelola artikel', 'href' => $basePath . 'admin/artikel.php'],
            ['key' => 'resep', 'label' => 'Kelola resep', 'href' => $basePath . 'admin/resep.php'],
        ],
    ];
    $logoSrc = $basePath . 'assets/img/LOGO%20Mom%26Kids%2B-3.png';
    $homeHref = $basePath . 'dashboard.php';
    $publicHref = $basePath . 'index.php';
    $logoutHref = $basePath . 'logout.php';
    ?>
    <div class="admin-shell">
        <div class="admin-mobile-bar">
            <a class="brand" href="<?php echo escape_html($homeHref); ?>">
                <img class="brand-logo" src="<?php echo escape_html($logoSrc); ?>" alt="Mom&amp;Kids+">
            </a>
            <button class="menu-toggle admin-sidebar-toggle" type="button" aria-expanded="false" aria-controls="admin-sidebar">
                <span class="sr-only">Buka menu admin</span>
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <div class="admin-sidebar-backdrop" data-sidebar-close></div>

        <aside class="admin-sidebar" id="admin-sidebar" aria-label="Navigasi Admin">
            <div class="admin-sidebar-header">
                <a class="brand" href="<?php echo escape_html($homeHref); ?>">
                    <img class="brand-logo" src="<?php echo escape_html($logoSrc); ?>" alt="Mom&amp;Kids+">
                </a>
                <div class="admin-user-pill">
                    <span class="admin-role-tag">Admin / Bidan</span>
                    <strong><?php echo escape_html($adminName); ?></strong>
                </div>
            </div>

            <nav class="admin-sidebar-nav">
                <?php foreach ($menuGroups as $groupLabel => $items): ?>
                    <div class="admin-nav-group">
                        <span class="admin-nav-label"><?php echo escape_html($groupLabel); ?></span>
                        <?php foreach ($items as $item): ?>
                            <a class="admin-nav-link<?php echo $activeKey === $item['key'] ? ' is-active' : ''; ?>"
                                href="<?php echo escape_html($item['href']); ?>"
                                <?php echo $activeKey === $item['key'] ? 'aria-current="page"' : ''; ?>>
                                <?php echo escape_html($item['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </nav>

            <div class="admin-sidebar-footer">
                <a class="button button-small button-outline admin-sidebar-btn" href="<?php echo escape_html($publicHref); ?>">
                    Lihat beranda
                </a>
                <form method="post" action="<?php echo escape_html($logoutHref); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
                    <button class="button button-small button-outline admin-sidebar-btn" type="submit">Keluar</button>
                </form>
            </div>
        </aside>

        <div class="admin-workspace">
    <?php
}

function render_admin_sidebar_end(): void
{
    ?>
        </div>
    </div>
    <?php
}
