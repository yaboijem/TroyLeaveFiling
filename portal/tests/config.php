<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'pass' => '',
        'name' => 'troyserverpayroll_test',
    ],
    'public_base' => '/TroyLeaveFiling/portal',
    'uploads' => dirname(__DIR__) . '/uploads',
    'timezone' => 'Asia/Manila',
    'login_sleep_us' => 0,
    'vapid' => [
        'subject' => '',
        'public_key' => '',
        'private_key' => '',
    ],
    'branch' => [
        'name' => 'Main Office',
        'code' => 'MAIN',
        'latitude' => 14.5995,
        'longitude' => 120.9842,
        'radius_meters' => 200,
        'accuracy_ceiling_meters' => 100,
        'accuracy_allowance_meters' => 20,
    ],
];
