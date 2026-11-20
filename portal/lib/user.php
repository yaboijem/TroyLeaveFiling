<?php
declare(strict_types=1);

/**
 * User DTO and home-location summary used by /api/me and later tasks.
 */

function portal_home_location_pin(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'label' => $row['label'] !== null ? (string) $row['label'] : null,
        'latitude' => (float) $row['latitude'],
        'longitude' => (float) $row['longitude'],
        'radius_meters' => (int) $row['radius_meters'],
        'status' => (string) $row['status'],
        'address_text' => $row['address_text'] ?? null,
        'street' => $row['street'] ?? null,
        'city' => $row['city'] ?? null,
        'province' => $row['province'] ?? null,
    ];
}

function portal_home_location_summary(mysqli $db, string $idno): array
{
    $stmt = $db->prepare('SELECT work_arrangement FROM dtr_employee_profile WHERE idno = ? LIMIT 1');
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $arrangement = is_array($profile) && !empty($profile['work_arrangement'])
        ? (string) $profile['work_arrangement']
        : 'onsite';

    $stmt = $db->prepare(
        'SELECT * FROM home_locations
          WHERE employee_id = ? AND status = ?
          ORDER BY id DESC LIMIT 1'
    );

    $approvedStatus = 'approved';
    $stmt->bind_param('ss', $idno, $approvedStatus);
    $stmt->execute();
    $approved = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $pendingStatus = 'pending';
    $stmt = $db->prepare(
        'SELECT * FROM home_locations
          WHERE employee_id = ? AND status = ?
          ORDER BY id DESC LIMIT 1'
    );
    $stmt->bind_param('ss', $idno, $pendingStatus);
    $stmt->execute();
    $pending = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (is_array($approved)) {
        return [
            'work_arrangement' => $arrangement,
            'status' => 'approved',
            'home_location' => portal_home_location_pin($approved),
            'pending' => is_array($pending) ? portal_home_location_pin($pending) : null,
        ];
    }

    if (is_array($pending)) {
        return [
            'work_arrangement' => $arrangement,
            'status' => 'pending',
            'home_location' => null,
            'pending' => portal_home_location_pin($pending),
        ];
    }

    return [
        'work_arrangement' => $arrangement,
        'status' => 'none',
        'home_location' => null,
        'pending' => null,
    ];
}

function portal_user_dto(mysqli $db, string $idno): ?array
{
    $stmt = $db->prepare(
        'SELECT e.idno, e.employeename, e.email, e.active, e.usertype, e.position,
                d.deptname,
                p.branch_id, p.work_arrangement,
                b.id AS branch_row_id, b.name AS branch_name, b.code AS branch_code,
                b.latitude AS branch_latitude, b.longitude AS branch_longitude,
                b.radius_meters AS branch_radius_meters
           FROM employeefiletbl e
      LEFT JOIN departmentfiletbl d ON d.deptcode = e.deptcode
      LEFT JOIN dtr_employee_profile p ON p.idno = e.idno
      LEFT JOIN branches b ON b.id = p.branch_id
          WHERE e.idno = ?
          LIMIT 1'
    );
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($row)) {
        return null;
    }

    $summary = portal_home_location_summary($db, $idno);
    $split = portal_split_name((string) $row['employeename']);
    $position = trim((string) ($row['position'] ?? ''));
    $department = $row['deptname'] !== null ? (string) $row['deptname'] : null;

    $branch = null;

    if ($row['branch_row_id'] !== null) {
        $branch = [
            'id' => (int) $row['branch_row_id'],
            'name' => (string) $row['branch_name'],
            'code' => (string) $row['branch_code'],
            'latitude' => (float) $row['branch_latitude'],
            'longitude' => (float) $row['branch_longitude'],
            'radius_meters' => (int) $row['branch_radius_meters'],
        ];
    }

    $employeeId = (string) $row['idno'];

    return [
        'id' => (int) $employeeId,
        'employee_id' => $employeeId,
        'name' => (string) $row['employeename'],
        'email' => (string) $row['email'],
        'is_active' => portal_active($row['active'] ?? null),
        'roles' => portal_roles((string) ($row['usertype'] ?? '')),
        'employee' => [
            'id' => (int) $employeeId,
            'first_name' => $split['first_name'],
            'middle_name' => null,
            'last_name' => $split['last_name'],
            'full_name' => (string) $row['employeename'],
            'department' => $department,
            'position' => $position !== '' ? $position : null,
            'date_hired' => null,
            'work_arrangement' => $summary['work_arrangement'],
            'home_location_status' => $summary['status'],
            'home_location' => $summary['home_location'],
            'branch' => $branch,
        ],
    ];
}
