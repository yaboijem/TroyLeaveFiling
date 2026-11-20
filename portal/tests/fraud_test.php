<?php
declare(strict_types=1);
require_once __DIR__ . '/evidence_helpers.php';

return [
    'punch persists and returns fraud evidence' => function (): void {
        portal_test_evidence('2026-11-19 07:59:30', '2026-11-19 07:59:40');
        portal_set_test_now('2026-11-19 08:00:00');
        $dto = portal_punch(portal_test_db(), '1015', 'time_in', [
            'client_uuid' => '33333333-0000-4000-8000-000000000001',
            'latitude' => 14.5995, 'longitude' => 120.9842, 'accuracy_meters' => 10,
        ], null);
        portal_assert_same([['type' => 'rapid_clock', 'severity' => 'medium']], $dto['fraud_flags']);
    },
    'in radius has no flag' => function (): void {
        $p = portal_test_evidence();
        portal_assert_same([], portal_evaluate_fraud(portal_test_db(), $p, null, $p['punched_at']));
    },
    'out of radius flags medium' => function (): void {
        $p = portal_test_evidence();
        $p['is_within_radius'] = 0;
        portal_assert_same([['type' => 'out_of_radius', 'severity' => 'medium']], portal_evaluate_fraud(portal_test_db(), $p, null, $p['punched_at']));
    },
    'jump over 120 kmh flags high' => function (): void {
        $p = portal_test_evidence();
        $previous = ['latitude' => 14.8, 'longitude' => 120.9842, 'punched_at' => '2026-11-19 07:59:00'];
        $flags = portal_evaluate_fraud(portal_test_db(), $p, $previous, $p['punched_at']);
        portal_assert_same([['type' => 'impossible_jump', 'severity' => 'high']], $flags);
        $details = portal_test_db()->query("SELECT details FROM fraud_flags WHERE type='impossible_jump'")->fetch_assoc();
        portal_assert_true(json_decode($details['details'], true)['estimated_speed_kmh'] > 120);
        $other = portal_test_evidence('2026-11-19 10:00:00');
        portal_assert_same([], portal_evaluate_fraud(portal_test_db(), $other, ['latitude' => 14.6085, 'longitude' => 120.9842, 'punched_at' => '2026-11-19 09:50:00'], $other['punched_at']));
    },
    'rapid clock within one minute flags' => function (): void {
        portal_test_evidence('2026-11-19 07:59:30');
        $p = portal_test_evidence();
        portal_assert_same([['type' => 'rapid_clock', 'severity' => 'medium']], portal_evaluate_fraud(portal_test_db(), $p, null, $p['punched_at']));
        $later = portal_test_evidence('2026-11-19 08:02:00');
        portal_assert_same([], portal_evaluate_fraud(portal_test_db(), $later, null, $later['punched_at']));
    },
    'flags are deduped per punch and type' => function (): void {
        $p = portal_test_evidence();
        $p['is_within_radius'] = 0;
        portal_evaluate_fraud(portal_test_db(), $p, null, $p['punched_at']);
        portal_assert_same([], portal_evaluate_fraud(portal_test_db(), $p, null, $p['punched_at']));
        portal_assert_same(1, (int) portal_test_db()->query('SELECT COUNT(*) n FROM fraud_flags')->fetch_assoc()['n']);
    },
];
