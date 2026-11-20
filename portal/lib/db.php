<?php
declare(strict_types=1);

/**
 * Database connection helpers. Prepared statements only in callers.
 */

function portal_db_connect(array $db): mysqli
{
    $name = (string) ($db['name'] ?? '');

    if (defined('PORTAL_TESTING') && PORTAL_TESTING && !str_ends_with($name, '_test')) {
        throw new RuntimeException(
            'Refusing to connect to a non-test database; the name must end with _test: ' . $name
        );
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli(
        (string) ($db['host'] ?? '127.0.0.1'),
        (string) ($db['user'] ?? ''),
        (string) ($db['pass'] ?? ''),
        $name !== '' ? $name : null,
        (int) ($db['port'] ?? 3306)
    );

    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '+08:00'");

    return $conn;
}

function portal_db(): mysqli
{
    static $conn = null;

    if (!$conn instanceof mysqli) {
        $conn = portal_db_connect(portal_config()['db']);
    }

    return $conn;
}
