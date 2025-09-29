<?php declare(strict_types=1);

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

require_once __DIR__ . '/../../src/Support/CssSanitizer.php';
require_once __DIR__ . '/../../src/Support/TokenRegistry.php';
require_once __DIR__ . '/../../src/Infra/Logger.php';
require_once __DIR__ . '/../../src/Infra/Routes.php';

$routesReflection = new ReflectionClass(Routes::class);
$routes = $routesReflection->newInstanceWithoutConstructor();

$sanitizeImportArray = $routesReflection->getMethod('sanitizeImportArray');
$sanitizeImportArray->setAccessible(true);

$maxDepthConst = $routesReflection->getReflectionConstant('IMPORT_MAX_DEPTH');

if ($maxDepthConst === false) {
    fwrite(STDERR, 'Unable to read import depth limit from Routes class.' . PHP_EOL);
    exit(1);
}

$maxDepth = (int) $maxDepthConst->getValue();

$payload = [];
$cursor = &$payload;

for ($i = 0; $i < $maxDepth; $i++) {
    $key = 'layer_' . $i;
    $cursor[$key] = [];
    $cursor = &$cursor[$key];
}

$cursor['encoded'] = json_encode(['should' => 'fail'], JSON_UNESCAPED_SLASHES);

$sanitized = $sanitizeImportArray->invoke($routes, $payload);

if (!is_array($sanitized)) {
    fwrite(STDERR, 'Sanitized payload should remain an array.' . PHP_EOL);
    exit(1);
}

$cursor = $sanitized;

for ($i = 0; $i < $maxDepth; $i++) {
    $key = 'layer_' . $i;

    if (!isset($cursor[$key]) || !is_array($cursor[$key])) {
        fwrite(STDERR, 'Expected nested layers to be preserved during sanitization.' . PHP_EOL);
        exit(1);
    }

    $cursor = $cursor[$key];
}

if (($cursor['encoded'] ?? null) !== '') {
    fwrite(STDERR, 'JSON strings exceeding depth limit should be rejected when sanitizing imports.' . PHP_EOL);
    exit(1);
}

