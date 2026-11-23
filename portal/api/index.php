<?php
declare(strict_types=1);

/**
 * Single front controller for /TroyLeaveFiling/portal/api/*.
 *
 * .htaccess routes every non-file request under api/ here; the route table
 * in api/routes.php decides the handler.
 */

ini_set('display_errors', '0');
require_once dirname(__DIR__) . '/lib/errors.php';
require_once dirname(__DIR__) . '/lib/response.php';
try {
require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/db.php';
require dirname(__DIR__) . '/lib/router.php';
require dirname(__DIR__) . '/lib/auth.php';
require dirname(__DIR__) . '/lib/user.php';
require dirname(__DIR__) . '/lib/login.php';
require dirname(__DIR__) . '/lib/gps.php';
require dirname(__DIR__) . '/lib/consent.php';
require dirname(__DIR__) . '/lib/settings.php';
require dirname(__DIR__) . '/lib/attendance.php';
require dirname(__DIR__) . '/lib/photos.php';
require dirname(__DIR__) . '/lib/fraud.php';
require dirname(__DIR__) . '/lib/history.php';
require dirname(__DIR__) . '/lib/notifications.php';
require dirname(__DIR__) . '/lib/home_location.php';
require dirname(__DIR__) . '/lib/admin_branches.php';
require dirname(__DIR__) . '/lib/admin_employees.php';
require dirname(__DIR__) . '/lib/admin_home_locations.php';
require dirname(__DIR__) . '/lib/push.php';

$db = portal_db();
$routes = require __DIR__ . '/routes.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/TroyLeaveFiling/portal/api/index.php'));
$base = rtrim(dirname(dirname($script)), '/');
$uri = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');

if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}

$path = '/' . ltrim($uri, '/');

    $match = portal_route_match($method, $path, $routes);

    if ($match === null) {
        $allowed = portal_route_allowed_methods($path, $routes);

        if ($allowed !== []) {
            header('Allow: ' . implode(', ', $allowed));
            portal_error('Method not allowed.', 'method_not_allowed', 405);
        }

        portal_error('Not found.', 'not_found', 404);
    }

    $auth = null;

    if (!$match['public']) {
        $auth = portal_require_auth($db);
    }

    if (in_array($method, ['POST', 'PATCH', 'DELETE'], true)) {
        portal_require_ajax();
    }

    $result = ($match['handler'])($db, $auth, $match['params']);
    $status = 200;

    if (
        is_array($result)
        && isset($result['status'], $result['payload'])
        && is_int($result['status'])
        && is_array($result['payload'])
    ) {
        $status = $result['status'];
        $result = $result['payload'];
    }

    portal_json($result, $status);
} catch (PortalApiException $e) {
    $details = $e->details;
    $errors = null;

    if (isset($details['errors']) && is_array($details['errors'])) {
        $errors = $details['errors'];
        unset($details['errors']);
    }

    $payload = portal_error_payload($e->getMessage(), $e->errorCode, $details, $errors);

    if ($e->session !== null) {
        $payload['session'] = $e->session;
    }

    portal_json($payload, $e->status);
} catch (Throwable $e) {
    error_log(
        'Portal API error: ' . $e->getMessage()
        . ' in ' . $e->getFile() . ':' . $e->getLine()
    );

    portal_error('Something went wrong. Please try again.', 'server_error', 500);
}
