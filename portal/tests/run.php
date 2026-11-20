<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$filter = $argv[1] ?? null;
$files = glob(__DIR__ . '/*_test.php') ?: [];
sort($files);

if (is_string($filter) && $filter !== '') {
    $files = array_values(array_filter(
        $files,
        static fn (string $file): bool => str_contains(basename($file), $filter)
    ));
}

if ($files === []) {
    fwrite(STDERR, "No test files matched.\n");
    exit(1);
}

$passed = 0;
$failed = 0;
$number = 0;

foreach ($files as $file) {
    /** @var array<string, callable> $tests */
    $tests = require $file;

    if (!is_array($tests)) {
        fwrite(STDERR, 'Test file did not return an array: ' . $file . "\n");
        $failed++;
        continue;
    }

    foreach ($tests as $name => $test) {
        $number++;

        if (!is_callable($test)) {
            $failed++;
            echo "not ok {$number} - {$name}\n  Test is not callable.\n";
            continue;
        }

        try {
            portal_test_reset();
            portal_set_test_now(null);
            $test();
            $passed++;
            echo "ok {$number} - {$name}\n";
        } catch (Throwable $e) {
            $failed++;
            echo "not ok {$number} - {$name}\n";
            echo '  ' . str_replace("\n", "\n  ", $e->getMessage()) . "\n";
        } finally {
            portal_set_test_now(null);
        }
    }
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
