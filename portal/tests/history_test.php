<?php
declare(strict_types=1);
require_once __DIR__ . '/evidence_helpers.php';

return [
    'newest first' => function (): void {
        portal_test_evidence('2026-11-17 17:00:00');
        portal_test_evidence('2026-11-18 08:00:00');
        portal_test_evidence('2026-11-19 08:00:00');
        $rows = portal_history_page(portal_test_db(), '1015', null, null, 1)['data'];
        portal_assert_same(['2026-11-19','2026-11-18','2026-11-17'], array_column($rows, 'work_date'));
    },
    'one row per header' => function (): void {
        portal_test_evidence('2026-11-18 08:00:00', '2026-11-18 12:00:00');
        portal_test_evidence('2026-11-18 14:00:00');
        $rows = portal_history_page(portal_test_db(), '1015', null, null, 1)['data'];
        portal_assert_same(2, count($rows));
        portal_assert_same(['2026-11-18','2026-11-18'], array_column($rows, 'work_date'));
        portal_assert_same('2026-11-18T14:00:00+08:00', $rows[0]['time_in']);
    },
    'open shift has null time out' => function (): void {
        portal_test_evidence();
        $row = portal_history_page(portal_test_db(), '1015', null, null, 1)['data'][0];
        portal_assert_null($row['time_out']);
        portal_assert_null($row['work_minutes']);
        portal_assert_same([], $row['breaks']);
    },
    'date range filters by work date' => function (): void {
        portal_test_evidence('2026-11-18 08:00:00');
        portal_test_evidence('2026-11-19 08:00:00');
        $rows = portal_handle_history_request(portal_test_db(), '1015', ['from' => '2026-11-18', 'to' => '2026-11-18'])['data'];
        portal_assert_same(['2026-11-18'], array_column($rows, 'work_date'));
    },
    'pagination' => function (): void {
        for ($i = 0; $i < 25; $i++) portal_test_evidence();
        $first = portal_history_page(portal_test_db(), '1015', null, null, 1);
        $second = portal_history_page(portal_test_db(), '1015', null, null, 2);
        portal_assert_same(20, count($first['data']));
        portal_assert_same(5, count($second['data']));
        portal_assert_same(25, $first['meta']['total']);
        portal_assert_same(2, $first['meta']['last_page']);
    },
    'per page is clamped' => function (): void {
        for ($i = 0; $i < 101; $i++) portal_test_evidence();
        portal_assert_same(100, count(portal_history_page(portal_test_db(), '1015', null, null, 1, 500)['data']));
        portal_assert_same(1, count(portal_history_page(portal_test_db(), '1015', null, null, 1, 0)['data']));
    },
    'breaks pair chronologically' => function (): void {
        $p = portal_test_evidence();
        $db = portal_test_db();
        foreach ([['break_in','12:01:00',null], ['break_out','13:00:00',59], ['break_in','15:00:00',null], ['break_out','15:15:00',15]] as [$type,$time,$minutes]) {
            $uuid = bin2hex(random_bytes(18));
            $at = '2026-11-19 ' . $time;
            $stmt = $db->prepare("INSERT INTO attendance_punches (attendance_id,idno,uuid,type,punched_at,break_kind,break_minutes,created_at) VALUES (?,'1015',?,?,?,'15_min',?,?)");
            $stmt->bind_param('isssis', $p['attendance_id'], $uuid, $type, $at, $minutes, $at);
            $stmt->execute();
        }
        $row = portal_history_page($db, '1015', null, null, 1)['data'][0];
        portal_assert_same([59,15], array_column($row['breaks'], 'minutes'));
    },
    'overnight row stays on start date' => function (): void {
        portal_test_evidence('2026-11-19 22:00:00', '2026-11-20 06:00:00');
        $row = portal_handle_history_request(portal_test_db(), '1015', ['from' => '2026-11-19','to' => '2026-11-19'])['data'][0];
        portal_assert_same('2026-11-19', $row['work_date']);
        portal_assert_same('2026-11-20T06:00:00+08:00', $row['time_out']);
    },
    'branch and location summary' => function (): void {
        $p = portal_test_evidence();
        portal_test_db()->query("UPDATE attendance_punches SET distance_from_branch_meters=12.5,verified_against_type='branch',verified_against_id=1 WHERE id={$p['id']}");
        $row = portal_history_page(portal_test_db(), '1015', null, null, 1)['data'][0];
        portal_assert_same('Main Office', $row['branch']['name']);
        portal_assert_same(12.5, $row['gps_location']['distance_from_branch_meters']);
        portal_assert_same(true, $row['gps_location']['is_within_radius']);
        portal_assert_same('branch', $row['gps_location']['verified_against_type']);
    },
    'fraud flags are per shift' => function (): void {
        $p = portal_test_evidence('2026-11-18 08:00:00');
        portal_test_evidence();
        portal_test_db()->query("INSERT INTO fraud_flags (attendance_id,punch_id,type,severity,created_at) VALUES ({$p['attendance_id']},{$p['id']},'rapid_clock','medium',NOW())");
        $rows = portal_history_page(portal_test_db(), '1015', null, null, 1)['data'];
        portal_assert_same([], $rows[0]['fraud_flags']);
        portal_assert_same('rapid_clock', $rows[1]['fraud_flags'][0]['type']);
        portal_assert_same('Rapid clock', $rows[1]['fraud_flags'][0]['label']);
    },
    'photo comes from the time in punch' => function (): void {
        $p = portal_test_evidence('2026-11-19 08:00:00', null, 'attendance/example.jpg');
        $row = portal_history_page(portal_test_db(), '1015', null, null, 1)['data'][0];
        portal_assert_same(portal_photo_url($p['uuid']), $row['photo']['path']);
    },
    'history only returns the caller rows' => function (): void {
        $p = portal_test_evidence();
        portal_test_db()->query("UPDATE attendance SET idno='1016' WHERE id={$p['attendance_id']}");
        portal_assert_same([], portal_history_page(portal_test_db(), '1015', null, null, 1)['data']);
    },
];
