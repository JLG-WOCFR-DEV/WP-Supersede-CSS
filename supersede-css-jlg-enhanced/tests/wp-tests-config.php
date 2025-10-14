<?php
/**
 * PHPUnit configuration for the Supersede CSS plugin.
 */

define('DB_NAME', getenv('WP_TESTS_DB_NAME') ?: 'wordpress_test');
define('DB_USER', getenv('WP_TESTS_DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('WP_TESTS_DB_PASSWORD') ?: 'root');
define('DB_HOST', getenv('WP_TESTS_DB_HOST') ?: '127.0.0.1');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

define('WP_TESTS_DOMAIN', getenv('WP_TESTS_DOMAIN') ?: 'example.org');
define('WP_TESTS_EMAIL', getenv('WP_TESTS_EMAIL') ?: 'admin@example.org');
define('WP_TESTS_TITLE', getenv('WP_TESTS_TITLE') ?: 'WordPress PHPUnit');
define('WP_PHP_BINARY', PHP_BINARY);

define('ABSPATH', dirname(__DIR__) . '/wordpress/');

define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills');

define('WP_DEBUG', true);

global $table_prefix;
$table_prefix = getenv('WP_TESTS_TABLE_PREFIX') ?: 'wptests_';
