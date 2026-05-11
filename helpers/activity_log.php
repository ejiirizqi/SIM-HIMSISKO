<?php
declare(strict_types=1);

function log_activity(?int $userId, string $userRole, string $username, string $action, ?string $details = null): void
{
    $stmt = db()->prepare(
        'INSERT INTO activity_logs (user_id, user_role, username, action, details) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $userRole,
        $username,
        $action,
        $details,
    ]);
}

function fetch_activity_logs(int $limit = 200): array
{
    $limit = max(1, min(1000, $limit));
    $stmt = db()->prepare(
        'SELECT id, user_id, user_role, username, action, details, created_at FROM activity_logs ORDER BY created_at DESC, id DESC LIMIT ?'
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}
