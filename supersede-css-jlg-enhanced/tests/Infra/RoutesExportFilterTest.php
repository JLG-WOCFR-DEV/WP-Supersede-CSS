<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SSC\Infra\Routes;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        public function __construct(
            private string $code = '',
            private string $message = '',
            private array $data = []
        ) {
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        public function get_error_data(): array
        {
            return $this->data;
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

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        /** @var array<string, mixed> */
        private array $params;

        /** @var array<string, mixed> */
        private array $json;

        /**
         * @param array<string, mixed> $params
         * @param array<string, mixed> $json
         */
        public function __construct(array $params = [], array $json = [])
        {
            $this->params = $params;
            $this->json = $json === [] ? $params : $json;
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
            return $this->json;
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

if (!function_exists('wp_check_invalid_utf8')) {
    function wp_check_invalid_utf8($string)
    {
        return is_string($string) ? $string : '';
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        unset($domain);

        return $text;
    }
}

if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize($value)
    {
        return $value;
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($value)
    {
        return is_string($value) ? strip_tags($value) : $value;
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses($value, $allowed_html = [])
    {
        unset($allowed_html);

        return is_string($value) ? strip_tags($value) : $value;
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

/** @var array<string, mixed> $ssc_options_store */
$ssc_options_store = [];

global $ssc_options_store;

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return $value;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, $value)
    {
        unset($hook);

        return $value;
    }
}

class DummyWpdb
{
    public string $options = 'wp_options';

    public string $last_error = '';

    /** @var array<string, mixed> */
    private array $store;

    /** @param array<string, mixed> $store */
    public function __construct(array $store)
    {
        $this->store = $store;
    }

    public function esc_like(string $text): string
    {
        return $text;
    }

    public function prepare(string $query, string $value): string
    {
        unset($value);

        return $query;
    }

    /**
     * @return array<int, object>
     */
    public function get_results(string $query): array
    {
        unset($query);
        $results = [];

        foreach ($this->store as $name => $value) {
            $row = new stdClass();
            $row->option_name = $name;
            $row->option_value = $value;
            $results[] = $row;
        }

        return $results;
    }
}

final class RoutesExportFilterTest extends TestCase
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

    public function testExportingWithoutModuleFiltersKeepsCustomOptions(): void
    {
        global $wpdb;
        $wpdb = new DummyWpdb([
            'ssc_settings' => ['color' => 'red'],
            'ssc_presets' => ['hero' => ['id' => 'hero-1']],
            'ssc_tokens_css' => ':root { --primary: #fff; }',
            'ssc_tokens_registry' => [['name' => '--primary', 'value' => '#fff', 'type' => 'color']],
            'ssc_custom_extra' => 'custom-value',
        ]);

        $response = $this->routes->exportConfig(new WP_REST_Request());
        $this->assertInstanceOf(WP_REST_Response::class, $response);

        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('ssc_custom_extra', $data);
    }

    public function testExplicitAllModuleSelectionFiltersUnmappedOptions(): void
    {
        global $wpdb;
        $wpdb = new DummyWpdb([
            'ssc_settings' => ['color' => 'red'],
            'ssc_presets' => ['hero' => ['id' => 'hero-1']],
            'ssc_tokens_css' => ':root { --primary: #fff; }',
            'ssc_tokens_registry' => [['name' => '--primary', 'value' => '#fff', 'type' => 'color']],
            'ssc_custom_extra' => 'custom-value',
        ]);

        $response = $this->routes->exportConfig(new WP_REST_Request(['modules' => ['all']]));
        $this->assertInstanceOf(WP_REST_Response::class, $response);

        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertArrayNotHasKey('ssc_custom_extra', $data);

        foreach (['ssc_settings', 'ssc_presets', 'ssc_tokens_css', 'ssc_tokens_registry'] as $expectedKey) {
            $this->assertArrayHasKey($expectedKey, $data);
        }
    }

    public function testModuleFilteringLimitsOptions(): void
    {
        global $wpdb;
        $wpdb = new DummyWpdb([
            'ssc_settings' => ['color' => 'red'],
            'ssc_presets' => ['hero' => ['id' => 'hero-1']],
            'ssc_tokens_css' => ':root { --primary: #fff; }',
            'ssc_tokens_registry' => [['name' => '--primary', 'value' => '#fff', 'type' => 'color']],
        ]);

        $response = $this->routes->exportConfig(new WP_REST_Request(['modules' => ['tokens']]));
        $this->assertInstanceOf(WP_REST_Response::class, $response);

        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertArrayNotHasKey('ssc_settings', $data);

        foreach (['ssc_tokens_css', 'ssc_tokens_registry'] as $expectedOption) {
            $this->assertArrayHasKey($expectedOption, $data);
        }
    }

    public function testImportConfigAppliesOnlySelectedModules(): void
    {
        global $ssc_options_store;
        $ssc_options_store = [
            'ssc_settings' => ['color' => 'blue'],
            'ssc_presets' => ['existing' => true],
        ];

        $payload = [
            'options' => [
                'ssc_settings' => ['color' => 'green', 'spacing' => 'large'],
                'ssc_presets' => ['new' => ['id' => 'p-1']],
            ],
            'modules' => ['settings'],
        ];

        $response = $this->routes->importConfig(new WP_REST_Request([], [], $payload));
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $this->assertSame('green', $ssc_options_store['ssc_settings']['color']);
        $this->assertSame(['existing' => true], $ssc_options_store['ssc_presets']);

        $data = $response->get_data();
        $this->assertContains('ssc_settings', $data['applied']);
        $this->assertContains('ssc_presets', $data['skipped']);
    }
}
