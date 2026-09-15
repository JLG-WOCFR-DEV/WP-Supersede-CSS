<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class Phase2CompatTest extends TestCase
{
    private function pluginDir(): string
    {
        return dirname(__DIR__, 2);
    }

    public function test_plugin_headers_declare_wordpress_71_compatibility(): void
    {
        $plugin = (string) file_get_contents($this->pluginDir() . '/supersede-css-jlg.php');
        $readme = (string) file_get_contents($this->pluginDir() . '/readme.txt');

        $this->assertStringContainsString('Requires at least: 6.3', $plugin);
        $this->assertStringContainsString('Tested up to: 7.1', $plugin);
        $this->assertStringContainsString('Requires PHP: 8.0', $plugin);

        $this->assertStringContainsString('Requires at least: 6.3', $readme);
        $this->assertStringContainsString('Tested up to: 7.1', $readme);
        $this->assertStringContainsString('Requires PHP: 8.0', $readme);
    }

    public function test_editor_css_loads_on_block_assets_for_iframed_canvas(): void
    {
        $plugin = (string) file_get_contents($this->pluginDir() . '/supersede-css-jlg.php');
        $canvasCss = $this->pluginDir() . '/assets/css/editor-canvas.css';

        $this->assertMatchesRegularExpression(
            "/add_action\(\s*'enqueue_block_assets',\s*'ssc_enqueue_block_canvas_inline_css'\s*\)/",
            $plugin,
            'Canvas CSS must load on enqueue_block_assets so WP 7.1 copies it into the editor iframe.'
        );
        $this->assertStringNotContainsString(
            "add_action('enqueue_block_editor_assets', 'ssc_enqueue_block_editor_inline_css')",
            $plugin,
            'Canvas CSS must not print from enqueue_block_editor_assets (parent frame).'
        );
        $this->assertStringContainsString('if (!is_admin())', $plugin);
        $this->assertStringContainsString("add_filter('block_editor_settings_all'", $plugin);
        $this->assertStringContainsString('assets/css/editor-canvas.css', $plugin);
        $this->assertFileExists($canvasCss);
        $this->assertStringContainsString(
            '/* SuperSede CSS (Editor iframe) */',
            (string) file_get_contents($canvasCss)
        );
    }

    public function test_admin_keeps_module_submenu_pages_registered(): void
    {
        $admin = (string) file_get_contents($this->pluginDir() . '/src/Admin/Admin.php');

        $this->assertStringContainsString('add_submenu_page', $admin);
        $this->assertDoesNotMatchRegularExpression(
            '/remove_submenu_page\s*\(/',
            $admin,
            'Module slugs must stay in $submenu so WP 7.1 user_can_access_admin_page() allows nav-tab URLs.'
        );
    }

    public function test_token_preview_editor_script_is_iframe_safe(): void
    {
        $script = (string) file_get_contents($this->pluginDir() . '/blocks/token-preview/index.js');
        $block = json_decode((string) file_get_contents($this->pluginDir() . '/blocks/token-preview/block.json'), true);

        $this->assertIsArray($block);
        $this->assertSame(3, (int) $block['apiVersion']);
        $this->assertStringContainsString('wp.blockEditor', $script);
        $this->assertStringContainsString('useBlockProps', $script);
        $this->assertDoesNotMatchRegularExpression(
            '/document\.head/',
            $script,
            'Editor JS must not inject styles into the parent document.head.'
        );
    }

    public function test_admin_css_does_not_restyle_wp_chrome_buttons_or_inter(): void
    {
        $adminCss = (string) file_get_contents($this->pluginDir() . '/assets/css/admin.css');
        $foundationCss = (string) file_get_contents($this->pluginDir() . '/assets/css/foundation.css');

        $this->assertStringNotContainsString('Inter', $adminCss);
        $this->assertStringNotContainsString('Inter', $foundationCss);
        $this->assertStringNotContainsString('#6e56cf', $foundationCss);
        $this->assertStringContainsString('--wp-admin-theme-color', $foundationCss);
        $this->assertStringNotContainsString('.ssc-wrap .button.button-primary', $adminCss);
        $this->assertStringNotContainsString('.ssc-app .button.button-primary', $adminCss);
        $this->assertStringNotContainsString('body[class*="page_supersede-css-jlg"] .ssc-viewport', $adminCss);
    }

    public function test_plugin_settings_use_settings_api(): void
    {
        $settings = (string) file_get_contents($this->pluginDir() . '/src/Admin/PluginSettings.php');
        $dashboard = (string) file_get_contents($this->pluginDir() . '/views/dashboard.php');

        $this->assertStringContainsString('register_setting', $settings);
        $this->assertStringContainsString('settings_fields', $settings);
        $this->assertStringContainsString('form-table', $settings);
        $this->assertStringContainsString('submit_button', $settings);
        $this->assertStringContainsString('PluginSettings::renderForm', $dashboard);
    }
}
