<?php
declare(strict_types=1);

function break_test_punch(string $type, string $at, ?string $kind = '15_min'): array
{
    portal_set_test_now($at);
    return portal_punch(portal_test_db(), '1015', $type, [
        'latitude' => 14.5995, 'longitude' => 120.9842, 'accuracy_meters' => 10,
        'client_uuid' => sprintf('%08x-0000-4000-8000-%012x', random_int(1, 0x7fffffff), random_int(1, 0x7fffffff)),
        'break_kind' => $kind,
    ], null);
}

function break_test_start(): void
{
    break_test_punch('time_in', '2026-11-19 08:00:00');
}

return [
    'break in without a shift conflicts' => function (): void {
        portal_assert_api_error(fn () => break_test_punch('break_in', '2026-11-19 12:00:00'), 'attendance_conflict', 409);
    },
    'breaks disabled is rejected' => function (): void {
        break_test_start();
        portal_test_db()->query('UPDATE app_settings SET breaks_enabled=0 WHERE id=1');
        $e = portal_assert_api_error(fn () => break_test_punch('break_in', '2026-11-19 12:00:00'), 'breaks_disabled', 422);
        portal_assert_same('Break in/out is currently disabled by an administrator.', $e->getMessage());
    },
    'double break in conflicts' => function (): void {
        break_test_start();
        break_test_punch('break_in', '2026-11-19 12:00:00');
        $e = portal_assert_api_error(fn () => break_test_punch('break_in', '2026-11-19 12:01:00'), 'attendance_conflict', 409);
        portal_assert_same('You are already on break.', $e->getMessage());
    },
    'break kind sets expected end' => function (): void {
        portal_assert_same(['15_min', '5_min', 'lunch_60', 'bio', 'phone', 'coaching', 'huddle', 'training'], portal_break_kinds());
        foreach (['15_min' => '2026-11-19 12:15:00', 'lunch_60' => '2026-11-19 13:00:00', 'bio' => null, '5_min' => null] as $kind => $expected) {
            portal_assert_same($expected, portal_expected_end_at($kind, '2026-11-19 12:00:00'));
        }
        break_test_start();
        $dto = break_test_punch('break_in', '2026-11-19 12:00:00', 'lunch_60');
        portal_assert_same('2026-11-19T13:00:00+08:00', $dto['expected_end_at']);
    },
    'unknown break kind is rejected' => function (): void {
        break_test_start();
        portal_assert_api_error(fn () => break_test_punch('break_in', '2026-11-19 12:00:00', 'invalid'), 'invalid_break_kind', 422);
    },
    'break out computes minutes' => function (): void {
        break_test_start();
        break_test_punch('break_in', '2026-11-19 12:01:00');
        $dto = break_test_punch('break_out', '2026-11-19 13:00:00');
        portal_assert_same(59, $dto['break_minutes']);
        portal_assert_same(false, $dto['is_overbreak']);
        portal_assert_same(59, (int) portal_open_shift(portal_test_db(), '1015')['break_minutes']);
    },
    'overbreak is flagged' => function (): void {
        break_test_start();
        break_test_punch('break_in', '2026-11-19 12:00:00');
        $dto = break_test_punch('break_out', '2026-11-19 13:01:00');
        portal_assert_same(true, $dto['is_overbreak']);
        portal_assert_same(1, (int) portal_open_shift(portal_test_db(), '1015')['is_overbreak']);
    },
    'two breaks accumulate' => function (): void {
        break_test_start();
        foreach ([['break_in','12:00:00'],['break_out','12:30:00'],['break_in','14:00:00'],['break_out','14:30:00']] as [$type,$time]) {
            break_test_punch($type, '2026-11-19 ' . $time);
        }
        portal_assert_same(60, (int) portal_open_shift(portal_test_db(), '1015')['break_minutes']);
    },
    'break out without break conflicts' => function (): void {
        break_test_start();
        $e = portal_assert_api_error(fn () => break_test_punch('break_out', '2026-11-19 12:00:00'), 'attendance_conflict', 409);
        portal_assert_same('You are not on break.', $e->getMessage());
    },
    'time out during break conflicts' => function (): void {
        break_test_start();
        break_test_punch('break_in', '2026-11-19 12:00:00');
        $e = portal_assert_api_error(fn () => break_test_punch('time_out', '2026-11-19 12:01:00'), 'attendance_conflict', 409);
        portal_assert_same('End your break first before clocking out.', $e->getMessage());
    },
    'work minutes subtract breaks' => function (): void {
        break_test_start();
        foreach ([['break_in','12:00:00'],['break_out','12:30:00'],['break_in','14:00:00'],['break_out','14:30:00']] as [$type,$time]) {
            break_test_punch($type, '2026-11-19 ' . $time);
        }
        portal_assert_same(480, break_test_punch('time_out', '2026-11-19 17:00:00')['work_minutes']);
    },
    'break out copies kind and due time from break in' => function (): void {
        break_test_start();
        $start = break_test_punch('break_in', '2026-11-19 12:00:00', 'lunch_60');
        $end = break_test_punch('break_out', '2026-11-19 13:00:00', null);
        portal_assert_same($start['break_kind'], $end['break_kind']);
        portal_assert_same($start['expected_end_at'], $end['expected_end_at']);
    },
    'session reports break' => function (): void {
        break_test_start();
        break_test_punch('break_in', '2026-11-19 12:00:00');
        $session = portal_punch_session(portal_test_db(), '1015');
        portal_assert_same(true, $session['open']);
        portal_assert_same(true, $session['on_break']);
        portal_assert_same('15_min', $session['break']['break_kind']);
        portal_assert_same('2026-11-19T12:15:00+08:00', $session['break']['expected_end_at']);
        portal_assert_same('break_in', portal_open_break(portal_test_db(), '1015')['type']);
        break_test_punch('break_out', '2026-11-19 12:15:00');
        $session = portal_punch_session(portal_test_db(), '1015');
        portal_assert_same(false, $session['on_break']);
        portal_assert_null($session['break']);
        portal_assert_null(portal_open_break(portal_test_db(), '1015'));
    },
];
