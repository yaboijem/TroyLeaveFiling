<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/retention.php';
$options = getopt('', ['days:', 'dry-run']);
$days = filter_var($options['days'] ?? 730, FILTER_VALIDATE_INT);
if ($days === false || $days < 30) {
    fwrite(STDERR, "Usage: php bin/purge.php --days=730 [--dry-run]\n");
    exit(1);
}
echo json_encode(portal_purge(portal_db(), $days, isset($options['dry-run'])), JSON_PRETTY_PRINT) . PHP_EOL;
