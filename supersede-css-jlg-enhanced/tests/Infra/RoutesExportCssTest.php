<?php declare(strict_types=1);

use SSC\Infra\Routes;
use SSC\Support\CssSanitizer;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        return $text;
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

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($value)
    {
        return is_string($value) ? strip_tags($value) : $value;
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

require_once __DIR__ . '/../../src/Support/CssSanitizer.php';
require_once __DIR__ . '/../../src/Infra/Routes.php';

$routesReflection = new ReflectionClass(Routes::class);
$routes = $routesReflection->newInstanceWithoutConstructor();

$tokenCss = ":root {\n    --primary-color: #123456;\n}";
$activeCss = "body { color: var(--primary-color); }";

$ssc_options_store['ssc_tokens_css'] = $tokenCss;
$ssc_options_store['ssc_active_css'] = $activeCss;

$response = $routes->exportCss();

if (!$response instanceof WP_REST_Response) {
    fwrite(STDERR, 'Expected exportCss to return a WP_REST_Response.' . PHP_EOL);
    exit(1);
}

$data = $response->get_data();

if (!is_array($data) || !array_key_exists('css', $data)) {
    fwrite(STDERR, 'Expected exportCss payload to include a css key.' . PHP_EOL);
    exit(1);
}

$expectedCss = CssSanitizer::sanitize($tokenCss . "\n" . $activeCss);

if ($data['css'] !== $expectedCss) {
    fwrite(STDERR, 'Expected exportCss to concatenate and sanitize token and active CSS.' . PHP_EOL);
    exit(1);
}

unset($ssc_options_store['ssc_tokens_css'], $ssc_options_store['ssc_active_css']);

$response = $routes->exportCss();

if (!($response instanceof WP_REST_Response)) {
    fwrite(STDERR, 'Expected exportCss fallback response to be a WP_REST_Response.' . PHP_EOL);
    exit(1);
}

$data = $response->get_data();

if (!is_array($data) || ($data['css'] ?? '') !== '/* Aucun CSS actif trouvé. */') {
    fwrite(STDERR, 'Expected fallback message when both CSS sources are empty.' . PHP_EOL);
    exit(1);
}
