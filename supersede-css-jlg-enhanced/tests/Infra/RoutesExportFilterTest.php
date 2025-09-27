<?php declare(strict_types=1);

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
        global $ssc_options_store;

        $ssc_options_store[$name] = $value;

        return true;
    }
}

/** @var array<string, mixed> $ssc_options_store */
$ssc_options_store = [];

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return $value;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, $value)
    {
        return $value;
    }
}

class DummyWpdb {
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

require_once __DIR__ . '/../../src/Infra/Routes.php';

global $wpdb;
$wpdb = new DummyWpdb([
    'ssc_settings' => ['color' => 'red'],
    'ssc_presets' => ['hero' => ['id' => 'hero-1']],
    'ssc_tokens_css' => ':root { --primary: #fff; }',
    'ssc_tokens_registry' => [['name' => '--primary', 'value' => '#fff', 'type' => 'color']],
    'ssc_custom_extra' => 'custom-value',
]);

$routesReflection = new ReflectionClass(Routes::class);
$routes = $routesReflection->newInstanceWithoutConstructor();

$exportAll = $routes->exportConfig(new WP_REST_Request());

if (!$exportAll instanceof WP_REST_Response) {
    fwrite(STDERR, 'Exporting without module filters should return a WP_REST_Response.' . PHP_EOL);
    exit(1);
}

$allData = $exportAll->get_data();

if (!is_array($allData) || !array_key_exists('ssc_custom_extra', $allData)) {
    fwrite(STDERR, 'Exporting all modules should keep custom options that are not mapped.' . PHP_EOL);
    exit(1);
}

$exportExplicitAll = $routes->exportConfig(new WP_REST_Request(['modules' => ['all']]));

if (!$exportExplicitAll instanceof WP_REST_Response) {
    fwrite(STDERR, 'Exporting with an explicit "all" module selection should return a WP_REST_Response.' . PHP_EOL);
    exit(1);
}

$explicitAllData = $exportExplicitAll->get_data();

if (!is_array($explicitAllData) || array_key_exists('ssc_custom_extra', $explicitAllData)) {
    fwrite(STDERR, 'Explicit "all" module selection should filter out options that are not part of any module.' . PHP_EOL);
    exit(1);
}

$expectedAllKeys = [
    'ssc_settings',
    'ssc_presets',
    'ssc_tokens_css',
    'ssc_tokens_registry',
];

foreach ($expectedAllKeys as $expectedKey) {
    if (!array_key_exists($expectedKey, $explicitAllData)) {
        fwrite(STDERR, 'Explicit "all" module selection should keep whitelisted option ' . $expectedKey . '.' . PHP_EOL);
        exit(1);
    }
}

$exportTokens = $routes->exportConfig(new WP_REST_Request(['modules' => ['tokens']]));

if (!$exportTokens instanceof WP_REST_Response) {
    fwrite(STDERR, 'Exporting with module filters should return a WP_REST_Response.' . PHP_EOL);
    exit(1);
}

$tokenData = $exportTokens->get_data();

if (!is_array($tokenData) || array_key_exists('ssc_settings', $tokenData) || array_key_exists('ssc_custom_extra', $tokenData)) {
    fwrite(STDERR, 'Module filtering should exclude settings and custom options when only tokens are requested.' . PHP_EOL);
    exit(1);
}

foreach (['ssc_tokens_css', 'ssc_tokens_registry'] as $expectedOption) {
    if (!array_key_exists($expectedOption, $tokenData)) {
        fwrite(STDERR, 'Token export should keep ' . $expectedOption . ' when filtering modules.' . PHP_EOL);
        exit(1);
    }
}

$ssc_options_store = [
    'ssc_settings' => ['color' => 'blue'],
    'ssc_presets' => ['existing' => true],
];

$importPayload = [
    'options' => [
        'ssc_settings' => ['color' => 'green', 'spacing' => 'large'],
        'ssc_presets' => ['new' => ['id' => 'p-1']],
    ],
    'modules' => ['settings'],
];

$importResponse = $routes->importConfig(new WP_REST_Request([], $importPayload));

if (!$importResponse instanceof WP_REST_Response || $importResponse->get_status() !== 200) {
    fwrite(STDERR, 'Importing with a valid module selection should succeed.' . PHP_EOL);
    exit(1);
}

if ($ssc_options_store['ssc_settings']['color'] !== 'green') {
    fwrite(STDERR, 'Filtered import should update settings.' . PHP_EOL);
    exit(1);
}

if ($ssc_options_store['ssc_presets'] !== ['existing' => true]) {
    fwrite(STDERR, 'Filtered import should not touch presets when module is not selected.' . PHP_EOL);
    exit(1);
}

$importData = $importResponse->get_data();

if (!in_array('ssc_settings', $importData['applied'], true)) {
    fwrite(STDERR, 'Filtered import should report settings as applied.' . PHP_EOL);
    exit(1);
}

if (!in_array('ssc_presets', $importData['skipped'], true)) {
    fwrite(STDERR, 'Filtered import should report presets as skipped.' . PHP_EOL);
    exit(1);
}
