<?php declare(strict_types=1);

use SSC\Support\CssSanitizer;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
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

require_once __DIR__ . '/../../src/Support/CssSanitizer.php';

function assertSameResult(string $expected, string $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . $expected . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . $actual . PHP_EOL);
        exit(1);
    }
}

function assertNotContains(string $needle, string $haystack, string $message): void
{
    if (strpos($haystack, $needle) !== false) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$cssWithLiteralBrace = '.foo::before { content: "{"; behavior: url(http://evil); }';
$sanitizedWithLiteralBrace = CssSanitizer::sanitize($cssWithLiteralBrace);

assertSameResult(
    '.foo::before {content:"{"}',
    $sanitizedWithLiteralBrace,
    'Literal brace content should be preserved while behavior is removed.'
);

assertNotContains('behavior', $sanitizedWithLiteralBrace, 'Behavior property should be stripped.');

$cssWithDoubleBrace = '.foo::before { content: "{}"; behavior: url(http://evil); }';
$sanitizedWithDoubleBrace = CssSanitizer::sanitize($cssWithDoubleBrace);

assertSameResult(
    '.foo::before {content:"{}"}',
    $sanitizedWithDoubleBrace,
    'Double brace content should remain intact and behavior removed.'
);

assertNotContains('behavior', $sanitizedWithDoubleBrace, 'Behavior property should not survive in the sanitized CSS.');

echo "All CssSanitizer tests passed." . PHP_EOL;
