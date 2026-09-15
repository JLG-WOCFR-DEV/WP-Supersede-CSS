<?php

declare(strict_types=1);

final class EditorCanvasAssetsTest extends WP_UnitTestCase
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
        delete_option(\SSC\Admin\PluginSettings::OPTION_NAME);

        $this->dequeueEditorCanvasStyle();
    }

    protected function tearDown(): void
    {
        $this->dequeueEditorCanvasStyle();

        if (isset($GLOBALS['current_screen'])) {
            unset($GLOBALS['current_screen']);
        }

        parent::tearDown();
    }

    public function test_editor_canvas_css_enqueues_real_stylesheet_when_cache_is_empty(): void
    {
        set_current_screen('post');
        $this->assertTrue(is_admin());

        ssc_enqueue_block_canvas_inline_css();

        $this->assertTrue(
            wp_style_is('ssc-editor-canvas', 'enqueued'),
            'A real editor-canvas.css handle must enqueue even when generated CSS is empty.'
        );

        $registered = wp_styles()->registered['ssc-editor-canvas'] ?? null;
        $this->assertNotNull($registered);
        $this->assertIsString($registered->src);
        $this->assertNotFalse($registered->src);
        $this->assertStringContainsString('editor-canvas.css', (string) $registered->src);
    }

    public function test_editor_canvas_css_is_not_enqueued_on_frontend(): void
    {
        $this->assertFalse(is_admin());

        ssc_enqueue_block_canvas_inline_css();

        $this->assertFalse(
            wp_style_is('ssc-editor-canvas', 'enqueued'),
            'Canvas CSS must not load on the frontend via enqueue_block_assets.'
        );
    }

    public function test_block_editor_settings_inject_iframe_marker_when_cache_is_empty(): void
    {
        set_current_screen('post');

        $settings = apply_filters('block_editor_settings_all', ['styles' => []], null);

        $this->assertIsArray($settings['styles']);
        $css = '';
        foreach ($settings['styles'] as $style) {
            if (is_array($style) && isset($style['css']) && is_string($style['css'])) {
                $css .= $style['css'];
            }
        }

        $this->assertStringContainsString(
            '/* SuperSede CSS (Editor iframe) */',
            $css,
            'block_editor_settings_all must inject a marker into the iframe even if the CSS cache is empty.'
        );
    }

    public function test_generated_css_is_added_inline_on_the_canvas_handle(): void
    {
        set_current_screen('post');
        update_option('ssc_active_css', 'body { color: rgb(1, 2, 3); }', false);

        ssc_enqueue_block_canvas_inline_css();

        $this->assertTrue(wp_style_is('ssc-editor-canvas', 'enqueued'));
        $inline = wp_styles()->get_data('ssc-editor-canvas', 'after');
        $this->assertIsArray($inline);
        $this->assertStringContainsString('rgb(1, 2, 3)', implode('', $inline));
    }

    private function dequeueEditorCanvasStyle(): void
    {
        wp_dequeue_style('ssc-editor-canvas');
        wp_deregister_style('ssc-editor-canvas');
        wp_dequeue_style('ssc-editor-styles-handle');
        wp_deregister_style('ssc-editor-styles-handle');
    }
}
