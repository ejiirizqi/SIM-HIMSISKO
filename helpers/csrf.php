<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function csrf_validate(?string $token): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!$token || !$sessionToken || !hash_equals((string)$sessionToken, (string)$token)) {
        http_response_code(419);
        echo 'CSRF token mismatch.';
        exit;
    }
}

