<?php
declare(strict_types=1);
require_once __DIR__ . '/alert_test.php';
return [
    'unread materialization remains pending for worker delivery' => function (): void {
        alert_test_break();
        portal_set_test_now('2026-11-19 12:13:00');
        portal_handle_notification_unread(portal_test_db(), ['idno' => '1015'], []);
        $sent = portal_deliver_pending_push(portal_test_db(), static fn () => true);
        portal_assert_same(1, $sent);
        portal_assert_same(0, portal_deliver_pending_push(portal_test_db(), static fn () => true));
    },
    'failed delivery stays pending and retries' => function (): void {
        portal_set_test_now('2026-11-19 08:00:00');
        portal_notify(portal_test_db(), '1015', 'Title', 'Body', ['type' => 'test'], 'retry');
        portal_assert_same(0, portal_deliver_pending_push(portal_test_db(), static fn () => false));
        $row = portal_test_db()->query('SELECT * FROM push_outbox')->fetch_assoc();
        portal_assert_null($row['delivered_at']);
        portal_assert_same(1, (int) $row['attempts']);
        portal_set_test_now('2026-11-19 08:06:00');
        portal_assert_same(1, portal_deliver_pending_push(portal_test_db(), static fn () => true));
    },
    'all notification origins enqueue delivery' => function (): void {
        $db = portal_test_db();
        portal_notify($db, '1015', 'Fraud', 'Flag', [], 'fraud:1:test');
        portal_notify($db, '1019', 'Home review', 'Approved', [], 'home-review:1:approved');
        portal_assert_same(2, portal_deliver_pending_push($db, static fn () => true));
    },
    'outbox follows caller transaction rollback' => function (): void {
        $db = portal_test_db();
        $db->begin_transaction();
        portal_notify($db, '1015', 'Title', 'Body', [], 'rollback');
        $db->rollback();
        portal_assert_same(0, (int) $db->query('SELECT COUNT(*) n FROM push_outbox')->fetch_assoc()['n']);
        portal_assert_same(0, portal_unread_count($db, '1015'));
    },
    'deleting visible alert never recreates event' => function (): void {
        $db = portal_test_db();
        alert_test_break();
        portal_set_test_now('2026-11-19 12:13:00');
        portal_materialize_break_alerts($db, '1015');
        portal_delete_all($db, '1015');
        portal_assert_same(0, portal_materialize_break_alerts($db, '1015'));
        portal_assert_same(0, portal_unread_count($db, '1015'));
    },
    'worker refuses to send inside caller transaction' => function (): void {
        $db = portal_test_db();
        $db->begin_transaction();
        try {
            try { portal_deliver_pending_push($db, static fn () => true); }
            catch (LogicException) { return; }
            throw new RuntimeException('Worker must refuse active transaction.');
        } finally { $db->rollback(); }
    },
];
