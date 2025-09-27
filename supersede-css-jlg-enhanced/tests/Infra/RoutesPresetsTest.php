<?php declare(strict_types=1);

use SSC\Infra\Routes;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
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
         * @return array<string, mixed>
         */
        public function get_json_params(): array
        {
            return is_array($this->json) ? $this->json : [];
        }
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return $value;
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

if (!function_exists('wp_kses')) {
    function wp_kses(string $string, array $allowed_html = []): string
    {
        unset($allowed_html);

        return strip_tags($string);
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

if (!function_exists('rest_sanitize_boolean')) {
    function rest_sanitize_boolean($value)
    {
        $sanitized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $sanitized ?? ($value ? true : false);
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        unset($domain);

        return $text;
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
        unset($autoload);

        $ssc_options_store[$name] = $value;

        return true;
    }
}

require_once __DIR__ . '/../../src/Support/CssSanitizer.php';
require_once __DIR__ . '/../../src/Infra/Routes.php';

$routesReflection = new ReflectionClass(Routes::class);
/** @var Routes $routes */
$routes = $routesReflection->newInstanceWithoutConstructor();

$legacyGlowPresets = [
    ['label' => '<b>Legacy</b>', 'color' => '#FF00FF', 'intensity' => '25'],
];

$legacyGlowRequest = new WP_REST_Request([
    'presets' => json_encode($legacyGlowPresets, JSON_THROW_ON_ERROR),
]);

$legacyGlowResponse = $routes->saveAvatarGlowPresets($legacyGlowRequest);

if (!$legacyGlowResponse instanceof WP_REST_Response || $legacyGlowResponse->get_status() !== 200) {
    fwrite(STDERR, 'Expected legacy avatar glow presets request to succeed.' . PHP_EOL);
    exit(1);
}

$storedGlowPresets = get_option('ssc_avatar_glow_presets', []);
$expectedGlowPresets = \SSC\Support\CssSanitizer::sanitizeAvatarGlowPresets($legacyGlowPresets);

if ($storedGlowPresets !== $expectedGlowPresets) {
    fwrite(STDERR, 'Legacy avatar glow presets should be decoded and sanitized.' . PHP_EOL);
    exit(1);
}

$jsonGlowPresets = [
    ['label' => 'JSON', 'color' => '#00FF00', 'intensity' => 10],
];

$jsonGlowRequest = new WP_REST_Request([], ['presets' => $jsonGlowPresets]);
$jsonGlowResponse = $routes->saveAvatarGlowPresets($jsonGlowRequest);

if (!$jsonGlowResponse instanceof WP_REST_Response || $jsonGlowResponse->get_status() !== 200) {
    fwrite(STDERR, 'Expected JSON avatar glow presets request to succeed.' . PHP_EOL);
    exit(1);
}

$storedJsonGlowPresets = get_option('ssc_avatar_glow_presets', []);
$expectedJsonGlow = \SSC\Support\CssSanitizer::sanitizeAvatarGlowPresets($jsonGlowPresets);

if ($storedJsonGlowPresets !== $expectedJsonGlow) {
    fwrite(STDERR, 'JSON avatar glow presets should overwrite existing presets after sanitization.' . PHP_EOL);
    exit(1);
}

$legacyPresets = [
    'first' => [
        'selector' => 'body',
        'styles' => '<script>alert(1)</script>color:red;margin-top: 10px;behavior:url(foo);',
    ],
];

$legacyPresetsRequest = new WP_REST_Request([
    'presets' => json_encode($legacyPresets, JSON_THROW_ON_ERROR),
]);

$legacyPresetsResponse = $routes->savePresets($legacyPresetsRequest);

if (!$legacyPresetsResponse instanceof WP_REST_Response || $legacyPresetsResponse->get_status() !== 200) {
    fwrite(STDERR, 'Expected legacy presets request to succeed.' . PHP_EOL);
    exit(1);
}

$storedLegacyPresets = get_option('ssc_presets', []);
$expectedLegacyPresets = \SSC\Support\CssSanitizer::sanitizePresetCollection($legacyPresets);

if ($storedLegacyPresets !== $expectedLegacyPresets) {
    fwrite(STDERR, 'Legacy presets should be decoded and sanitized.' . PHP_EOL);
    exit(1);
}

$legacyProps = $expectedLegacyPresets['first']['props'] ?? [];
if (!isset($legacyProps['color']) || $legacyProps['color'] !== 'red') {
    fwrite(STDERR, 'Legacy preset styles should keep safe declarations.' . PHP_EOL);
    exit(1);
}

if (!isset($legacyProps['margin-top']) || $legacyProps['margin-top'] !== '10px') {
    fwrite(STDERR, 'Legacy preset styles should retain multiple declarations.' . PHP_EOL);
    exit(1);
}

if (isset($legacyProps['behavior'])) {
    fwrite(STDERR, 'Disallowed declarations should not survive legacy preset sanitization.' . PHP_EOL);
    exit(1);
}

$jsonPresets = [
    'second' => [
        'selector' => '.example',
        'styles' => 'color: blue; padding: 4px 8px; behavior:url(foo);',
    ],
];

$jsonPresetsRequest = new WP_REST_Request([], ['presets' => $jsonPresets]);
$jsonPresetsResponse = $routes->savePresets($jsonPresetsRequest);

if (!$jsonPresetsResponse instanceof WP_REST_Response || $jsonPresetsResponse->get_status() !== 200) {
    fwrite(STDERR, 'Expected JSON presets request to succeed.' . PHP_EOL);
    exit(1);
}

$storedJsonPresets = get_option('ssc_presets', []);
$expectedJsonPresets = \SSC\Support\CssSanitizer::sanitizePresetCollection($jsonPresets);

if ($storedJsonPresets !== $expectedJsonPresets) {
    fwrite(STDERR, 'JSON presets should overwrite existing presets after sanitization.' . PHP_EOL);
    exit(1);
}

$jsonProps = $expectedJsonPresets['second']['props'] ?? [];
if (!isset($jsonProps['color']) || $jsonProps['color'] !== 'blue') {
    fwrite(STDERR, 'Imported preset styles should expose color declarations to the UI.' . PHP_EOL);
    exit(1);
}

if (!isset($jsonProps['padding']) || $jsonProps['padding'] !== '4px 8px') {
    fwrite(STDERR, 'Imported preset styles should keep shorthand declarations.' . PHP_EOL);
    exit(1);
}

if (isset($jsonProps['behavior'])) {
    fwrite(STDERR, 'Unsafe declarations must be stripped from imported presets.' . PHP_EOL);
    exit(1);
}

