<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SSC\Infra\Routes;
use SSC\Support\CssSanitizer;

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
        unset($autoload);
        global $ssc_options_store;

        $ssc_options_store[$name] = $value;

        return true;
    }
}

final class RoutesPresetsTest extends TestCase
{
    private Routes $routes;

    protected function setUp(): void
    {
        parent::setUp();

        global $ssc_options_store;
        $ssc_options_store = [];

        $reflection = new \ReflectionClass(Routes::class);
        $this->routes = $reflection->newInstanceWithoutConstructor();
    }

    public function testAvatarGlowPresetsFromQueryAreSanitized(): void
    {
        $legacyGlowPresets = [
            ['label' => '<b>Legacy</b>', 'color' => '#FF00FF', 'intensity' => '25'],
        ];

        $request = new WP_REST_Request([
            'presets' => json_encode($legacyGlowPresets, JSON_THROW_ON_ERROR),
        ]);

        $response = $this->routes->saveAvatarGlowPresets($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $expected = CssSanitizer::sanitizeAvatarGlowPresets($legacyGlowPresets);
        $this->assertSame($expected, get_option('ssc_avatar_glow_presets', []));
    }

    public function testAvatarGlowPresetsFromJsonBodyOverwriteExisting(): void
    {
        $jsonGlowPresets = [
            ['label' => 'JSON', 'color' => '#00FF00', 'intensity' => 10],
        ];

        $request = new WP_REST_Request([], [], ['presets' => $jsonGlowPresets]);
        $response = $this->routes->saveAvatarGlowPresets($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $expected = CssSanitizer::sanitizeAvatarGlowPresets($jsonGlowPresets);
        $this->assertSame($expected, get_option('ssc_avatar_glow_presets', []));
    }

    public function testPresetCollectionFromQueryIsSanitized(): void
    {
        $legacyPresets = [
            'first' => [
                'selector' => 'body',
                'styles' => '<script>alert(1)</script>color:red;margin-top: 10px;behavior:url(foo);',
            ],
        ];

        $request = new WP_REST_Request([
            'presets' => json_encode($legacyPresets, JSON_THROW_ON_ERROR),
        ]);

        $response = $this->routes->savePresets($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $expected = CssSanitizer::sanitizePresetCollection($legacyPresets);
        $this->assertSame($expected, get_option('ssc_presets', []));

        $props = $expected['first']['props'] ?? [];
        $this->assertSame('red', $props['color'] ?? null);
        $this->assertSame('10px', $props['margin-top'] ?? null);
        $this->assertArrayNotHasKey('behavior', $props);
    }

    public function testPresetCollectionFromJsonBodyOverwritesStoredPresets(): void
    {
        $jsonPresets = [
            'second' => [
                'selector' => '.example',
                'styles' => 'color: blue; padding: 4px 8px; behavior:url(foo);',
            ],
        ];

        $request = new WP_REST_Request([], [], ['presets' => $jsonPresets]);
        $response = $this->routes->savePresets($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $expected = CssSanitizer::sanitizePresetCollection($jsonPresets);
        $this->assertSame($expected, get_option('ssc_presets', []));

        $props = $expected['second']['props'] ?? [];
        $this->assertSame('blue', $props['color'] ?? null);
        $this->assertSame('4px 8px', $props['padding'] ?? null);
        $this->assertArrayNotHasKey('behavior', $props);
    }
}
