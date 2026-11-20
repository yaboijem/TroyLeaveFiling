<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/seed.php';

$result = portal_seed(portal_db(), portal_config());

echo 'Branch id: ' . $result['branch_id'] . PHP_EOL;
echo 'Profiles: ' . $result['profiles'] . PHP_EOL;
