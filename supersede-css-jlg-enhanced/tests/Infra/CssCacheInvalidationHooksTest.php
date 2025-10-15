<?php declare(strict_types=1);

final class CssCacheInvalidationHooksTest extends WP_UnitTestCase
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
        delete_option('ssc_tokens_registry');
        delete_option('ssc_css_desktop');
        delete_option('ssc_css_tablet');
        delete_option('ssc_css_mobile');
    }

    private function primeCache(): void
    {
        update_option('ssc_css_cache', 'cached', false);
        update_option('ssc_css_cache_meta', ['version' => '1'], false);
    }

    private function assertCacheMeta(array $expected): void
    {
        $meta = get_option('ssc_css_cache_meta');

        $this->assertIsArray($meta);

        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $meta);
            $this->assertSame($value, $meta[$key]);
        }
    }

    public function test_cache_is_invalidated_when_managed_option_is_updated(): void
    {
        $this->primeCache();

        update_option('ssc_active_css', 'body {color:red;}', false);

        $this->assertFalse(get_option('ssc_css_cache'));
        $this->assertCacheMeta([
            'version' => null,
            'status' => 'stale',
        ]);
        $meta = get_option('ssc_css_cache_meta');
        $this->assertArrayHasKey('last_invalidated_at', $meta);
        $this->assertIsInt($meta['last_invalidated_at']);
    }

    public function test_cache_is_not_touched_for_unrelated_options(): void
    {
        $this->primeCache();

        update_option('ssc_unrelated_option', 'value', false);

        $this->assertSame('cached', get_option('ssc_css_cache'));
        $this->assertSame(['version' => '1'], get_option('ssc_css_cache_meta'));
    }

    public function test_cache_is_invalidated_when_managed_option_is_deleted(): void
    {
        update_option('ssc_active_css', 'body {color:red;}', false);
        $this->primeCache();

        delete_option('ssc_active_css');

        $this->assertFalse(get_option('ssc_css_cache'));
        $this->assertCacheMeta([
            'version' => null,
            'status' => 'stale',
        ]);
    }

    public function test_cache_is_invalidated_when_theme_switches(): void
    {
        $this->primeCache();

        do_action('switch_theme');

        $this->assertFalse(get_option('ssc_css_cache'));
        $this->assertCacheMeta([
            'version' => null,
            'status' => 'stale',
        ]);
    }

    public function test_cache_is_invalidated_when_customizer_saves(): void
    {
        $this->primeCache();

        do_action('customize_save_after', null);

        $this->assertFalse(get_option('ssc_css_cache'));
        $this->assertCacheMeta([
            'version' => null,
            'status' => 'stale',
        ]);
    }
}
