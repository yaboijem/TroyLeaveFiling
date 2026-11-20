<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/home_location.php';
require dirname(__DIR__) . '/lib/profile.php';
try {
    $args = portal_parse_set_profile_args(array_slice($argv, 1));
    echo json_encode(portal_set_profile(portal_db(), $args['idno'], $args['branch'], $args['arrangement']), JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . PHP_EOL); exit(1); }
