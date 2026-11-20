<?php
declare(strict_types=1);

function portal_history_day_dto(array $header, array $punches, array $flags, ?array $branch, ?array $photoPunch): array
{
    $in = null;
    $breaks = [];
    $pending = null;
    $correction = null;
    foreach ($punches as $punch) {
        if ($punch['type'] === 'time_out' && $punch['source'] === 'admin') {
            $correction = ['source' => 'admin', 'notes' => $punch['notes'], 'timestamp' => portal_iso($punch['punched_at'])];
        }
        if ($punch['type'] === 'time_in' && $in === null) $in = $punch;
        if ($punch['type'] === 'break_in') {
            $breaks[] = [
                'kind' => $punch['break_kind'], 'break_in' => portal_iso($punch['punched_at']),
                'break_out' => null, 'minutes' => null, 'is_overbreak' => false,
            ];
            $pending = count($breaks) - 1;
        } elseif ($punch['type'] === 'break_out' && $pending !== null) {
            $breaks[$pending]['break_out'] = portal_iso($punch['punched_at']);
            $breaks[$pending]['minutes'] = isset($punch['break_minutes']) ? (int) $punch['break_minutes'] : null;
            $breaks[$pending]['is_overbreak'] = (bool) $punch['is_overbreak'];
            $pending = null;
        }
    }
    return [
        'id' => (int) $header['id'], 'work_date' => $header['work_date'],
        'time_in' => portal_iso($header['time_in']), 'time_out' => portal_iso($header['time_out']),
        'break_minutes' => (int) $header['break_minutes'],
        'work_minutes' => isset($header['work_minutes']) ? (int) $header['work_minutes'] : null,
        'is_late' => (bool) $header['is_late'], 'is_early_timeout' => (bool) $header['is_early_timeout'],
        'is_overbreak' => (bool) $header['is_overbreak'],
        'branch' => $branch,
        'gps_location' => $in === null ? null : [
            'distance_from_branch_meters' => isset($in['distance_from_branch_meters']) ? (float) $in['distance_from_branch_meters'] : null,
            'is_within_radius' => isset($in['is_within_radius']) ? (bool) $in['is_within_radius'] : null,
            'verified_against_type' => $in['verified_against_type'],
        ],
        'photo' => $photoPunch === null ? null : ['path' => portal_photo_url($photoPunch['uuid'])],
        'fraud_flags' => $flags, 'breaks' => $breaks, 'correction' => $correction,
    ];
}

function portal_history_page(mysqli $db, string $idno, ?string $from, ?string $to, int $page, int $perPage = 20): array
{
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $where = 'a.employee_id=?';
    $values = [$idno];
    $types = 's';
    if ($from !== null) { $where .= ' AND a.work_date>=?'; $values[] = $from; $types .= 's'; }
    if ($to !== null) { $where .= ' AND a.work_date<=?'; $values[] = $to; $types .= 's'; }
    $stmt = $db->prepare("SELECT COUNT(*) n FROM attendance a WHERE {$where}");
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['n'];
    $stmt->close();
    $offset = ($page - 1) * $perPage;
    $stmt = $db->prepare("SELECT a.*,b.name branch_name FROM attendance a LEFT JOIN branches b ON b.id=a.branch_id WHERE {$where} ORDER BY a.time_in DESC,a.id DESC LIMIT ? OFFSET ?");
    $pageValues = [...$values, $perPage, $offset];
    $stmt->bind_param($types . 'ii', ...$pageValues);
    $stmt->execute();
    $headers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $punchMap = [];
    $flagMap = [];
    if ($headers !== []) {
        $ids = implode(',', array_map(static fn (array $row): int => (int) $row['id'], $headers));
        foreach ($db->query("SELECT * FROM attendance_punches WHERE attendance_id IN ({$ids}) ORDER BY punched_at,id")->fetch_all(MYSQLI_ASSOC) as $punch) {
            $punchMap[$punch['attendance_id']][] = $punch;
        }
        foreach ($db->query("SELECT attendance_id,type,severity FROM fraud_flags WHERE attendance_id IN ({$ids}) ORDER BY id")->fetch_all(MYSQLI_ASSOC) as $flag) {
            $flagMap[$flag['attendance_id']][] = ['type' => $flag['type'], 'severity' => $flag['severity']];
        }
    }
    $data = [];
    foreach ($headers as $header) {
        $punches = $punchMap[$header['id']] ?? [];
        $photo = null;
        foreach ($punches as $punch) {
            if ($punch['type'] === 'time_in' && !empty($punch['photo_path'])) { $photo = $punch; break; }
        }
        $branch = $header['branch_id'] !== null && $header['branch_name'] !== null
            ? ['id' => (int) $header['branch_id'], 'name' => $header['branch_name']] : null;
        $data[] = portal_history_day_dto($header, $punches, $flagMap[$header['id']] ?? [], $branch, $photo);
    }
    $last = max(1, (int) ceil($total / $perPage));
    $url = static fn (int $number): string => rtrim(portal_config()['public_base'], '/') . '/api/attendance/history?' . http_build_query(array_filter([
        'from' => $from, 'to' => $to, 'page' => $number, 'per_page' => $perPage,
    ], static fn ($v): bool => $v !== null));
    return [
        'data' => $data,
        'links' => ['first' => $url(1), 'last' => $url($last), 'prev' => $page > 1 ? $url($page - 1) : null, 'next' => $page < $last ? $url($page + 1) : null],
        'meta' => ['current_page' => $page, 'last_page' => $last, 'per_page' => $perPage, 'total' => $total],
    ];
}

function portal_handle_history_request(mysqli $db, string $idno, array $query): array
{
    return portal_history_page($db, $idno, $query['from'] ?? null, $query['to'] ?? null, (int) ($query['page'] ?? 1), (int) ($query['per_page'] ?? 20));
}
