<?php declare(strict_types=1);

if (defined('SSC_WP_TEST_SUITE_AVAILABLE') && !SSC_WP_TEST_SUITE_AVAILABLE) {
    fwrite(STDOUT, basename(__FILE__) . " skipped: WordPress test suite unavailable." . PHP_EOL);

    return;
}

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

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return sprintf('[%s] %s', $domain, $text);
    }
}

/** @var array<string, mixed> $ssc_options_store */
$ssc_options_store = [];

/** @var int $ssc_css_invalidation_calls */
$ssc_css_invalidation_calls = 0;

global $ssc_options_store;

global $ssc_css_invalidation_calls;

if (!class_exists('SSC\\Infra\\Activity\\EventRecorder')) {
    class ssc_test_EventRecorder
    {
        public static function install(): void
        {
        }

        public static function maybeUpgrade(): void
        {
        }

        public static function record(string $event, array $payload = []): void
        {
        }
    }

    class_alias(ssc_test_EventRecorder::class, 'SSC\\Infra\\Activity\\EventRecorder');
}

if (!function_exists('ssc_invalidate_css_cache')) {
    function ssc_invalidate_css_cache(): void
    {
        global $ssc_css_invalidation_calls;

        $ssc_css_invalidation_calls++;
    }
}

if (function_exists('add_action')) {
    add_action('ssc_css_cache_invalidated', static function (): void {
        global $ssc_css_invalidation_calls;

        $ssc_css_invalidation_calls++;
    });
}

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

if (!function_exists('delete_option')) {
    function delete_option($name)
    {
        global $ssc_options_store;

        unset($ssc_options_store[$name]);

        return true;
    }
}

/**
 * @param string $name
 * @param mixed  $default
 * @return mixed
 */
function ssc_test_get_option_value(string $name, $default = null)
{
    global $ssc_options_store;

    if (!is_array($ssc_options_store)) {
        $ssc_options_store = [];
    }

    if (array_key_exists($name, $ssc_options_store)) {
        return $ssc_options_store[$name];
    }

    if (function_exists('get_option')) {
        $sentinel = (object) ['ssc_option_probe' => true];
        $value = get_option($name, $sentinel);

        if ($value !== $sentinel) {
            return $value;
        }
    }

    return $default;
}

function ssc_test_delete_option(string $name): void
{
    global $ssc_options_store;

    if (!is_array($ssc_options_store)) {
        $ssc_options_store = [];
    }

    unset($ssc_options_store[$name]);

    if (function_exists('delete_option')) {
        delete_option($name);
    }
}

require_once __DIR__ . '/../../src/Support/CssSanitizer.php';
require_once __DIR__ . '/../../src/Support/TokenRegistry.php';

$normalizedResult = TokenRegistry::normalizeRegistry([
    [
        'name' => '--BrandPrimary',
        'value' => '#3366ff',
        'type' => 'color',
        'description' => 'Primary brand color.',
        'group' => 'Brand',
    ],
]);

$normalized = $normalizedResult['tokens'];

if ($normalizedResult['duplicates'] !== []) {
    fwrite(STDERR, 'TokenRegistry::normalizeRegistry should not report duplicates for unique tokens.' . PHP_EOL);
    exit(1);
}

if ($normalized === [] || $normalized[0]['name'] !== '--BrandPrimary') {
    fwrite(STDERR, 'TokenRegistry::normalizeRegistry should preserve the original token casing.' . PHP_EOL);
    exit(1);
}

$initialTokens = [
    [
        'name' => '--BrandPrimary',
        'value' => '#3366ff',
        'type' => 'color',
        'description' => 'Primary brand color.',
        'group' => 'Brand',
    ],
];

$registryResult = TokenRegistry::saveRegistry($initialTokens);

$registry = $registryResult['tokens'];

if ($registryResult['duplicates'] !== []) {
    fwrite(STDERR, 'TokenRegistry::saveRegistry should not report duplicates for unique tokens.' . PHP_EOL);
    exit(1);
}

if ($registry === [] || $registry[0]['name'] !== '--BrandPrimary') {
    fwrite(STDERR, 'TokenRegistry::saveRegistry should preserve the original token casing.' . PHP_EOL);
    exit(1);
}

if (!isset($registry[0]['context']) || $registry[0]['context'] !== TokenRegistry::getDefaultContext()) {
    fwrite(STDERR, 'TokenRegistry::saveRegistry should assign the default context to tokens without explicit context.' . PHP_EOL);
    exit(1);
}

$persistedCss = ssc_test_get_option_value('ssc_tokens_css');

if (!is_string($persistedCss)) {
    fwrite(STDERR, 'TokenRegistry::saveRegistry should persist CSS using the original token name.' . PHP_EOL);
    exit(1);
}

if (strpos($persistedCss, '--BrandPrimary') === false) {
    fwrite(STDERR, 'Persisted CSS should contain the original token casing.' . PHP_EOL);
    exit(1);
}

if ($ssc_css_invalidation_calls !== 1) {
    fwrite(STDERR, 'TokenRegistry::saveRegistry should invalidate the CSS cache once.' . PHP_EOL);
    exit(1);
}

$invalidationsAfterFirstSave = $ssc_css_invalidation_calls;
TokenRegistry::saveRegistry($initialTokens);

if ($ssc_css_invalidation_calls !== $invalidationsAfterFirstSave) {
    fwrite(STDERR, 'TokenRegistry::saveRegistry should avoid cache invalidation when the CSS output is unchanged.' . PHP_EOL);
    exit(1);
}

$whitespacePaddedCss = "\n  " . $persistedCss . "\n\n";
update_option('ssc_tokens_css', $whitespacePaddedCss);

$invalidationsAfterWhitespacePadding = $ssc_css_invalidation_calls;
TokenRegistry::saveRegistry($initialTokens);

if ($ssc_css_invalidation_calls !== $invalidationsAfterWhitespacePadding) {
    fwrite(STDERR, 'TokenRegistry::saveRegistry should ignore cosmetic differences in stored CSS when deciding to invalidate the cache.' . PHP_EOL);
    exit(1);
}

$cssInvalidationsBeforeRefresh = $ssc_css_invalidation_calls;
ssc_test_delete_option('ssc_tokens_css');

$refreshedRegistry = TokenRegistry::getRegistry();

if ($ssc_css_invalidation_calls !== $cssInvalidationsBeforeRefresh + 1) {
    fwrite(STDERR, 'TokenRegistry::getRegistry should invalidate the CSS cache when CSS needs regeneration.' . PHP_EOL);
    exit(1);
}

if ($refreshedRegistry === [] || $refreshedRegistry[0]['name'] !== '--BrandPrimary') {
    fwrite(STDERR, 'TokenRegistry::getRegistry should return the stored tokens after regenerating CSS.' . PHP_EOL);
    exit(1);
}

$regeneratedCss = ssc_test_get_option_value('ssc_tokens_css');

if (!is_string($regeneratedCss) || strpos($regeneratedCss, '--BrandPrimary') === false) {
    fwrite(STDERR, 'TokenRegistry::getRegistry should regenerate CSS when missing.' . PHP_EOL);
    exit(1);
}

$roundTripRegistry = TokenRegistry::convertCssToRegistry((string) $regeneratedCss);

if ($roundTripRegistry === [] || $roundTripRegistry[0]['name'] !== '--BrandPrimary') {
    fwrite(STDERR, 'convertCssToRegistry should keep the original casing after import.' . PHP_EOL);
    exit(1);
}

if (!isset($roundTripRegistry[0]['context']) || $roundTripRegistry[0]['context'] !== TokenRegistry::getDefaultContext()) {
    fwrite(STDERR, 'convertCssToRegistry should assign the default context when none is provided.' . PHP_EOL);
    exit(1);
}

$regeneratedCss = TokenRegistry::tokensToCss($roundTripRegistry);

if (strpos($regeneratedCss, '--BrandPrimary') === false) {
    fwrite(STDERR, 'tokensToCss should keep the original casing when exporting.' . PHP_EOL);
    exit(1);
}

$ssc_css_invalidation_calls = 0;
$ssc_options_store = [
    'ssc_tokens_registry' => [
        [
            'name' => '--legacy-token',
            'value' => '#f1f5f9',
            'type' => 'color',
            'description' => 'Legacy token with missing metadata.',
            'group' => 'Legacy',
        ],
    ],
    'ssc_tokens_css' => ":root {\n    --legacy-token: #0f172a;\n}",
];

$mismatchedRegistry = TokenRegistry::getRegistry();

if ($ssc_css_invalidation_calls !== 1) {
    fwrite(STDERR, 'TokenRegistry::getRegistry should invalidate the CSS cache once when normalizing legacy entries.' . PHP_EOL);
    exit(1);
}

if ($mismatchedRegistry === [] || $mismatchedRegistry[0]['name'] !== '--legacy-token') {
    fwrite(STDERR, 'TokenRegistry::getRegistry should keep legacy token names when normalizing entries.' . PHP_EOL);
    exit(1);
}

if (!isset($mismatchedRegistry[0]['context']) || $mismatchedRegistry[0]['context'] !== TokenRegistry::getDefaultContext()) {
    fwrite(STDERR, 'TokenRegistry::getRegistry should assign the default context to legacy entries missing metadata.' . PHP_EOL);
    exit(1);
}

$normalizedLegacyCss = ssc_test_get_option_value('ssc_tokens_css');

if (!is_string($normalizedLegacyCss) || strpos($normalizedLegacyCss, '--legacy-token') === false) {
    fwrite(STDERR, 'TokenRegistry::getRegistry should regenerate CSS for legacy entries using normalized metadata.' . PHP_EOL);
    exit(1);
}

$ssc_options_store = [];

$ssc_css_invalidation_calls = 0;
$ssc_options_store['ssc_tokens_css'] = ":root {\n    --fallback-token: 1rem;\n}";

$fallbackRegistry = TokenRegistry::getRegistry();

if ($ssc_css_invalidation_calls !== 1) {
    fwrite(STDERR, 'TokenRegistry::getRegistry should invalidate the CSS cache once when importing legacy CSS.' . PHP_EOL);
    exit(1);
}

if ($fallbackRegistry === [] || $fallbackRegistry[0]['name'] !== '--fallback-token') {
    fwrite(STDERR, 'TokenRegistry::getRegistry should import tokens from legacy CSS.' . PHP_EOL);
    exit(1);
}

$ssc_css_invalidation_calls = 0;
$ssc_options_store = [];

$defaultsRegistry = TokenRegistry::getRegistry();

if ($ssc_css_invalidation_calls !== 1) {
    fwrite(STDERR, 'TokenRegistry::getRegistry should invalidate the CSS cache once when bootstrapping defaults.' . PHP_EOL);
    exit(1);
}

if ($defaultsRegistry === []) {
    fwrite(STDERR, 'TokenRegistry::getRegistry should expose default tokens when no data is stored.' . PHP_EOL);
    exit(1);
}

$ssc_css_invalidation_calls = 0;
$ssc_options_store = [];

$existingTokens = [
    [
        'name' => '--BrandPrimary',
        'value' => '#3366ff',
        'type' => 'color',
        'description' => 'Primary brand color.',
        'group' => 'Brand',
    ],
    [
        'name' => '--SpacingSmall',
        'value' => '4px',
        'type' => 'number',
        'description' => 'Small spacing token.',
        'group' => 'Spacing',
    ],
];

$incomingTokens = [
    [
        'name' => '--BrandPrimary',
        'value' => '#123456',
        'type' => 'text',
        'description' => '',
        'group' => 'Legacy',
    ],
    [
        'name' => '--NewToken',
        'value' => 'value',
        'type' => 'text',
        'description' => '',
        'group' => 'Legacy',
    ],
];

$mergedTokens = TokenRegistry::mergeMetadata($incomingTokens, $existingTokens);

if ($mergedTokens === [] || count($mergedTokens) !== 2) {
    fwrite(STDERR, 'mergeMetadata should preserve the list of incoming tokens.' . PHP_EOL);
    exit(1);
}

if ($mergedTokens[0]['type'] !== 'color' || $mergedTokens[0]['group'] !== 'Brand' || $mergedTokens[0]['description'] !== 'Primary brand color.') {
    fwrite(STDERR, 'mergeMetadata should restore metadata from the existing registry when names match.' . PHP_EOL);
    exit(1);
}

if ($mergedTokens[0]['value'] !== '#123456') {
    fwrite(STDERR, 'mergeMetadata should keep the incoming value for matching tokens.' . PHP_EOL);
    exit(1);
}

if (!isset($mergedTokens[0]['context']) || $mergedTokens[0]['context'] !== TokenRegistry::getDefaultContext()) {
    fwrite(STDERR, 'mergeMetadata should preserve the context from the existing registry.' . PHP_EOL);
    exit(1);
}

if ($mergedTokens[1]['type'] !== 'text' || $mergedTokens[1]['group'] !== 'Legacy') {
    fwrite(STDERR, 'mergeMetadata should leave unmatched tokens untouched.' . PHP_EOL);
    exit(1);
}

if (!isset($mergedTokens[1]['context']) || $mergedTokens[1]['context'] !== TokenRegistry::getDefaultContext()) {
    fwrite(STDERR, 'mergeMetadata should assign the default context to unmatched tokens.' . PHP_EOL);
    exit(1);
}

$ssc_options_store = [];

$underscoredTokens = [
    [
        'name' => '--spacing_small',
        'value' => '8px',
        'type' => 'text',
        'description' => 'Spacing token with underscore.',
        'group' => 'Spacing',
    ],
];

$savedRegistryResult = TokenRegistry::saveRegistry($underscoredTokens);
$savedRegistry = $savedRegistryResult['tokens'];

if ($savedRegistryResult['duplicates'] !== []) {
    fwrite(STDERR, 'saveRegistry should not report duplicates for unique underscored tokens.' . PHP_EOL);
    exit(1);
}

if ($savedRegistry === [] || $savedRegistry[0]['name'] !== '--spacing_small') {
    fwrite(STDERR, 'saveRegistry should preserve underscores in token names.' . PHP_EOL);
    exit(1);
}

$supportedTypes = TokenRegistry::getSupportedTypes();

if (!isset($supportedTypes['color']['label']) || $supportedTypes['color']['label'] !== '[supersede-css-jlg] Couleur') {
    fwrite(STDERR, 'getSupportedTypes should return translated labels.' . PHP_EOL);
    exit(1);
}

foreach (['spacing', 'font', 'shadow', 'gradient', 'border', 'dimension', 'transition'] as $expectedType) {
    if (!isset($supportedTypes[$expectedType])) {
        fwrite(STDERR, sprintf('getSupportedTypes should expose the "%s" type.', $expectedType) . PHP_EOL);
        exit(1);
    }
}

if (!isset($supportedTypes['shadow']['help']) || strpos($supportedTypes['shadow']['help'], 'box-shadow') === false) {
    fwrite(STDERR, 'getSupportedTypes should provide contextual help for textarea-capable types.' . PHP_EOL);
    exit(1);
}

$storedRegistry = ssc_test_get_option_value('ssc_tokens_registry');

if (!is_array($storedRegistry)) {
    fwrite(STDERR, 'saveRegistry should persist the registry with the underscored token.' . PHP_EOL);
    exit(1);
}

if ($storedRegistry === [] || $storedRegistry[0]['name'] !== '--spacing_small') {
    fwrite(STDERR, 'Persisted registry should keep underscores in token names.' . PHP_EOL);
    exit(1);
}

$persistedRegistryCss = ssc_test_get_option_value('ssc_tokens_css');

if (!is_string($persistedRegistryCss) || strpos($persistedRegistryCss, '--spacing_small') === false) {
    fwrite(STDERR, 'Persisted CSS should include the underscored token name.' . PHP_EOL);
    exit(1);
}

$roundTrip = TokenRegistry::getRegistry();

if ($roundTrip === [] || $roundTrip[0]['name'] !== '--spacing_small') {
    fwrite(STDERR, 'getRegistry should return tokens with underscores intact.' . PHP_EOL);
    exit(1);
}

if (!isset($roundTrip[0]['context']) || $roundTrip[0]['context'] !== TokenRegistry::getDefaultContext()) {
    fwrite(STDERR, 'getRegistry should expose the stored context for tokens.' . PHP_EOL);
    exit(1);
}

$roundTripCss = TokenRegistry::tokensToCss($roundTrip);

if (strpos($roundTripCss, '--spacing_small') === false) {
    fwrite(STDERR, 'tokensToCss should keep underscores after round-trip.' . PHP_EOL);
    exit(1);
}

ssc_test_delete_option('ssc_tokens_registry');
ssc_test_delete_option('ssc_tokens_css');

$ssc_options_store = [];

$cssWithLeadingComment = '/* initial token */ --comment-prefixed: 24px;';
$registryFromCommentedCss = TokenRegistry::convertCssToRegistry($cssWithLeadingComment);

if ($registryFromCommentedCss === [] || $registryFromCommentedCss[0]['name'] !== '--comment-prefixed') {
    fwrite(STDERR, 'convertCssToRegistry should parse tokens after comment delimiters.' . PHP_EOL);
    exit(1);
}

if (!isset($registryFromCommentedCss[0]['context']) || $registryFromCommentedCss[0]['context'] !== TokenRegistry::getDefaultContext()) {
    fwrite(STDERR, 'convertCssToRegistry should default the context for comment-prefixed tokens.' . PHP_EOL);
    exit(1);
}

$commentedResult = TokenRegistry::saveRegistry($registryFromCommentedCss);
if ($commentedResult['duplicates'] !== []) {
    fwrite(STDERR, 'saveRegistry should not report duplicates when CSS comments are ignored.' . PHP_EOL);
    exit(1);
}

$commentedRegistryStore = ssc_test_get_option_value('ssc_tokens_registry');

if (!is_array($commentedRegistryStore)) {
    fwrite(STDERR, 'saveRegistry should persist tokens parsed after leading comments.' . PHP_EOL);
    exit(1);
}

$commentedCssStore = ssc_test_get_option_value('ssc_tokens_css');

if (!is_string($commentedCssStore) || strpos($commentedCssStore, '--comment-prefixed') === false) {
    fwrite(STDERR, 'Persisted CSS should include tokens parsed after leading comments.' . PHP_EOL);
    exit(1);
}

ssc_test_delete_option('ssc_tokens_registry');
ssc_test_delete_option('ssc_tokens_css');

$ssc_options_store = [];

$annotatedCss = '/* note */ --my-token: value;';
$annotatedRegistry = TokenRegistry::convertCssToRegistry($annotatedCss);

if ($annotatedRegistry === [] || $annotatedRegistry[0]['name'] !== '--my-token') {
    fwrite(STDERR, 'convertCssToRegistry should capture tokens defined after annotated comments.' . PHP_EOL);
    exit(1);
}

if (!isset($annotatedRegistry[0]['context']) || $annotatedRegistry[0]['context'] !== TokenRegistry::getDefaultContext()) {
    fwrite(STDERR, 'convertCssToRegistry should attach the default context to annotated tokens.' . PHP_EOL);
    exit(1);
}

$annotatedResult = TokenRegistry::saveRegistry($annotatedRegistry);
if ($annotatedResult['duplicates'] !== []) {
    fwrite(STDERR, 'saveRegistry should not report duplicates when annotated comments are present.' . PHP_EOL);
    exit(1);
}

$annotatedRegistryStore = ssc_test_get_option_value('ssc_tokens_registry');

if (!is_array($annotatedRegistryStore)) {
    fwrite(STDERR, 'saveRegistry should persist tokens that follow annotated comments.' . PHP_EOL);
    exit(1);
}

$annotatedCssStore = ssc_test_get_option_value('ssc_tokens_css');

if (!is_string($annotatedCssStore) || strpos($annotatedCssStore, '--my-token:') === false) {
    fwrite(STDERR, 'Persisted CSS should retain tokens declared after annotated comments.' . PHP_EOL);
    exit(1);
}

$multilineTokens = [
    [
        'name' => '--shadow-card',
        'value' => "0 2px 6px rgba(15, 23, 42, 0.12)\r\n0 12px 24px rgba(15, 23, 42, 0.16)",
        'type' => 'shadow',
        'description' => 'Shadow token with multiple lines.',
        'group' => 'Surfaces',
    ],
];

$multilineResult = TokenRegistry::normalizeRegistry($multilineTokens);
$normalizedMultiline = $multilineResult['tokens'];

if ($multilineResult['duplicates'] !== []) {
    fwrite(STDERR, 'normalizeRegistry should not report duplicates for multi-line tokens.' . PHP_EOL);
    exit(1);
}

if ($normalizedMultiline === [] || $normalizedMultiline[0]['type'] !== 'shadow') {
    fwrite(STDERR, 'normalizeRegistry should keep the declared token type for textarea tokens.' . PHP_EOL);
    exit(1);
}

if ($normalizedMultiline[0]['value'] !== "0 2px 6px rgba(15, 23, 42, 0.12)\n0 12px 24px rgba(15, 23, 42, 0.16)") {
    fwrite(STDERR, 'normalizeRegistry should preserve multi-line textarea values.' . PHP_EOL);
    exit(1);
}

$contextualTokens = [
    [
        'name' => '--theme-color',
        'value' => '#ffffff',
        'type' => 'color',
        'description' => 'Light mode color.',
        'group' => 'Theme',
        'context' => ':root',
    ],
    [
        'name' => '--theme-color',
        'value' => '#000000',
        'type' => 'color',
        'description' => 'Dark mode color.',
        'group' => 'Theme',
        'context' => '[data-theme="dark"]',
    ],
];

$contextualResult = TokenRegistry::normalizeRegistry($contextualTokens);
if ($contextualResult['duplicates'] !== []) {
    fwrite(STDERR, 'normalizeRegistry should allow duplicate token names across different contexts.' . PHP_EOL);
    exit(1);
}

if (count($contextualResult['tokens']) !== 2) {
    fwrite(STDERR, 'normalizeRegistry should retain distinct entries for context-specific tokens.' . PHP_EOL);
    exit(1);
}
