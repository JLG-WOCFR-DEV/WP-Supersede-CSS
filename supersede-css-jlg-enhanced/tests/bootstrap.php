<?php
declare(strict_types=1);

use SSC\Tests\Support\WordPressInstaller;

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = dirname(__DIR__) . '/vendor/wp-phpunit/wp-phpunit';
}

require __DIR__ . '/Support/WordPressInstaller.php';

$wordpressAvailable = true;

try {
    WordPressInstaller::ensure();
} catch (\RuntimeException $exception) {
    $wordpressAvailable = false;
    fwrite(
        STDERR,
        '[wp-phpunit] WordPress test suite unavailable: ' . $exception->getMessage() . PHP_EOL .
        'Falling back to offline bootstrap (tests relying on WordPress will be skipped).' . PHP_EOL
    );
}

if ($wordpressAvailable && file_exists($_tests_dir . '/includes/functions.php')) {
    require $_tests_dir . '/includes/functions.php';

    require dirname(__DIR__) . '/vendor/autoload.php';

    tests_add_filter('muplugins_loaded', static function (): void {
        require dirname(__DIR__) . '/supersede-css-jlg.php';
    });

    require $_tests_dir . '/includes/bootstrap.php';

    return;
}

require __DIR__ . '/offline/bootstrap.php';
