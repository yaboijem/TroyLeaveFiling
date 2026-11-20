<?php
declare(strict_types=1);

/**
 * Live portal configuration.
 * Never web-served: .htaccess denies lib/.
 */
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'root',
        'pass' => '',
        'name' => 'troyserverpayroll',
    ],

    'public_base' => '/TroyLeaveFiling/portal',
    'uploads' => dirname(__DIR__) . '/uploads',
    'timezone' => 'Asia/Manila',
    'login_sleep_us' => 300000,

    // Empty keys disable device push; the rest of the portal still works.
    'vapid' => [
        'subject' => '',
        'public_key' => '',
        'private_key' => '',
    ],

    // TODO: replace with the real office coordinates before go-live.
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
