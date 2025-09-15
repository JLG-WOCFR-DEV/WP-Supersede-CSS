<?php declare(strict_types=1);
namespace SSC\Infra;

if (!defined('ABSPATH')) { exit; }

final class Routes {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register']);
    }

    public static function boot(): void {
        new self();
    }

    public function register(): void {
        register_rest_route('ssc/v1', '/save-css', [
            'methods' => 'POST',
            'permission_callback' => function() { return current_user_can('manage_options'); },
            'callback' => [$this, 'saveCss'],
        ]);

        register_rest_route('ssc/v1', '/health', [
            'methods' => 'GET',
            'permission_callback' => function() { return current_user_can('manage_options'); },
            'callback' => [$this, 'healthCheck'],
        ]);

        register_rest_route('ssc/v1', '/clear-log', [
            'methods' => 'POST',
            'permission_callback' => function() { return current_user_can('manage_options'); },
            'callback' => [$this, 'clearLog'],
        ]);

        register_rest_route('ssc/v1', '/avatar-glow-presets', [
            [
                'methods' => 'GET',
                'permission_callback' => function() { return current_user_can('manage_options'); },
                'callback' => [$this, 'getAvatarGlowPresets'],
            ],
            [
                'methods' => 'POST',
                'permission_callback' => function() { return current_user_can('manage_options'); },
                'callback' => [$this, 'saveAvatarGlowPresets'],
            ]
        ]);
        
        register_rest_route('ssc/v1', '/reset-all-css', [
            'methods' => 'POST',
            'permission_callback' => function() { return current_user_can('manage_options'); },
            'callback' => [$this, 'resetAllCss'],
        ]);

        register_rest_route('ssc/v1', '/presets', [
            [
                'methods' => 'GET',
                'permission_callback' => function() { return current_user_can('manage_options'); },
                'callback' => [$this, 'getPresets'],
            ],
            [
                'methods' => 'POST',
                'permission_callback' => function() { return current_user_can('manage_options'); },
                'callback' => [$this, 'savePresets'],
            ]
        ]);

        // NOUVEAUX ENDPOINTS POUR L'EXPORT
        register_rest_route('ssc/v1', '/export-config', [
            'methods' => 'GET',
            'permission_callback' => function() { return current_user_can('manage_options'); },
            'callback' => [$this, 'exportConfig'],
        ]);
        register_rest_route('ssc/v1', '/export-css', [
            'methods' => 'GET',
            'permission_callback' => function() { return current_user_can('manage_options'); },
            'callback' => [$this, 'exportCss'],
        ]);
    }

    public function saveCss(\WP_REST_Request $request): \WP_REST_Response {
        $css = $request->get_param('css');
        $option_name = $request->get_param('option_name') ?: 'ssc_active_css';
        // Enforce whitelist for option_name to avoid clobbering unintended options.
        $allowed_options = ['ssc_active_css','ssc_tokens_css'];
        $option_name = sanitize_key($option_name);
        if (!in_array($option_name, $allowed_options, true)) {
            $option_name = 'ssc_active_css';
        }
        
        if (!is_string($css)) {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Invalid CSS.'], 400);
        }
        
        $append = $request->get_param('append');

        if ($append) {
            $existing_css = get_option($option_name, '');
            if (strpos($existing_css, $css) === false) {
                 $css = $existing_css . "\n\n" . $css;
            } else {
                $css = $existing_css;
            }
        }

        update_option(sanitize_key($option_name), $css, false);
        
        if (class_exists('\SSC\Infra\Logger')) {
            \SSC\Infra\Logger::add('css_saved', ['size' => strlen($css) . ' bytes', 'option' => $option_name]);
        }
        
        return new \WP_REST_Response(['ok' => true], 200);
    }

    public function healthCheck(): \WP_REST_Response {
        $assets_to_check = [
            'css/admin.css', 'css/ux.css', 'js/ux.js', 'js/utilities.js',
            'codemirror/lib/codemirror.js', 'codemirror/lib/codemirror.css'
        ];
        $asset_status = [];
        foreach($assets_to_check as $asset) {
            $asset_status[$asset] = is_file(SSC_PLUGIN_DIR . 'assets/' . $asset) ? 'OK' : 'Manquant';
        }

        $data = [
            'plugin_version' => defined('SSC_VERSION') ? SSC_VERSION : 'N/A',
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'rest_api_status' => 'OK',
            'asset_files_exist' => $asset_status,
            'composer_dependencies' => [
                'Minify' => class_exists('\MatthiasMullie\Minify\CSS') ? 'Chargé' : 'Non trouvé',
            ]
        ];
        return new \WP_REST_Response($data, 200);
    }

    public function clearLog(): \WP_REST_Response {
        if (class_exists('\SSC\Infra\Logger')) {
            \SSC\Infra\Logger::clear();
        }
        return new \WP_REST_Response(['ok' => true], 200);
    }
    
    public function getAvatarGlowPresets(): \WP_REST_Response {
        $presets = get_option('ssc_avatar_glow_presets', []);
        return new \WP_REST_Response($presets, 200);
    }

    public function saveAvatarGlowPresets(\WP_REST_Request $request): \WP_REST_Response {
        $presets_json = $request->get_param('presets');
        $presets = json_decode($presets_json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($presets)) {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Invalid JSON.'], 400);
        }

        update_option('ssc_avatar_glow_presets', $presets, false);
        return new \WP_REST_Response(['ok' => true], 200);
    }
    
    public function resetAllCss(): \WP_REST_Response {
        delete_option('ssc_active_css');
        delete_option('ssc_tokens_css');
        delete_option('ssc_css_desktop');
        delete_option('ssc_css_tablet');
        delete_option('ssc_css_mobile');

        if (class_exists('\SSC\Infra\Logger')) {
            \SSC\Infra\Logger::add('css_resetted', []);
        }
        return new \WP_REST_Response(['ok' => true, 'message' => 'All CSS options have been reset.']);
    }

    public function getPresets(): \WP_REST_Response {
        $presets = get_option('ssc_presets', []);
        return new \WP_REST_Response($presets, 200);
    }

    public function savePresets(\WP_REST_Request $request): \WP_REST_Response {
        $presets_json = $request->get_param('presets');
        $presets = json_decode($presets_json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($presets)) {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Invalid JSON.'], 400);
        }

        update_option('ssc_presets', $presets, false);
        return new \WP_REST_Response(['ok' => true], 200);
    }
    
    // NOUVELLES FONCTIONS POUR L'EXPORT
    public function exportConfig(): \WP_REST_Response {
        global $wpdb;
        $options = [];
        $sql = $wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
            \esc_like('ssc_') . '%'
        );
        $results = $wpdb->get_results($sql);
        foreach ($results as $result) {
            $options[$result->option_name] = maybe_unserialize($result->option_value);
        }
        return new \WP_REST_Response($options, 200);
    }

    public function exportCss(): \WP_REST_Response {
        $css = get_option('ssc_active_css', '/* Aucun CSS actif trouvé. */');
        return new \WP_REST_Response(['css' => $css], 200);
    }
}