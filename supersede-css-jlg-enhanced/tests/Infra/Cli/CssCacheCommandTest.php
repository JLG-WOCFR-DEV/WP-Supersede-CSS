<?php declare(strict_types=1);

use SSC\Infra\Cli\CssCacheCommand;

final class CssCacheCommandTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        delete_option('ssc_css_cache');
        delete_option('ssc_css_cache_meta');
        delete_option('ssc_active_css');
        delete_option('ssc_tokens_css');
    }

    public function test_execute_without_existing_cache_does_not_rebuild(): void
    {
        $command = new CssCacheCommand();

        $result = $command->execute();

        $this->assertSame('success', $result['status']);
        $this->assertFalse($result['had_cache']);
        $this->assertFalse($result['rebuilt']);
        $this->assertSame(0, $result['size']);
        $this->assertFalse(get_option('ssc_css_cache'));
        $meta = get_option('ssc_css_cache_meta');
        $this->assertIsArray($meta);
        $this->assertSame('stale', $meta['status']);
        $this->assertNull($meta['version']);
        $this->assertStringContainsString('--rebuild', $result['message']);
    }

    public function test_execute_with_rebuild_regenerates_cache(): void
    {
        update_option('ssc_active_css', 'body { color: red; }', false);

        $command = new CssCacheCommand();

        $result = $command->execute(['rebuild' => true]);

        $expectedCss = 'body {color:red}';

        $this->assertSame('success', $result['status']);
        $this->assertFalse($result['had_cache']);
        $this->assertTrue($result['rebuilt']);
        $this->assertSame(strlen($expectedCss), $result['size']);
        $this->assertSame($expectedCss, get_option('ssc_css_cache'));
        $meta = get_option('ssc_css_cache_meta');
        $this->assertIsArray($meta);
        $this->assertSame(SSC_VERSION, $meta['version']);
        $this->assertSame('warm', $meta['status']);
        $this->assertSame('regenerated', $meta['generation_method']);
        $this->assertStringContainsString('Nouveau cache généré', $result['message']);
    }

    public function test_execute_with_existing_cache_and_rebuild_replaces_value(): void
    {
        update_option('ssc_css_cache', 'old-cache', false);
        update_option('ssc_css_cache_meta', ['version' => '1.0'], false);
        update_option('ssc_active_css', '.card { color: blue; }', false);

        $command = new CssCacheCommand();

        $result = $command->execute(['rebuild' => 'yes']);

        $expectedCss = '.card {color:blue}';

        $this->assertSame('success', $result['status']);
        $this->assertTrue($result['had_cache']);
        $this->assertTrue($result['rebuilt']);
        $this->assertSame($expectedCss, get_option('ssc_css_cache'));
        $meta = get_option('ssc_css_cache_meta');
        $this->assertIsArray($meta);
        $this->assertSame(SSC_VERSION, $meta['version']);
        $this->assertSame('warm', $meta['status']);
        $this->assertStringContainsString('Cache CSS vidé.', $result['message']);
    }

    public function test_execute_with_rebuild_and_no_css_returns_warning(): void
    {
        $command = new CssCacheCommand();

        $result = $command->execute(['rebuild' => 'false']);

        $this->assertSame('success', $result['status']);
        $this->assertFalse($result['rebuilt']);

        $result = $command->execute(['rebuild' => true]);

        $this->assertSame('warning', $result['status']);
        $this->assertFalse($result['rebuilt']);
        $this->assertSame(0, $result['size']);
        $this->assertStringContainsString('Aucun CSS actif', $result['message']);
    }
}
