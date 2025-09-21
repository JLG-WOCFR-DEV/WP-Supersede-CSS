<?php declare(strict_types=1);

use SSC\Infra\Routes;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback): void {}
}

if (!function_exists('register_rest_route')) {
    function register_rest_route(...$args): void {}
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        $key = strtolower((string) $key);

        return preg_replace('/[^a-z0-9_]/', '', $key);
    }
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
    function wp_kses($string, $allowed_html = [], $allowed_protocols = [])
    {
        return strip_tags((string) $string);
    }
}

if (!function_exists('wp_kses_bad_protocol')) {
    function wp_kses_bad_protocol($string, $allowed_protocols)
    {
        return (string) $string;
    }
}

if (!function_exists('wp_allowed_protocols')) {
    function wp_allowed_protocols()
    {
        return ['http', 'https', 'data'];
    }
}

/** @var array<string, mixed> $ssc_options_store */
$ssc_options_store = [
    'ssc_admin_log' => [
        [
            't' => '2024-01-01T00:00:00Z',
            'user' => 'alice',
            'action' => 'existing',
            'data' => ['note' => 'original'],
        ],
    ],
];

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
require_once __DIR__ . '/../../src/Infra/Logger.php';
require_once __DIR__ . '/../../src/Infra/Routes.php';

$originalLog = $ssc_options_store['ssc_admin_log'];

$routesReflection = new ReflectionClass(Routes::class);
$routes = $routesReflection->newInstanceWithoutConstructor();

$applyMethod = $routesReflection->getMethod('applyImportedOptions');
$applyMethod->setAccessible(true);

$result = $applyMethod->invoke($routes, [
    'ssc_admin_log' => 'not-an-array',
]);

if ($ssc_options_store['ssc_admin_log'] !== $originalLog) {
    fwrite(STDERR, "Existing admin log should remain unchanged when import is invalid." . PHP_EOL);
    exit(1);
}

if (!is_array($result) || !isset($result['skipped']) || !in_array('ssc_admin_log', $result['skipped'], true)) {
    fwrite(STDERR, "Invalid admin log import should be reported as skipped." . PHP_EOL);
    exit(1);
}

$ssc_options_store['ssc_tokens_registry'] = [];
$ssc_options_store['ssc_tokens_css'] = '';

$tokensCss = ":root {\n    --brand-color: #123456;\n    --spacing-small: 8px;\n}";
$sanitizedTokensCss = \SSC\Support\CssSanitizer::sanitize($tokensCss);
$expectedRegistry = \SSC\Support\TokenRegistry::convertCssToRegistry($sanitizedTokensCss);

if ($expectedRegistry === []) {
    fwrite(STDERR, "Test fixture should yield at least one token." . PHP_EOL);
    exit(1);
}

$result = $applyMethod->invoke($routes, [
    'ssc_tokens_css' => $tokensCss,
]);

if (!is_array($result) || !isset($result['applied']) || !in_array('ssc_tokens_css', $result['applied'], true)) {
    fwrite(STDERR, "Token CSS import should be reported as applied." . PHP_EOL);
    exit(1);
}

if ($ssc_options_store['ssc_tokens_registry'] !== $expectedRegistry) {
    fwrite(STDERR, "Token registry should reflect imported CSS tokens." . PHP_EOL);
    exit(1);
}

$expectedCss = \SSC\Support\TokenRegistry::tokensToCss($expectedRegistry);

if ($ssc_options_store['ssc_tokens_css'] !== $expectedCss) {
    fwrite(STDERR, "Token CSS option should be synchronized with registry output." . PHP_EOL);
    exit(1);
}
