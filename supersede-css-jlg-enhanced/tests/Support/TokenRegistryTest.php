<?php declare(strict_types=1);

use SSC\Support\TokenRegistry;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        return trim(strip_tags((string) $value));
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($value)
    {
        return trim(strip_tags((string) $value));
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses(string $string, array $allowed_html = []): string
    {
        return strip_tags($string);
    }
}

if (!function_exists('wp_kses_bad_protocol')) {
    function wp_kses_bad_protocol(string $string, array $allowed_protocols): string
    {
        return $string;
    }
}

if (!function_exists('wp_allowed_protocols')) {
    function wp_allowed_protocols(): array
    {
        return ['http', 'https'];
    }
}

/** @var array<string, mixed> $ssc_options_store */
$ssc_options_store = [];

global $ssc_options_store;

if (!function_exists('get_option')) {
    function get_option($name, $default = false)
    {
        global $ssc_options_store;

        return $ssc_options_store[$name] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($name, $value, $autoload = false)
    {
        global $ssc_options_store;

        $ssc_options_store[$name] = $value;

        return true;
    }
}

require_once __DIR__ . '/../../src/Support/CssSanitizer.php';
require_once __DIR__ . '/../../src/Support/TokenRegistry.php';

$normalized = TokenRegistry::normalizeRegistry([
    [
        'name' => '--BrandPrimary',
        'value' => '#3366ff',
        'type' => 'color',
        'description' => 'Primary brand color.',
        'group' => 'Brand',
    ],
]);

if ($normalized === [] || $normalized[0]['name'] !== '--BrandPrimary') {
    fwrite(STDERR, 'TokenRegistry::normalizeRegistry should preserve the original token casing.' . PHP_EOL);
    exit(1);
}

$registry = TokenRegistry::saveRegistry([
    [
        'name' => '--BrandPrimary',
        'value' => '#3366ff',
        'type' => 'color',
        'description' => 'Primary brand color.',
        'group' => 'Brand',
    ],
]);

if ($registry === [] || $registry[0]['name'] !== '--BrandPrimary') {
    fwrite(STDERR, 'TokenRegistry::saveRegistry should preserve the original token casing.' . PHP_EOL);
    exit(1);
}

if (!isset($ssc_options_store['ssc_tokens_css']) || !is_string($ssc_options_store['ssc_tokens_css'])) {
    fwrite(STDERR, 'TokenRegistry::saveRegistry should persist CSS using the original token name.' . PHP_EOL);
    exit(1);
}

if (strpos($ssc_options_store['ssc_tokens_css'], '--BrandPrimary') === false) {
    fwrite(STDERR, 'Persisted CSS should contain the original token casing.' . PHP_EOL);
    exit(1);
}

$roundTripRegistry = TokenRegistry::convertCssToRegistry($ssc_options_store['ssc_tokens_css']);

if ($roundTripRegistry === [] || $roundTripRegistry[0]['name'] !== '--BrandPrimary') {
    fwrite(STDERR, 'convertCssToRegistry should keep the original casing after import.' . PHP_EOL);
    exit(1);
}

$regeneratedCss = TokenRegistry::tokensToCss($roundTripRegistry);

if (strpos($regeneratedCss, '--BrandPrimary') === false) {
    fwrite(STDERR, 'tokensToCss should keep the original casing when exporting.' . PHP_EOL);
    exit(1);
}
