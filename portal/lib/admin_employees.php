<?php
declare(strict_types=1);

/**
 * Admin employee assignment list. Bulk writes live in this file too.
 */

function portal_admin_page(array $params, int $defaultPerPage = 50): array
{
    $page = max(1, (int) ($params['page'] ?? 1));
    $perPage = (int) ($params['per_page'] ?? $defaultPerPage);

    if ($perPage < 1) {
        $perPage = $defaultPerPage;
    }

    $perPage = min(100, $perPage);

    return [$page, $perPage, ($page - 1) * $perPage];
}

function portal_admin_employee_dto(array $row): array
{
    $hasProfile = (int) ($row['has_profile'] ?? 0) === 1;
    $arrangement = $hasProfile && !empty($row['work_arrangement'])
        ? (string) $row['work_arrangement']
        : 'onsite';

    $branch = null;

    if ($row['branch_name'] !== null) {
        $branch = [
            'id' => (int) $row['branch_id_join'],
            'name' => (string) $row['branch_name'],
            'code' => (string) $row['branch_code'],
            'is_active' => (int) ($row['branch_is_active'] ?? 1) === 1,
        ];
    }

    $position = trim((string) ($row['position'] ?? ''));

    return [
        'idno' => (string) $row['idno'],
        'name' => (string) $row['employeename'],
        'email' => (string) $row['email'],
        'department' => $row['deptname'] !== null ? (string) $row['deptname'] : null,
        'position' => $position !== '' ? $position : null,
        'is_active' => portal_active($row['active'] ?? null),
        'has_profile' => $hasProfile,
        'branch' => $branch,
        'work_arrangement' => $arrangement,
        'home_location_status' => (string) ($row['home_location_status'] ?? 'none'),
    ];
}

function portal_admin_employees_index(mysqli $db, array $auth, array $params): array
{
    portal_require_role($auth, ['admin']);

    [$page, $perPage, $offset] = portal_admin_page($params);

    $where = [];
    $types = '';
    $values = [];

    $active = strtolower(trim((string) ($params['active'] ?? '1')));
    if (in_array($active, ['1', 'yes', 'true'], true)) {
        $where[] = "UPPER(e.active) IN ('YES', '1', 'TRUE')";
    } elseif (in_array($active, ['0', 'no', 'false'], true)) {
        $where[] = "UPPER(e.active) NOT IN ('YES', '1', 'TRUE')";
    }

    $search = trim((string) ($params['search'] ?? ''));
    if ($search !== '') {
        $like = '%' . addcslashes($search, '%_\\') . '%';
        $where[] = '(e.employeename LIKE ? OR e.email LIKE ? OR e.idno LIKE ?)';
        $types .= 'sss';
        array_push($values, $like, $like, $like);
    }

    $branch = trim((string) ($params['branch_id'] ?? ''));
    if ($branch !== '' && strtolower($branch) !== 'all') {
        if ($branch === 'unassigned' || $branch === '0') {
            $where[] = 'p.branch_id IS NULL';
        } elseif (ctype_digit($branch) && (int) $branch > 0) {
            $where[] = 'p.branch_id = ?';
            $types .= 'i';
            $values[] = (int) $branch;
        }
    }

    $arrangement = strtolower(trim((string) ($params['arrangement'] ?? '')));
    if (in_array($arrangement, ['onsite', 'wfh', 'hybrid'], true)) {
        if ($arrangement === 'onsite') {
            $where[] = "COALESCE(p.work_arrangement, 'onsite') = 'onsite'";
        } else {
            $where[] = 'p.work_arrangement = ?';
            $types .= 's';
            $values[] = $arrangement;
        }
    }

    $approvedExists = "EXISTS (SELECT 1 FROM home_locations h
        WHERE h.idno COLLATE utf8mb4_general_ci = e.idno COLLATE utf8mb4_general_ci
          AND h.status = 'approved')";
    $pendingExists = "EXISTS (SELECT 1 FROM home_locations h
        WHERE h.idno COLLATE utf8mb4_general_ci = e.idno COLLATE utf8mb4_general_ci
          AND h.status = 'pending')";

    $homeStatus = strtolower(trim((string) ($params['home_location_status'] ?? '')));
    if ($homeStatus === 'approved') {
        $where[] = $approvedExists;
    } elseif ($homeStatus === 'pending') {
        $where[] = '(' . $pendingExists . ' AND NOT ' . $approvedExists . ')';
    } elseif ($homeStatus === 'none') {
        $where[] = '(NOT ' . $approvedExists . ' AND NOT ' . $pendingExists . ')';
    }

    $from = ' FROM employeefiletbl e
              LEFT JOIN departmentfiletbl d ON d.deptcode = e.deptcode
              LEFT JOIN dtr_employee_profile p
                     ON p.idno COLLATE utf8mb4_general_ci = e.idno COLLATE utf8mb4_general_ci
              LEFT JOIN branches b ON b.id = p.branch_id';

    $whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $db->prepare('SELECT COUNT(*) AS c' . $from . $whereSql);
    if ($types !== '') {
        $countStmt->bind_param($types, ...$values);
    }
    $countStmt->execute();
    $total = (int) ($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
    $countStmt->close();

    $sql = 'SELECT e.idno, e.employeename, e.email, e.active, e.position, d.deptname,
                   p.branch_id, p.work_arrangement,
                   CASE WHEN p.idno IS NULL THEN 0 ELSE 1 END AS has_profile,
                   b.id AS branch_id_join, b.name AS branch_name,
                   b.code AS branch_code, b.is_active AS branch_is_active,
                   CASE
                       WHEN ' . $approvedExists . " THEN 'approved'
                       WHEN " . $pendingExists . " THEN 'pending'
                       ELSE 'none'
                   END AS home_location_status"
        . $from
        . $whereSql
        . ' ORDER BY e.employeename ASC, e.idno ASC LIMIT ? OFFSET ?';

    $dataTypes = $types . 'ii';
    $dataValues = array_merge($values, [$perPage, $offset]);
    $stmt = $db->prepare($sql);
    $stmt->bind_param($dataTypes, ...$dataValues);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $lastPage = max(1, (int) ceil($total / $perPage));

    return [
        'data' => array_map('portal_admin_employee_dto', $rows),
        'meta' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'from' => $total === 0 ? null : $offset + 1,
            'to' => $total === 0 ? null : min($offset + $perPage, $total),
        ],
    ];
}
