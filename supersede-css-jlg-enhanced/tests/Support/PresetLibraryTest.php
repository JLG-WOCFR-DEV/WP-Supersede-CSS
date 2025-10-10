<?php declare(strict_types=1);

use SSC\Support\PresetLibrary;
use SSC\Support\CssSanitizer;

final class PresetLibraryTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        delete_option('ssc_presets');
    }

    public function test_ensure_defaults_populates_presets(): void
    {
        PresetLibrary::ensureDefaults();

        $stored = get_option('ssc_presets');
        $this->assertIsArray($stored);
        $this->assertArrayHasKey('headless_ui_minimal', $stored);
        $this->assertSame(
            CssSanitizer::sanitizePresetCollection(PresetLibrary::getDefaults()),
            $stored
        );
    }

    public function test_ensure_defaults_keeps_existing_non_empty_collection(): void
    {
        $existing = CssSanitizer::sanitizePresetCollection([
            'custom' => [
                'name' => 'Custom Preset',
                'scope' => '.custom',
                'props' => [
                    '--color-accent' => '#ff0000',
                ],
            ],
        ]);

        update_option('ssc_presets', $existing, false);

        PresetLibrary::ensureDefaults();

        $this->assertSame($existing, get_option('ssc_presets'));
    }

    public function test_catalog_entries_include_core_metadata(): void
    {
        $entries = PresetLibrary::getCatalogEntries();

        $this->assertIsArray($entries);
        $this->assertNotEmpty($entries);

        $first = $entries[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('css', $first);
        $this->assertArrayHasKey('meta', $first);
        $this->assertArrayHasKey('token_count', $first);

        $this->assertIsArray($first['meta']);
        $this->assertArrayHasKey('family', $first['meta']);
        $this->assertArrayHasKey('token_priorities', $first['meta']);
    }

    public function test_render_catalog_stylesheet_concatenates_css(): void
    {
        $entries = [
            [
                'id' => 'demo',
                'name' => 'Demo Preset',
                'css' => ':root {\n    --color: #fff;\n}',
            ],
            [
                'id' => 'empty',
                'name' => 'Empty',
                'css' => '',
            ],
        ];

        $css = PresetLibrary::renderCatalogStylesheet($entries);

        $this->assertStringContainsString('Demo Preset', $css);
        $this->assertStringContainsString('--color: #fff;', $css);
        $this->assertStringNotContainsString('Empty', $css);
    }
}
