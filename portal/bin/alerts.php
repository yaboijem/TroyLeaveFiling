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
        $created += portal_materialize_break_alerts($db, $idno);
    }
}
if (!isset($options['dry-run'])) $pushed = portal_deliver_pending_push($db);
echo (isset($options['dry-run']) ? 'dry-run ' : '') . "created={$created} pushed={$pushed}" . PHP_EOL;
