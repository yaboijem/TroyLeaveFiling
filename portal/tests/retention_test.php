<?php
declare(strict_types=1);
require_once __DIR__ . '/evidence_helpers.php';

return [
    'old closed shifts are purged with their photos' => function (): void {
        $root = portal_config()['uploads'];
        if (!is_dir($root . '/attendance')) mkdir($root . '/attendance', 0777, true);
        $path = $root . '/attendance/retention-test.jpg';
        file_put_contents($path, 'test-only');
        try {
            $p = portal_test_evidence('2024-01-01 08:00:00', '2024-01-01 17:00:00', 'attendance/retention-test.jpg');
            portal_test_db()->query("INSERT INTO fraud_flags (attendance_id,punch_id,type,severity,created_at) VALUES ({$p['attendance_id']},{$p['id']},'rapid_clock','medium','2024-01-01 08:00:00')");
            portal_test_evidence('2026-01-01 08:00:00', '2026-01-01 17:00:00');
            portal_test_evidence('2024-01-02 08:00:00');
            $result = portal_purge(portal_test_db(), 730, false, '2026-11-20 08:00:00');
            portal_assert_same(1, $result['headers']);
            portal_assert_same(1, $result['punches']);
            portal_assert_same(1, $result['photos']);
            portal_assert_true(!is_file($path));
            portal_assert_same(0, (int) portal_test_db()->query('SELECT COUNT(*) n FROM fraud_flags')->fetch_assoc()['n']);
            portal_assert_same(2, (int) portal_test_db()->query('SELECT COUNT(*) n FROM attendance')->fetch_assoc()['n']);
        } finally { if (is_file($path)) unlink($path); }
    },
    'open shifts are kept forever' => function (): void {
        portal_test_evidence('2024-01-01 08:00:00');
        portal_assert_same(0, portal_purge(portal_test_db(), 730, false, '2026-11-20 08:00:00')['headers']);
        portal_assert_same(1, (int) portal_test_db()->query('SELECT COUNT(*) n FROM attendance')->fetch_assoc()['n']);
    },
    'dry run writes nothing' => function (): void {
        portal_test_evidence('2024-01-01 08:00:00', '2024-01-01 17:00:00');
        portal_assert_same(1, portal_purge(portal_test_db(), 730, true, '2026-11-20 08:00:00')['headers']);
        portal_assert_same(1, (int) portal_test_db()->query('SELECT COUNT(*) n FROM attendance')->fetch_assoc()['n']);
    },
    'days below thirty is rejected' => function (): void {
        try { portal_purge(portal_test_db(), 29); }
        catch (InvalidArgumentException) { return; }
        throw new RuntimeException('Expected retention minimum.');
    },
    'old login attempts are cleared' => function (): void {
        portal_record_login_failure(portal_test_db(), 'test@example.test', '127.0.0.1', '2026-09-20 08:00:00');
        portal_record_login_failure(portal_test_db(), 'test@example.test', '127.0.0.1', '2026-11-20 08:00:00');
        portal_assert_same(1, portal_purge(portal_test_db(), 730, false, '2026-11-20 08:00:00')['login_attempts']);
        portal_assert_same(1, (int) portal_test_db()->query('SELECT COUNT(*) n FROM login_attempts')->fetch_assoc()['n']);
    },
];
