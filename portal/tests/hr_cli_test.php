<?php
declare(strict_types=1);
require_once __DIR__ . '/evidence_helpers.php';

return [
    'set profile creates a new hire' => function (): void {
        $row = portal_set_profile(portal_test_db(), '1020', 1, 'onsite');
        portal_assert_same('1020', $row['idno']);
        portal_assert_same(1, $row['branch_id']);
        portal_assert_same('onsite', $row['work_arrangement']);
    },
    'set profile updates arrangement and branch' => function (): void {
        portal_set_profile(portal_test_db(), '1015', null, 'wfh');
        portal_assert_same(2, portal_set_profile(portal_test_db(), '1015', 2, null)['branch_id']);
    },
    'set profile keeps omitted fields' => function (): void {
        portal_assert_same(1, portal_set_profile(portal_test_db(), '1015', null, 'wfh')['branch_id']);
        portal_assert_same('wfh', portal_set_profile(portal_test_db(), '1015', 2, null)['work_arrangement']);
    },
    'set profile rejects unknown employee' => function (): void {
        portal_assert_api_error(fn () => portal_set_profile(portal_test_db(), 'missing', 1, 'onsite'), 'not_found', 404);
    },
    'set profile rejects unknown branch' => function (): void {
        portal_assert_api_error(fn () => portal_set_profile(portal_test_db(), '1015', 99999, null), 'validation_error', 422);
    },
    'set profile rejects inactive branch' => function (): void {
        $db = portal_test_db();
        $db->query('UPDATE branches SET is_active = 0 WHERE id = 2');

        portal_assert_api_error(fn () => portal_set_profile($db, '1015', 2, null), 'validation_error', 422);
    },
    'set profile accepts active branch' => function (): void {
        $db = portal_test_db();
        $db->query('UPDATE branches SET is_active = 1 WHERE id = 2');

        portal_assert_same(2, portal_set_profile($db, '1015', 2, null)['branch_id']);
    },
    'set profile rejects invalid arrangement' => function (): void {
        portal_assert_api_error(fn () => portal_set_profile(portal_test_db(), '1015', null, 'invalid'), 'validation_error', 422);
    },
    'set profile args parse' => function (): void {
        portal_assert_same(['idno' => '1020', 'branch' => 1, 'arrangement' => 'wfh'], portal_parse_set_profile_args(['--idno=1020','--branch=1','--arrangement=wfh']));
    },
    'close session closes abandoned shift' => function (): void {
        $p = portal_test_evidence();
        portal_test_db()->query("INSERT INTO attendance_punches (attendance_id,idno,uuid,type,punched_at,break_minutes,created_at) VALUES ({$p['attendance_id']},'1015','55555555-0000-4000-8000-000000000001','break_out','2026-11-19 12:30:00',30,NOW())");
        $result = portal_close_session(portal_test_db(), '1015', '2026-11-19 17:00:00', 'Forgot', 'Ana');
        portal_assert_same(510, (int) $result['attendance']['work_minutes']);
        portal_assert_same('2026-11-19 17:00:00', $result['attendance']['time_out']);
    },
    'close session writes admin punch' => function (): void {
        portal_test_evidence();
        $p = portal_close_session(portal_test_db(), '1015', '2026-11-19 17:00:00', 'Forgot', 'Ana')['punch'];
        portal_assert_same('time_out', $p['type']);
        portal_assert_same('admin', $p['source']);
        portal_assert_same('Closed by Ana: Forgot', $p['notes']);
        portal_assert_null($p['photo_path']);
        portal_assert_null($p['latitude']);
    },
    'close session refuses no open shift' => function (): void {
        portal_assert_api_error(fn () => portal_close_session(portal_test_db(), '1015', null, '', 'HR'), 'attendance_conflict', 409);
    },
    'close session refuses time before time in' => function (): void {
        portal_test_evidence();
        portal_assert_api_error(fn () => portal_close_session(portal_test_db(), '1015', '2026-11-19 07:00:00', '', 'HR'), 'validation_error', 422);
    },
    'close session args parse' => function (): void {
        portal_assert_same(['idno' => '1015','at' => '2026-11-19 17:00:00','note' => 'Forgot','by' => 'Ana'], portal_parse_close_session_args(['--idno=1015','--at=2026-11-19 17:00:00','--note=Forgot','--by=Ana']));
    },
];
