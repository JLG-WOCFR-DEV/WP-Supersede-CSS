<?php declare(strict_types=1);

use SSC\Support\CssRevisions;

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

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return $value;
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses($string, $allowed_html)
    {
        unset($allowed_html);

        return strip_tags((string) $string);
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

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($value, $options = 0, $depth = 512)
    {
        return json_encode($value, $options, $depth);
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user()
    {
        return (object) [
            'ID' => 42,
            'user_login' => 'revision_tester',
        ];
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
$ssc_options_store = [
    'ssc_active_css' => 'body { color: blue; }',
    'ssc_css_desktop' => 'body { color: blue; }',
    'ssc_css_tablet' => '',
    'ssc_css_mobile' => '',
];

global $ssc_options_store;

/** @var array<string, array<int, array{callback: callable, accepted_args: int}>> $ssc_filters_store */
$ssc_filters_store = [];

global $ssc_filters_store;

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        global $ssc_filters_store;

        $priority = (int) $priority;
        if (!isset($ssc_filters_store[$hook])) {
            $ssc_filters_store[$hook] = [];
        }
        if (!isset($ssc_filters_store[$hook][$priority])) {
            $ssc_filters_store[$hook][$priority] = [];
        }

        $ssc_filters_store[$hook][$priority][] = [
            'callback' => $callback,
            'accepted_args' => max(1, (int) $accepted_args),
        ];

        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value)
    {
        global $ssc_filters_store;

        $args = func_get_args();

        if (!isset($ssc_filters_store[$hook]) || $ssc_filters_store[$hook] === []) {
            return $value;
        }

        ksort($ssc_filters_store[$hook]);

        foreach ($ssc_filters_store[$hook] as $callbacks) {
            foreach ($callbacks as $entry) {
                $accepted = max(1, (int) ($entry['accepted_args'] ?? 1));
                $params = [$value];
                if ($accepted > 1) {
                    $additional = array_slice($args, 2, $accepted - 1);
                    $params = array_merge($params, $additional);
                }

                $value = call_user_func_array($entry['callback'], $params);
            }
        }

        return $value;
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

if (!function_exists('delete_option')) {
    function delete_option($name)
    {
        global $ssc_options_store;

        unset($ssc_options_store[$name]);

        return true;
    }
}

/** @var int $ssc_cache_invalidations */
$ssc_cache_invalidations = 0;

global $ssc_cache_invalidations;

if (!function_exists('ssc_invalidate_css_cache')) {
    function ssc_invalidate_css_cache(): void
    {
        global $ssc_cache_invalidations;

        $ssc_cache_invalidations++;
    }
}

require_once __DIR__ . '/../../src/Support/CssSanitizer.php';
require_once __DIR__ . '/../../src/Support/TokenRegistry.php';
require_once __DIR__ . '/../../src/Support/CssRevisions.php';

$rawCss = "body { color: red; }<script>alert('oops');</script>";
$rawSegments = [
    'desktop' => 'body { color: red; }',
    'tablet' => '<script>bad()</script>',
    'mobile' => '',
];

CssRevisions::record('ssc_active_css', $rawCss, ['segments' => $rawSegments]);

$revisions = CssRevisions::all();
if (count($revisions) !== 1) {
    fwrite(STDERR, 'A single revision should be available after recording once.' . PHP_EOL);
    exit(1);
}

$revision = $revisions[0];

if ($revision['author'] !== 'revision_tester') {
    fwrite(STDERR, 'The recorded revision should persist the current user as author.' . PHP_EOL);
    exit(1);
}

$expectedCss = \SSC\Support\CssSanitizer::sanitize($rawCss);
if ($revision['css'] !== $expectedCss) {
    fwrite(STDERR, 'The stored revision CSS should be sanitized.' . PHP_EOL);
    exit(1);
}

$expectedSegments = [];
foreach ($rawSegments as $key => $value) {
    $expectedSegments[$key] = \SSC\Support\CssSanitizer::sanitize((string) $value);
}

foreach ($expectedSegments as $segmentKey => $sanitizedValue) {
    $storedValue = $revision['segments'][$segmentKey] ?? null;
    if ($storedValue !== $sanitizedValue) {
        fwrite(STDERR, sprintf('The "%s" segment should be sanitized in the stored revision.', $segmentKey) . PHP_EOL);
        exit(1);
    }
}

$revisionId = $revision['id'];

update_option('ssc_active_css', 'body { color: green; }', false);
update_option('ssc_css_desktop', 'body { color: green; }', false);
update_option('ssc_css_tablet', 'body { color: green; }', false);
update_option('ssc_css_mobile', 'body { color: green; }', false);

$ssc_cache_invalidations = 0;
$restored = CssRevisions::restore($revisionId);

if ($restored === null) {
    fwrite(STDERR, 'Restoring a known revision should return its payload.' . PHP_EOL);
    exit(1);
}

if (get_option('ssc_active_css') !== $expectedCss) {
    fwrite(STDERR, 'Restoring a revision should rewrite the main CSS option.' . PHP_EOL);
    exit(1);
}

foreach ($expectedSegments as $segmentKey => $sanitizedValue) {
    $optionName = [
        'desktop' => 'ssc_css_desktop',
        'tablet' => 'ssc_css_tablet',
        'mobile' => 'ssc_css_mobile',
    ][$segmentKey];

    if (get_option($optionName) !== $sanitizedValue) {
        fwrite(STDERR, sprintf('Restoring should rewrite the %s segment option.', $segmentKey) . PHP_EOL);
        exit(1);
    }
}

if ($ssc_cache_invalidations < 1) {
    fwrite(STDERR, 'Restoring a revision should invalidate the CSS cache.' . PHP_EOL);
    exit(1);
}

// Reset the revision store to validate the maximum retention count.
update_option('ssc_css_revisions', []);

$ref = new ReflectionClass(CssRevisions::class);
$maxRevisions = (int) $ref->getConstant('MAX_REVISIONS');

for ($i = 0; $i < $maxRevisions + 3; $i++) {
    $css = sprintf('.test-%d { color: #%1$02d%1$02d%1$02d; }', $i % 99);
    CssRevisions::record('ssc_active_css', $css, ['segments' => [
        'desktop' => $css,
        'tablet' => '',
        'mobile' => '',
    ]]);
}

$stored = get_option('ssc_css_revisions', []);

if (!is_array($stored) || count($stored) !== $maxRevisions) {
    fwrite(STDERR, 'The revision history should be trimmed to the configured maximum size.' . PHP_EOL);
    exit(1);
}

$ssc_filters_store = [];
add_filter('ssc_css_revisions_max', static function ($value) {
    unset($value);

    return 5;
});

update_option('ssc_css_revisions', []);

for ($i = 0; $i < 8; $i++) {
    $css = sprintf('.filtered-%d { color: #%1$02d%1$02d%1$02d; }', $i % 99);
    CssRevisions::record('ssc_active_css', $css, ['segments' => [
        'desktop' => $css,
        'tablet' => '',
        'mobile' => '',
    ]]);
}

$filteredStored = get_option('ssc_css_revisions', []);

if (!is_array($filteredStored) || count($filteredStored) !== 5) {
    fwrite(STDERR, 'The revision limit should be overridable via the ssc_css_revisions_max filter.' . PHP_EOL);
    exit(1);
}

$ssc_filters_store = [];

$latest = CssRevisions::all()[0] ?? null;
$latestCss = sprintf('.test-%d { color: #%1$02d%1$02d%1$02d; }', ($maxRevisions + 2) % 99);
$expectedLatestCss = \SSC\Support\CssSanitizer::sanitize($latestCss);

if (!$latest || $latest['css'] !== $expectedLatestCss) {
    fwrite(STDERR, 'The newest revision should be kept at the beginning of the stack.' . PHP_EOL);
    exit(1);
}

$duplicateTokensCss = ':root { --color-duplicate: #111111; --Color-Duplicate: #222222; }';
CssRevisions::record('ssc_tokens_css', $duplicateTokensCss);

$tokenRevision = null;
foreach (CssRevisions::all() as $candidate) {
    if (($candidate['option'] ?? '') === 'ssc_tokens_css') {
        $tokenRevision = $candidate;
        break;
    }
}

if ($tokenRevision === null) {
    fwrite(STDERR, 'A revision targeting the tokens CSS should be stored.' . PHP_EOL);
    exit(1);
}

update_option('ssc_tokens_registry', []);
$originalTokensCss = '/* existing tokens css */';
update_option('ssc_tokens_css', $originalTokensCss);

$ssc_cache_invalidations = 0;
$duplicateRestore = CssRevisions::restore($tokenRevision['id']);

if (!is_array($duplicateRestore) || ($duplicateRestore['error'] ?? '') !== 'tokens_duplicates') {
    fwrite(STDERR, 'Restoring a tokens revision with duplicates should expose an error structure.' . PHP_EOL);
    exit(1);
}

if (!isset($duplicateRestore['revision']['id']) || $duplicateRestore['revision']['id'] !== $tokenRevision['id']) {
    fwrite(STDERR, 'The error payload should reference the attempted revision.' . PHP_EOL);
    exit(1);
}

$duplicates = $duplicateRestore['duplicates'] ?? [];
if (!is_array($duplicates) || $duplicates === []) {
    fwrite(STDERR, 'Duplicate conflicts should be returned alongside the error payload.' . PHP_EOL);
    exit(1);
}

if (get_option('ssc_tokens_css') !== $originalTokensCss) {
    fwrite(STDERR, 'The tokens CSS option should remain unchanged when duplicates are detected.' . PHP_EOL);
    exit(1);
}

if ($ssc_cache_invalidations !== 0) {
    fwrite(STDERR, 'Failing to restore a tokens revision should not invalidate the CSS cache.' . PHP_EOL);
    exit(1);
}
