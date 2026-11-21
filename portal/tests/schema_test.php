<?php
declare(strict_types=1);

/**
 * Task 2 tests — schema shape, seed idempotency and fixtures.
 */

function schema_table_names(mysqli $db): array
{
    $rows = $db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM);

    return array_map(static fn (array $row): string => (string) $row[0], $rows);
}

function schema_column_names(mysqli $db, string $table): array
{
    $stmt = $db->prepare(
        'SELECT LOWER(column_name) AS name
           FROM information_schema.columns
          WHERE table_schema = DATABASE() AND table_name = ?'
    );
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(static fn (array $row): string => (string) $row['name'], $rows);
}

function schema_count(mysqli $db, string $sql): int
{
    $row = $db->query($sql)->fetch_assoc();

    return (int) ($row['c'] ?? 0);
}

return [
    'schema creates every table' => function (): void {
        $tables = schema_table_names(portal_test_db());
        $expected = [
            'attendance',
            'attendance_punches',
            'branches',
            'dtr_employee_profile',
            'app_settings',
            'consents',
            'home_locations',
            'portal_notifications',
            'push_subscriptions',
            'fraud_flags',
            'login_attempts',
        ];

        foreach ($expected as $table) {
            portal_assert_true(in_array($table, $tables, true), 'Missing table: ' . $table);
        }
    },

    'test schema provides the Troy tables' => function (): void {
        $tables = schema_table_names(portal_test_db());

        portal_assert_true(in_array('employeefiletbl', $tables, true), 'Missing employeefiletbl.');
        portal_assert_true(in_array('departmentfiletbl', $tables, true), 'Missing departmentfiletbl.');
    },

    'attendance has readable time in and time out columns' => function (): void {
        $columns = schema_column_names(portal_test_db(), 'attendance');

        portal_assert_true(in_array('time_in', $columns, true), 'Missing attendance.time_in.');
        portal_assert_true(in_array('time_out', $columns, true), 'Missing attendance.time_out.');
    },

    'attendance stores the shift branch' => function (): void {
        $columns = schema_column_names(portal_test_db(), 'attendance');

        portal_assert_true(in_array('branch_id', $columns, true), 'Missing attendance.branch_id.');
    },

    'attendance allows split shifts' => function (): void {
        $db = portal_test_db();
        $rows = $db->query(
            "SELECT index_name, non_unique,
                    GROUP_CONCAT(LOWER(column_name) ORDER BY seq_in_index) AS cols
               FROM information_schema.statistics
              WHERE table_schema = DATABASE() AND table_name = 'attendance'
              GROUP BY index_name, non_unique"
        )->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as $row) {
            $isUnique = (int) $row['non_unique'] === 0;
            if ($isUnique && $row['cols'] === 'idno,work_date') {
                throw new RuntimeException('attendance must not be unique per employee and work_date.');
            }
        }
    },

    'punch rows cascade with their header' => function (): void {
        $db = portal_test_db();
        $row = $db->query(
            "SELECT delete_rule
               FROM information_schema.referential_constraints
              WHERE constraint_schema = DATABASE()
                AND table_name = 'attendance_punches'
                AND referenced_table_name = 'attendance'
              LIMIT 1"
        )->fetch_assoc();

        portal_assert_same('CASCADE', strtoupper((string) ($row['delete_rule'] ?? '')));
    },

    'notification dedupe key is unique' => function (): void {
        $db = portal_test_db();
        $row = $db->query(
            "SELECT non_unique
               FROM information_schema.statistics
              WHERE table_schema = DATABASE()
                AND table_name = 'portal_notifications'
                AND index_name = 'uq_notif_dedupe'
              LIMIT 1"
        )->fetch_assoc();

        portal_assert_true($row !== null, 'Missing uq_notif_dedupe index.');
        portal_assert_same(0, (int) $row['non_unique'], 'uq_notif_dedupe must be unique.');
    },

    'seed is idempotent and preserves work arrangements' => function (): void {
        $db = portal_test_db();
        $config = portal_config();
        $now = portal_now()->format('Y-m-d H:i:s');

        $db->query('DELETE FROM dtr_employee_profile');
        $db->query('DELETE FROM branches');
        $db->query("DELETE FROM employeefiletbl WHERE idno NOT IN ('1015', '1019')");

        $stmt = $db->prepare(
            'INSERT INTO dtr_employee_profile (idno, branch_id, work_arrangement, created_at, updated_at)
             VALUES (?, NULL, ?, ?, ?)'
        );
        $idno = '1019';
        $arrangement = 'wfh';
        $stmt->bind_param('ssss', $idno, $arrangement, $now, $now);
        $stmt->execute();
        $stmt->close();

        portal_seed($db, $config);
        portal_seed($db, $config);

        portal_assert_same(1, schema_count($db, 'SELECT COUNT(*) AS c FROM branches'), 'branch count');
        portal_assert_same(2, schema_count($db, 'SELECT COUNT(*) AS c FROM dtr_employee_profile'), 'profile count');

        $profile = $db->query("SELECT work_arrangement FROM dtr_employee_profile WHERE idno = '1019'")->fetch_assoc();
        portal_assert_same('wfh', $profile['work_arrangement'] ?? null, 'existing arrangement must be preserved');

        portal_assert_same(1, schema_count($db, 'SELECT COUNT(*) AS c FROM app_settings'), 'settings row count');
        $settings = $db->query('SELECT breaks_enabled FROM app_settings WHERE id = 1')->fetch_assoc();
        portal_assert_same(1, (int) ($settings['breaks_enabled'] ?? 0), 'breaks_enabled');
    },

    'seed stores configured branch coordinates' => function (): void {
        $db = portal_test_db();
        $config = portal_config();

        portal_seed($db, $config);

        $stmt = $db->prepare('SELECT * FROM branches WHERE code = ? LIMIT 1');
        $code = (string) $config['branch']['code'];
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $branch = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        portal_assert_true(is_array($branch), 'Seed did not create the configured branch.');

        $expected = $config['branch'];
        portal_assert_same((float) $expected['latitude'], (float) $branch['latitude'], 'latitude');
        portal_assert_same((float) $expected['longitude'], (float) $branch['longitude'], 'longitude');
        portal_assert_same((int) $expected['radius_meters'], (int) $branch['radius_meters'], 'radius');
    },
];
