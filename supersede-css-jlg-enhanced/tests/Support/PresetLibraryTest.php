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
}
