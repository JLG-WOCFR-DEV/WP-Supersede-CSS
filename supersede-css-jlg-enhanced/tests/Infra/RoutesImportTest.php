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

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($value, $options = 0, $depth = 512)
    {
        return json_encode($value, $options, $depth);
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses($string, $allowed_html)
    {
        unset($allowed_html);

        return strip_tags((string) $string);
    }
}

if (!function_exists('wp_kses_bad_protocol')) {
    function wp_kses_bad_protocol(string $string, array $allowed_protocols): string
    {
        unset($allowed_protocols);

        return $string;
    }
}

if (!function_exists('wp_allowed_protocols')) {
    function wp_allowed_protocols(): array
    {
        return ['http', 'https'];
    }
}

if (!function_exists('absint')) {
    function absint($value)
    {
        return abs((int) $value);
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

$singleTokenCss = ":root {\n    --solitary-token: #bada55\n}";
$singleTokenSanitized = \SSC\Support\CssSanitizer::sanitize($singleTokenCss);
$singleTokenRegistry = \SSC\Support\TokenRegistry::convertCssToRegistry($singleTokenSanitized);

if (count($singleTokenRegistry) !== 1) {
    fwrite(STDERR, "A sanitized CSS payload with a single token should yield exactly one registry entry." . PHP_EOL);
    exit(1);
}

$singleToken = $singleTokenRegistry[0];
if ($singleToken['name'] !== '--solitary-token' || $singleToken['value'] !== '#bada55') {
    fwrite(STDERR, "Single token CSS without trailing semicolon should preserve the token name and value." . PHP_EOL);
    exit(1);
}

$noSemicolonCss = ":root {\n    --first-token: 10px;\n    --last-token: 1rem\n}";
$noSemicolonSanitized = \SSC\Support\CssSanitizer::sanitize($noSemicolonCss);
$noSemicolonRegistry = \SSC\Support\TokenRegistry::convertCssToRegistry($noSemicolonSanitized);

if (count($noSemicolonRegistry) !== 2) {
    fwrite(STDERR, "CSS without a trailing semicolon on the last declaration should still return all tokens." . PHP_EOL);
    exit(1);
}

if ($noSemicolonRegistry[0]['name'] !== '--first-token' || $noSemicolonRegistry[0]['value'] !== '10px') {
    fwrite(STDERR, "First token should remain unchanged when converting CSS with missing trailing semicolon." . PHP_EOL);
    exit(1);
}

if ($noSemicolonRegistry[1]['name'] !== '--last-token' || $noSemicolonRegistry[1]['value'] !== '1rem') {
    fwrite(STDERR, "Last token without a trailing semicolon should be preserved during CSS conversion." . PHP_EOL);
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

$duplicateTokensCss = ":root {\n    --duplicate-token: 4px;\n    --duplicate-token: 8px;\n}";
$duplicateSanitized = \SSC\Support\CssSanitizer::sanitize($duplicateTokensCss);
$duplicateRegistry = \SSC\Support\TokenRegistry::convertCssToRegistry($duplicateSanitized);

if (count($duplicateRegistry) !== 1) {
    fwrite(STDERR, "Duplicate CSS tokens should be collapsed into a single registry entry." . PHP_EOL);
    exit(1);
}

$duplicateToken = $duplicateRegistry[0];

if ($duplicateToken['name'] !== '--duplicate-token' || $duplicateToken['value'] !== '8px') {
    fwrite(STDERR, "Duplicate token conversion should keep the last value encountered." . PHP_EOL);
    exit(1);
}

$ssc_options_store['ssc_tokens_css'] = '';
$ssc_options_store['ssc_tokens_registry'] = [];

$duplicateImportResult = $applyMethod->invoke($routes, [
    'ssc_tokens_css' => $duplicateTokensCss,
]);

if (!is_array($duplicateImportResult) || !in_array('ssc_tokens_css', $duplicateImportResult['applied'] ?? [], true)) {
    fwrite(STDERR, "Duplicate token CSS import should still be reported as applied." . PHP_EOL);
    exit(1);
}

$storedRegistry = $ssc_options_store['ssc_tokens_registry'];

if (!is_array($storedRegistry) || count($storedRegistry) !== 1) {
    fwrite(STDERR, "Token registry should contain a single entry after importing duplicate CSS tokens." . PHP_EOL);
    exit(1);
}

$storedToken = $storedRegistry[0];

if ($storedToken['name'] !== '--duplicate-token' || $storedToken['value'] !== '8px') {
    fwrite(STDERR, "Token registry should preserve the last duplicate token value after import." . PHP_EOL);
    exit(1);
}

$storedCss = $ssc_options_store['ssc_tokens_css'];
$expectedStoredCss = \SSC\Support\TokenRegistry::tokensToCss($storedRegistry);

if ($storedCss !== $expectedStoredCss) {
    fwrite(STDERR, "Persisted CSS should only contain the deduplicated token definition." . PHP_EOL);
    exit(1);
}

\SSC\Support\TokenRegistry::saveRegistry([
    [
        'name' => '--existing-token',
        'value' => '#abcdef',
        'type' => 'color',
        'description' => '',
        'group' => 'Legacy',
    ],
]);

$emptyTokensResult = $applyMethod->invoke($routes, [
    'ssc_tokens_css' => '',
]);

if (!is_array($emptyTokensResult) || !in_array('ssc_tokens_css', $emptyTokensResult['applied'] ?? [], true)) {
    fwrite(STDERR, "Empty token CSS import should be reported as applied." . PHP_EOL);
    exit(1);
}

if ($ssc_options_store['ssc_tokens_registry'] !== []) {
    fwrite(STDERR, "Empty token CSS import should reset the token registry." . PHP_EOL);
    exit(1);
}

$expectedEmptyCss = \SSC\Support\TokenRegistry::tokensToCss([]);

if ($ssc_options_store['ssc_tokens_css'] !== $expectedEmptyCss) {
    fwrite(STDERR, "Empty token CSS import should persist an empty CSS template." . PHP_EOL);
    exit(1);
}

$objectPayload = new stdClass();
$objectPayload->title = '<strong>Title</strong>';
$objectPayload->count = 5;
$objectPayload->nested = new stdClass();
$objectPayload->nested->note = '<em>Nested</em>';

$jsonOnlyObject = new class() implements JsonSerializable {
    public function jsonSerialize(): mixed
    {
        return ['danger' => '<script>alert(1)</script>'];
    }
};

$ssc_options_store['ssc_settings'] = [];

$objectImportResult = $applyMethod->invoke($routes, [
    'ssc_settings' => [
        'object_payload' => $objectPayload,
        'json_only_object' => $jsonOnlyObject,
    ],
]);

$expectedSettings = [
    'object_payload' => [
        'title' => 'Title',
        'count' => 5,
        'nested' => [
            'note' => 'Nested',
        ],
    ],
    'json_only_object' => '{"danger":"alert(1)"}',
];

if ($ssc_options_store['ssc_settings'] !== $expectedSettings) {
    fwrite(STDERR, "Object payloads should be sanitized recursively or serialized when needed." . PHP_EOL);
    exit(1);
}

if (!is_array($objectImportResult) || !in_array('ssc_settings', $objectImportResult['applied'] ?? [], true)) {
    fwrite(STDERR, "Object payload import should be reported as applied." . PHP_EOL);
    exit(1);
}
