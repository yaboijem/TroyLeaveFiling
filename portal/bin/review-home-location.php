<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/user.php';
require dirname(__DIR__) . '/lib/notifications.php';
require dirname(__DIR__) . '/lib/home_location.php';
try {
    $args = portal_parse_review_args(array_slice($argv, 1));
    echo json_encode(portal_review_home_location(portal_db(), $args['id'], $args['action'], $args['note'], $args['by']), JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . PHP_EOL); exit(1); }
