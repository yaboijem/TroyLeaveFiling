<?php
declare(strict_types=1);

/**
 * Seed the branch, one profile per Troy employee and the settings row.
 * Safe to run repeatedly; existing profiles (and their work arrangements)
 * are never overwritten.
 */
function portal_seed(mysqli $db, array $config): array
{
    $branch = $config['branch'];
    $now = portal_now()->format('Y-m-d H:i:s');

    $stmt = $db->prepare(
        'INSERT INTO branches
            (name, code, latitude, longitude, radius_meters, accuracy_ceiling_meters,
             accuracy_allowance_meters, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            latitude = VALUES(latitude),
            longitude = VALUES(longitude),
            radius_meters = VALUES(radius_meters),
            accuracy_ceiling_meters = VALUES(accuracy_ceiling_meters),
            accuracy_allowance_meters = VALUES(accuracy_allowance_meters),
            updated_at = VALUES(updated_at)'
    );

    $name = (string) $branch['name'];
    $code = (string) $branch['code'];
    $latitude = (float) $branch['latitude'];
    $longitude = (float) $branch['longitude'];
    $radius = (int) $branch['radius_meters'];
    $ceiling = (int) $branch['accuracy_ceiling_meters'];
    $allowance = (float) $branch['accuracy_allowance_meters'];

    $stmt->bind_param('ssddiidss', $name, $code, $latitude, $longitude, $radius, $ceiling, $allowance, $now, $now);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare('SELECT id FROM branches WHERE code = ? LIMIT 1');
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($row)) {
        throw new RuntimeException('Seed could not read back the branch: ' . $code);
    }

    $branchId = (int) $row['id'];

    $stmt = $db->prepare(
        "INSERT IGNORE INTO dtr_employee_profile
            (idno, branch_id, work_arrangement, created_at, updated_at)
         SELECT idno, ?, 'onsite', ?, ?
           FROM employeefiletbl"
    );
    $stmt->bind_param('iss', $branchId, $now, $now);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare(
        'INSERT IGNORE INTO app_settings (id, breaks_enabled, updated_at) VALUES (1, 1, ?)'
    );
    $stmt->bind_param('s', $now);
    $stmt->execute();
    $stmt->close();

    $profiles = (int) $db->query('SELECT COUNT(*) AS c FROM dtr_employee_profile')->fetch_assoc()['c'];

    return ['branch_id' => $branchId, 'profiles' => $profiles];
}
