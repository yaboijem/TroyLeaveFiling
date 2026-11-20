<?php
declare(strict_types=1);

/**
 * Shared bootstrap for web endpoints and CLI scripts.
 *
 * Loads configuration, error handling and the JSON response helpers.
 * Database access is loaded separately so tests and CLIs control it.
 */

require_once __DIR__ . '/errors.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/response.php';

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$portalLogDir = dirname(__DIR__) . '/logs';

if (is_dir($portalLogDir) && is_writable($portalLogDir)) {
    ini_set('error_log', $portalLogDir . '/php-error.log');
}

date_default_timezone_set((string) (portal_config()['timezone'] ?? 'Asia/Manila'));

if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');

    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}
