<?php
declare(strict_types=1);

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = dirname(__DIR__) . '/vendor/wp-phpunit/wp-phpunit';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    fwrite(STDERR, "Could not find the WordPress test suite. Set WP_TESTS_DIR or run composer install." . PHP_EOL);
    exit(1);
}

require $_tests_dir . '/includes/functions.php';

require dirname(__DIR__) . '/vendor/autoload.php';

tests_add_filter('muplugins_loaded', static function (): void {
    require dirname(__DIR__) . '/supersede-css-jlg.php';
});

require $_tests_dir . '/includes/bootstrap.php';
