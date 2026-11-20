<?php
declare(strict_types=1);

function push_test_input(): array
{
    return ['endpoint' => 'https://fcm.googleapis.com/send/test-only', 'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-key']];
}

return [
    'public key is null when unset' => function (): void {
        portal_assert_null(portal_push_public_key());
    },
    'public key returns configured value' => function (): void {
        $script = tempnam(sys_get_temp_dir(), 'troy-push-');
        $configPath = tempnam(sys_get_temp_dir(), 'troy-config-');
        $config = portal_config();
        $config['vapid']['public_key'] = 'configured-public-key';
        file_put_contents($configPath, '<?php return ' . var_export($config, true) . ';');
        $root = dirname(__DIR__);
        file_put_contents($script, '<?php define("PORTAL_CONFIG_PATH",' . var_export($configPath, true) . '); require ' . var_export($root . '/lib/core.php', true) . '; require ' . var_export($root . '/lib/push.php', true) . '; echo portal_push_public_key();');
        try {
            exec('"' . PHP_BINARY . '" "' . $script . '"', $lines, $status);
            portal_assert_same(0, $status);
            portal_assert_same('configured-public-key', implode('', $lines));
        } finally { unlink($script); unlink($configPath); }
    },
    'subscribe inserts subscription' => function (): void {
        $input = push_test_input();
        portal_push_subscribe(portal_test_db(), '1015', $input);
        $row = portal_test_db()->query('SELECT * FROM push_subscriptions')->fetch_assoc();
        portal_assert_same(hash('sha256', $input['endpoint']), $row['endpoint_hash']);
    },
    'resubscribe updates same row' => function (): void {
        portal_set_test_now('2026-11-19 08:00:00');
        portal_push_subscribe(portal_test_db(), '1015', push_test_input());
        portal_set_test_now('2026-11-19 09:00:00');
        portal_push_subscribe(portal_test_db(), '1016', push_test_input());
        $rows = portal_test_db()->query('SELECT * FROM push_subscriptions')->fetch_all(MYSQLI_ASSOC);
        portal_assert_same(1, count($rows));
        portal_assert_same('1016', $rows[0]['employee_id']);
        portal_assert_same('2026-11-19 09:00:00', $rows[0]['updated_at']);
    },
    'subscribe validates endpoint and keys' => function (): void {
        portal_assert_api_error(fn () => portal_push_subscribe(portal_test_db(), '1015', []), 'validation_error', 422);
    },
    'unsubscribe removes only owner row' => function (): void {
        $input = push_test_input();
        portal_push_subscribe(portal_test_db(), '1015', $input);
        portal_push_unsubscribe(portal_test_db(), '1016', $input['endpoint']);
        portal_assert_same(1, (int) portal_test_db()->query('SELECT COUNT(*) n FROM push_subscriptions')->fetch_assoc()['n']);
        portal_push_unsubscribe(portal_test_db(), '1015', $input['endpoint']);
        portal_assert_same(0, (int) portal_test_db()->query('SELECT COUNT(*) n FROM push_subscriptions')->fetch_assoc()['n']);
    },
    'send is noop without keys' => function (): void {
        portal_push_subscribe(portal_test_db(), '1015', push_test_input());
        portal_push_send(portal_test_db(), '1015', 'Test', 'Test');
        portal_assert_same(1, (int) portal_test_db()->query('SELECT COUNT(*) n FROM push_subscriptions')->fetch_assoc()['n']);
    },
    'payload shape' => function (): void {
        portal_assert_same(['title' => 'Break ending soon','body' => '2 minutes left','data' => ['type' => 'break_ending']], json_decode(portal_push_payload('Break ending soon', '2 minutes left', ['type' => 'break_ending']), true));
    },
    'vapid route returns 503 when unset' => function (): void {
        portal_assert_api_error(fn () => portal_handle_vapid_key(portal_test_db(), ['idno' => '1015'], []), 'push_not_configured', 503);
    },
];
