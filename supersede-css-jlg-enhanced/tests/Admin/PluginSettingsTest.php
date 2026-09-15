<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SSC\Admin\PluginSettings;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

final class PluginSettingsTest extends TestCase
{
    public function test_sanitize_normalizes_checkbox_and_legacy_values(): void
    {
        $enabled = PluginSettings::sanitize(['inject_editor_css' => '1']);
        $this->assertTrue($enabled['inject_editor_css']);

        $disabled = PluginSettings::sanitize([]);
        $this->assertFalse($disabled['inject_editor_css']);

        $legacy = PluginSettings::sanitize('not-an-array');
        $this->assertSame(PluginSettings::defaults(), $legacy);
    }

    public function test_defaults_inject_editor_css(): void
    {
        $defaults = PluginSettings::defaults();

        $this->assertArrayHasKey('inject_editor_css', $defaults);
        $this->assertTrue($defaults['inject_editor_css']);
    }
}
