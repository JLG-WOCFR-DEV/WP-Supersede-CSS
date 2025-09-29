<?php declare(strict_types=1);

use SSC\Infra\CssRevisions;

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

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user()
    {
        return (object) [
            'ID' => 1,
            'user_login' => 'tester',
        ];
    }
}

/** @var array<string, mixed> $ssc_options_store */
$ssc_options_store = [];

/** @var int $ssc_cache_invalidations */
$ssc_cache_invalidations = 0;

global $ssc_options_store, $ssc_cache_invalidations;

if (!function_exists('ssc_invalidate_css_cache')) {
    function ssc_invalidate_css_cache(): void
    {
        global $ssc_cache_invalidations;

        $ssc_cache_invalidations++;
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

require_once __DIR__ . '/../../src/Infra/CssRevisions.php';

CssRevisions::record('ssc_active_css', 'body { color: red; }');
update_option('ssc_active_css', 'body { color: red; }');

$revisions = get_option('ssc_css_revisions', []);

if (!is_array($revisions) || count($revisions) !== 1) {
    fwrite(STDERR, 'Recording an initial revision should store exactly one entry.' . PHP_EOL);
    exit(1);
}

$firstRevision = $revisions[0];

if (($firstRevision['user'] ?? null) !== 'tester') {
    fwrite(STDERR, 'Revisions should record the acting user login.' . PHP_EOL);
    exit(1);
}

CssRevisions::record('ssc_active_css', 'body { color: blue; }');
update_option('ssc_active_css', 'body { color: blue; }');

$revisions = get_option('ssc_css_revisions', []);

if (!is_array($revisions) || count($revisions) !== 2) {
    fwrite(STDERR, 'Recording a second revision should keep two entries in chronological order.' . PHP_EOL);
    exit(1);
}

$olderRevision = $revisions[1];
$olderRevisionId = $olderRevision['id'] ?? '';

if (!is_string($olderRevisionId) || $olderRevisionId === '') {
    fwrite(STDERR, 'Each revision should expose a string identifier.' . PHP_EOL);
    exit(1);
}

if (!CssRevisions::restore($olderRevisionId)) {
    fwrite(STDERR, 'Restoring an existing revision should return true.' . PHP_EOL);
    exit(1);
}

if (get_option('ssc_active_css') !== 'body { color: red; }') {
    fwrite(STDERR, 'Restoring a revision should update the targeted option with the stored CSS.' . PHP_EOL);
    exit(1);
}

if ($ssc_cache_invalidations !== 1) {
    fwrite(STDERR, 'Restoring a revision should invalidate the CSS cache once.' . PHP_EOL);
    exit(1);
}

$revisionsAfterRestore = get_option('ssc_css_revisions', []);

if (($revisionsAfterRestore[0]['css'] ?? null) !== 'body { color: red; }') {
    fwrite(STDERR, 'Restoring a revision should promote it to the top of the stack.' . PHP_EOL);
    exit(1);
}

if (($revisionsAfterRestore[1]['css'] ?? null) !== 'body { color: blue; }') {
    fwrite(STDERR, 'Restoring a revision should keep more recent history entries.' . PHP_EOL);
    exit(1);
}

$reflection = new ReflectionClass(CssRevisions::class);
$maxRevisions = (int) $reflection->getConstant('MAX_REVISIONS');

for ($index = 0; $index < $maxRevisions + 5; $index++) {
    $css = sprintf('body { color: #%02d%02d%02d; }', $index, $index, $index);
    CssRevisions::record('ssc_active_css', $css);
}

$boundedRevisions = get_option('ssc_css_revisions', []);

if (!is_array($boundedRevisions) || count($boundedRevisions) !== $maxRevisions) {
    fwrite(STDERR, 'The revision stack should be capped to the configured maximum.' . PHP_EOL);
    exit(1);
}

$expectedLatest = sprintf('body { color: #%02d%02d%02d; }', $maxRevisions + 4, $maxRevisions + 4, $maxRevisions + 4);

if (($boundedRevisions[0]['css'] ?? null) !== $expectedLatest) {
    fwrite(STDERR, 'The newest CSS should be available at the top of the bounded stack.' . PHP_EOL);
    exit(1);
}

if (CssRevisions::restore('non-existent')) {
    fwrite(STDERR, 'Restoring a non-existent revision should fail gracefully.' . PHP_EOL);
    exit(1);
}
