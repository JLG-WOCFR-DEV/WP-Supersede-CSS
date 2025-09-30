<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('WP_UNINSTALL_PLUGIN')) {
    define('WP_UNINSTALL_PLUGIN', true);
}

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

/** @var list<string> $ssc_deleted_options */
$ssc_deleted_options = [];

global $ssc_deleted_options;

if (!function_exists('delete_option')) {
    function delete_option(string $option_name): void
    {
        global $ssc_deleted_options;

        $ssc_deleted_options[] = $option_name;
    }
}

if (!function_exists('is_multisite')) {
    function is_multisite(): bool
    {
        return false;
    }
}

final class UninstallCleanupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $ssc_deleted_options;
        $ssc_deleted_options = [];
    }

    public function testUninstallRemovesCachedOptions(): void
    {
        require __DIR__ . '/../../uninstall.php';

        global $ssc_deleted_options;
        $this->assertContains('ssc_css_cache', $ssc_deleted_options);
        $this->assertContains('ssc_css_cache_meta', $ssc_deleted_options);
    }
}
