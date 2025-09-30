<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SSC\Infra\Routes;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        $key = strtolower((string) $key);

        return preg_replace('/[^a-z0-9_]/', '', $key);
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
        return (string) $string;
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses($string, $allowed_html)
    {
        unset($allowed_html);

        return strip_tags((string) $string);
    }
}

final class RoutesImportJsonLimitTest extends TestCase
{
    private Routes $routes;

    private ReflectionMethod $sanitizeImportArray;

    private int $maxDepth;

    protected function setUp(): void
    {
        parent::setUp();

        $reflection = new \ReflectionClass(Routes::class);
        $this->routes = $reflection->newInstanceWithoutConstructor();

        $this->sanitizeImportArray = $reflection->getMethod('sanitizeImportArray');
        $this->sanitizeImportArray->setAccessible(true);

        $this->maxDepth = (int) $reflection->getConstant('IMPORT_MAX_DEPTH');
    }

    public function testDeepJsonStringsAreRejectedWhenSanitizingImports(): void
    {
        $payload = [];
        $cursor = &$payload;

        for ($i = 0; $i < $this->maxDepth; $i++) {
            $key = 'layer_' . $i;
            $cursor[$key] = [];
            $cursor = &$cursor[$key];
        }

        $cursor['encoded'] = json_encode(['should' => 'fail'], JSON_UNESCAPED_SLASHES);

        $sanitized = $this->sanitizeImportArray->invoke($this->routes, $payload);
        $this->assertIsArray($sanitized);

        $cursor = $sanitized;
        for ($i = 0; $i < $this->maxDepth; $i++) {
            $key = 'layer_' . $i;
            $this->assertArrayHasKey($key, $cursor);
            $this->assertIsArray($cursor[$key]);
            $cursor = $cursor[$key];
        }

        $this->assertSame('', $cursor['encoded'] ?? null);
    }
}
