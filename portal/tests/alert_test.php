<?php
declare(strict_types=1);
require_once __DIR__ . '/evidence_helpers.php';

function alert_test_break(?string $kind = '15_min'): int
{
    $p = portal_test_evidence();
    $db = portal_test_db();
    $due = $kind === '15_min' ? '2026-11-19 12:15:00' : null;
    $uuid = bin2hex(random_bytes(18));
    $stmt = $db->prepare("INSERT INTO attendance_punches (attendance_id,idno,uuid,type,punched_at,break_kind,expected_end_at,created_at) VALUES (?,'1015',?,'break_in','2026-11-19 12:00:00',?,?,'2026-11-19 12:00:00')");
    $stmt->bind_param('isss', $p['attendance_id'], $uuid, $kind, $due);
    $stmt->execute();
    return (int) $db->insert_id;
}

return [
    'break ending alert fires two minutes before' => function (): void {
        alert_test_break();
        portal_assert_same(0, portal_materialize_break_alerts(portal_test_db(), '1015', '2026-11-19 12:12:00'));
        portal_assert_same(1, portal_materialize_break_alerts(portal_test_db(), '1015', '2026-11-19 12:13:00'));
        portal_assert_same(0, portal_materialize_break_alerts(portal_test_db(), '1015', '2026-11-19 12:14:00'));
        portal_assert_same('Break ending soon', portal_notifications_page(portal_test_db(), '1015', 1)['data'][0]['title']);
    },
    'past due alert fires five minutes after' => function (): void {
        alert_test_break();
        portal_materialize_break_alerts(portal_test_db(), '1015', '2026-11-19 12:13:00');
        portal_assert_same(1, portal_materialize_break_alerts(portal_test_db(), '1015', '2026-11-19 12:20:00'));
        portal_assert_same('Break is past due', portal_notifications_page(portal_test_db(), '1015', 1)['data'][0]['title']);
    },
    'overbreak fires at sixty minutes' => function (): void {
        alert_test_break('bio');
        portal_assert_same(1, portal_materialize_break_alerts(portal_test_db(), '1015', '2026-11-19 13:01:00'));
        portal_assert_same(0, portal_materialize_break_alerts(portal_test_db(), '1015', '2026-11-19 13:10:00'));
        portal_assert_same('Overbreak', portal_notifications_page(portal_test_db(), '1015', 1)['data'][0]['title']);
    },
    'materialize is idempotent' => function (): void {
        alert_test_break();
        portal_materialize_break_alerts(portal_test_db(), '1015', '2026-11-19 12:13:00');
        portal_assert_same(0, portal_materialize_break_alerts(portal_test_db(), '1015', '2026-11-19 12:13:00'));
    },
    'untimed break gets no ending alerts' => function (): void {
        alert_test_break('bio');
        portal_assert_same(0, portal_materialize_break_alerts(portal_test_db(), '1015', '2026-11-19 12:20:00'));
    },
    'dry run writes nothing' => function (): void {
        alert_test_break();
        $db = portal_test_db();
        // The due scan is the read-only path used by the CLI's dry-run.
        portal_assert_same(['1015'], portal_due_break_alerts($db, '2026-11-19 12:13:00'));
        $previousConfig = getenv('PORTAL_CONFIG_PATH');
        putenv('PORTAL_CONFIG_PATH=' . __DIR__ . '/config.php');
        try {
            exec('"' . PHP_BINARY . '" "' . dirname(__DIR__) . '/bin/alerts.php" --dry-run', $output, $status);
            portal_assert_same(0, $status);
            portal_assert_true(str_contains(implode("\n", $output), 'due=1015'));
            portal_assert_true(str_contains(implode("\n", $output), 'dry-run created=0 pushed=0'));
        } finally {
            putenv($previousConfig === false ? 'PORTAL_CONFIG_PATH' : 'PORTAL_CONFIG_PATH=' . $previousConfig);
        }
        portal_assert_same(0, portal_unread_count($db, '1015'));
    },
];
