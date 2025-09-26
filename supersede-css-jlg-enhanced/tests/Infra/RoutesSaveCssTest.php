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

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return $value;
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
require_once __DIR__ . '/../../src/Infra/Routes.php';

$routesReflection = new ReflectionClass(Routes::class);
$routes = $routesReflection->newInstanceWithoutConstructor();

$request = new WP_REST_Request([
    'option_name' => ['unexpected'],
    'css' => 'body { color: red; }',
]);

$response = $routes->saveCss($request);

if (!$response instanceof WP_REST_Response) {
    fwrite(STDERR, 'Expected WP_REST_Response when saving CSS.' . PHP_EOL);
    exit(1);
}

if ($response->get_status() !== 200) {
    fwrite(STDERR, 'Expected response status 200 when option_name is not a string.' . PHP_EOL);
    exit(1);
}

if (!array_key_exists('ssc_active_css', $ssc_options_store)) {
    fwrite(STDERR, 'Expected CSS to be saved under the default option name.' . PHP_EOL);
    exit(1);
}

if (array_key_exists('ssc_tokens_css', $ssc_options_store)) {
    fwrite(STDERR, 'Unexpected CSS stored under ssc_tokens_css.' . PHP_EOL);
    exit(1);
}

$initialRegistry = [
    [
        'name' => '--first-token',
        'value' => '#abcdef',
        'type' => 'color',
        'description' => 'Existing color token.',
        'group' => 'Palette',
    ],
    [
        'name' => '--second-token',
        'value' => '8px',
        'type' => 'text',
        'description' => 'Existing size token.',
        'group' => 'Spacing',
    ],
];

\SSC\Support\TokenRegistry::saveRegistry($initialRegistry);

$tokenCss = ":root {\n    --first-token: #123456;\n    --second-token: 1.5rem\n}";
$tokenRequest = new WP_REST_Request([
    'option_name' => 'ssc_tokens_css',
    'css' => $tokenCss,
]);

$tokenResponse = $routes->saveCss($tokenRequest);

if (!$tokenResponse instanceof WP_REST_Response || $tokenResponse->get_status() !== 200) {
    fwrite(STDERR, 'Saving CSS tokens should return a successful WP_REST_Response.' . PHP_EOL);
    exit(1);
}

$sanitizedTokenCss = \SSC\Support\CssSanitizer::sanitize($tokenCss);
$convertedRegistry = \SSC\Support\TokenRegistry::convertCssToRegistry($sanitizedTokenCss);
$normalizedInitial = \SSC\Support\TokenRegistry::normalizeRegistry($initialRegistry);
$initialByName = [];

foreach ($normalizedInitial as $existingToken) {
    $initialByName[strtolower($existingToken['name'])] = $existingToken;
}

$expectedRegistry = [];

foreach ($convertedRegistry as $token) {
    $key = strtolower($token['name']);
    if (isset($initialByName[$key])) {
        $existingToken = $initialByName[$key];
        $token['type'] = $existingToken['type'];
        $token['group'] = $existingToken['group'];
        $token['description'] = $existingToken['description'];
    }

    $expectedRegistry[] = $token;
}

$expectedRegistryCss = \SSC\Support\TokenRegistry::tokensToCss($expectedRegistry);

if ($ssc_options_store['ssc_tokens_registry'] !== $expectedRegistry) {
    fwrite(STDERR, 'Saving CSS tokens should update the token registry using the sanitized declarations.' . PHP_EOL);
    exit(1);
}

if ($ssc_options_store['ssc_tokens_css'] !== $expectedRegistryCss) {
    fwrite(STDERR, 'Saving CSS tokens should persist normalized CSS output that retains all tokens.' . PHP_EOL);
    exit(1);
}

$complexValueCss = ":root {\n    --with-semicolon: 'foo;bar';\n}";
$sanitizedComplexValueCss = \SSC\Support\CssSanitizer::sanitize($complexValueCss);
$complexValueRegistry = \SSC\Support\TokenRegistry::convertCssToRegistry($sanitizedComplexValueCss);

if (count($complexValueRegistry) !== 1) {
    fwrite(STDERR, 'CSS token parsing should keep declarations with embedded semicolons.' . PHP_EOL);
    exit(1);
}

$complexValueToken = $complexValueRegistry[0];

if ($complexValueToken['value'] !== "'foo;bar'") {
    fwrite(STDERR, 'CSS token parsing should keep the full value of declarations with embedded semicolons.' . PHP_EOL);
    exit(1);
}

$roundTripCss = \SSC\Support\TokenRegistry::tokensToCss($complexValueRegistry);
$roundTripRegistry = \SSC\Support\TokenRegistry::convertCssToRegistry($roundTripCss);

if ($roundTripRegistry !== $complexValueRegistry) {
    fwrite(STDERR, 'Converting CSS tokens to the registry and back should preserve the full value.' . PHP_EOL);
    exit(1);
}
