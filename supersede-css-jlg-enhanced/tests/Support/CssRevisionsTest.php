<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SSC\Support\CssRevisions;
use SSC\Support\CssSanitizer;

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
$ssc_options_store = [];

global $ssc_options_store;

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

        if (isset($GLOBALS['ssc_deleted_options']) && is_array($GLOBALS['ssc_deleted_options'])) {
            $GLOBALS['ssc_deleted_options'][] = (string) $name;
        }

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

final class CssRevisionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        global $ssc_options_store, $ssc_cache_invalidations;

        $ssc_options_store = [
            'ssc_active_css' => 'body { color: blue; }',
            'ssc_css_desktop' => 'body { color: blue; }',
            'ssc_css_tablet' => '',
            'ssc_css_mobile' => '',
            'ssc_css_revisions' => [],
        ];
        $ssc_cache_invalidations = 0;
    }

    public function testRecordSanitizesInputAndRestoreRewritesOptions(): void
    {
        $rawCss = "body { color: red; }<script>alert('oops');</script>";
        $rawSegments = [
            'desktop' => 'body { color: red; }',
            'tablet' => '<script>bad()</script>',
            'mobile' => '',
        ];

        CssRevisions::record('ssc_active_css', $rawCss, ['segments' => $rawSegments]);

        $revisions = CssRevisions::all();
        $this->assertCount(1, $revisions, 'A single revision should be available after recording once.');

        $revision = $revisions[0];
        $this->assertSame('revision_tester', $revision['author']);

        $expectedCss = CssSanitizer::sanitize($rawCss);
        $this->assertSame($expectedCss, $revision['css']);

        foreach ($rawSegments as $key => $value) {
            $this->assertSame(
                CssSanitizer::sanitize((string) $value),
                $revision['segments'][$key] ?? null,
                sprintf('The "%s" segment should be sanitized in the stored revision.', $key)
            );
        }

        update_option('ssc_active_css', 'body { color: green; }', false);
        update_option('ssc_css_desktop', 'body { color: green; }', false);
        update_option('ssc_css_tablet', 'body { color: green; }', false);
        update_option('ssc_css_mobile', 'body { color: green; }', false);

        $restored = CssRevisions::restore($revision['id']);
        $this->assertNotNull($restored, 'Restoring a known revision should return its payload.');
        $this->assertSame($expectedCss, get_option('ssc_active_css'));

        $expectedSegments = [
            'desktop' => 'ssc_css_desktop',
            'tablet' => 'ssc_css_tablet',
            'mobile' => 'ssc_css_mobile',
        ];

        foreach ($expectedSegments as $segmentKey => $optionName) {
            $this->assertSame(
                CssSanitizer::sanitize((string) $rawSegments[$segmentKey]),
                get_option($optionName),
                sprintf('Restoring should rewrite the %s segment option.', $segmentKey)
            );
        }

        global $ssc_cache_invalidations;
        $this->assertGreaterThanOrEqual(1, $ssc_cache_invalidations, 'Restoring a revision should invalidate the CSS cache.');
    }

    public function testRevisionHistoryIsTrimmedToMaximumSize(): void
    {
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
        $this->assertIsArray($stored);
        $this->assertCount($maxRevisions, $stored, 'The revision history should be trimmed to the configured maximum size.');

        $latest = CssRevisions::all()[0] ?? null;
        $latestCss = sprintf('.test-%d { color: #%1$02d%1$02d%1$02d; }', ($maxRevisions + 2) % 99);
        $expectedLatestCss = CssSanitizer::sanitize($latestCss);

        $this->assertNotNull($latest);
        $this->assertSame($expectedLatestCss, $latest['css'], 'The newest revision should be kept at the beginning of the stack.');
    }
}
