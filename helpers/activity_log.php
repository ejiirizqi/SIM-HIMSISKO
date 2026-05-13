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

/**
 * Fetch activity logs with optional filters.
 *
 * @return array<int, array<string, mixed>>
 */
function fetch_activity_logs_filtered(
    ?string $role = null,
    ?string $username = null,
    ?string $action = null,
    ?string $fromDate = null,
    ?string $toDate = null,
    int $limit = 250
): array {
    $limit = max(1, min(1000, $limit));

    $where = [];
    $params = [];

    if ($role !== null && $role !== '') {
        $where[] = 'user_role = ?';
        $params[] = $role;
    }

    if ($username !== null && $username !== '') {
        $where[] = 'username LIKE ?';
        $params[] = '%' . $username . '%';
    }

    if ($action !== null && $action !== '') {
        $where[] = 'action LIKE ?';
        $params[] = '%' . $action . '%';
    }

    if ($fromDate !== null && $fromDate !== '') {
        $where[] = 'created_at >= ?';
        $params[] = $fromDate . ' 00:00:00';
    }

    if ($toDate !== null && $toDate !== '') {
        $where[] = 'created_at <= ?';
        $params[] = $toDate . ' 23:59:59';
    }

    $sql = 'SELECT id, user_id, user_role, username, action, details, created_at FROM activity_logs';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ?';
    $params[] = $limit;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

