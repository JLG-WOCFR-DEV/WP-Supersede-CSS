<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SSC\Admin\Layout;

if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__(string $text, string $domain = 'default'): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL) ?: $url;
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return 'https://example.com/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_kses_allowed_html')) {
    function wp_kses_allowed_html(string $context = 'post'): array
    {
        return [
            'a' => [
                'href' => true,
                'class' => true,
                'aria-*' => true,
            ],
            'button' => [
                'class' => true,
                'type' => true,
                'aria-*' => true,
            ],
            'div' => [
                'class' => true,
                'id' => true,
            ],
            'main' => [
                'id' => true,
                'class' => true,
                'tabindex' => true,
            ],
            'nav' => [
                'id' => true,
                'class' => true,
                'aria-*' => true,
            ],
            'style' => [
                'id' => true,
                'type' => true,
            ],
            'strong' => [
                'class' => true,
            ],
            'p' => [
                'class' => true,
            ],
        ];
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses(string $string, array $allowed_html)
    {
        unset($allowed_html);

        return $string;
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

if (!function_exists('wp_http_validate_url')) {
    function wp_http_validate_url(string $url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : false;
    }
}

class LayoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $property = new \ReflectionProperty(Layout::class, 'allowedTagsCache');
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

    public function test_render_uses_wp_admin_charter_and_sanitizes_styles(): void
    {
        $pageContent = '<style>.foo { behavior:url(https://evil.test); color: red; }</style><p>Contenu <strong>test</strong></p>';

        ob_start();
        Layout::render($pageContent, 'supersede-css-jlg');
        $rendered = ob_get_clean();

        $this->assertStringContainsString('class="wrap"', $rendered);
        $this->assertStringContainsString('<h1 class="wp-heading-inline">', $rendered);
        $this->assertStringContainsString('nav-tab-wrapper', $rendered);
        $this->assertStringContainsString('nav-tab-active', $rendered);
        $this->assertStringContainsString('class="subsubsub"', $rendered);
        $this->assertStringContainsString('id="ssc-cmdk"', $rendered);
        $this->assertStringContainsString('id="ssc-main-content"', $rendered);
        $this->assertStringContainsString('tabindex="-1"', $rendered);
        $this->assertStringContainsString('aria-current="page"', $rendered);
        $this->assertStringContainsString('class="ssc-skip-link"', $rendered);

        $this->assertStringNotContainsString('id="ssc-sidebar"', $rendered);
        $this->assertStringNotContainsString('ssc-topbar', $rendered);
        $this->assertStringNotContainsString('ssc-viewport', $rendered);
        $this->assertStringNotContainsString('id="ssc-theme"', $rendered);
        $this->assertStringNotContainsString('id="ssc-mobile-menu"', $rendered);

        $this->assertStringNotContainsString('behavior', $rendered);
        $this->assertStringContainsString('.foo {color:red}', $rendered);
    }

    public function test_fundamentals_tab_is_active_on_dashboard(): void
    {
        ob_start();
        Layout::render('<p>Contenu</p>', 'supersede-css-jlg');
        $rendered = ob_get_clean();

        $this->assertMatchesRegularExpression(
            '/class="nav-tab nav-tab-active"[^>]*>\s*Fondamentaux/s',
            $rendered
        );
        $this->assertStringContainsString('class="current"', $rendered);
        $this->assertStringNotContainsString('ssc-sidebar-heading', $rendered);
    }

    public function test_tools_tab_is_active_on_debug_center(): void
    {
        ob_start();
        Layout::render('<p>Contenu</p>', 'supersede-css-jlg-debug-center');
        $rendered = ob_get_clean();

        $this->assertMatchesRegularExpression(
            '/class="nav-tab nav-tab-active"[^>]*>\s*Outils &amp; Maintenance/s',
            $rendered
        );
        $this->assertStringContainsString('aria-current="page"', $rendered);
        $this->assertStringContainsString('supersede-css-jlg-debug-center', $rendered);
        $this->assertDoesNotMatchRegularExpression(
            '/class="nav-tab nav-tab-active"[^>]*>\s*Fondamentaux/s',
            $rendered
        );
    }
}
