<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$failed = false;
function preflight_check(string $label, bool $ok, bool $warning = false): void
{
    global $failed;
    echo ($ok ? 'OK ' : ($warning ? 'WARN ' : 'FAIL ')) . $label . PHP_EOL;
    if (!$ok && !$warning) $failed = true;
}
preflight_check('PHP >= 8.2', PHP_VERSION_ID >= 80200);
$config = getenv('PORTAL_CONFIG_PATH') ?: dirname(__DIR__) . '/lib/config.php';
preflight_check('config file exists', is_file($config));
if (!is_file($config)) exit(1);
require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/db.php';
$cfg = portal_config();
foreach (['mysqli','gd','fileinfo','curl','mbstring','openssl'] as $extension) {
    preflight_check('extension ' . $extension, extension_loaded($extension));
}
preflight_check('timezone Asia/Manila', date_default_timezone_get() === 'Asia/Manila');
preflight_check('session cookie path /', ini_get('session.cookie_path') === '/');
preflight_check('office coordinates set', (float) $cfg['branch']['latitude'] !== 0.0 && (float) $cfg['branch']['longitude'] !== 0.0);
preflight_check('uploads/attendance writable', is_dir($cfg['uploads'] . '/attendance') && is_writable($cfg['uploads'] . '/attendance'));
$deny = dirname(__DIR__) . '/uploads/.htaccess';
preflight_check('private upload access denied', is_file($deny) && str_contains(file_get_contents($deny), 'Require all denied'));
try {
    $db = portal_db();
    preflight_check('database connected', true);
    $tables = array_column($db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM), 0);
    foreach (['attendance','attendance_punches','branches','dtr_employee_profile','app_settings','consents','home_locations','portal_notifications','push_subscriptions','fraud_flags','login_attempts'] as $table) {
        preflight_check('table ' . $table, in_array($table, $tables, true));
    }
} catch (Throwable $e) { preflight_check('database: ' . $e->getMessage(), false); }
preflight_check('VAPID keys configured (optional)', !empty($cfg['vapid']['public_key']) && !empty($cfg['vapid']['private_key']), true);
preflight_check('push vendor installed (optional)', is_file(dirname(__DIR__) . '/vendor/autoload.php'), true);
echo 'WARN Confirm HTTPS, Apache AllowOverride/mod_rewrite, and Windows Task Scheduler jobs manually.' . PHP_EOL;
exit($failed ? 1 : 0);
