<?php

declare(strict_types=1);

use ReflectionProperty;
use SSC\Admin\Layout;

class LayoutTest extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $property = new ReflectionProperty(Layout::class, 'allowedTagsCache');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function test_allowed_tags_cache_contains_accessibility_attributes(): void
    {
        $firstCall = Layout::allowed_tags();
        $secondCall = Layout::allowed_tags();

        $this->assertSame($firstCall, $secondCall, 'allowed_tags should cache and return the same structure.');
        $this->assertArrayHasKey('button', $firstCall);
        $this->assertArrayHasKey('aria-*', $firstCall['button']);
        $this->assertTrue($firstCall['button']['aria-*']);
        $this->assertArrayHasKey('style', $firstCall);
        $this->assertArrayHasKey('type', $firstCall['style']);
    }

    public function test_render_wraps_content_with_accessible_shell_and_sanitizes_styles(): void
    {
        $pageContent = '<style>.foo { behavior:url(https://evil.test); color: red; }</style><p>Contenu <strong>test</strong></p>';

        ob_start();
        Layout::render($pageContent, 'supersede-css-jlg');
        $rendered = ob_get_clean();

        $this->assertStringContainsString('class="ssc-skip-link"', $rendered);
        $this->assertStringContainsString('id="ssc-sidebar"', $rendered);
        $this->assertStringContainsString('aria-label="Navigation Supersede CSS"', $rendered);
        $this->assertStringContainsString('id="ssc-main-content"', $rendered);
        $this->assertStringContainsString('tabindex="-1"', $rendered);
        $this->assertStringContainsString('aria-current="page"', $rendered);

        $this->assertStringNotContainsString('behavior', $rendered);
        $this->assertStringContainsString('.foo {color:red}', $rendered);
    }
}
