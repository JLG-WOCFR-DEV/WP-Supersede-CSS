<?php
/**
 * Plugin Name: Supersede CSS JLG (Enhanced)
 * Description: Boîte à outils visuelle pour CSS avec presets, éditeurs live, tokens, et un centre de débogage amélioré.
 * Version: 10.0.5
 * Author: JLG (Enhanced by AI)
 * Text Domain: supersede-css-jlg
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) { exit; }

define('SSC_VERSION','10.0.5');
define('SSC_PLUGIN_FILE', __FILE__);
define('SSC_PLUGIN_DIR', plugin_dir_path(__FILE__));
// CORRECTION : Déclaration de l'URL plus robuste pour éviter les erreurs 404.
define('SSC_PLUGIN_URL', plugins_url('/', __FILE__));

spl_autoload_register(function($class){
    $prefix = 'SSC\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

add_action('plugins_loaded', function(){
    if (is_admin()) {
        new SSC\Admin\Admin();
    }
    if (class_exists('SSC\Infra\Routes')) {
        SSC\Infra\Routes::boot();
    }
    
    add_action('wp_enqueue_scripts', function(){
        $css_main = get_option('ssc_active_css', '');
        $css_tokens = get_option('ssc_tokens_css', '');
        $css = $css_tokens . "\n" . $css_main;

        if (!empty(trim($css))) {
            wp_register_style('ssc-styles-handle', false);
            wp_enqueue_style('ssc-styles-handle');
            wp_add_inline_style('ssc-styles-handle', '/* Supersede CSS */' . $css);
        }
    }, 99);
});

add_action('plugins_loaded', function() {
    load_plugin_textdomain('supersede-css-jlg', false, dirname(plugin_basename(__FILE__)) . '/languages');
});