<?php
declare(strict_types=1);

/**
 * Admin branch management. All entry points require an admin session row.
 */

function portal_admin_number(mixed $value): ?float
{
    if (is_int($value) || is_float($value)) {
        $number = (float) $value;
        return is_finite($number) ? $number : null;
    }

    if (is_string($value) && is_numeric(trim($value))) {
        $number = (float) trim($value);
        return is_finite($number) ? $number : null;
    }

    return null;
}

function portal_admin_integer(mixed $value): ?int
{
    if (is_int($value)) {
        return $value;
    }

    if (is_float($value) && floor($value) === $value) {
        return (int) $value;
    }

    if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
        return (int) trim($value);
    }

    return null;
}

function portal_admin_bool(mixed $value): ?bool
{
    if (is_bool($value)) {
        return $value;
    }

    if ($value === 1 || $value === '1' || $value === 'true' || $value === 'TRUE') {
        return true;
    }

    if ($value === 0 || $value === '0' || $value === 'false' || $value === 'FALSE') {
        return false;
    }

    return null;
}

function portal_admin_branch_row(mysqli $db, int $id): array
{
    $stmt = $db->prepare(
        'SELECT b.*,
                (SELECT COUNT(*) FROM dtr_employee_profile p WHERE p.branch_id = b.id) AS employee_count,
                (SELECT COUNT(*) FROM attendance a WHERE a.branch_id = b.id) AS attendance_count
           FROM branches b
          WHERE b.id = ?
          LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($row)) {
        throw new PortalApiException('Branch not found.', 'not_found', 404);
    }

    return $row;
}

function portal_admin_branch_dto(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'code' => (string) $row['code'],
        'address' => $row['address'] !== null ? (string) $row['address'] : null,
        'latitude' => (float) $row['latitude'],
        'longitude' => (float) $row['longitude'],
        'radius_meters' => (int) $row['radius_meters'],
        'accuracy_ceiling_meters' => (int) $row['accuracy_ceiling_meters'],
        'accuracy_allowance_meters' => (float) $row['accuracy_allowance_meters'],
        'is_active' => (int) ($row['is_active'] ?? 1) === 1,
        'employee_count' => (int) ($row['employee_count'] ?? 0),
        'attendance_count' => (int) ($row['attendance_count'] ?? 0),
        'created_at' => portal_iso($row['created_at'] ?? null),
        'updated_at' => portal_iso($row['updated_at'] ?? null),
    ];
}

function portal_admin_branch_usage(mysqli $db, int $id): array
{
    $stmt = $db->prepare(
        'SELECT
            (SELECT COUNT(*) FROM dtr_employee_profile WHERE branch_id = ?) AS employees,
            (SELECT COUNT(*) FROM attendance WHERE branch_id = ?) AS attendance'
    );
    $stmt->bind_param('ii', $id, $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return [
        'employees' => (int) ($row['employees'] ?? 0),
        'attendance' => (int) ($row['attendance'] ?? 0),
    ];
}

function portal_admin_branch_input(mysqli $db, array $input, ?array $existing = null): array
{
    $data = $existing !== null ? array_merge($existing, $input) : $input;
    $errors = [];

    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') {
        $errors['name'][] = 'Branch name is required.';
    } elseif (mb_strlen($name) > 255) {
        $errors['name'][] = 'Branch name is too long.';
    }

    $code = strtoupper(trim((string) ($data['code'] ?? '')));
    if ($code === '') {
        $errors['code'][] = 'Branch code is required.';
    } elseif (mb_strlen($code) > 50) {
        $errors['code'][] = 'Branch code is too long.';
    } else {
        $existingId = $existing !== null ? (int) $existing['id'] : 0;
        $stmt = $db->prepare('SELECT id FROM branches WHERE code = ? AND id <> ? LIMIT 1');
        $stmt->bind_param('si', $code, $existingId);
        $stmt->execute();
        $duplicate = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (is_array($duplicate)) {
            $errors['code'][] = 'This code is already used by another branch.';
        }
    }

    $address = trim((string) ($data['address'] ?? ''));
    if ($address === '') {
        $address = null;
    } elseif (mb_strlen($address) > 500) {
        $errors['address'][] = 'Address is too long.';
    }

    $latitude = portal_admin_number($data['latitude'] ?? null);
    if ($latitude === null || $latitude < -90 || $latitude > 90) {
        $errors['latitude'][] = 'Latitude must be between -90 and 90.';
    }

    $longitude = portal_admin_number($data['longitude'] ?? null);
    if ($longitude === null || $longitude < -180 || $longitude > 180) {
        $errors['longitude'][] = 'Longitude must be between -180 and 180.';
    }

    $radius = portal_admin_integer($data['radius_meters'] ?? null);
    if ($radius === null || $radius < 1 || $radius > 10000) {
        $errors['radius_meters'][] = 'Radius must be between 1 and 10000 meters.';
    }

    $ceiling = portal_admin_integer($data['accuracy_ceiling_meters'] ?? null);
    if ($ceiling === null || $ceiling < 1 || $ceiling > 10000) {
        $errors['accuracy_ceiling_meters'][] = 'Accuracy ceiling must be between 1 and 10000 meters.';
    }

    $allowance = portal_admin_number($data['accuracy_allowance_meters'] ?? null);
    if ($allowance === null || $allowance < 0 || $allowance > 10000) {
        $errors['accuracy_allowance_meters'][] = 'Accuracy allowance must be between 0 and 10000 meters.';
    }

    $isActive = true;
    if (array_key_exists('is_active', $data)) {
        $parsedActive = portal_admin_bool($data['is_active']);
        if ($parsedActive === null) {
            $errors['is_active'][] = 'Active must be true or false.';
        } else {
            $isActive = $parsedActive;
        }
    }

    if ($errors !== []) {
        throw new PortalApiException('The given data was invalid.', 'validation_error', 422, ['errors' => $errors]);
    }

    return [
        'name' => $name,
        'code' => $code,
        'address' => $address,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'radius_meters' => $radius,
        'accuracy_ceiling_meters' => $ceiling,
        'accuracy_allowance_meters' => $allowance,
        'is_active' => $isActive,
    ];
}

function portal_admin_branches_index(mysqli $db, array $auth, array $params): array
{
    portal_require_role($auth, ['admin']);

    $where = [];
    $types = '';
    $values = [];

    $status = strtolower(trim((string) ($params['status'] ?? 'all')));
    if ($status === 'active') {
        $where[] = 'b.is_active = 1';
    } elseif ($status === 'inactive') {
        $where[] = 'b.is_active = 0';
    }

    $search = trim((string) ($params['search'] ?? ''));
    if ($search !== '') {
        $like = '%' . addcslashes($search, '%_\\') . '%';
        $where[] = '(b.name LIKE ? OR b.code LIKE ? OR b.address LIKE ?)';
        $types .= 'sss';
        array_push($values, $like, $like, $like);
    }

    $sql = 'SELECT b.*,
                   (SELECT COUNT(*) FROM dtr_employee_profile p WHERE p.branch_id = b.id) AS employee_count,
                   (SELECT COUNT(*) FROM attendance a WHERE a.branch_id = b.id) AS attendance_count
              FROM branches b';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY b.name ASC, b.id ASC';

    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$values);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return ['data' => array_map('portal_admin_branch_dto', $rows)];
}

function portal_admin_branches_store(mysqli $db, array $auth, array $input): array
{
    portal_require_role($auth, ['admin']);
    $data = portal_admin_branch_input($db, $input);
    $now = portal_now()->format('Y-m-d H:i:s');
    $isActive = $data['is_active'] ? 1 : 0;

    $stmt = $db->prepare(
        'INSERT INTO branches
            (name, code, address, latitude, longitude, radius_meters,
             accuracy_ceiling_meters, accuracy_allowance_meters, is_active, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'sssddiidiss',
        $data['name'],
        $data['code'],
        $data['address'],
        $data['latitude'],
        $data['longitude'],
        $data['radius_meters'],
        $data['accuracy_ceiling_meters'],
        $data['accuracy_allowance_meters'],
        $isActive,
        $now,
        $now
    );
    $stmt->execute();
    $id = (int) $db->insert_id;
    $stmt->close();

    return ['data' => portal_admin_branch_dto(portal_admin_branch_row($db, $id))];
}

function portal_admin_branch_lock_row(mysqli $db, int $id): array
{
    $stmt = $db->prepare('SELECT * FROM branches WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($row)) {
        throw new PortalApiException('Branch not found.', 'not_found', 404);
    }

    return $row;
}

function portal_admin_branch_has_employees(int $employees): PortalApiException
{
    return new PortalApiException(
        'Move the employees assigned to this branch before deactivating it.',
        'branch_has_employees',
        409,
        ['employees' => $employees]
    );
}

function portal_admin_branches_update(mysqli $db, array $auth, int $id, array $input): array
{
    portal_require_role($auth, ['admin']);
    $now = portal_now()->format('Y-m-d H:i:s');

    $db->begin_transaction();

    try {
        $existing = portal_admin_branch_lock_row($db, $id);
        $data = portal_admin_branch_input($db, $input, $existing);

        if ((int) $existing['is_active'] === 1 && !$data['is_active']) {
            $usage = portal_admin_branch_usage($db, $id);

            if ($usage['employees'] > 0) {
                throw portal_admin_branch_has_employees($usage['employees']);
            }
        }

        $isActive = $data['is_active'] ? 1 : 0;

        $stmt = $db->prepare(
            'UPDATE branches
                SET name = ?, code = ?, address = ?, latitude = ?, longitude = ?,
                    radius_meters = ?, accuracy_ceiling_meters = ?, accuracy_allowance_meters = ?,
                    is_active = ?, updated_at = ?
              WHERE id = ?'
        );
        $stmt->bind_param(
            'sssddiidisi',
            $data['name'],
            $data['code'],
            $data['address'],
            $data['latitude'],
            $data['longitude'],
            $data['radius_meters'],
            $data['accuracy_ceiling_meters'],
            $data['accuracy_allowance_meters'],
            $isActive,
            $now,
            $id
        );
        $stmt->execute();
        $stmt->close();

        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }

    return ['data' => portal_admin_branch_dto(portal_admin_branch_row($db, $id))];
}

function portal_admin_branches_deactivate(mysqli $db, array $auth, int $id): array
{
    portal_require_role($auth, ['admin']);
    $now = portal_now()->format('Y-m-d H:i:s');

    $db->begin_transaction();

    try {
        portal_admin_branch_lock_row($db, $id);
        $usage = portal_admin_branch_usage($db, $id);

        if ($usage['employees'] > 0) {
            throw portal_admin_branch_has_employees($usage['employees']);
        }

        $stmt = $db->prepare('UPDATE branches SET is_active = 0, updated_at = ? WHERE id = ?');
        $stmt->bind_param('si', $now, $id);
        $stmt->execute();
        $stmt->close();

        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }

    return ['data' => portal_admin_branch_dto(portal_admin_branch_row($db, $id))];
}

function portal_admin_branches_activate(mysqli $db, array $auth, int $id): array
{
    portal_require_role($auth, ['admin']);
    $now = portal_now()->format('Y-m-d H:i:s');

    $db->begin_transaction();

    try {
        portal_admin_branch_lock_row($db, $id);

        $stmt = $db->prepare('UPDATE branches SET is_active = 1, updated_at = ? WHERE id = ?');
        $stmt->bind_param('si', $now, $id);
        $stmt->execute();
        $stmt->close();

        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }

    return ['data' => portal_admin_branch_dto(portal_admin_branch_row($db, $id))];
}

function portal_admin_branches_delete(mysqli $db, array $auth, int $id): array
{
    portal_require_role($auth, ['admin']);

    $db->begin_transaction();

    try {
        portal_admin_branch_lock_row($db, $id);
        $usage = portal_admin_branch_usage($db, $id);

        if ($usage['employees'] > 0 || $usage['attendance'] > 0) {
            throw new PortalApiException(
                'This branch has employees or attendance history. Deactivate it instead.',
                'branch_in_use',
                409,
                $usage
            );
        }

        $stmt = $db->prepare('DELETE FROM branches WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }

    return ['data' => ['deleted' => true]];
}
