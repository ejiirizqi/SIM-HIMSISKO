<?php
declare(strict_types=1);

require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../helpers/activity_log.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: ' . url('login.php'));
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    $user = current_user();
    if (!$user || ($user['role'] ?? '') !== $role) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function session_user_from_row(array $row): array
{
    return [
        'id' => (int)$row['id'],
        'username' => (string)$row['username'],
        'role' => (string)$row['role'],
        'nama_lengkap' => isset($row['nama_lengkap']) ? (string)$row['nama_lengkap'] : null,
        'email' => isset($row['email']) ? (string)$row['email'] : null,
        'foto_profil' => !empty($row['foto_profil']) ? (string)$row['foto_profil'] : null,
    ];
}

function refresh_session_user(int $userId): void
{
    $stmt = db()->prepare('SELECT id, username, role, nama_lengkap, email, foto_profil FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        session_regenerate_id(true);
        $_SESSION['user'] = session_user_from_row($row);
    }
}

/**
 * @param string|null $blockReason Set ke 'pending', 'rejected', atau null jika gagal login biasa
 */
function login_user(string $username, string $password, ?string &$blockReason = null): bool
{
    $blockReason = null;
    $stmt = db()->prepare(
        'SELECT id, username, password, role, nama_lengkap, email, foto_profil, approval_status FROM users WHERE username = ? LIMIT 1'
    );
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }
    if (!password_verify($password, (string)$row['password'])) {
        return false;
    }

    if (($row['role'] ?? '') === 'mahasiswa') {
        $st = (string)($row['approval_status'] ?? 'active');
        if ($st === 'pending') {
            $blockReason = 'pending';
            return false;
        }
        if ($st === 'rejected') {
            $blockReason = 'rejected';
            return false;
        }
    }

    session_regenerate_id(true);
    $_SESSION['user'] = session_user_from_row($row);
    log_activity((int)$row['id'], (string)$row['role'], (string)$row['username'], 'Login berhasil', null);
    return true;
}

function logout_user(): void
{
    $user = current_user();
    if ($user) {
        log_activity((int)($user['id'] ?? null), (string)($user['role'] ?? 'mahasiswa'), (string)($user['username'] ?? ''), 'Logout', null);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}
