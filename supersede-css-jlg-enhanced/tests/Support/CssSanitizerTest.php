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

if (!function_exists('absint')) {
    function absint($value)
    {
        return abs((int) $value);
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

$fontFaceCss = "@font-face { font-family: 'Custom'; src: url('https://example.com/font.woff2') format('woff2'), url('data:font/woff2;base64,AAAA'), url('javascript:alert(1)'); font-display: swap; unicode-range: U+000-5FF; }";
$sanitizedFontFace = CssSanitizer::sanitize($fontFaceCss);

assertSameResult(
    "@font-face {font-family:'Custom'; src:url('https://example.com/font.woff2') format('woff2'), url('data:font/woff2;base64,AAAA'),; font-display:swap; unicode-range:U+000-5FF}",
    $sanitizedFontFace,
    'Font-face declarations should keep src/font-display/unicode-range values while preserving safe URLs.'
);

assertNotContains('javascript', $sanitizedFontFace, 'Dangerous javascript URLs should be stripped from font-face declarations.');

$mediaCss = '@media screen and (min-width: 600px) { .foo { color: red; behavior: url(http://evil); } }';
$sanitizedMedia = CssSanitizer::sanitize($mediaCss);

assertSameResult(
    '@media screen and (min-width: 600px) {.foo {color:red}}',
    $sanitizedMedia,
    '@media blocks should survive sanitation with their nested declarations cleaned.'
);

assertNotContains('behavior', $sanitizedMedia, '@media nested declarations should not retain disallowed properties.');

$supportsCss = '@supports (display: grid) { .grid { display: grid; behavior: url(https://evil); } }';
$sanitizedSupports = CssSanitizer::sanitize($supportsCss);

assertSameResult(
    '@supports (display: grid) {.grid {display:grid}}',
    $sanitizedSupports,
    '@supports blocks should retain nested rules after sanitation.'
);

assertNotContains('behavior', $sanitizedSupports, '@supports nested declarations should remove disallowed properties.');

$keyframesCss = '@keyframes spin { from { transform: rotate(0deg); behavior: url(https://evil); } 50% { transform: rotate(180deg); } to { transform: rotate(360deg); } }';
$sanitizedKeyframes = CssSanitizer::sanitize($keyframesCss);

assertSameResult(
    '@keyframes spin {from {transform:rotate(0deg)} 50% {transform:rotate(180deg)} to {transform:rotate(360deg)}}',
    $sanitizedKeyframes,
    '@keyframes blocks should keep their keyframe selectors and sanitized declarations.'
);

assertNotContains('behavior', $sanitizedKeyframes, '@keyframes nested declarations should strip disallowed properties.');

$presetWithBraceSelector = CssSanitizer::sanitizePresetCollection([
    'dangerous' => [
        'name' => 'Danger',
        'scope' => '.foo { color: red; }',
        'props' => [
            'color' => 'red',
        ],
    ],
]);

assertSameResult(
    '',
    $presetWithBraceSelector['dangerous']['scope'],
    'Selectors containing braces should be rejected during preset sanitation.'
);

$presetWithDirectiveSelector = CssSanitizer::sanitizePresetCollection([
    [
        'name' => 'Directive',
        'scope' => '@media (min-width: 600px)',
        'props' => [
            'color' => 'blue',
        ],
    ],
]);

assertSameResult(
    '',
    $presetWithDirectiveSelector['preset_0']['scope'],
    'Selectors using directives should be rejected during preset sanitation.'
);

echo "All CssSanitizer tests passed." . PHP_EOL;
