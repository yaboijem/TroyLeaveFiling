<?php
declare(strict_types=1);

function shift_test_punch(string $type, string $at, string $idno = '1015', ?string $uuid = null): array
{
    portal_set_test_now($at);
    return portal_punch(portal_test_db(), $idno, $type, [
        'latitude' => 14.5995, 'longitude' => 120.9842, 'accuracy_meters' => 10,
        'client_uuid' => $uuid ?? sprintf('%08x-0000-4000-8000-%012x', random_int(1, 0x7fffffff), random_int(1, 0x7fffffff)),
    ], null);
}

return [
    'time in opens one shift' => function (): void {
        $uuid = '11111111-0000-4000-8000-000000000001';
        $punch = shift_test_punch('time_in', '2026-11-19 08:00:00', '1015', $uuid);
        $db = portal_test_db();
        $row = $db->query('SELECT * FROM attendance')->fetch_assoc();
        portal_assert_same('2026-11-19 08:00:00', $row['time_in']);
        portal_assert_null($row['time_out']);
        portal_assert_same('2026-11-19', $row['work_date']);
        portal_assert_same(1, (int) $row['branch_id']);
        portal_assert_same(1, (int) $db->query('SELECT COUNT(*) n FROM attendance')->fetch_assoc()['n']);
        portal_assert_same($uuid, $punch['uuid']);
        portal_assert_same('time_in', $punch['type']);
        portal_assert_same('2026-11-19T08:00:00+08:00', $punch['timestamp']);
        $session = portal_punch_session($db, '1015');
        portal_assert_same(true, $session['open']);
        portal_assert_same(false, $session['on_break']);
        portal_assert_same($punch['id'], $session['time_in']['id']);
    },
    'second time in conflicts' => function (): void {
        shift_test_punch('time_in', '2026-11-19 08:00:00');
        $e = portal_assert_api_error(fn () => shift_test_punch('time_in', '2026-11-19 08:01:00'), 'attendance_conflict', 409);
        portal_assert_same(true, $e->session['open']);
        portal_assert_same(1, (int) portal_test_db()->query('SELECT COUNT(*) n FROM attendance')->fetch_assoc()['n']);
    },
    'time out without a shift conflicts' => function (): void {
        $e = portal_assert_api_error(fn () => shift_test_punch('time_out', '2026-11-19 17:00:00'), 'attendance_conflict', 409);
        portal_assert_same('You have not clocked in yet today.', $e->getMessage());
    },
    'time out closes the shift' => function (): void {
        shift_test_punch('time_in', '2026-11-19 08:00:00');
        $out = shift_test_punch('time_out', '2026-11-19 17:00:00');
        $row = portal_test_db()->query('SELECT * FROM attendance')->fetch_assoc();
        portal_assert_same('2026-11-19 17:00:00', $row['time_out']);
        portal_assert_same(540, (int) $row['work_minutes']);
        portal_assert_same(540, $out['work_minutes']);
        portal_assert_same('time_out', $out['type']);
        portal_assert_same(false, portal_punch_session(portal_test_db(), '1015')['open']);
    },
    'work minutes never go negative' => function (): void {
        shift_test_punch('time_in', '2026-11-19 08:00:00');
        portal_assert_same(0, shift_test_punch('time_out', '2026-11-19 07:00:00')['work_minutes']);
    },
    'overnight stays on the start date' => function (): void {
        shift_test_punch('time_in', '2026-11-19 22:00:00');
        shift_test_punch('time_out', '2026-11-20 06:00:00');
        $row = portal_test_db()->query("SELECT * FROM attendance WHERE work_date BETWEEN '2026-11-19' AND '2026-11-19'")->fetch_assoc();
        portal_assert_same('2026-11-19', $row['work_date']);
        portal_assert_same(480, (int) $row['work_minutes']);
    },
    'duplicate client uuid returns the same punch' => function (): void {
        $uuid = '11111111-0000-4000-8000-000000000001';
        $first = shift_test_punch('time_in', '2026-11-19 08:00:00', '1015', $uuid);
        $second = shift_test_punch('time_in', '2026-11-19 08:01:00', '1015', $uuid);
        portal_assert_same($first['id'], $second['id']);
        portal_assert_same(1, (int) portal_test_db()->query('SELECT COUNT(*) n FROM attendance_punches')->fetch_assoc()['n']);
    },
    'uuid owned by another employee conflicts' => function (): void {
        $uuid = '11111111-0000-4000-8000-000000000001';
        shift_test_punch('time_in', '2026-11-19 08:00:00', '1015', $uuid);
        portal_assert_api_error(fn () => shift_test_punch('time_in', '2026-11-19 08:01:00', '1016', $uuid), 'attendance_conflict', 409);
        portal_assert_same(1, (int) portal_test_db()->query('SELECT COUNT(*) n FROM attendance')->fetch_assoc()['n']);
    },
    'different uuid creates a new punch only after close' => function (): void {
        shift_test_punch('time_in', '2026-11-19 08:00:00');
        shift_test_punch('time_out', '2026-11-19 12:00:00');
        shift_test_punch('time_in', '2026-11-19 14:00:00');
        portal_assert_same(2, (int) portal_test_db()->query('SELECT COUNT(*) n FROM attendance')->fetch_assoc()['n']);
    },
    'work punches follow the latest shift' => function (): void {
        shift_test_punch('time_in', '2026-11-19 08:00:00');
        $out = shift_test_punch('time_out', '2026-11-19 12:00:00');
        portal_assert_same($out['id'], portal_work_punches(portal_test_db(), '1015')[1]['id']);
        $evening = shift_test_punch('time_in', '2026-11-19 18:00:00');
        $work = portal_work_punches(portal_test_db(), '1015');
        portal_assert_same(1, count($work));
        portal_assert_same($evening['id'], $work[0]['id']);
    },
];
