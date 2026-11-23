<?php
declare(strict_types=1);

function admin_attendance_auth(string $usertype = 'admin'): array
{
    return ['idno' => '1015', 'usertype' => $usertype, 'employeename' => 'SICANGCO, JIRIEL L.'];
}

function admin_attendance_seed(): void
{
    $db = portal_test_db();

    $db->query(
        "INSERT INTO attendance
            (id, idno, branch_id, work_date, time_in, time_out, break_minutes, work_minutes,
             is_late, is_early_timeout, is_overbreak, source, notes, created_at, updated_at)
         VALUES
            (1, '1015', 1, '2026-11-20', '2026-11-20 08:05:00', '2026-11-20 17:00:00', 30, 505,
             1, 0, 0, 'app', NULL, '2026-11-20 08:05:00', '2026-11-20 17:00:00'),
            (2, '1017', 2, '2026-11-19', '2026-11-19 09:00:00', NULL, 0, NULL,
             0, 0, 0, 'app', NULL, '2026-11-19 09:00:00', '2026-11-19 09:00:00')"
    );

    $db->query(
        "INSERT INTO attendance_punches
            (id, attendance_id, idno, uuid, type, punched_at, latitude, longitude,
             gps_accuracy_meters, distance_from_branch_meters, is_within_radius,
             source, notes, photo_path, break_kind, break_minutes, is_overbreak, created_at)
         VALUES
            (1, 1, '1015', 'aaaaaaaa-0000-4000-8000-000000000001', 'time_in', '2026-11-20 08:05:00',
             14.5995, 120.9842, 10.00, 0.00, 1, 'app', 'Morning punch', 'attendance/admin-test.jpg',
             NULL, NULL, 0, '2026-11-20 08:05:00'),
            (2, 1, '1015', 'aaaaaaaa-0000-4000-8000-000000000002', 'break_in', '2026-11-20 12:00:00',
             NULL, NULL, NULL, NULL, NULL, 'app', NULL, NULL, 'lunch_60', NULL, 0, '2026-11-20 12:00:00'),
            (3, 1, '1015', 'aaaaaaaa-0000-4000-8000-000000000003', 'break_out', '2026-11-20 12:30:00',
             NULL, NULL, NULL, NULL, NULL, 'app', NULL, NULL, 'lunch_60', 30, 0, '2026-11-20 12:30:00'),
            (4, 1, '1015', 'aaaaaaaa-0000-4000-8000-000000000004', 'time_out', '2026-11-20 17:00:00',
             NULL, NULL, NULL, NULL, NULL, 'admin', 'Closed by HR', NULL, NULL, NULL, 0, '2026-11-20 17:00:00'),
            (5, 2, '1017', 'aaaaaaaa-0000-4000-8000-000000000005', 'time_in', '2026-11-19 09:00:00',
             14.6100, 121.0000, 25.00, 120.00, 1, 'app', NULL, NULL, NULL, NULL, 0, '2026-11-19 09:00:00')"
    );

    $db->query(
        "INSERT INTO fraud_flags
            (id, attendance_id, punch_id, type, severity, details, status, created_at)
         VALUES
            (1, 1, 1, 'out_of_radius', 'medium', '{}', 'open', '2026-11-20 08:05:00'),
            (2, 2, 5, 'rapid_clock', 'high', '{}', 'reviewed', '2026-11-19 09:00:00')"
    );
}

function admin_attendance_photo_file(): string
{
    $directory = portal_config()['uploads'] . '/attendance';

    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the test photo directory.');
    }

    $path = $directory . '/admin-test.jpg';
    $image = imagecreatetruecolor(4, 4);
    imagejpeg($image, $path, 80);
    imagedestroy($image);

    return $path;
}

return [
    'attendance list returns punches newest first' => function (): void {
        admin_attendance_seed();

        $result = portal_admin_attendance_index(portal_test_db(), admin_attendance_auth(), []);

        portal_assert_same(5, $result['meta']['total'], 'total punches');
        portal_assert_same(1, $result['meta']['last_page'], 'single page');
        portal_assert_same(5, count($result['data']), 'page rows');
        portal_assert_same(4, $result['data'][0]['id'], 'newest punch');
        portal_assert_same('time_out', $result['data'][0]['type'], 'newest type');
        portal_assert_same('SICANGCO, JIRIEL L.', $result['data'][0]['employee']['name'], 'employee name');
        portal_assert_same('1015', $result['data'][0]['employee']['employee_id'], 'employee id');
        portal_assert_same('MAIN', $result['data'][0]['branch']['code'], 'branch code');
    },

    'attendance list defaults to ten rows per page' => function (): void {
        admin_attendance_seed();

        $result = portal_admin_attendance_index(portal_test_db(), admin_attendance_auth(), []);

        portal_assert_same(10, $result['meta']['per_page'], 'default per page');
        portal_assert_same(1, $result['meta']['last_page'], 'single page');
        portal_assert_same(1, $result['meta']['from'], 'first row');
        portal_assert_same(5, $result['meta']['to'], 'last row');
    },

    'attendance list filters by date range inclusive' => function (): void {
        admin_attendance_seed();
        $db = portal_test_db();

        $day = portal_admin_attendance_index($db, admin_attendance_auth(), [
            'date_from' => '2026-11-20',
            'date_to' => '2026-11-20',
        ]);
        portal_assert_same(4, $day['meta']['total'], 'one day');

        $range = portal_admin_attendance_index($db, admin_attendance_auth(), [
            'date_from' => '2026-11-19',
            'date_to' => '2026-11-20',
        ]);
        portal_assert_same(5, $range['meta']['total'], 'two days');
    },

    'attendance list filters by type' => function (): void {
        admin_attendance_seed();

        $result = portal_admin_attendance_index(portal_test_db(), admin_attendance_auth(), ['type' => 'time_in']);

        portal_assert_same(2, $result['meta']['total'], 'time in punches');
    },

    'attendance list filters by branch' => function (): void {
        admin_attendance_seed();
        $db = portal_test_db();

        $main = portal_admin_attendance_index($db, admin_attendance_auth(), ['branch_id' => '1']);
        portal_assert_same(4, $main['meta']['total'], 'main branch');

        $annex = portal_admin_attendance_index($db, admin_attendance_auth(), ['branch_id' => '2']);
        portal_assert_same(1, $annex['meta']['total'], 'annex branch');
    },

    'attendance list filters by department' => function (): void {
        admin_attendance_seed();
        $db = portal_test_db();

        $ops = portal_admin_attendance_index($db, admin_attendance_auth(), ['department_id' => 'OPS - SITE']);
        portal_assert_same(4, $ops['meta']['total'], 'operations department');

        $admin = portal_admin_attendance_index($db, admin_attendance_auth(), ['department_id' => 'ADMIN']);
        portal_assert_same(1, $admin['meta']['total'], 'admin department');
    },

    'attendance list searches name and employee id' => function (): void {
        admin_attendance_seed();
        $db = portal_test_db();

        $byName = portal_admin_attendance_index($db, admin_attendance_auth(), ['q' => 'jiriel']);
        portal_assert_same(4, $byName['meta']['total'], 'name search');

        $byId = portal_admin_attendance_index($db, admin_attendance_auth(), ['q' => '1017']);
        portal_assert_same(1, $byId['meta']['total'], 'id search');
    },

    'attendance search treats percent literally' => function (): void {
        admin_attendance_seed();

        $result = portal_admin_attendance_index(portal_test_db(), admin_attendance_auth(), ['q' => '%%%']);

        portal_assert_same(0, $result['meta']['total'], 'a percent query must not match everything');
    },

    'attendance list filters by open flags' => function (): void {
        admin_attendance_seed();
        $db = portal_test_db();

        $with = portal_admin_attendance_index($db, admin_attendance_auth(), ['has_open_flags' => '1']);
        portal_assert_same(1, $with['meta']['total'], 'with open flags');

        $without = portal_admin_attendance_index($db, admin_attendance_auth(), ['has_open_flags' => '0']);
        portal_assert_same(4, $without['meta']['total'], 'without open flags');
    },

    'attendance list filters by legacy employee id' => function (): void {
        admin_attendance_seed();

        $result = portal_admin_attendance_index(portal_test_db(), admin_attendance_auth(), ['employee_id' => '1017']);

        portal_assert_same(1, $result['meta']['total'], 'legacy employee filter');
    },

    'attendance list returns gps photo and flags' => function (): void {
        admin_attendance_seed();

        $result = portal_admin_attendance_index(portal_test_db(), admin_attendance_auth(), [
            'type' => 'time_in',
            'branch_id' => '1',
        ]);
        $punch = $result['data'][0];

        portal_assert_same(14.5995, $punch['gps_location']['latitude'], 'latitude');
        portal_assert_same(true, $punch['gps_location']['is_within_radius'], 'inside radius');
        portal_assert_same('out_of_radius', $punch['fraud_flags'][0]['type'], 'flag type');
        portal_assert_same('open', $punch['fraud_flags'][0]['status'], 'flag status');
        portal_assert_same('attendance/admin-test.jpg', $punch['photo']['path'], 'photo path');
        portal_assert_same(true, $punch['is_late'], 'late from the shift header');
    },

    'attendance list paginates' => function (): void {
        admin_attendance_seed();

        $result = portal_admin_attendance_index(portal_test_db(), admin_attendance_auth(), [
            'per_page' => '2',
            'page' => '2',
        ]);

        portal_assert_same(2, $result['meta']['current_page'], 'current page');
        portal_assert_same(3, $result['meta']['last_page'], 'last page');
        portal_assert_same(3, $result['meta']['from'], 'from');
        portal_assert_same(4, $result['meta']['to'], 'to');
        portal_assert_same(2, count($result['data']), 'row count');
    },

    'attendance list clamps per page' => function (): void {
        admin_attendance_seed();

        $result = portal_admin_attendance_index(portal_test_db(), admin_attendance_auth(), ['per_page' => '9999']);

        portal_assert_same(500, $result['meta']['per_page'], 'per page clamps to 500');
    },

    'approver can list attendance' => function (): void {
        admin_attendance_seed();

        $result = portal_admin_attendance_index(portal_test_db(), admin_attendance_auth('approver'), []);

        portal_assert_same(5, $result['meta']['total'], 'approver sees punches');
    },

    'employee cannot list attendance' => function (): void {
        portal_assert_api_error(
            fn () => portal_admin_attendance_index(portal_test_db(), admin_attendance_auth(''), []),
            'forbidden',
            403
        );
    },

    'attendance photo returns a private path' => function (): void {
        admin_attendance_seed();
        $file = admin_attendance_photo_file();

        try {
            $photo = portal_admin_attendance_photo_response(portal_test_db(), admin_attendance_auth('approver'), 1);

            portal_assert_same(
                str_replace('\\', '/', $file),
                str_replace('\\', '/', $photo['path']),
                'photo path'
            );
            portal_assert_same('image/jpeg', $photo['mime'], 'photo mime');
            portal_assert_same('selfie_1.jpg', $photo['filename'], 'photo filename');
        } finally {
            if (is_file($file)) unlink($file);
        }
    },

    'attendance photo rejects employees' => function (): void {
        admin_attendance_seed();

        portal_assert_api_error(
            fn () => portal_admin_attendance_photo_response(portal_test_db(), admin_attendance_auth(''), 1),
            'forbidden',
            403
        );
    },

    'attendance photo returns 404 when missing' => function (): void {
        admin_attendance_seed();

        portal_assert_api_error(
            fn () => portal_admin_attendance_photo_response(portal_test_db(), admin_attendance_auth(), 2),
            'not_found',
            404
        );
    },

    'departments list returns code and name' => function (): void {
        $result = portal_admin_departments_index(portal_test_db(), admin_attendance_auth('approver'), []);

        portal_assert_same(2, count($result['data']), 'departments');
        portal_assert_same('ADMIN', $result['data'][0]['id'], 'first department id');
        portal_assert_same('ADMIN', $result['data'][0]['name'], 'first department name');
    },

    'employee cannot list departments' => function (): void {
        portal_assert_api_error(
            fn () => portal_admin_departments_index(portal_test_db(), admin_attendance_auth(''), []),
            'forbidden',
            403
        );
    },

    'admin settings show breaks enabled' => function (): void {
        $result = portal_admin_settings_show(portal_test_db(), admin_attendance_auth(), []);

        portal_assert_same(true, $result['data']['breaks_enabled'], 'breaks enabled');
    },

    'admin can disable breaks' => function (): void {
        $result = portal_admin_settings_update(portal_test_db(), admin_attendance_auth(), ['breaks_enabled' => false]);

        portal_assert_same(false, $result['data']['breaks_enabled'], 'response');
        portal_assert_same(false, portal_settings(portal_test_db())['breaks_enabled'], 'database');
    },

    'approver cannot update settings' => function (): void {
        portal_assert_api_error(
            fn () => portal_admin_settings_update(portal_test_db(), admin_attendance_auth('approver'), ['breaks_enabled' => false]),
            'forbidden',
            403
        );
    },

    'settings update rejects a non boolean' => function (): void {
        $error = portal_assert_api_error(
            fn () => portal_admin_settings_update(portal_test_db(), admin_attendance_auth(), ['breaks_enabled' => 'maybe']),
            'validation_error',
            422
        );

        portal_assert_true(isset($error->details['errors']['breaks_enabled']), 'breaks_enabled error');
    },
];
