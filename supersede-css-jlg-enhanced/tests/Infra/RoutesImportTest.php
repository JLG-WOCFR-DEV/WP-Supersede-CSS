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

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        /** @var mixed */
        private $data;

        private int $status;

        public function __construct($data = null, int $status = 200)
        {
            $this->data = $data;
            $this->status = $status;
        }

        public function get_status(): int
        {
            return $this->status;
        }

        /** @return mixed */
        public function get_data()
        {
            return $this->data;
        }
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        /** @var array<string, mixed> */
        private array $params;

        /** @var array<string, mixed>|null */
        private ?array $json;

        /**
         * @param array<string, mixed> $params
         * @param array<string, mixed>|null $json
         */
        public function __construct(array $params = [], ?array $json = null)
        {
            $this->params = $params;
            $this->json = $json;
        }

        public function get_param(string $key)
        {
            return $this->params[$key] ?? null;
        }

        /**
         * @return array<string, mixed>|null
         */
        public function get_json_params(): ?array
        {
            return $this->json ?? $this->params;
        }
    }
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

if (!function_exists('wp_check_invalid_utf8')) {
    function wp_check_invalid_utf8($string)
    {
        return trim(strip_tags((string) $string));
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        unset($domain);

        return $text;
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

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user()
    {
        return (object) [
            'ID' => 1,
            'user_login' => 'tester',
        ];
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

$sanitizeTokensMethod = $routesReflection->getMethod('sanitizeImportTokens');
$sanitizeTokensMethod->setAccessible(true);

$ssc_options_store['ssc_tokens_css'] = '__original_css__';
$ssc_options_store['ssc_tokens_registry'] = '__original_registry__';

$rawRegistryPayload = [
    [
        'name' => 'Primary Color',
        'value' => '#FFFFFF',
        'type' => 'color',
        'description' => '<strong>Important</strong>',
        'group' => 'Colors',
    ],
    [
        'name' => '',
        'value' => 'should-be-ignored',
        'type' => 'text',
        'description' => 'Unused',
        'group' => '',
    ],
];

$sanitizedRegistryPayload = $sanitizeTokensMethod->invoke($routes, $rawRegistryPayload);

if (!is_array($sanitizedRegistryPayload) || count($sanitizedRegistryPayload) !== 1) {
    fwrite(STDERR, "Token registry imports should normalize entries without persisting side effects." . PHP_EOL);
    exit(1);
}

if ($ssc_options_store['ssc_tokens_registry'] !== '__original_registry__' || $ssc_options_store['ssc_tokens_css'] !== '__original_css__') {
    fwrite(STDERR, "Sanitizing token registry imports should not modify stored options before apply." . PHP_EOL);
    exit(1);
}

$registryImportResult = $applyMethod->invoke($routes, [
    'ssc_tokens_registry' => $rawRegistryPayload,
]);

if (!is_array($registryImportResult) || !in_array('ssc_tokens_registry', $registryImportResult['applied'] ?? [], true)) {
    fwrite(STDERR, "Token registry array imports should be reported as applied." . PHP_EOL);
    exit(1);
}

$storedRegistryAfterDirectImport = $ssc_options_store['ssc_tokens_registry'];

if (!is_array($storedRegistryAfterDirectImport) || count($storedRegistryAfterDirectImport) !== 1) {
    fwrite(STDERR, "Direct token registry imports should persist normalized entries." . PHP_EOL);
    exit(1);
}

$storedDirectToken = $storedRegistryAfterDirectImport[0];

if ($storedDirectToken['name'] !== '--Primary-Color' || $storedDirectToken['value'] !== '#FFFFFF') {
    fwrite(STDERR, "Token registry imports should normalize token names and values." . PHP_EOL);
    exit(1);
}

if ($storedDirectToken['group'] !== 'Colors' || $storedDirectToken['description'] !== 'Important') {
    fwrite(STDERR, "Token registry imports should sanitize metadata fields." . PHP_EOL);
    exit(1);
}

$expectedRegistryCss = \SSC\Support\TokenRegistry::tokensToCss($storedRegistryAfterDirectImport);

if ($ssc_options_store['ssc_tokens_css'] !== $expectedRegistryCss) {
    fwrite(STDERR, "Token registry imports should regenerate the CSS representation once applied." . PHP_EOL);
    exit(1);
}

$ssc_options_store['ssc_tokens_registry'] = [];
$ssc_options_store['ssc_tokens_css'] = '';

$registryWithMetadata = [
    [
        'name' => '--brand-primary',
        'value' => '#112233',
        'type' => 'color',
        'description' => 'Primary brand color',
        'group' => 'Brand',
    ],
];

$combinedImportResult = $applyMethod->invoke($routes, [
    'ssc_tokens_registry' => $registryWithMetadata,
    'ssc_tokens_css' => ":root {\n    --brand-primary: #112233;\n}",
]);

if (!is_array($combinedImportResult) || !in_array('ssc_tokens_registry', $combinedImportResult['applied'] ?? [], true) || !in_array('ssc_tokens_css', $combinedImportResult['applied'] ?? [], true)) {
    fwrite(STDERR, "Combined registry and CSS imports should report both options as applied." . PHP_EOL);
    exit(1);
}

$storedCombinedRegistry = $ssc_options_store['ssc_tokens_registry'];

if (!is_array($storedCombinedRegistry) || count($storedCombinedRegistry) !== 1) {
    fwrite(STDERR, "Combined registry and CSS imports should persist a single normalized token." . PHP_EOL);
    exit(1);
}

$storedCombinedToken = $storedCombinedRegistry[0];

if ($storedCombinedToken['type'] !== 'color' || $storedCombinedToken['group'] !== 'Brand' || $storedCombinedToken['description'] !== 'Primary brand color') {
    fwrite(STDERR, "Registry metadata should survive subsequent CSS imports processed in the same payload." . PHP_EOL);
    exit(1);
}

$ssc_options_store['ssc_tokens_registry'] = \SSC\Support\TokenRegistry::saveRegistry([
    [
        'name' => '--spacing-large',
        'value' => '32px',
        'type' => 'number',
        'description' => 'Large spacing token',
        'group' => 'Spacing',
    ],
]);

$cssOnlyResult = $applyMethod->invoke($routes, [
    'ssc_tokens_css' => ":root {\n    --spacing-large: 40px;\n}",
]);

if (!is_array($cssOnlyResult) || !in_array('ssc_tokens_css', $cssOnlyResult['applied'] ?? [], true)) {
    fwrite(STDERR, "CSS-only imports should report the tokens CSS option as applied." . PHP_EOL);
    exit(1);
}

$storedCssOnlyRegistry = $ssc_options_store['ssc_tokens_registry'];

if (!is_array($storedCssOnlyRegistry) || count($storedCssOnlyRegistry) !== 1) {
    fwrite(STDERR, "CSS-only imports should preserve the registry structure." . PHP_EOL);
    exit(1);
}

$storedCssOnlyToken = $storedCssOnlyRegistry[0];

if ($storedCssOnlyToken['value'] !== '40px') {
    fwrite(STDERR, "CSS-only imports should update the token value from the CSS payload." . PHP_EOL);
    exit(1);
}

if ($storedCssOnlyToken['type'] !== 'number' || $storedCssOnlyToken['group'] !== 'Spacing' || $storedCssOnlyToken['description'] !== 'Large spacing token') {
    fwrite(STDERR, "CSS-only imports should retain metadata from the existing registry after merging." . PHP_EOL);
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
    fwrite(STDERR, 'Actual settings: ' . json_encode($ssc_options_store['ssc_settings']) . PHP_EOL);
    exit(1);
}

if (!is_array($objectImportResult) || !in_array('ssc_settings', $objectImportResult['applied'] ?? [], true)) {
    fwrite(STDERR, "Object payload import should be reported as applied." . PHP_EOL);
    exit(1);
}

$ssc_options_store['ssc_settings'] = [];

$duplicateKeyPayload = [
    'options' => [
        'ssc_settings' => [
            'Name One' => 'First Value',
            'name-one' => 'Second Value',
        ],
    ],
];

$duplicateKeyResponse = $routes->importConfig(new WP_REST_Request([], $duplicateKeyPayload));

if (!$duplicateKeyResponse instanceof WP_REST_Response) {
    fwrite(STDERR, "Duplicate key import should return a REST response." . PHP_EOL);
    exit(1);
}

$duplicateKeyData = $duplicateKeyResponse->get_data();

if (!is_array($duplicateKeyData) || ($duplicateKeyData['ok'] ?? null) !== true) {
    fwrite(STDERR, "Duplicate key import should succeed with ok=true." . PHP_EOL);
    exit(1);
}

if (!in_array('ssc_settings', $duplicateKeyData['applied'] ?? [], true)) {
    fwrite(STDERR, "Duplicate key import should still apply the settings option." . PHP_EOL);
    exit(1);
}

$expectedDuplicateMessage = 'ssc_settings (duplicate key: nameone)';

if (!in_array($expectedDuplicateMessage, $duplicateKeyData['skipped'] ?? [], true)) {
    fwrite(STDERR, "Duplicate key import should report the normalized key in the skipped list." . PHP_EOL);
    exit(1);
}

$storedSettings = $ssc_options_store['ssc_settings'] ?? null;

if (!is_array($storedSettings) || $storedSettings !== ['nameone' => 'First Value']) {
    fwrite(STDERR, "Duplicate key import should keep the first occurrence of the normalized key." . PHP_EOL);
    fwrite(STDERR, 'Actual settings: ' . json_encode($storedSettings) . PHP_EOL);
    exit(1);
}

$ssc_options_store['ssc_active_css'] = 'original-css';

$unknownModulesRequest = new WP_REST_Request(
    ['modules' => ['totally-unknown']],
    [
        'modules' => ['totally-unknown'],
        'options' => [
            'ssc_active_css' => 'body { color: red; }',
        ],
    ]
);

$unknownModulesResponse = $routes->importConfig($unknownModulesRequest);

if (!$unknownModulesResponse instanceof WP_REST_Response) {
    fwrite(STDERR, "Import with unknown modules should return a REST response." . PHP_EOL);
    exit(1);
}

if ($unknownModulesResponse->get_status() !== 400) {
    fwrite(STDERR, "Import with unknown modules should fail with a 400 status." . PHP_EOL);
    exit(1);
}

$unknownModulesData = $unknownModulesResponse->get_data();

if (!is_array($unknownModulesData) || ($unknownModulesData['ok'] ?? null) !== false) {
    fwrite(STDERR, "Import with unknown modules should report failure with ok=false." . PHP_EOL);
    exit(1);
}

$expectedMessage = 'No valid Supersede CSS modules were selected for import.';

if (($unknownModulesData['message'] ?? '') !== $expectedMessage) {
    fwrite(STDERR, "Import with unknown modules should return an explicit error message." . PHP_EOL);
    exit(1);
}

if (($unknownModulesData['skipped'] ?? []) !== ['ssc_active_css']) {
    fwrite(STDERR, "Import with unknown modules should report skipped options." . PHP_EOL);
    exit(1);
}

if ($ssc_options_store['ssc_active_css'] !== 'original-css') {
    fwrite(STDERR, "Import with unknown modules should not change any stored options." . PHP_EOL);
    exit(1);
}
