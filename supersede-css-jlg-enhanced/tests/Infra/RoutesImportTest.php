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
    function wp_kses($string, $allowed_html)
    {
        unset($allowed_html);

        return strip_tags((string) $string);
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

$ssc_options_store['ssc_tokens_css'] = '';
$ssc_options_store['ssc_tokens_registry'] = [];

$tokensCss = ":root {\n    --primary-color: #123456;\n    --spacing-md: 16px;\n}";
$tokensResult = $applyMethod->invoke($routes, [
    'ssc_tokens_css' => $tokensCss,
]);

$sanitizedCss = \SSC\Support\CssSanitizer::sanitize($tokensCss);
$expectedTokens = \SSC\Support\TokenRegistry::convertCssToRegistry($sanitizedCss);

if ($expectedTokens === []) {
    fwrite(STDERR, "Expected tokens should not be empty for the provided CSS." . PHP_EOL);
    exit(1);
}

$expectedCss = \SSC\Support\TokenRegistry::tokensToCss($expectedTokens);

if (!is_array($tokensResult) || !in_array('ssc_tokens_css', $tokensResult['applied'] ?? [], true)) {
    fwrite(STDERR, "Token CSS import should be reported as applied." . PHP_EOL);
    exit(1);
}

if ($ssc_options_store['ssc_tokens_registry'] !== $expectedTokens) {
    fwrite(STDERR, "Imported token CSS should update the token registry." . PHP_EOL);
    exit(1);
}

if ($ssc_options_store['ssc_tokens_css'] !== $expectedCss) {
    fwrite(STDERR, "Imported token CSS should be persisted with normalized formatting." . PHP_EOL);
    exit(1);
}
