<?php
declare(strict_types=1);

/**
 * Configuration and clock helpers.
 */

function portal_config(): array
{
    static $config = null;

    if ($config === null) {
        $path = defined('PORTAL_CONFIG_PATH')
            ? PORTAL_CONFIG_PATH
            : (getenv('PORTAL_CONFIG_PATH') ?: __DIR__ . '/config.php');

        if (!is_string($path) || !is_file($path)) {
            throw new RuntimeException('Portal config file is missing: ' . (string) $path);
        }

        $loaded = require $path;

        if (!is_array($loaded)) {
            throw new RuntimeException('Portal config must return an array: ' . $path);
        }

        $config = $loaded;
    }

    return $config;
}

function portal_tz(): DateTimeZone
{
    static $tz = null;

    if ($tz === null) {
        $name = portal_config()['timezone'] ?? 'Asia/Manila';
        $tz = new DateTimeZone((string) $name);
    }

    return $tz;
}

function portal_set_test_now(?string $value): void
{
    $GLOBALS['portal_test_now'] = $value;
}

function portal_now(): DateTimeImmutable
{
    $test = $GLOBALS['portal_test_now'] ?? null;

    if (is_string($test) && $test !== '') {
        return new DateTimeImmutable($test, portal_tz());
    }

    return new DateTimeImmutable('now', portal_tz());
}

function portal_iso(?string $datetime): ?string
{
    if ($datetime === null || trim($datetime) === '') {
        return null;
    }

    try {
        return (new DateTimeImmutable($datetime, portal_tz()))->format('c');
    } catch (Throwable) {
        return null;
    }
}
