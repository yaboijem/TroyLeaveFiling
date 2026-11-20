<?php
declare(strict_types=1);
function boundary_test_request(?array $config): array
{
    $script = tempnam(__DIR__, 'boundary-');
    $configPath = tempnam(__DIR__, 'config-');
    if ($config !== null) file_put_contents($configPath, '<?php return ' . var_export($config, true) . ';');
    else unlink($configPath);
    file_put_contents($script, '<?php define("PORTAL_CONFIG_PATH",' . var_export($configPath, true) . '); require ' . var_export(dirname(__DIR__) . '/api/index.php', true) . ';');
    try {
        exec('"' . PHP_BINARY . '" -d display_errors=0 "' . $script . '" 2>NUL', $lines);
        return json_decode(implode('', $lines), true) ?: [];
    } finally { unlink($script); if (is_file($configPath)) unlink($configPath); }
}
return [
    'missing configuration returns JSON' => function (): void {
        portal_assert_same('server_error', boundary_test_request(null)['code'] ?? null);
    },
    'database connection failure returns JSON' => function (): void {
        $config = portal_config();
        $config['db']['port'] = 1;
        portal_assert_same('server_error', boundary_test_request($config)['code'] ?? null);
    },
];
