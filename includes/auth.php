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

function redirect_if_authenticated(): void
{
    if (isset($_SESSION['user_id'])) {
        redirect_to('dashboard.php');
    }
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        redirect_to('login.php');
    }
}

function is_valid_non_future_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $dateErrors = DateTimeImmutable::getLastErrors();

    return $date !== false
        && ($dateErrors === false
            || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        && $date->format('Y-m-d') === $value
        && $date <= new DateTimeImmutable('today');
}
