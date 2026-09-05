<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SSC\Admin\ModuleRegistry;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        unset($domain);

        return $text;
    }
}

class ModuleRegistryTest extends TestCase
{
    public function test_grouped_menu_exposes_stable_group_keys_and_page_slugs(): void
    {
        $grouped = ModuleRegistry::groupedMenu();

        $this->assertSame(['fundamentals', 'visual-builders', 'effects', 'tools'], array_keys($grouped));

        foreach ($grouped as $key => $group) {
            $this->assertSame($key, $group['key']);
            $this->assertNotSame('', $group['label']);
            $this->assertNotEmpty($group['items']);
        }

        $this->assertArrayHasKey(ModuleRegistry::BASE_SLUG, $grouped['fundamentals']['items']);
        $this->assertArrayHasKey(ModuleRegistry::BASE_SLUG . '-utilities', $grouped['fundamentals']['items']);
        $this->assertArrayHasKey(ModuleRegistry::BASE_SLUG . '-debug-center', $grouped['tools']['items']);
        $this->assertArrayHasKey(ModuleRegistry::BASE_SLUG . '-layout-builder', $grouped['visual-builders']['items']);
        $this->assertArrayHasKey(ModuleRegistry::BASE_SLUG . '-anim', $grouped['effects']['items']);
    }
}
