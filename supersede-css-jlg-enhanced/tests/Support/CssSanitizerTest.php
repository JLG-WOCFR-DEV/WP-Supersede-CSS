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

$customPropertyWithScrollBehavior = ':root { --snippet: scroll-behavior: smooth; color: red; }';
$sanitizedCustomProperty = CssSanitizer::sanitize($customPropertyWithScrollBehavior);

if (preg_match('/scroll-behavior:\s*smooth/', $sanitizedCustomProperty) !== 1) {
    fwrite(STDERR, 'Custom property snippets should preserve scroll-behavior declarations.' . PHP_EOL);
    fwrite(STDERR, 'Sanitized CSS: ' . $sanitizedCustomProperty . PHP_EOL);
    exit(1);
}

$fontFaceCss = "@font-face { font-family: 'Custom'; src: url('https://example.com/font.woff2') format('woff2'), url('data:font/woff2;base64,AAAA'), url('javascript:alert(1)'); font-display: swap; unicode-range: U+000-5FF; }";
$sanitizedFontFace = CssSanitizer::sanitize($fontFaceCss);

assertSameResult(
    "@font-face {font-family:'Custom'; src:url('https://example.com/font.woff2') format('woff2'), url('data:font/woff2;base64,AAAA'); font-display:swap; unicode-range:U+000-5FF}",
    $sanitizedFontFace,
    'Font-face declarations should keep src/font-display/unicode-range values while preserving safe URLs.'
);

assertNotContains('javascript', $sanitizedFontFace, 'Dangerous javascript URLs should be stripped from font-face declarations.');
assertNotContains(',;', $sanitizedFontFace, 'Sanitized font-face declarations should not contain trailing comma-semicolon sequences.');

$multiBackgroundCss = "body { background-image: url('javascript:alert(1)'), url('https://example.com/safe.png'), url('data:image/png;base64,AAAA'); }";
$sanitizedMultiBackground = CssSanitizer::sanitize($multiBackgroundCss);

assertSameResult(
    "body {background-image:url('https://example.com/safe.png'), url('data:image/png;base64,AAAA')}",
    $sanitizedMultiBackground,
    'Background images with multiple URLs should drop rejected entries without leaving stray commas.'
);

assertNotContains('javascript:alert(1)', $sanitizedMultiBackground, 'Rejected background-image URLs should not remain in the sanitized output.');

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

$cssWithDanglingImport = "@import url(\"foo.css\")\nbody { color: red; }";
$sanitizedDanglingImport = CssSanitizer::sanitize($cssWithDanglingImport);

assertSameResult(
    'body {color:red}',
    $sanitizedDanglingImport,
    '@import rules without a semicolon should be discarded while preserving subsequent blocks.'
);

$quotedExploit = '.foo { content: "</style><script>alert(1)</script>"; color: blue; } .bar { color: __SSC_CSS_TOKEN_0__; }';
$sanitizedExploit = CssSanitizer::sanitize($quotedExploit);

assertNotContains('</style>', $sanitizedExploit, 'Sanitized CSS should not reintroduce closing style tags.');
assertNotContains('<script', $sanitizedExploit, 'Sanitized CSS should not reintroduce script tags.');
assertNotContains('__SSC_CSS_TOKEN_', $sanitizedExploit, 'Sanitized CSS should not leak sanitizer placeholders.');

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

$reflection = new ReflectionClass(CssSanitizer::class);
$sanitizeUrls = $reflection->getMethod('sanitizeUrls');
$sanitizeUrls->setAccessible(true);

assertSameResult(
    'content: "url(foo)"',
    $sanitizeUrls->invoke(null, 'content: "url(foo)"'),
    'Quoted string literals containing url(...) should be preserved during URL sanitization.'
);

assertSameResult(
    "content: 'url(bar)'",
    $sanitizeUrls->invoke(null, "content: 'url(bar)'"),
    'Single-quoted literals that mention url(...) should remain unchanged.'
);

assertSameResult(
    '/* url(foo) inside comment */',
    $sanitizeUrls->invoke(null, '/* url(foo) inside comment */'),
    'Comments containing url(...) should not trigger URL normalization.'
);

assertSameResult(
    'background: url("https://example.com/image.png")',
    $sanitizeUrls->invoke(null, 'background: url(https://example.com/image.png)'),
    'Actual url() tokens should continue to be normalized to safe values.'
);

assertSameResult(
    'background:url("https://example.com/image.png")',
    $sanitizeUrls->invoke(null, 'background:url ( "https://example.com/image.png" )'),
    'URL sanitizer should allow insignificant whitespace between the keyword and the opening parenthesis.'
);

assertSameResult(
    'background:)',
    $sanitizeUrls->invoke(null, 'background: url(javascript:alert(1))'),
    'Dangerous url() tokens should keep being stripped.'
);

assertSameResult(
    'background:',
    $sanitizeUrls->invoke(null, "background:url ( '\\6a\\61\\76\\61\\73\\63\\72\\69\\70\\74:alert(1)' )"),
    'Whitespace before the url parenthesis should not allow escaped dangerous protocols.'
);

assertSameResult(
    'background:',
    $sanitizeUrls->invoke(null, 'background: url("\\6a\\61\\76\\61\\73\\63\\72\\69\\70\\74:alert(1)")'),
    'CSS-escaped dangerous protocols inside url() should be decoded and removed.'
);

assertSameResult(
    "@font-face {src:url('https://example.com/good.woff2') format('woff2')}",
    CssSanitizer::sanitize("@font-face { src: url('javascript:alert(1)') format('woff2'), url('https://example.com/good.woff2') format('woff2'); }"),
    'Rejected font-face src entries should remove their trailing descriptors.'
);

$imageSetCss = 'div { background-image: image-set(url("javascript:alert(1)") 1x type("image/png"), url("https://example.com/safe.png") 2x type("image/png")); }';
$sanitizedImageSet = CssSanitizer::sanitize($imageSetCss);

assertSameResult(
    'div {background-image:image-set(url("https://example.com/safe.png") 2x type("image/png"))}',
    $sanitizedImageSet,
    'Rejected image-set entries should remove associated resolution and type descriptors.'
);

assertNotContains(
    '__SSC_CSS_TOKEN_',
    $sanitizeUrls->invoke(null, '__SSC_CSS_TOKEN_0__'),
    'URL sanitizer should not expose sanitizer placeholder markers.'
);

$danglingBlockCss = 'body { width: expression(alert(1))';
$sanitizedDanglingBlock = CssSanitizer::sanitize($danglingBlockCss);

assertNotContains(
    'expression',
    $sanitizedDanglingBlock,
    'Dangling blocks without a closing brace should not retain disallowed expressions.'
);

assertNotContains(
    'width',
    $sanitizedDanglingBlock,
    'Dangling blocks without a closing brace should not keep unsafe declarations.'
);

$sanitizeImports = $reflection->getMethod('sanitizeImports');
$sanitizeImports->setAccessible(true);

assertNotContains(
    '__SSC_CSS_TOKEN_',
    $sanitizeImports->invoke(null, '@import url(https://example.com); __SSC_CSS_TOKEN_1__'),
    'Import sanitizer should strip unknown sanitizer markers.'
);

assertSameResult(
    '@import url("https://example.com/style.css");',
    $sanitizeImports->invoke(null, '@import "https://example.com/style.css";'),
    'Actual @import at-rules should keep being normalized to safe URLs.'
);

assertSameResult(
    '@import url("https://example.com/style.css");',
    $sanitizeImports->invoke(null, '@import/*comment*/ url("https://example.com/style.css");'),
    'CSS comments adjacent to @import should be stripped before sanitization.'
);

assertSameResult(
    'content: "@import url(foo)"',
    $sanitizeImports->invoke(null, 'content: "@import url(foo)"'),
    'Literal strings containing @import should remain untouched by the import sanitizer.'
);

assertSameResult(
    '--example: "@import url(foo)"',
    $sanitizeImports->invoke(null, '--example: "@import url(foo)"'),
    'Custom property values containing @import should remain untouched by the import sanitizer.'
);

$cssWithDanglingImport = '@import url("https://example.com/style.css")' . PHP_EOL . 'body { color: red; }';
$sanitizedDanglingImport = CssSanitizer::sanitize($cssWithDanglingImport);

assertSameResult(
    'body {color:red}',
    $sanitizedDanglingImport,
    'Dangling @import rules should be dropped while preserving subsequent rule blocks.'
);

echo "All CssSanitizer tests passed." . PHP_EOL;
