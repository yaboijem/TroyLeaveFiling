<?php
declare(strict_types=1);
require_once __DIR__ . '/evidence_helpers.php';

return [
    'unread handler uses client count envelope' => function (): void {
        portal_assert_same(['count' => 0], portal_handle_notification_unread(portal_test_db(), ['idno' => '1015'], []));
    },
    'notify inserts and returns an id' => function (): void {
        portal_assert_true(portal_notify(portal_test_db(), '1015', 'Hello', 'Message') > 0);
    },
    'dedupe key inserts once' => function (): void {
        $db = portal_test_db();
        portal_notify($db, '1015', 'Hello', 'Message', [], 'unique-test');
        portal_assert_null(portal_notify($db, '1015', 'Hello', 'Message', [], 'unique-test'));
        portal_assert_same(1, portal_unread_count($db, '1015'));
    },
    'list is newest first and scoped to employee' => function (): void {
        $db = portal_test_db();
        portal_set_test_now('2026-11-19 08:00:00');
        portal_notify($db, '1015', 'Old', 'Message');
        portal_notify($db, '1016', 'Other', 'Message');
        portal_set_test_now('2026-11-19 09:00:00');
        portal_notify($db, '1015', 'New', 'Message');
        portal_assert_same(['New','Old'], array_column(portal_notifications_page($db, '1015', 1)['data'], 'title'));
    },
    'unread count' => function (): void {
        $db = portal_test_db();
        $id = portal_notify($db, '1015', 'One', 'Body');
        portal_notify($db, '1015', 'Two', 'Body');
        portal_notify($db, '1015', 'Three', 'Body');
        portal_mark_read($db, '1015', (string) $id);
        portal_assert_same(2, portal_unread_count($db, '1015'));
    },
    'read one sets read_at' => function (): void {
        $db = portal_test_db();
        $id = portal_notify($db, '1015', 'One', 'Body');
        portal_assert_null(portal_mark_read($db, '1016', (string) $id));
        portal_set_test_now('2026-11-19 09:00:00');
        portal_assert_same('2026-11-19T09:00:00+08:00', portal_mark_read($db, '1015', (string) $id)['read_at']);
    },
    'read all marks all' => function (): void {
        $db = portal_test_db();
        portal_notify($db, '1015', 'One', 'Body');
        portal_notify($db, '1015', 'Two', 'Body');
        portal_assert_same(2, portal_mark_all_read($db, '1015'));
        portal_assert_same(0, portal_unread_count($db, '1015'));
    },
    'delete one' => function (): void {
        $db = portal_test_db();
        $id = portal_notify($db, '1015', 'One', 'Body');
        portal_assert_same(false, portal_delete_notification($db, '1016', (string) $id));
        portal_assert_same(true, portal_delete_notification($db, '1015', (string) $id));
    },
    'delete all' => function (): void {
        $db = portal_test_db();
        portal_notify($db, '1015', 'One', 'Body');
        portal_notify($db, '1016', 'Two', 'Body');
        portal_assert_same(1, portal_delete_all($db, '1015'));
        portal_assert_same(1, portal_unread_count($db, '1016'));
    },
    'notification dto id is string and payload object' => function (): void {
        portal_notify(portal_test_db(), '1015', 'One', 'Body', ['kind' => 'test']);
        $dto = portal_notifications_page(portal_test_db(), '1015', 1)['data'][0];
        portal_assert_true(is_string($dto['id']));
        portal_assert_same(['kind' => 'test'], $dto['payload']);
    },
    'fraud flag creates one notification' => function (): void {
        $db = portal_test_db();
        $p = portal_test_evidence();
        $p['is_within_radius'] = 0;
        portal_evaluate_fraud($db, $p, null, $p['punched_at']);
        portal_evaluate_fraud($db, $p, null, $p['punched_at']);
        $row = $db->query('SELECT dedupe_key FROM portal_notifications')->fetch_assoc();
        portal_assert_same("fraud:{$p['id']}:out_of_radius", $row['dedupe_key'] ?? null);
        portal_assert_same(1, portal_unread_count($db, '1015'));
    },
];
