<?php
/**
 * Plugin Name: Supersede CSS JLG (Enhanced)
 * Description: Boîte à outils visuelle pour CSS avec presets, éditeurs live, tokens, et un centre de débogage amélioré.
 * Version: 10.0.7
 * Requires PHP: 8.0
 * Author: JLG (Enhanced by AI)
 * Text Domain: supersede-css-jlg
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) { exit; }

use SSC\Blocks\TokenPreview;
use SSC\Infra\Activity\EventRecorder;
use SSC\Infra\Approvals\ApprovalSlaMonitor;
use SSC\Infra\Capabilities\CapabilityManager;
use SSC\Infra\Cli\CssCacheCommand;
use SSC\Support\CssSanitizer;
use SSC\Support\PresetLibrary;

use function wp_clear_scheduled_hook;

define('SSC_VERSION','10.0.7');
define('SSC_PLUGIN_FILE', __FILE__);
define('SSC_PLUGIN_DIR', plugin_dir_path(__FILE__));
// CORRECTION : Déclaration de l'URL plus robuste pour éviter les erreurs 404.
define('SSC_PLUGIN_URL', plugins_url('/', __FILE__));

$composerAutoload = __DIR__ . '/vendor/autoload.php';

if (file_exists($composerAutoload)) {
    require $composerAutoload;
} else {
    require __DIR__ . '/src/autoload-fallback.php';
}

if (!function_exists('ssc_get_required_capability')) {
    /**
     * Returns the capability required to access Supersede CSS features.
     *
     * Developers can override the default capability (`manage_options`) by
     * hooking into the `ssc_required_capability` filter.
     *
     * @return string
     */
    function ssc_get_required_capability(): string
    {
        $capability = apply_filters('ssc_required_capability', 'manage_options');

        if (!is_string($capability) || $capability === '') {
            $capability = 'manage_options';
        }

        return $capability;
    }
}

if (!function_exists('ssc_get_cached_css')) {
    function ssc_get_cached_css(): string
    {
        global $ssc_css_runtime_cache;

        if (is_array($ssc_css_runtime_cache)) {
            $runtime_css = isset($ssc_css_runtime_cache['css']) ? $ssc_css_runtime_cache['css'] : null;
            $runtime_version = isset($ssc_css_runtime_cache['version']) ? $ssc_css_runtime_cache['version'] : null;

            if (is_string($runtime_css) && $runtime_version === SSC_VERSION) {
                return $runtime_css;
            }
        }

        $cache_meta = get_option('ssc_css_cache_meta', []);
        $cached_version = is_array($cache_meta) && isset($cache_meta['version'])
            ? (string) $cache_meta['version']
            : null;

        $cached = get_option('ssc_css_cache', false);

        if ($cached_version !== SSC_VERSION) {
            ssc_invalidate_css_cache();
            $cached = false;
        }

        if (is_string($cached)) {
            $ssc_css_runtime_cache = [
                'css' => $cached,
                'version' => SSC_VERSION,
            ];

            return $cached;
        }

        $css_main = get_option('ssc_active_css', '');
        $css_tokens = get_option('ssc_tokens_css', '');

        $css_main = is_string($css_main) ? $css_main : '';
        $css_tokens = is_string($css_tokens) ? $css_tokens : '';

        $css_combined = $css_tokens . "\n" . $css_main;
        $css_filtered = CssSanitizer::sanitize($css_combined);

        update_option('ssc_css_cache', $css_filtered, false);
        update_option('ssc_css_cache_meta', [
            'version' => SSC_VERSION,
        ], false);

        delete_option('ssc_css_cache_last_had_cache');

        return $css_filtered;
    }
}

if (!function_exists('ssc_invalidate_css_cache')) {
    function ssc_invalidate_css_cache(): void
    {
        $cached = get_option('ssc_css_cache', false);
        $hadCache = is_string($cached) && trim($cached) !== '';

        delete_option('ssc_css_cache');
        delete_option('ssc_css_cache_meta');

        if ($hadCache) {
            update_option('ssc_css_cache_last_had_cache', 1, false);
        } else {
            delete_option('ssc_css_cache_last_had_cache');
        }

        if (function_exists('do_action')) {
            do_action('ssc_css_cache_invalidated');
        }
    }
}

if (!function_exists('ssc_maybe_invalidate_css_cache_on_option_change')) {
    /**
     * Ensure inline CSS cache is purged whenever one of the managed CSS options changes.
     *
     * Professional WordPress products typically hook into option updates to avoid serving stale caches
     * when administrators modify data outside of the provided UI (CLI, WP-CLI, direct database writes…).
     * This mirrors that behaviour by reacting to core option lifecycle events.
     *
     * @param string $option    Option name that triggered the hook.
     * @param mixed  $old_value Previous value (unused).
     * @param mixed  $value     New value (unused).
     */
    function ssc_maybe_invalidate_css_cache_on_option_change(string $option, $old_value = null, $value = null): void
    {
        static $watched = [
            'ssc_active_css',
            'ssc_tokens_css',
            'ssc_tokens_registry',
            'ssc_css_desktop',
            'ssc_css_tablet',
            'ssc_css_mobile',
        ];

        if (!in_array($option, $watched, true)) {
            return;
        }

        if (function_exists('ssc_invalidate_css_cache')) {
            ssc_invalidate_css_cache();
        }
    }

    add_action('updated_option', 'ssc_maybe_invalidate_css_cache_on_option_change', 10, 3);
    add_action('added_option', 'ssc_maybe_invalidate_css_cache_on_option_change', 10, 2);
    add_action('deleted_option', 'ssc_maybe_invalidate_css_cache_on_option_change', 10, 1);
}

register_activation_hook(__FILE__, static function (): void {
    EventRecorder::install();
    CapabilityManager::grantDefaultCapabilities();
    ApprovalSlaMonitor::registerSchedule();
});

register_deactivation_hook(__FILE__, static function (): void {
    wp_clear_scheduled_hook(ApprovalSlaMonitor::HOOK);
});

add_action('switch_theme', static function (): void {
    if (function_exists('ssc_invalidate_css_cache')) {
        ssc_invalidate_css_cache();
    }
});

add_action('customize_save_after', static function (): void {
    if (function_exists('ssc_invalidate_css_cache')) {
        ssc_invalidate_css_cache();
    }
});

add_action('plugins_loaded', function(){
    EventRecorder::maybeUpgrade();
    CapabilityManager::grantDefaultCapabilities();
    ApprovalSlaMonitor::bootstrap();

    $cache_meta = get_option('ssc_css_cache_meta', []);
    $cached_version = is_array($cache_meta) && isset($cache_meta['version'])
        ? (string) $cache_meta['version']
        : null;

    if ($cached_version !== SSC_VERSION) {
        ssc_invalidate_css_cache();
        ssc_get_cached_css();
    }

    PresetLibrary::ensureDefaults();

    if (is_admin()) {
        new SSC\Admin\Admin();
    }
    if (class_exists('SSC\Infra\Routes')) {
        SSC\Infra\Routes::register();
    }

    add_action('wp_enqueue_scripts', 'ssc_enqueue_frontend_inline_css', 99);
});

if (!function_exists('ssc_get_inline_style_handle')) {
    /**
     * Returns the stylesheet handle used when outputting inline CSS.
     *
     * @param string $context  The rendering context (e.g. "frontend", "editor").
     * @param string $fallback Default handle used when the filter returns an empty value.
     */
    function ssc_get_inline_style_handle(string $context, string $fallback): string
    {
        $handle = apply_filters('ssc_inline_style_handle', $fallback, $context);

        if (!is_string($handle)) {
            return $fallback;
        }

        $handle = trim($handle);

        return $handle !== '' ? $handle : $fallback;
    }
}

if (!function_exists('ssc_prepare_inline_css_for_output')) {
    /**
     * Normalise cached CSS before injecting it into a page.
     *
     * Professional-grade plugins expose filters so that enterprise stacks can
     * inject instrumentation (for example CSP nonce attributes or telemetry
     * markers) without forking the code. The optional $resanitize flag keeps the
     * existing defensive sanitation in place for contexts where the CSS may
     * travel through extra filters (such as the block editor pipeline).
     *
     * @param string $context    A short name describing the render target.
     * @param bool   $resanitize Whether to re-run sanitization on the cached CSS.
     */
    function ssc_prepare_inline_css_for_output(string $context, bool $resanitize = false): string
    {
        $css = ssc_get_cached_css();

        if ($css === '') {
            return '';
        }

        if ($resanitize) {
            $css = CssSanitizer::sanitize($css);

            if ($css === '') {
                return '';
            }
        }

        $filtered = apply_filters('ssc_inline_css', $css, $context);

        return is_string($filtered) ? trim($filtered) : '';
    }
}

if (!function_exists('ssc_enqueue_frontend_inline_css')) {
    /**
     * Enqueues the cached CSS on the public-facing site.
     */
    function ssc_enqueue_frontend_inline_css(): void
    {
        $css_filtered = ssc_prepare_inline_css_for_output('frontend');

        if ($css_filtered === '') {
            return;
        }

        $handle = ssc_get_inline_style_handle('frontend', 'ssc-styles-handle');

        wp_register_style($handle, false, [], SSC_VERSION);
        wp_enqueue_style($handle);
        wp_add_inline_style($handle, '/* Supersede CSS */' . $css_filtered);
    }
}

if (!function_exists('ssc_enqueue_block_editor_inline_css')) {
    /**
     * Injecte le CSS Supersede dans l'éditeur de blocs.
     */
    function ssc_enqueue_block_editor_inline_css(): void
    {
        $css_filtered = ssc_prepare_inline_css_for_output('editor', true);

        if ($css_filtered === '') {
            return;
        }

        $handle = ssc_get_inline_style_handle('editor', 'ssc-editor-styles-handle');

        wp_register_style($handle, false, [], SSC_VERSION);
        wp_enqueue_style($handle);
        wp_add_inline_style($handle, '/* Supersede CSS (Editor) */' . $css_filtered);
    }
}

add_action('enqueue_block_editor_assets', 'ssc_enqueue_block_editor_inline_css');

add_action('plugins_loaded', function() {
    load_plugin_textdomain('supersede-css-jlg', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

if (defined('WP_CLI') && WP_CLI) {
    add_action('cli_init', static function (): void {
        if (!class_exists('\\WP_CLI')) {
            return;
        }

        \WP_CLI::add_command('ssc css flush', new CssCacheCommand());
    });
}

if (!function_exists('ssc_register_blocks')) {
    function ssc_register_blocks(): void
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        if (function_exists('wp_installing') && wp_installing()) {
            return;
        }

        TokenPreview::register();
    }
}

add_action('init', 'ssc_register_blocks');

