<?php
declare(strict_types=1);

/**
 * Admin home-location review list and detail. Admin and approver roles.
 */

function portal_admin_home_pin_dto(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'label' => $row['label'] !== null ? (string) $row['label'] : null,
        'latitude' => (float) $row['latitude'],
        'longitude' => (float) $row['longitude'],
        'radius_meters' => (int) $row['radius_meters'],
        'address_text' => $row['address_text'] !== null ? (string) $row['address_text'] : null,
        'street' => $row['street'] !== null ? (string) $row['street'] : null,
        'city' => $row['city'] !== null ? (string) $row['city'] : null,
        'province' => $row['province'] !== null ? (string) $row['province'] : null,
    ];
}

function portal_admin_home_location_row(mysqli $db, int $id): array
{
    $stmt = $db->prepare(
        'SELECT h.*, e.employeename, e.email, d.deptname,
                p.work_arrangement, p.branch_id,
                b.name AS branch_name, b.code AS branch_code
           FROM home_locations h
      LEFT JOIN employeefiletbl e
             ON e.idno COLLATE utf8mb4_general_ci = h.idno COLLATE utf8mb4_general_ci
      LEFT JOIN departmentfiletbl d ON d.deptcode = e.deptcode
      LEFT JOIN dtr_employee_profile p
             ON p.idno COLLATE utf8mb4_general_ci = h.idno COLLATE utf8mb4_general_ci
      LEFT JOIN branches b ON b.id = p.branch_id
          WHERE h.id = ?
          LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($row)) {
        throw new PortalApiException('Home location not found.', 'not_found', 404);
    }

    return $row;
}

function portal_admin_home_location_approved(mysqli $db, string $idno): ?array
{
    $stmt = $db->prepare(
        "SELECT * FROM home_locations
          WHERE idno = ? AND status = 'approved'
          ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return is_array($row) ? $row : null;
}

function portal_admin_home_location_dto(array $row, ?array $approved = null): array
{
    $branch = null;

    if ($row['branch_name'] !== null) {
        $branch = [
            'id' => (int) $row['branch_id'],
            'name' => (string) $row['branch_name'],
            'code' => (string) $row['branch_code'],
        ];
    }

    return [
        'id' => (int) $row['id'],
        'employee' => [
            'idno' => (string) $row['idno'],
            'name' => (string) ($row['employeename'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'department' => $row['deptname'] !== null ? (string) $row['deptname'] : null,
            'work_arrangement' => !empty($row['work_arrangement']) ? (string) $row['work_arrangement'] : 'onsite',
            'branch' => $branch,
        ],
        'label' => $row['label'] !== null ? (string) $row['label'] : null,
        'latitude' => (float) $row['latitude'],
        'longitude' => (float) $row['longitude'],
        'radius_meters' => (int) $row['radius_meters'],
        'address_text' => $row['address_text'] !== null ? (string) $row['address_text'] : null,
        'street' => $row['street'] !== null ? (string) $row['street'] : null,
        'city' => $row['city'] !== null ? (string) $row['city'] : null,
        'province' => $row['province'] !== null ? (string) $row['province'] : null,
        'status' => (string) $row['status'],
        'created_at' => portal_iso($row['created_at'] ?? null),
        'reviewed_by' => $row['reviewed_by'] !== null ? (string) $row['reviewed_by'] : null,
        'reviewed_at' => portal_iso($row['reviewed_at'] ?? null),
        'review_note' => $row['review_note'] !== null ? (string) $row['review_note'] : null,
        'current_approved' => $approved !== null ? portal_admin_home_pin_dto($approved) : null,
    ];
}

function portal_admin_home_location_counts(mysqli $db): array
{
    $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    $result = $db->query('SELECT status, COUNT(*) AS c FROM home_locations GROUP BY status');

    while ($row = $result->fetch_assoc()) {
        $status = (string) $row['status'];

        if (array_key_exists($status, $counts)) {
            $counts[$status] = (int) $row['c'];
        }
    }

    return $counts;
}

function portal_admin_home_locations_index(mysqli $db, array $auth, array $params): array
{
    portal_require_role($auth, ['admin', 'approver']);

    [$page, $perPage, $offset] = portal_admin_page($params, 20);

    $status = strtolower(trim((string) ($params['status'] ?? 'pending')));

    if (!in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
        $status = 'pending';
    }

    $where = [];
    $types = '';
    $values = [];

    if ($status !== 'all') {
        $where[] = 'h.status = ?';
        $types .= 's';
        $values[] = $status;
    }

    $search = trim((string) ($params['search'] ?? ''));

    if ($search !== '') {
        $like = '%' . addcslashes($search, '%_\\') . '%';
        $where[] = '(e.employeename LIKE ? OR e.email LIKE ? OR h.address_text LIKE ?
                     OR h.street LIKE ? OR h.city LIKE ? OR h.province LIKE ?)';
        $types .= 'ssssss';
        array_push($values, $like, $like, $like, $like, $like, $like);
    }

    $from = ' FROM home_locations h
              LEFT JOIN employeefiletbl e
                     ON e.idno COLLATE utf8mb4_general_ci = h.idno COLLATE utf8mb4_general_ci
              LEFT JOIN departmentfiletbl d ON d.deptcode = e.deptcode
              LEFT JOIN dtr_employee_profile p
                     ON p.idno COLLATE utf8mb4_general_ci = h.idno COLLATE utf8mb4_general_ci
              LEFT JOIN branches b ON b.id = p.branch_id';

    $whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';
    $order = $status === 'pending'
        ? 'h.created_at ASC, h.id ASC'
        : 'h.created_at DESC, h.id DESC';

    $countStmt = $db->prepare('SELECT COUNT(*) AS c' . $from . $whereSql);
    if ($types !== '') {
        $countStmt->bind_param($types, ...$values);
    }
    $countStmt->execute();
    $total = (int) ($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
    $countStmt->close();

    $sql = 'SELECT h.*, e.employeename, e.email, d.deptname,
                   p.work_arrangement, p.branch_id,
                   b.name AS branch_name, b.code AS branch_code'
        . $from
        . $whereSql
        . ' ORDER BY ' . $order . ' LIMIT ? OFFSET ?';

    $dataTypes = $types . 'ii';
    $dataValues = array_merge($values, [$perPage, $offset]);
    $stmt = $db->prepare($sql);
    $stmt->bind_param($dataTypes, ...$dataValues);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $lastPage = max(1, (int) ceil($total / $perPage));

    return [
        'data' => array_map('portal_admin_home_location_dto', $rows),
        'meta' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'from' => $total === 0 ? null : $offset + 1,
            'to' => $total === 0 ? null : min($offset + $perPage, $total),
            'counts' => portal_admin_home_location_counts($db),
        ],
    ];
}

function portal_admin_home_locations_show(mysqli $db, array $auth, int $id): array
{
    portal_require_role($auth, ['admin', 'approver']);

    $row = portal_admin_home_location_row($db, $id);
    $approved = portal_admin_home_location_approved($db, (string) $row['idno']);

    return ['data' => portal_admin_home_location_dto($row, $approved)];
}

function portal_admin_home_locations_review(mysqli $db, array $auth, int $id, array $input): array
{
    portal_require_role($auth, ['admin', 'approver']);

    $errors = [];
    $action = strtolower(trim((string) ($input['action'] ?? '')));

    if (!in_array($action, ['approve', 'reject'], true)) {
        $errors['action'][] = 'Choose approve or reject.';
    }

    $note = trim((string) ($input['note'] ?? ''));

    if ($action === 'reject' && $note === '') {
        $errors['note'][] = 'Enter a reason for rejecting this home location.';
    }

    if ($errors !== []) {
        throw new PortalApiException('The given data was invalid.', 'validation_error', 422, ['errors' => $errors]);
    }

    $row = portal_admin_home_location_row($db, $id);
    $reviewerIdno = trim((string) ($auth['idno'] ?? ''));

    if ($reviewerIdno !== '' && (string) $row['idno'] === $reviewerIdno) {
        throw new PortalApiException(
            'You cannot review your own home location. Ask another admin or approver.',
            'cannot_review_own_pin',
            403
        );
    }

    if ((string) $row['status'] !== 'pending') {
        throw new PortalApiException(
            'This request is no longer reviewable. Submit a new home location.',
            'invalid_action',
            422
        );
    }

    $reviewer = trim((string) ($auth['employeename'] ?? ''));

    if ($reviewer === '') {
        $reviewer = trim((string) ($auth['idno'] ?? 'HR'));
    }

    $reviewer = mb_substr($reviewer !== '' ? $reviewer : 'HR', 0, 100);

    portal_review_home_location($db, $id, $action, $note !== '' ? $note : null, $reviewer);

    return portal_admin_home_locations_show($db, $auth, $id);
}
