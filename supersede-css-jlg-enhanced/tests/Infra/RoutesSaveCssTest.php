<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SSC\Infra\Routes;
use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;

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

/** @var int $ssc_cache_invalidations */
$ssc_cache_invalidations = 0;

global $ssc_cache_invalidations;

if (!function_exists('ssc_invalidate_css_cache')) {
    function ssc_invalidate_css_cache(): void
    {
        global $ssc_cache_invalidations;

        $ssc_cache_invalidations++;
    }
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
        unset($autoload);
        global $ssc_options_store;

        $ssc_options_store[$name] = $value;

        return true;
    }
}

final class RoutesSaveCssTest extends TestCase
{
    private Routes $routes;

    protected function setUp(): void
    {
        parent::setUp();

        global $ssc_options_store, $ssc_cache_invalidations;
        $ssc_options_store = [];
        $ssc_cache_invalidations = 0;

        $reflection = new \ReflectionClass(Routes::class);
        $this->routes = $reflection->newInstanceWithoutConstructor();
    }

    public function testNonStringOptionNameDefaultsToActiveCss(): void
    {
        global $ssc_options_store;

        $request = new WP_REST_Request([
            'option_name' => ['unexpected'],
            'css' => 'body { color: red; }',
        ]);

        $response = $this->routes->saveCss($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $this->assertArrayHasKey('ssc_active_css', $ssc_options_store);
        $this->assertArrayNotHasKey('ssc_tokens_css', $ssc_options_store);
    }

    public function testSavingTokenCssUpdatesRegistryAndCss(): void
    {
        global $ssc_options_store;

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

        TokenRegistry::saveRegistry($initialRegistry);

        $tokenCss = ":root {\n    --first-token: #123456;\n    --second-token: 1.5rem\n}";
        $request = new WP_REST_Request([
            'option_name' => 'ssc_tokens_css',
            'css' => $tokenCss,
        ]);

        $response = $this->routes->saveCss($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $sanitizedCss = CssSanitizer::sanitize($tokenCss);
        $convertedRegistry = TokenRegistry::convertCssToRegistry($sanitizedCss);
        $normalizedInitial = TokenRegistry::normalizeRegistry($initialRegistry);

        $initialByName = [];
        foreach ($normalizedInitial as $existingToken) {
            $initialByName[strtolower($existingToken['name'])] = $existingToken;
        }

        $expectedRegistry = [];
        foreach ($convertedRegistry as $token) {
            $key = strtolower($token['name']);
            if (isset($initialByName[$key])) {
                $existing = $initialByName[$key];
                $token['type'] = $existing['type'];
                $token['group'] = $existing['group'];
                $token['description'] = $existing['description'];
            }
            $expectedRegistry[] = $token;
        }

        $expectedCss = TokenRegistry::tokensToCss($expectedRegistry);

        $this->assertSame($expectedRegistry, $ssc_options_store['ssc_tokens_registry']);
        $this->assertSame($expectedCss, $ssc_options_store['ssc_tokens_css']);
    }

    public function testTokenConversionPreservesValuesWithSemicolons(): void
    {
        $complexValueCss = ":root {\n    --with-semicolon: 'foo;bar';\n}";
        $sanitized = CssSanitizer::sanitize($complexValueCss);
        $registry = TokenRegistry::convertCssToRegistry($sanitized);

        $this->assertCount(1, $registry);
        $this->assertSame("'foo;bar'", $registry[0]['value']);

        $roundTrip = TokenRegistry::convertCssToRegistry(TokenRegistry::tokensToCss($registry));
        $this->assertSame($registry, $roundTrip);
    }

    public function testLegacyTabletCssIsSanitizedAndCacheInvalidated(): void
    {
        global $ssc_options_store, $ssc_cache_invalidations;

        $legacyTabletCss = "body { color: red; }<script>alert('xss');</script>";
        $ssc_options_store['ssc_css_tablet'] = $legacyTabletCss;
        $ssc_cache_invalidations = 0;

        $request = new WP_REST_Request([
            'option_name' => 'ssc_active_css',
            'css' => 'body { color: blue; }',
        ]);

        $response = $this->routes->saveCss($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $expectedSanitizedTabletCss = CssSanitizer::sanitize($legacyTabletCss);
        $this->assertSame($expectedSanitizedTabletCss, $ssc_options_store['ssc_css_tablet']);
        $this->assertGreaterThanOrEqual(2, $ssc_cache_invalidations);
    }
}
