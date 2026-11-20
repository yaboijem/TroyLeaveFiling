<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/attendance.php';
require dirname(__DIR__) . '/lib/notifications.php';
require dirname(__DIR__) . '/lib/push.php';
$options = getopt('', ['dry-run']);
$db = portal_db();
$due = portal_due_break_alerts($db);
$created = 0;
$pushed = 0;
foreach ($due as $idno) {
    echo 'due=' . $idno . PHP_EOL;
    if (!isset($options['dry-run'])) {
        $pending = portal_pending_break_alerts($db, $idno);
        $created += portal_materialize_break_alerts($db, $idno);
        foreach ($pending as $alert) {
            portal_push_send($db, $idno, $alert['title'], 'Check your current break and return when ready.', ['punch_id' => $alert['punch_id']]);
            if (portal_push_public_key() !== null && class_exists(\Minishlink\WebPush\WebPush::class)) $pushed++;
        }
    }
}
echo (isset($options['dry-run']) ? 'dry-run ' : '') . "created={$created} pushed={$pushed}" . PHP_EOL;
