<?php declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    define('WP_UNINSTALL_PLUGIN', true);
}

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

/** @var list<string> $ssc_deleted_options */
$ssc_deleted_options = [];

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

require __DIR__ . '/../../uninstall.php';

if (!function_exists('assertOptionDeleted')) {
    function assertOptionDeleted(string $option_name, array $deleted_options): void
    {
        if (!in_array($option_name, $deleted_options, true)) {
            fwrite(
                STDERR,
                sprintf(
                    'Failed asserting that option "%s" was deleted. Deleted options: %s' . PHP_EOL,
                    $option_name,
                    implode(', ', $deleted_options)
                )
            );

            exit(1);
        }
    }
}

assertOptionDeleted('ssc_css_cache', $ssc_deleted_options);
assertOptionDeleted('ssc_css_cache_meta', $ssc_deleted_options);
