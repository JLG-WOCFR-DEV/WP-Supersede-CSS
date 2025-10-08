<?php declare(strict_types=1);

final class CssCacheRuntimeTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        global $ssc_css_runtime_cache;

        $ssc_css_runtime_cache = null;
        delete_option('ssc_css_cache');
        delete_option('ssc_css_cache_meta');
        delete_option('ssc_active_css');
        delete_option('ssc_tokens_css');
    }

    public function test_runtime_cache_short_circuits_option_lookups(): void
    {
        update_option('ssc_active_css', 'body { color: red; }', false);
        update_option('ssc_tokens_css', '', false);

        $first = ssc_get_cached_css();
        $this->assertStringContainsString('body', $first);

        add_filter('pre_option_ssc_css_cache', [$this, 'failOnOptionAccess']);
        add_filter('pre_option_ssc_css_cache_meta', [$this, 'failOnOptionAccess']);

        $second = ssc_get_cached_css();
        $this->assertSame($first, $second);

        remove_filter('pre_option_ssc_css_cache', [$this, 'failOnOptionAccess']);
        remove_filter('pre_option_ssc_css_cache_meta', [$this, 'failOnOptionAccess']);

        ssc_invalidate_css_cache();
        update_option('ssc_active_css', 'body { color: blue; }', false);

        $third = ssc_get_cached_css();
        $this->assertStringContainsString('blue', $third);
    }

    public function failOnOptionAccess(): void
    {
        $this->fail('Runtime cache should prevent option lookups once primed.');
    }
}
