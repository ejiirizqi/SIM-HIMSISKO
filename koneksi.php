<?php
declare(strict_types=1);

/**
 * Sesuaikan BASE_URL dengan path aplikasi Anda.
 * Kosongkan string jika aplikasi ada di root document (mis. http://localhost/)
 * atau isi nama folder di bawah htdocs, mis: '/sim-himsisko'
 */
if (!defined('BASE_URL')) {
    define('BASE_URL', '/sim-himsisko');
}

const DB_HOST = '127.0.0.1';
const DB_NAME = 'db_himsisko';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

function url(string $path = ''): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $base = rtrim(BASE_URL, '/');
    if ($base === '') {
        return '/' . $path;
    }
    return $base . '/' . $path;
}

/** URL publik untuk file dalam folder uploads (path relatif, mis. dok/abc.jpg). */
function url_upload(string $relative): string
{
    return url('uploads/' . ltrim(str_replace('\\', '/', $relative), '/'));
}

/** Logo branding HIMSISKO (PNG di folder assets). */
function brand_logo_url(): string
{
    return url('assets/logo-himsisko.png');
}

function project_root(): string
{
    return __DIR__;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}
