<?php
declare(strict_types=1);

/**
 * Copy this file to lib/config.php and fill in the real values.
 * lib/config.php is never served: .htaccess denies lib/.
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'pass' => '',
        'name' => 'troyserverpayroll',
    ],

    // Public URL path of the portal, without a trailing slash.
    'public_base' => '/TroyLeaveFiling/portal',

    // Absolute path to the private uploads directory.
    'uploads' => dirname(__DIR__) . '/uploads',

    'timezone' => 'Asia/Manila',

    // Delay applied before rejecting bad credentials, in microseconds.
    'login_sleep_us' => 300000,

    // Leave both keys empty to disable device push; the rest of the portal works.
    'vapid' => [
        'subject' => '',
        'public_key' => '',
        'private_key' => '',
    ],

    // Office geofence used for onsite punches.
    'branch' => [
        'name' => 'Main Office',
        'code' => 'MAIN',
        'latitude' => 0.0,
        'longitude' => 0.0,
        'radius_meters' => 200,
        'accuracy_ceiling_meters' => 100,
        'accuracy_allowance_meters' => 20,
    ],
];
