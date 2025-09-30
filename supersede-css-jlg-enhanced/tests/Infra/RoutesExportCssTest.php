<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SSC\Infra\Routes;
use SSC\Support\CssSanitizer;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        unset($domain);

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

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        return trim(strip_tags((string) $value));
    }
}

final class RoutesExportCssTest extends TestCase
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

    public function testExportCssConcatenatesAndSanitizesSources(): void
    {
        global $ssc_options_store;

        $tokenCss = "<div>\n:root {\n    --primary-color: #123456;\n}\n</div>";
        $activeCss = "body { color: var(--primary-color); }<script>alert('oops');</script>";

        $ssc_options_store['ssc_tokens_css'] = $tokenCss;
        $ssc_options_store['ssc_active_css'] = $activeCss;

        $response = $this->routes->exportCss();

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $data = $response->get_data();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('css', $data);

        $expectedCss = CssSanitizer::sanitize($tokenCss . "\n" . $activeCss);
        $this->assertSame($expectedCss, $data['css']);
    }

    public function testExportCssFallsBackWhenSourcesEmpty(): void
    {
        $response = $this->routes->exportCss();

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $data = $response->get_data();

        $this->assertIsArray($data);
        $this->assertSame('/* Aucun CSS actif trouvé. */', $data['css'] ?? null);
    }
}
