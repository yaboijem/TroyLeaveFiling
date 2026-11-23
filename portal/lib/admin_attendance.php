<?php
declare(strict_types=1);

/**
 * Admin attendance review and the settings/departments it needs.
 *
 * The page mirrors the DTR attendance screen: one row per punch, newest
 * first, with employee, branch, GPS, selfie and fraud evidence.
 */

function portal_admin_attendance_date(string $value, bool $endOfDay = false): ?string
{
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, portal_tz());

    if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $value) {
        return null;
    }

    if ($endOfDay) {
        $date = $date->modify('+1 day');
    }

    return $date->format('Y-m-d H:i:s');
}

function portal_admin_attendance_flag_dto(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'type' => (string) $row['type'],
        'severity' => (string) $row['severity'],
        'status' => (string) $row['status'],
    ];
}

function portal_admin_attendance_dto(array $row, array $flags = []): array
{
    $latitude = isset($row['latitude']) && $row['latitude'] !== null ? (float) $row['latitude'] : null;
    $longitude = isset($row['longitude']) && $row['longitude'] !== null ? (float) $row['longitude'] : null;
    $gps = $latitude === null && $longitude === null ? null : [
        'latitude' => $latitude,
        'longitude' => $longitude,
        'accuracy_meters' => isset($row['gps_accuracy_meters']) && $row['gps_accuracy_meters'] !== null
            ? (float) $row['gps_accuracy_meters'] : null,
        'distance_from_branch_meters' => isset($row['distance_from_branch_meters']) && $row['distance_from_branch_meters'] !== null
            ? (float) $row['distance_from_branch_meters'] : null,
        'is_within_radius' => isset($row['is_within_radius']) && $row['is_within_radius'] !== null
            ? (bool) (int) $row['is_within_radius'] : null,
    ];

    $branch = isset($row['branch_id']) && $row['branch_id'] !== null ? [
        'id' => (int) $row['branch_id'],
        'code' => (string) ($row['branch_code'] ?? ''),
        'name' => (string) ($row['branch_name'] ?? ''),
    ] : null;

    $idno = (string) $row['idno'];
    $employeeName = trim((string) ($row['employeename'] ?? ''));
    $department = trim((string) ($row['deptname'] ?? ''));

    return [
        'id' => (int) $row['id'],
        'uuid' => (string) $row['uuid'],
        'type' => (string) $row['type'],
        'break_kind' => $row['break_kind'] !== null ? (string) $row['break_kind'] : null,
        'expected_end_at' => portal_iso($row['expected_end_at'] ?? null),
        'timestamp' => portal_iso($row['punched_at'] ?? null),
        'is_late' => (bool) (int) ($row['is_late'] ?? 0),
        'is_early_timeout' => (bool) (int) ($row['is_early_timeout'] ?? 0),
        'work_minutes' => isset($row['work_minutes']) && $row['work_minutes'] !== null
            ? (int) $row['work_minutes'] : null,
        'break_minutes' => isset($row['break_minutes']) && $row['break_minutes'] !== null
            ? (int) $row['break_minutes'] : null,
        'is_overbreak' => (bool) (int) ($row['is_overbreak'] ?? 0),
        'source' => (string) ($row['source'] ?? 'app'),
        'is_offline' => false,
        'notes' => $row['notes'] !== null && trim((string) $row['notes']) !== ''
            ? (string) $row['notes'] : null,
        'employee' => [
            'id' => $idno,
            'employee_id' => $idno,
            'name' => $employeeName !== '' ? $employeeName : $idno,
            'department' => $department !== '' ? $department : null,
            'position' => isset($row['position']) && trim((string) $row['position']) !== ''
                ? (string) $row['position'] : null,
        ],
        'branch' => $branch,
        'device' => null,
        'photo' => !empty($row['photo_path']) ? ['path' => (string) $row['photo_path']] : null,
        'gps_location' => $gps,
        'fraud_flags' => array_map('portal_admin_attendance_flag_dto', $flags),
        'created_at' => portal_iso($row['created_at'] ?? null),
    ];
}

function portal_admin_attendance_index(mysqli $db, array $auth, array $params): array
{
    portal_require_role($auth, ['admin', 'approver']);

    $where = [];
    $types = '';
    $values = [];

    $from = trim((string) ($params['date_from'] ?? ''));
    if ($from !== '') {
        $start = portal_admin_attendance_date($from);
        if ($start !== null) {
            $where[] = 'p.punched_at >= ?';
            $types .= 's';
            $values[] = $start;
        }
    }

    $to = trim((string) ($params['date_to'] ?? ''));
    if ($to !== '') {
        $end = portal_admin_attendance_date($to, true);
        if ($end !== null) {
            $where[] = 'p.punched_at < ?';
            $types .= 's';
            $values[] = $end;
        }
    }

    $type = strtolower(trim((string) ($params['type'] ?? '')));
    if (in_array($type, ['time_in', 'time_out', 'break_in', 'break_out'], true)) {
        $where[] = 'p.type = ?';
        $types .= 's';
        $values[] = $type;
    }

    $branchId = portal_admin_integer($params['branch_id'] ?? null);
    if ($branchId !== null && $branchId > 0) {
        $where[] = 'a.branch_id = ?';
        $types .= 'i';
        $values[] = $branchId;
    }

    $department = trim((string) ($params['department_id'] ?? ''));
    if ($department !== '') {
        $where[] = 'e.deptcode = ?';
        $types .= 's';
        $values[] = $department;
    }

    $employeeId = trim((string) ($params['employee_id'] ?? ''));
    if ($employeeId !== '') {
        $where[] = 'p.idno = ?';
        $types .= 's';
        $values[] = $employeeId;
    }

    $openFlags = portal_admin_bool($params['has_open_flags'] ?? null);
    if ($openFlags === true) {
        $where[] = "EXISTS (SELECT 1 FROM fraud_flags f WHERE f.punch_id = p.id AND f.status = 'open')";
    } elseif ($openFlags === false) {
        $where[] = "NOT EXISTS (SELECT 1 FROM fraud_flags f WHERE f.punch_id = p.id AND f.status = 'open')";
    }

    $search = trim((string) ($params['q'] ?? ''));
    if (mb_strlen($search) >= 3) {
        $like = '%' . addcslashes($search, '%_\\') . '%';
        $where[] = '(e.employeename LIKE ? OR e.idno LIKE ?)';
        $types .= 'ss';
        array_push($values, $like, $like);
    }

    $page = max(1, (int) ($params['page'] ?? 1));
    $perPage = (int) ($params['per_page'] ?? 10);
    if ($perPage < 1) {
        $perPage = 10;
    }
    $perPage = min(500, $perPage);
    $offset = ($page - 1) * $perPage;

    $fromSql = 'FROM attendance_punches p
        JOIN attendance a ON a.id = p.attendance_id
        LEFT JOIN employeefiletbl e ON e.idno = p.idno COLLATE utf8mb4_general_ci
        LEFT JOIN departmentfiletbl d ON d.deptcode = e.deptcode COLLATE utf8mb4_general_ci
        LEFT JOIN branches b ON b.id = a.branch_id';

    $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

    $countSql = 'SELECT COUNT(*) AS total ' . $fromSql . $whereSql;
    $stmt = $db->prepare($countSql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$values);
    }
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $sql = 'SELECT p.*, a.branch_id, a.work_minutes, a.is_late, a.is_early_timeout,
                   e.employeename, e.position, e.deptcode, d.deptname,
                   b.name AS branch_name, b.code AS branch_code '
        . $fromSql . $whereSql
        . ' ORDER BY p.punched_at DESC, p.id DESC LIMIT ? OFFSET ?';

    $pageValues = [...$values, $perPage, $offset];
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types . 'ii', ...$pageValues);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $flags = [];
    if ($rows !== []) {
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $flagRows = $db->query(
            'SELECT id, punch_id, type, severity, status
               FROM fraud_flags
              WHERE punch_id IN (' . implode(',', $ids) . ')
              ORDER BY id'
        )->fetch_all(MYSQLI_ASSOC);

        foreach ($flagRows as $flag) {
            $flags[(int) $flag['punch_id']][] = $flag;
        }
    }

    $data = array_map(
        static fn (array $row): array => portal_admin_attendance_dto($row, $flags[(int) $row['id']] ?? []),
        $rows
    );

    $lastPage = max(1, (int) ceil($total / $perPage));
    $count = count($rows);
    $firstRow = $total === 0 || $count === 0 ? null : $offset + 1;
    $lastRow = $count === 0 ? null : $offset + $count;

    return [
        'data' => $data,
        'meta' => [
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
            'from' => $firstRow,
            'to' => $lastRow,
        ],
    ];
}

function portal_admin_attendance_photo_response(mysqli $db, array $auth, int $punchId): array
{
    portal_require_role($auth, ['admin', 'approver']);

    $stmt = $db->prepare('SELECT photo_path FROM attendance_punches WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $punchId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($row)) {
        throw new PortalApiException('Attendance record not found.', 'not_found', 404);
    }

    $path = portal_private_photo_path($row['photo_path'] ?? null);
    if ($path === null) {
        throw new PortalApiException('No selfie captured for this record.', 'not_found', 404);
    }

    return ['path' => $path, 'mime' => 'image/jpeg', 'filename' => 'selfie_' . $punchId . '.jpg'];
}

function portal_handle_admin_attendance_photo(mysqli $db, array $auth, array $params): never
{
    $photo = portal_admin_attendance_photo_response($db, $auth, (int) $params['id']);

    session_write_close();
    header('Content-Type: ' . $photo['mime']);
    header('Content-Length: ' . filesize($photo['path']));
    header('Content-Disposition: inline; filename="' . $photo['filename'] . '"');
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    readfile($photo['path']);
    exit;
}

function portal_admin_departments_index(mysqli $db, array $auth, array $params): array
{
    portal_require_role($auth, ['admin', 'approver']);

    $rows = $db->query(
        'SELECT deptcode, deptname FROM departmentfiletbl ORDER BY deptname ASC, deptcode ASC'
    )->fetch_all(MYSQLI_ASSOC);

    return [
        'data' => array_map(
            static fn (array $row): array => [
                'id' => (string) $row['deptcode'],
                'name' => trim((string) ($row['deptname'] ?? '')) !== ''
                    ? (string) $row['deptname'] : (string) $row['deptcode'],
            ],
            $rows
        ),
    ];
}

function portal_admin_settings_show(mysqli $db, array $auth, array $params): array
{
    portal_require_role($auth, ['admin']);

    return ['data' => portal_settings($db)];
}

function portal_admin_settings_update(mysqli $db, array $auth, array $input): array
{
    portal_require_role($auth, ['admin']);

    $breaks = portal_admin_bool($input['breaks_enabled'] ?? null);

    if ($breaks === null) {
        throw new PortalApiException(
            'The given data was invalid.',
            'validation_error',
            422,
            ['errors' => ['breaks_enabled' => ['Breaks enabled must be true or false.']]]
        );
    }

    $now = portal_now()->format('Y-m-d H:i:s');
    $value = $breaks ? 1 : 0;
    $stmt = $db->prepare(
        'INSERT INTO app_settings (id, breaks_enabled, updated_at)
         VALUES (1, ?, ?)
         ON DUPLICATE KEY UPDATE breaks_enabled = VALUES(breaks_enabled), updated_at = VALUES(updated_at)'
    );
    $stmt->bind_param('is', $value, $now);
    $stmt->execute();
    $stmt->close();

    return ['data' => portal_settings($db)];
}
