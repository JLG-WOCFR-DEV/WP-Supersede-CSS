<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

define('SSC_WP_TEST_SUITE_AVAILABLE', false);

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__, 2));
}

if (!class_exists('WP_UnitTestCase')) {
    /**
     * Minimal shim so that PHPUnit test cases extending WordPress' base class are reported as skipped
     * when the WordPress test suite cannot be bootstrapped (e.g. due to offline environments).
     */
    abstract class WP_UnitTestCase extends TestCase
    {
        protected function setUp(): void
        {
            $this->markTestSkipped('WordPress test suite is not available in this environment.');
        }
    }
}

// Provide a very small autoloader for the SSC namespace so that unit tests can exercise classes directly
// without requiring the full WordPress bootstrap sequence.
if (!class_exists('SSC\\AutoloadPlaceholder', false)) {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'SSC\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $path = dirname(__DIR__, 2) . '/src/' . str_replace('\\', '/', $relative) . '.php';

        if (is_file($path)) {
            require_once $path;
        }
    });
}
