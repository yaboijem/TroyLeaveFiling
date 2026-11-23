<?php
declare(strict_types=1);

/**
 * Test bootstrap. Everything here is test-only.
 */

defined('PORTAL_TESTING') || define('PORTAL_TESTING', true);
defined('PORTAL_CONFIG_PATH') || define('PORTAL_CONFIG_PATH', __DIR__ . '/config.php');

require_once dirname(__DIR__) . '/lib/bootstrap.php';
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/seed.php';
require_once dirname(__DIR__) . '/lib/router.php';
require_once dirname(__DIR__) . '/lib/auth.php';
require_once dirname(__DIR__) . '/lib/user.php';
require_once dirname(__DIR__) . '/lib/login.php';
require_once dirname(__DIR__) . '/lib/gps.php';
require_once dirname(__DIR__) . '/lib/consent.php';
require_once dirname(__DIR__) . '/lib/settings.php';
require_once dirname(__DIR__) . '/lib/attendance.php';
require_once dirname(__DIR__) . '/lib/photos.php';
require_once dirname(__DIR__) . '/lib/fraud.php';
require_once dirname(__DIR__) . '/lib/retention.php';
require_once dirname(__DIR__) . '/lib/history.php';
require_once dirname(__DIR__) . '/lib/notifications.php';
require_once dirname(__DIR__) . '/lib/home_location.php';
require_once dirname(__DIR__) . '/lib/profile.php';
require_once dirname(__DIR__) . '/lib/admin_branches.php';
require_once dirname(__DIR__) . '/lib/admin_employees.php';
require_once dirname(__DIR__) . '/lib/push.php';

function portal_assert_same(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        $prefix = $message !== '' ? $message . ' — ' : '';
        throw new RuntimeException(
            $prefix . 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
        );
    }
}

function portal_assert_true(bool $value, string $message = ''): void
{
    if (!$value) {
        throw new RuntimeException($message !== '' ? $message : 'Expected true.');
    }
}

function portal_assert_null(mixed $value, string $message = ''): void
{
    if ($value !== null) {
        $prefix = $message !== '' ? $message . ' — ' : '';
        throw new RuntimeException($prefix . 'Expected null, got ' . var_export($value, true));
    }
}

function portal_assert_api_error(callable $fn, string $code, int $status): PortalApiException
{
    try {
        $fn();
    } catch (PortalApiException $e) {
        portal_assert_same($code, $e->errorCode, 'error code');
        portal_assert_same($status, $e->status, 'HTTP status');
        return $e;
    }

    throw new RuntimeException("Expected PortalApiException {$code} ({$status}).");
}

function portal_test_db(): mysqli
{
    static $db = null;

    if ($db instanceof mysqli) {
        return $db;
    }

    $config = portal_config()['db'];
    $name = (string) $config['name'];

    if (!str_ends_with($name, '_test')) {
        throw new RuntimeException('Test database name must end with _test: ' . $name);
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $server = new mysqli(
        (string) $config['host'],
        (string) $config['user'],
        (string) $config['pass'],
        null,
        (int) ($config['port'] ?? 3306)
    );
    $server->set_charset('utf8mb4');
    $server->query(
        'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $name)
        . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    $server->close();

    $db = portal_db_connect($config);

    foreach ([__DIR__ . '/troy_schema.sql', dirname(__DIR__) . '/sql/001_schema.sql', dirname(__DIR__) . '/sql/002_branch_admin.sql', dirname(__DIR__) . '/sql/003_push_outbox.sql'] as $schema) {
        if (!is_file($schema)) {
            continue;
        }

        $sql = file_get_contents($schema);

        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('Schema file is empty or unreadable: ' . $schema);
        }

        $db->multi_query($sql);
        while ($db->more_results()) {
            $db->next_result();
        }
    }

    return $db;
}

function portal_test_reset(): void
{
    $db = portal_test_db();

    $check = $db->query("SHOW TABLES LIKE 'attendance'");

    if ($check->num_rows === 0) {
        return;
    }

    static $passwordHash = null;

    if ($passwordHash === null) {
        $passwordHash = password_hash(portal_test_password(), PASSWORD_BCRYPT);
    }

    $now = portal_now()->format('Y-m-d H:i:s');

    $db->begin_transaction();

    try {
        foreach ([
            'push_outbox',
            'fraud_flags',
            'attendance_punches',
            'attendance',
            'portal_notifications',
            'push_subscriptions',
            'consents',
            'home_locations',
            'login_attempts',
            'dtr_employee_profile',
            'branches',
            'app_settings',
            'departmentfiletbl',
            'employeefiletbl',
        ] as $table) {
            $db->query('DELETE FROM `' . $table . '`');
        }

        $db->query("INSERT INTO departmentfiletbl (deptcode, deptname) VALUES
            ('OPS - SITE', 'OPERATIONS - PROJECT SITE'),
            ('ADMIN', 'ADMIN')");

        $employees = [
            ['1015', 'SICANGCO, JIRIEL L.', 'OPS - SITE', 'P2490', 'GENERAL FOREMAN', 'jiriellsicangco@gmail.com', 'YES', 'admin'],
            ['1016', 'SICANGCO, JIREL L.', 'ADMIN', 'HO', 'SUPERVISOR', 'sicangco_jiriel@yahoo.com', 'YES', 'approver'],
            ['1017', 'LANIE L. SICANGCO', 'ADMIN', 'HO', 'SUPERVISOR', 'lanielsicangco@gmail.com', 'YES', ''],
            ['1018', 'LIM, ANA', 'ADMIN', 'HO', 'STAFF', 'ana.lim@example.test', 'NO', ''],
            ['1019', 'GARCIA, LIZA', 'ADMIN', 'HO', 'STAFF', 'liza.garcia@example.test', 'YES', ''],
            ['1020', 'NEW, HIRE', 'ADMIN', 'HO', 'STAFF', 'new.hire@example.test', 'YES', ''],
        ];

        $stmt = $db->prepare(
            "INSERT INTO employeefiletbl
                (idno, employeename, deptcode, divcode, position, employmentstatus, email,
                 active, usertype, password, session_token, last_login, imagesrc)
             VALUES (?, ?, ?, ?, ?, 'Regular', ?, ?, ?, ?, NULL, NULL, '')"
        );

        foreach ($employees as [$idno, $name, $deptCode, $divCode, $position, $email, $active, $userType]) {
            $stmt->bind_param(
                'sssssssss',
                $idno,
                $name,
                $deptCode,
                $divCode,
                $position,
                $email,
                $active,
                $userType,
                $passwordHash
            );
            $stmt->execute();
        }

        $stmt->close();

        $branches = [
            [1, 'Main Office', 'MAIN', 14.5995, 120.9842, 200, 100, 20.0],
            [2, 'Annex', 'ANNEX', 14.6100, 121.0000, 200, 100, 20.0],
        ];

        $stmt = $db->prepare(
            'INSERT INTO branches
                (id, name, code, latitude, longitude, radius_meters,
                 accuracy_ceiling_meters, accuracy_allowance_meters, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($branches as [$id, $name, $code, $latitude, $longitude, $radius, $ceiling, $allowance]) {
            $stmt->bind_param(
                'issddiidss',
                $id,
                $name,
                $code,
                $latitude,
                $longitude,
                $radius,
                $ceiling,
                $allowance,
                $now,
                $now
            );
            $stmt->execute();
        }

        $stmt->close();

        $profiles = [
            '1015' => 'onsite',
            '1016' => 'onsite',
            '1017' => 'onsite',
            '1018' => 'onsite',
            '1019' => 'wfh',
        ];

        $stmt = $db->prepare(
            'INSERT INTO dtr_employee_profile (idno, branch_id, work_arrangement, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($profiles as $idno => $arrangement) {
            $branchId = 1;
            $stmt->bind_param('sisss', $idno, $branchId, $arrangement, $now, $now);
            $stmt->execute();
        }

        $stmt->close();

        $stmt = $db->prepare(
            'INSERT INTO consents (idno, type, granted, granted_at, revoked_at, updated_at)
             VALUES (?, ?, 1, ?, NULL, ?)'
        );

        foreach (['1015', '1016', '1017', '1019'] as $idno) {
            foreach (['biometric_photos', 'gps_location'] as $type) {
                $stmt->bind_param('ssss', $idno, $type, $now, $now);
                $stmt->execute();
            }
        }

        $stmt->close();

        $stmt = $db->prepare('INSERT INTO app_settings (id, breaks_enabled, updated_at) VALUES (1, 1, ?)');
        $stmt->bind_param('s', $now);
        $stmt->execute();
        $stmt->close();

        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function portal_test_employee(string $idno = '1015'): array
{
    $db = portal_test_db();
    $stmt = $db->prepare('SELECT * FROM employeefiletbl WHERE idno = ? LIMIT 1');
    $stmt->bind_param('s', $idno);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_array($row)) {
        throw new RuntimeException('Fixture employee not found: ' . $idno);
    }

    return $row;
}

function portal_test_password(): string
{
    return 'Password123!';
}
