<?php

declare(strict_types=1);

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

function db(): PDO
{
    static $connection = null;

    if ($connection === null) {
        $connection = require __DIR__ . '/../config/database.php';
    }

    return $connection;
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function is_valid_csrf_token($submittedToken): bool
{
    return is_string($submittedToken)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submittedToken);
}

function redirect_to(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $statement = db()->prepare(
        'SELECT id, nama, email, role FROM users WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => (int) $_SESSION['user_id']]);
    $user = $statement->fetch();

    if (!$user) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }

    return $user;
}

function home_url_for_role(?string $role): string
{
    return $role === 'admin' ? 'dashboard.php' : 'index.php';
}

function redirect_if_authenticated(): void
{
    $user = current_user();
    if ($user !== null) {
        redirect_to(home_url_for_role($user['role']));
    }
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        $scriptName = is_string($_SERVER['SCRIPT_NAME'] ?? null)
            ? $_SERVER['SCRIPT_NAME']
            : '';
        $loginPath = str_contains($scriptName, '/admin/')
            ? '../login.php'
            : 'login.php';
        redirect_to($loginPath);
    }
}

function is_localhost_request(): bool
{
    $remoteAddress = is_string($_SERVER['REMOTE_ADDR'] ?? null)
        ? $_SERVER['REMOTE_ADDR']
        : '';

    return in_array($remoteAddress, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true);
}

function is_valid_non_future_date(string $value, int $minYear = 1900): bool
{
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) !== 1) {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $dateErrors = DateTimeImmutable::getLastErrors();

    return $date !== false
        && ($dateErrors === false
            || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        && $date->format('Y-m-d') === $value
        && (int) $date->format('Y') >= $minYear
        && $date <= new DateTimeImmutable('today');
}
