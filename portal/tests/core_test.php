<?php
declare(strict_types=1);

/**
 * Task 1 tests — configuration, clock, error payloads and DB guard.
 *
 * Each test file returns ['test name' => callable].
 */

return [
    'config loads from PORTAL_CONFIG_PATH' => function (): void {
        $config = portal_config();
        portal_assert_same('troyserverpayroll_test', $config['db']['name']);
        portal_assert_same('/TroyLeaveFiling/portal', $config['public_base']);
    },

    'now uses Asia/Manila' => function (): void {
        portal_set_test_now('2026-11-19 08:02:11');
        portal_assert_same('+08:00', portal_now()->format('P'));
        portal_assert_same('2026-11-19 08:02:11', portal_now()->format('Y-m-d H:i:s'));
    },

    'iso formats with offset' => function (): void {
        portal_assert_same('2026-11-19T08:02:11+08:00', portal_iso('2026-11-19 08:02:11'));
        portal_assert_null(portal_iso(null));
        portal_assert_null(portal_iso('not-a-date'));
    },

    'error payload keeps message code and details' => function (): void {
        portal_assert_same(
            ['message' => 'Outside radius.', 'code' => 'gps_out_of_range', 'details' => ['distance_meters' => 212.4]],
            portal_error_payload('Outside radius.', 'gps_out_of_range', ['distance_meters' => 212.4])
        );
    },

    'ajax detection requires the header' => function (): void {
        portal_assert_true(portal_is_ajax(['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']));
        portal_assert_true(!portal_is_ajax([]));
    },

    'db refuses a non-test database' => function (): void {
        try {
            portal_db_connect([
                'host' => '127.0.0.1',
                'user' => 'root',
                'pass' => '',
                'name' => 'troyserverpayroll',
                'port' => 3306,
            ]);
        } catch (RuntimeException $e) {
            portal_assert_true(str_contains($e->getMessage(), '_test'), 'Message must name the _test suffix.');
            return;
        }
        throw new RuntimeException('Expected portal_db_connect to refuse a non-test database.');
    },

    'security headers are defined' => function (): void {
        $headers = portal_security_headers();

        portal_assert_same('nosniff', $headers['X-Content-Type-Options'] ?? null);
        portal_assert_same('DENY', $headers['X-Frame-Options'] ?? null);
        portal_assert_same('no-referrer', $headers['Referrer-Policy'] ?? null);
        portal_assert_true(str_contains($headers['Permissions-Policy'] ?? '', 'geolocation=(self)'));
        portal_assert_true(str_contains($headers['Content-Security-Policy'] ?? '', "frame-ancestors 'none'"));
    },
];
