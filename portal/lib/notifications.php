<?php
declare(strict_types=1);

function portal_notify(mysqli $db, string $idno, string $title, string $body, array $data = [], ?string $dedupeKey = null): ?int
{
    $json = json_encode($data, JSON_THROW_ON_ERROR);
    $now = portal_now()->format('Y-m-d H:i:s');
    $stmt = $db->prepare('INSERT IGNORE INTO portal_notifications (employee_id,title,body,data,dedupe_key,created_at) VALUES (?,?,?,?,?,?)');
    $stmt->bind_param('ssssss', $idno, $title, $body, $json, $dedupeKey, $now);
    $stmt->execute();
    $id = $stmt->affected_rows > 0 ? (int) $db->insert_id : null;
    $stmt->close();
    return $id;
}

function portal_notification_dto(array $row): array
{
    return [
        'id' => (string) $row['id'], 'title' => $row['title'], 'body' => $row['body'],
        'payload' => json_decode($row['data'] ?? '[]', true) ?: [],
        'read_at' => portal_iso($row['read_at']), 'created_at' => portal_iso($row['created_at']),
    ];
}

function portal_notifications_page(mysqli $db, string $idno, int $page, int $perPage = 20): array
{
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $stmt = $db->prepare('SELECT COUNT(*) n FROM portal_notifications WHERE employee_id=?');
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['n'];
    $stmt->close();
    $offset = ($page - 1) * $perPage;
    $stmt = $db->prepare('SELECT * FROM portal_notifications WHERE employee_id=? ORDER BY created_at DESC,id DESC LIMIT ? OFFSET ?');
    $stmt->bind_param('sii', $idno, $perPage, $offset);
    $stmt->execute();
    $data = array_map('portal_notification_dto', $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
    $last = max(1, (int) ceil($total / $perPage));
    $url = static fn (int $p): string => portal_config()['public_base'] . '/api/notifications?' . http_build_query(['page' => $p, 'per_page' => $perPage]);
    return [
        'data' => $data, 'links' => ['first' => $url(1), 'last' => $url($last), 'prev' => $page > 1 ? $url($page - 1) : null, 'next' => $page < $last ? $url($page + 1) : null],
        'meta' => ['total' => $total, 'current_page' => $page, 'last_page' => $last, 'per_page' => $perPage],
    ];
}

function portal_unread_count(mysqli $db, string $idno): int
{
    $stmt = $db->prepare('SELECT COUNT(*) n FROM portal_notifications WHERE employee_id=? AND read_at IS NULL');
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $count = (int) $stmt->get_result()->fetch_assoc()['n'];
    $stmt->close();
    return $count;
}

function portal_mark_read(mysqli $db, string $idno, string $id): ?array
{
    $now = portal_now()->format('Y-m-d H:i:s');
    $stmt = $db->prepare('UPDATE portal_notifications SET read_at=COALESCE(read_at,?) WHERE employee_id=? AND id=?');
    $stmt->bind_param('sss', $now, $idno, $id);
    $stmt->execute();
    $stmt->close();
    $stmt = $db->prepare('SELECT * FROM portal_notifications WHERE employee_id=? AND id=?');
    $stmt->bind_param('ss', $idno, $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? portal_notification_dto($row) : null;
}

function portal_mark_all_read(mysqli $db, string $idno): int
{
    $now = portal_now()->format('Y-m-d H:i:s');
    $stmt = $db->prepare('UPDATE portal_notifications SET read_at=? WHERE employee_id=? AND read_at IS NULL');
    $stmt->bind_param('ss', $now, $idno);
    $stmt->execute();
    $count = $stmt->affected_rows;
    $stmt->close();
    return $count;
}

function portal_delete_notification(mysqli $db, string $idno, string $id): bool
{
    $stmt = $db->prepare('DELETE FROM portal_notifications WHERE employee_id=? AND id=?');
    $stmt->bind_param('ss', $idno, $id);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();
    return $deleted;
}

function portal_delete_all(mysqli $db, string $idno): int
{
    $stmt = $db->prepare('DELETE FROM portal_notifications WHERE employee_id=?');
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $count = $stmt->affected_rows;
    $stmt->close();
    return $count;
}

function portal_pending_break_alerts(mysqli $db, string $idno, ?string $now = null): array
{
    $break = portal_open_break($db, $idno);
    if ($break === null) return [];
    $clock = $now === null ? portal_now() : new DateTimeImmutable($now, portal_tz());
    $nowSeconds = $clock->getTimestamp();
    $candidates = [];
    if ($break['expected_end_at'] !== null) {
        $due = (new DateTimeImmutable($break['expected_end_at'], portal_tz()))->getTimestamp();
        if ($nowSeconds >= $due - 120) $candidates["break-ending:{$break['id']}"] = 'Break ending soon';
        if ($nowSeconds >= $due + 300) $candidates["break-past-due:{$break['id']}"] = 'Break is past due';
    }
    $start = (new DateTimeImmutable($break['punched_at'], portal_tz()))->getTimestamp();
    if ($nowSeconds >= $start + 3600) $candidates["overbreak:{$break['id']}"] = 'Overbreak';
    $pending = [];
    foreach ($candidates as $key => $title) {
        $stmt = $db->prepare('SELECT id FROM portal_notifications WHERE dedupe_key=? LIMIT 1');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$exists) $pending[] = ['key' => $key, 'title' => $title, 'punch_id' => (int) $break['id']];
    }
    return $pending;
}

function portal_materialize_break_alerts(mysqli $db, string $idno, ?string $now = null): int
{
    $created = 0;
    foreach (portal_pending_break_alerts($db, $idno, $now) as $alert) {
        if (portal_notify($db, $idno, $alert['title'], 'Check your current break and return when ready.', ['punch_id' => $alert['punch_id']], $alert['key']) !== null) $created++;
    }
    return $created;
}

function portal_due_break_alerts(mysqli $db, ?string $now = null): array
{
    $employees = $db->query('SELECT DISTINCT employee_id FROM attendance WHERE time_out IS NULL')->fetch_all(MYSQLI_ASSOC);
    $due = [];
    foreach ($employees as $employee) {
        $idno = (string) $employee['employee_id'];
        if (portal_pending_break_alerts($db, $idno, $now) !== []) $due[] = $idno;
    }
    return $due;
}

function portal_handle_notification_unread(mysqli $db, ?array $auth, array $params): array
{
    $idno = (string) $auth['idno'];
    portal_materialize_break_alerts($db, $idno);
    return ['count' => portal_unread_count($db, $idno)];
}
