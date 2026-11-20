<?php
declare(strict_types=1);
require_once __DIR__ . '/evidence_helpers.php';
return [
    'employee cannot forge admin provenance' => function (): void {
        $file = portal_test_image();
        $uuid = '66666666-0000-4000-8000-000000000001';
        $path = portal_config()['uploads'] . '/attendance/' . $uuid . '.jpg';
        try {
            portal_handle_punch_request(portal_test_db(), '1015', 'time_in', ['client_uuid' => $uuid, 'latitude' => 14.5995, 'longitude' => 120.9842, 'source' => 'admin', 'notes' => 'Closed by HR'], ['selfie' => $file]);
            foreach (['attendance', 'attendance_punches'] as $table) {
                $row = portal_test_db()->query("SELECT source,notes FROM {$table}")->fetch_assoc();
                portal_assert_same('app', $row['source']);
                portal_assert_null($row['notes']);
            }
        } finally { unlink($file['tmp_name']); if (is_file($path)) unlink($path); }
    },
    'accuracy rejects malformed negative and nonscalar values' => function (): void {
        foreach (['garbage', -1, ['1'], INF, NAN] as $accuracy) {
            $e = portal_assert_api_error(fn () => portal_handle_punch_request(portal_test_db(), '1015', 'time_in', ['latitude' => 14.5995, 'longitude' => 120.9842, 'accuracy_meters' => $accuracy], []), 'validation_error', 422);
            portal_assert_true(isset($e->details['errors']['accuracy_meters']));
        }
    },
    'compressed huge image rejected before decode' => function (): void {
        $path = tempnam(__DIR__, 'limit-');
        $chunk = 'IHDR' . pack('NNCCCCC', 30000, 30000, 8, 2, 0, 0, 0);
        $png = "\x89PNG\r\n\x1a\n" . pack('N', 13) . $chunk . pack('N', crc32($chunk));
        file_put_contents($path, $png);
        try { portal_assert_api_error(fn () => portal_validate_selfie(['error' => 0,'tmp_name' => $path,'size' => strlen($png)]), 'invalid_selfie', 422); }
        finally { unlink($path); }
    },
    'replacement approval never resurrects retired pin' => function (): void {
        $db = portal_test_db();
        $input = ['latitude' => 14.5, 'longitude' => 121];
        $a = portal_submit_home_location($db, '1019', $input, static fn () => null);
        portal_review_home_location($db, $a['id'], 'approve', null, 'HR');
        $b = portal_submit_home_location($db, '1019', $input, static fn () => null);
        portal_review_home_location($db, $b['id'], 'approve', null, 'HR');
        portal_review_home_location($db, $b['id'], 'reject', 'Revoked', 'HR');
        portal_assert_same('none', portal_home_location_payload($db, '1019')['data']['status']);
        portal_assert_api_error(fn () => portal_review_home_location($db, $a['id'], 'approve', null, 'HR'), 'invalid_action', 422);
    },
    'superseded request cannot be approved' => function (): void {
        $db = portal_test_db();
        $input = ['latitude' => 14.5, 'longitude' => 121];
        $a = portal_submit_home_location($db, '1019', $input, static fn () => null);
        portal_submit_home_location($db, '1019', $input, static fn () => null);
        portal_assert_api_error(fn () => portal_review_home_location($db, $a['id'], 'approve', null, 'HR'), 'invalid_action', 422);
    },
    'history includes admin correction provenance' => function (): void {
        portal_test_evidence();
        portal_close_session(portal_test_db(), '1015', '2026-11-19 17:00:00', 'Forgot', 'Ana');
        $row = portal_history_page(portal_test_db(), '1015', null, null, 1)['data'][0];
        portal_assert_same('admin', $row['correction']['source'] ?? null);
        portal_assert_same('Closed by Ana: Forgot', $row['correction']['notes'] ?? null);
    },
    'push rejects arbitrary or disguised endpoints' => function (): void {
        foreach (['https://127.0.0.1/x','https://localhost/x','https://192.168.1.2/x','https://push.example.test/x','https://fcm.googleapis.com.attacker.test/x','https://evil@fcm.googleapis.com/x','https://fcm.googleapis.com:8443/x'] as $endpoint) {
            portal_assert_api_error(fn () => portal_push_subscribe(portal_test_db(), '1015', ['endpoint' => $endpoint, 'keys' => ['p256dh' => 'key','auth' => 'key']]), 'validation_error', 422);
        }
    },
];
