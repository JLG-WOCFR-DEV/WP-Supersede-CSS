<?php declare(strict_types=1);
namespace SSC\Infra;

use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;

if (!defined('ABSPATH')) { exit; }

final class Routes {
    private const IMPORT_HANDLERS = [
        'ssc_active_css' => 'sanitizeImportCss',
        'ssc_tokens_css' => 'sanitizeImportCss',
        'ssc_tokens_registry' => 'sanitizeImportTokens',
        'ssc_css_desktop' => 'sanitizeImportCss',
        'ssc_css_tablet' => 'sanitizeImportCss',
        'ssc_css_mobile' => 'sanitizeImportCss',
        'ssc_presets' => 'sanitizeImportPresets',
        'ssc_avatar_glow_presets' => 'sanitizeImportAvatarGlowPresets',
        'ssc_admin_log' => 'sanitizeImportAdminLog',
        'ssc_settings' => 'sanitizeImportArray',
        'ssc_modules_enabled' => 'sanitizeImportArray',
        'ssc_optimization_settings' => 'sanitizeImportArray',
        'ssc_secret' => 'sanitizeImportString',
        'ssc_safe_mode' => 'sanitizeImportBoolean',
    ];

    /**
     * @var array<string, array{label: string, options: list<string>}>
     */
    private const CONFIG_MODULES = [
        'css' => [
            'label' => 'CSS actif & variantes responsives',
            'options' => [
                'ssc_active_css',
                'ssc_css_desktop',
                'ssc_css_tablet',
                'ssc_css_mobile',
            ],
        ],
        'tokens' => [
            'label' => 'Design Tokens',
            'options' => [
                'ssc_tokens_css',
                'ssc_tokens_registry',
            ],
        ],
        'presets' => [
            'label' => 'Presets & collections',
            'options' => [
                'ssc_presets',
            ],
        ],
        'avatar' => [
            'label' => 'Presets Avatar Glow',
            'options' => [
                'ssc_avatar_glow_presets',
            ],
        ],
        'settings' => [
            'label' => 'Paramètres généraux',
            'options' => [
                'ssc_settings',
                'ssc_modules_enabled',
                'ssc_optimization_settings',
                'ssc_secret',
                'ssc_safe_mode',
            ],
        ],
        'logs' => [
            'label' => "Journal d'administration",
            'options' => [
                'ssc_admin_log',
            ],
        ],
    ];

    public function __construct() {
        add_action('rest_api_init', [$this, 'register']);
    }

    public static function boot(): void {
        new self();
    }

    public function register(): void {
        register_rest_route('ssc/v1', '/save-css', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'saveCss'],
        ]);

        register_rest_route('ssc/v1', '/health', [
            'methods' => 'GET',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'healthCheck'],
        ]);

        register_rest_route('ssc/v1', '/clear-log', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'clearLog'],
        ]);

        register_rest_route('ssc/v1', '/avatar-glow-presets', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'getAvatarGlowPresets'],
            ],
            [
                'methods' => 'POST',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'saveAvatarGlowPresets'],
            ]
        ]);

        register_rest_route('ssc/v1', '/reset-all-css', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'resetAllCss'],
        ]);

        register_rest_route('ssc/v1', '/presets', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'getPresets'],
            ],
            [
                'methods' => 'POST',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'savePresets'],
            ]
        ]);

        register_rest_route('ssc/v1', '/tokens', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'getTokens'],
            ],
            [
                'methods' => 'POST',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'saveTokens'],
            ],
        ]);

        // NOUVEAUX ENDPOINTS POUR L'EXPORT
        register_rest_route('ssc/v1', '/export-config', [
            'methods' => 'GET',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'exportConfig'],
        ]);
        register_rest_route('ssc/v1', '/export-css', [
            'methods' => 'GET',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'exportCss'],
        ]);

        register_rest_route('ssc/v1', '/import-config', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'importConfig'],
        ]);
    }

    public function saveCss(\WP_REST_Request $request): \WP_REST_Response {
        $css_raw = $request->get_param('css');
        $option_name = $request->get_param('option_name') ?: 'ssc_active_css';
        if (!is_string($option_name)) {
            $option_name = 'ssc_active_css';
        } else {
            $option_name = sanitize_key($option_name);
        }
        // Enforce whitelist for option_name to avoid clobbering unintended options.
        $allowed_options = ['ssc_active_css','ssc_tokens_css'];
        if (!in_array($option_name, $allowed_options, true)) {
            $option_name = 'ssc_active_css';
        }

        $append = \rest_sanitize_boolean($request->get_param('append'));

        $sanitized_segments = ['desktop' => '', 'tablet' => '', 'mobile' => ''];
        $segment_payload = false;

        if ($option_name === 'ssc_active_css') {
            $segments_config = [
                'desktop' => ['param' => 'css_desktop', 'option' => 'ssc_css_desktop'],
                'tablet' => ['param' => 'css_tablet', 'option' => 'ssc_css_tablet'],
                'mobile' => ['param' => 'css_mobile', 'option' => 'ssc_css_mobile'],
            ];

            foreach ($segments_config as $key => $config) {
                $raw_value = $request->get_param($config['param']);
                if ($raw_value !== null) {
                    $segment_payload = true;
                    $sanitized_value = $this->sanitizeCssSegment($raw_value);
                    update_option($config['option'], $sanitized_value, false);
                    $sanitized_segments[$key] = $sanitized_value;
                } else {
                    $existing_value = get_option($config['option'], '');
                    $existing_value = is_string($existing_value) ? $existing_value : '';
                    $sanitized_segments[$key] = CssSanitizer::sanitize($existing_value);
                }
            }
        }

        if ($segment_payload) {
            $incoming_css = $this->combineResponsiveCss($sanitized_segments);
            $append = false;
        } else {
            if (!is_string($css_raw)) {
                return new \WP_REST_Response(['ok' => false, 'message' => 'Invalid CSS.'], 400);
            }

            $incoming_css = CssSanitizer::sanitize(\wp_unslash($css_raw));
        }

        $existing_css = get_option($option_name, '');
        $existing_css = is_string($existing_css) ? $existing_css : '';
        $existing_css = CssSanitizer::sanitize($existing_css);

        if ($append) {
            if ($incoming_css !== '' && strpos($existing_css, $incoming_css) === false) {
                $css_to_store = trim($existing_css . "\n\n" . $incoming_css);
            } else {
                $css_to_store = $existing_css;
            }
        } else {
            $css_to_store = $incoming_css;
        }

        if ($option_name === 'ssc_tokens_css') {
            $tokens = TokenRegistry::convertCssToRegistry($css_to_store);
            $sanitizedTokens = TokenRegistry::saveRegistry($tokens);
            $css_to_store = TokenRegistry::tokensToCss($sanitizedTokens);
        } else {
            update_option($option_name, $css_to_store, false);
        }

        if (class_exists('\SSC\Infra\Logger')) {
            \SSC\Infra\Logger::add('css_saved', ['size' => strlen($css_to_store) . ' bytes', 'option' => $option_name]);
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
        $presets = is_array($presets) ? $presets : [];
        $presets = CssSanitizer::sanitizeAvatarGlowPresets($presets);
        return new \WP_REST_Response($presets, 200);
    }

    public function saveAvatarGlowPresets(\WP_REST_Request $request): \WP_REST_Response {
        $presets_json = wp_unslash($request->get_param('presets'));

        if (!is_string($presets_json)) {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Invalid JSON.'], 400);
        }

        $presets = json_decode($presets_json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($presets)) {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Invalid JSON.'], 400);
        }

        $presets = CssSanitizer::sanitizeAvatarGlowPresets($presets);

        update_option('ssc_avatar_glow_presets', $presets, false);
        return new \WP_REST_Response(['ok' => true], 200);
    }

    public function resetAllCss(): \WP_REST_Response {
        delete_option('ssc_active_css');
        delete_option('ssc_tokens_css');
        delete_option('ssc_tokens_registry');
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
        $presets = is_array($presets) ? $presets : [];
        $presets = CssSanitizer::sanitizePresetCollection($presets);
        return new \WP_REST_Response($presets, 200);
    }

    public function savePresets(\WP_REST_Request $request): \WP_REST_Response {
        $presets_json = wp_unslash($request->get_param('presets'));

        if (!is_string($presets_json)) {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Invalid JSON.'], 400);
        }

        $presets = json_decode($presets_json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($presets)) {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Invalid JSON.'], 400);
        }

        $presets = CssSanitizer::sanitizePresetCollection($presets);

        update_option('ssc_presets', $presets, false);
        return new \WP_REST_Response(['ok' => true], 200);
    }

    public function getTokens(): \WP_REST_Response {
        $registry = TokenRegistry::getRegistry();

        return new \WP_REST_Response([
            'tokens' => $registry,
            'css' => TokenRegistry::tokensToCss($registry),
            'types' => TokenRegistry::getSupportedTypes(),
        ], 200);
    }

    public function saveTokens(\WP_REST_Request $request): \WP_REST_Response {
        $payload = $request->get_json_params();

        if (!is_array($payload)) {
            $rawTokens = $request->get_param('tokens');
            if (is_string($rawTokens)) {
                $decoded = json_decode($rawTokens, true);
                $payload = ['tokens' => $decoded];
            } elseif (is_array($rawTokens)) {
                $payload = ['tokens' => $rawTokens];
            } else {
                $payload = [];
            }
        }

        $tokens = $payload['tokens'] ?? null;

        if (!is_array($tokens)) {
            return new \WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid tokens payload.', 'supersede-css-jlg'),
            ], 400);
        }

        $sanitized = TokenRegistry::saveRegistry($tokens);

        return new \WP_REST_Response([
            'ok' => true,
            'tokens' => $sanitized,
            'css' => TokenRegistry::tokensToCss($sanitized),
        ], 200);
    }

    // NOUVELLES FONCTIONS POUR L'EXPORT
    /**
     * @return \WP_REST_Response|\WP_Error
     */
    public function exportConfig(\WP_REST_Request $request) {
        global $wpdb;
        $options = [];
        $like_pattern = $wpdb->esc_like('ssc_') . '%';
        $sql = $wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like_pattern
        );
        $results = $wpdb->get_results($sql);

        if (empty($results)) {
            $last_error = trim((string) $wpdb->last_error);

            if ($results === null || $last_error !== '') {
                if (class_exists('\SSC\Infra\Logger')) {
                    \SSC\Infra\Logger::add('export_config_db_error', ['message' => $last_error]);
                }

                return new \WP_Error(
                    'ssc_export_config_db_error',
                    __('Unable to export configuration due to a database error.', 'supersede-css-jlg'),
                    ['status' => 500]
                );
            }

            return new \WP_REST_Response([], 200);
        }

        foreach ($results as $result) {
            $options[$result->option_name] = maybe_unserialize($result->option_value);
        }

        foreach ($options as $name => &$value) {
            if (!is_array($value)) {
                continue;
            }

            array_walk_recursive($value, static function (&$item): void {
                if (is_string($item)) {
                    $item = wp_kses_post($item);
                }
            });
        }
        unset($value);
        $modules = $this->normalizeModules($request->get_param('modules'));

        if ($modules === []) {
            return new \WP_Error(
                'ssc_export_config_invalid_modules',
                __('No valid Supersede CSS modules were selected for export.', 'supersede-css-jlg'),
                ['status' => 400]
            );
        }

        $options = $this->filterOptionsByModules($options, $modules);

        return new \WP_REST_Response($options, 200);
    }

    public function exportCss(): \WP_REST_Response {
        $css = get_option('ssc_active_css', '/* Aucun CSS actif trouvé. */');
        $css = is_string($css) ? $css : '';
        $css = CssSanitizer::sanitize($css);
        if ($css === '') {
            $css = '/* Aucun CSS actif trouvé. */';
        }
        return new \WP_REST_Response(['css' => $css], 200);
    }

    public function importConfig(\WP_REST_Request $request): \WP_REST_Response {
        $json = $request->get_json_params();

        if (!is_array($json)) {
            return new \WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid JSON payload.', 'supersede-css-jlg'),
                'applied' => [],
                'skipped' => [],
            ], 400);
        }

        $modules = $this->normalizeModules($json['modules'] ?? $request->get_param('modules'));
        $options = $json['options'] ?? $json;

        if (!is_array($options)) {
            return new \WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid import format.', 'supersede-css-jlg'),
                'applied' => [],
                'skipped' => [],
            ], 400);
        }

        $originalOptionKeys = array_map(static fn($key): string => (string) $key, array_keys($options));

        if ($modules === []) {
            return new \WP_REST_Response([
                'ok' => false,
                'message' => __('No valid Supersede CSS modules were selected for import.', 'supersede-css-jlg'),
                'applied' => [],
                'skipped' => array_values($originalOptionKeys),
            ], 400);
        }

        $options = $this->filterOptionsByModules($options, $modules);
        $filteredOut = array_values(array_diff(
            $originalOptionKeys,
            array_map(static fn($key): string => (string) $key, array_keys($options))
        ));

        if ($options === []) {
            return new \WP_REST_Response([
                'ok' => false,
                'message' => __('The selected modules do not contain any importable Supersede CSS options.', 'supersede-css-jlg'),
                'applied' => [],
                'skipped' => array_values($originalOptionKeys),
            ], 400);
        }

        $result = $this->applyImportedOptions($options);

        if ($filteredOut !== []) {
            $result['skipped'] = array_values(array_unique(array_merge($result['skipped'], $filteredOut)));
        }

        if (empty($result['applied'])) {
            return new \WP_REST_Response([
                'ok' => false,
                'message' => __('No valid Supersede CSS options were found in this import.', 'supersede-css-jlg'),
                'applied' => $result['applied'],
                'skipped' => $result['skipped'],
            ], 400);
        }

        if (class_exists(__NAMESPACE__ . '\\Logger')) {
            Logger::add('config_imported', [
                'applied' => (string) count($result['applied']),
                'skipped' => (string) count($result['skipped']),
            ]);
        }

        return new \WP_REST_Response([
            'ok' => true,
            'applied' => $result['applied'],
            'skipped' => $result['skipped'],
        ], 200);
    }

    /**
     * @return array<string, array{label: string, options: list<string>}>
     */
    public static function getConfigModules(): array
    {
        $modules = self::CONFIG_MODULES;

        foreach ($modules as &$module) {
            $module['label'] = __($module['label'], 'supersede-css-jlg');
        }
        unset($module);

        return $modules;
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    private function normalizeModules($raw): array
    {
        $allModules = array_keys(self::CONFIG_MODULES);

        if ($raw === null) {
            return $allModules;
        }

        if (is_string($raw)) {
            $raw = [$raw];
        }

        if (!is_array($raw)) {
            return $allModules;
        }

        $normalized = [];

        foreach ($raw as $module) {
            if (!is_string($module)) {
                continue;
            }

            $key = sanitize_key($module);

            if ($key === 'all') {
                return $allModules;
            }

            if (isset(self::CONFIG_MODULES[$key])) {
                $normalized[] = $key;
            }
        }

        $normalized = array_values(array_unique($normalized));

        if ($normalized === []) {
            return [];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $options
     * @param list<string> $modules
     * @return array<string, mixed>
     */
    private function filterOptionsByModules(array $options, array $modules): array
    {
        if ($modules === []) {
            return [];
        }

        if (!$this->shouldFilterModules($modules)) {
            return $options;
        }

        $allowedOptions = $this->getModuleOptionWhitelist($modules);

        if ($allowedOptions === []) {
            return [];
        }

        $allowedKeys = array_flip($allowedOptions);

        return array_intersect_key($options, $allowedKeys);
    }

    /**
     * @param list<string> $modules
     * @return list<string>
     */
    private function getModuleOptionWhitelist(array $modules): array
    {
        $options = [];

        foreach ($modules as $module) {
            foreach (self::CONFIG_MODULES[$module]['options'] as $optionName) {
                $options[$optionName] = true;
            }
        }

        return array_keys($options);
    }

    /**
     * @param list<string> $modules
     */
    private function shouldFilterModules(array $modules): bool
    {
        if ($modules === []) {
            return true;
        }

        return count($modules) < count(self::CONFIG_MODULES);
    }

    /**
     * @param array<mixed> $options
     * @return array{applied: list<string>, skipped: list<string>}
     */
    private function applyImportedOptions(array $options): array
    {
        $applied = [];
        $skipped = [];

        foreach ($options as $name => $value) {
            if (!is_string($name)) {
                $skipped[] = (string) $name;
                continue;
            }

            $optionName = sanitize_key($name);
            if ($optionName === '' || strncmp($optionName, 'ssc_', 4) !== 0) {
                $skipped[] = $optionName === '' ? $name : $optionName;
                continue;
            }

            if (!isset(self::IMPORT_HANDLERS[$optionName])) {
                $skipped[] = $optionName;
                continue;
            }

            $handler = self::IMPORT_HANDLERS[$optionName];
            $sanitizedValue = $this->$handler($value);

            if ($sanitizedValue === null) {
                $skipped[] = $optionName;
                continue;
            }

            if ($optionName === 'ssc_tokens_css') {
                $tokens = TokenRegistry::convertCssToRegistry($sanitizedValue);
                TokenRegistry::saveRegistry($tokens);
                $applied[] = $optionName;
                continue;
            }

            update_option($optionName, $sanitizedValue, false);
            $applied[] = $optionName;
        }

        return [
            'applied' => $applied,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param mixed $value
     */
    private function sanitizeImportCss($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        return CssSanitizer::sanitize($value);
    }

    /**
     * @param mixed $value
     * @return array<int, array{name: string, value: string, type: string, description: string, group: string}>|null
     */
    private function sanitizeImportTokens($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        return TokenRegistry::saveRegistry($value);
    }

    /**
     * @param mixed $value
     */
    private function sanitizeImportPresets($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        return CssSanitizer::sanitizePresetCollection($value);
    }

    /**
     * @param mixed $value
     */
    private function sanitizeImportAvatarGlowPresets($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        return CssSanitizer::sanitizeAvatarGlowPresets($value);
    }

    /**
     * @param mixed $value
     */
    private function sanitizeImportArray($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $sanitized = [];

        foreach ($value as $key => $item) {
            $sanitizedKey = is_string($key) ? sanitize_key($key) : (string) $key;
            if ($sanitizedKey === '') {
                $sanitizedKey = 'key_' . md5((string) $key);
            }

            if (is_array($item)) {
                $nested = $this->sanitizeImportArray($item);
                if ($nested === null) {
                    continue;
                }
                $sanitized[$sanitizedKey] = $nested;
                continue;
            }

            if (is_bool($item)) {
                $sanitized[$sanitizedKey] = $item;
                continue;
            }

            if (is_int($item) || is_float($item)) {
                $sanitized[$sanitizedKey] = $item + 0;
                continue;
            }

            if ($item === null) {
                $sanitized[$sanitizedKey] = '';
                continue;
            }

            if (is_string($item)) {
                $sanitized[$sanitizedKey] = sanitize_text_field($item);
                continue;
            }

            if (is_object($item)) {
                $objectVars = get_object_vars($item);
                if (is_array($objectVars) && $objectVars !== []) {
                    $nested = $this->sanitizeImportArray($objectVars);
                    if ($nested !== null) {
                        $sanitized[$sanitizedKey] = $nested;
                        continue;
                    }
                }

                $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
                if (defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
                    $jsonOptions |= JSON_PARTIAL_OUTPUT_ON_ERROR;
                }

                $encoded = function_exists('wp_json_encode')
                    ? wp_json_encode($item, $jsonOptions)
                    : json_encode($item, $jsonOptions);

                if (!is_string($encoded)) {
                    $encoded = '';
                }

                $sanitized[$sanitizedKey] = sanitize_text_field($encoded);
                continue;
            }

            $sanitized[$sanitizedKey] = sanitize_text_field((string) $item);
        }

        return $sanitized;
    }

    /**
     * @param mixed $value
     */
    private function sanitizeImportString($value): ?string
    {
        if (is_string($value)) {
            return sanitize_text_field($value);
        }

        if (is_scalar($value)) {
            return sanitize_text_field((string) $value);
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    private function sanitizeImportBoolean($value): ?bool
    {
        if ($value === null) {
            return false;
        }

        return (bool) \rest_sanitize_boolean($value);
    }

    /**
     * @param mixed $value
     */
    private function sanitizeImportAdminLog($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $sanitized = [];

        foreach ($value as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $timestamp = isset($entry['t']) ? sanitize_text_field((string) $entry['t']) : '';
            $user = isset($entry['user']) ? sanitize_text_field((string) $entry['user']) : 'anon';
            $action = isset($entry['action']) ? sanitize_text_field((string) $entry['action']) : '';
            $data = isset($entry['data']) ? $this->sanitizeImportArray($entry['data']) : [];

            if ($action === '') {
                continue;
            }

            if ($timestamp === '') {
                $timestamp = gmdate('c');
            }

            if ($user === '') {
                $user = 'anon';
            }

            if (!is_array($data)) {
                $data = [];
            }

            $sanitized[] = [
                't' => $timestamp,
                'user' => $user,
                'action' => $action,
                'data' => $data,
            ];
        }

        if ($sanitized === []) {
            return null;
        }

        if (count($sanitized) > Logger::MAX) {
            $sanitized = array_slice($sanitized, 0, Logger::MAX);
        }

        return $sanitized;
    }

    private function sanitizeCssSegment($value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $value = \wp_unslash($value);

        return CssSanitizer::sanitize($value);
    }

    private function combineResponsiveCss(array $segments): string
    {
        $desktop = $segments['desktop'] ?? '';
        $tablet = $segments['tablet'] ?? '';
        $mobile = $segments['mobile'] ?? '';

        $parts = [];

        if ($desktop !== '') {
            $parts[] = $desktop;
        }

        if (trim($tablet) !== '') {
            $parts[] = "@media (max-width: 782px) {\n{$tablet}\n}";
        }

        if (trim($mobile) !== '') {
            $parts[] = "@media (max-width: 480px) {\n{$mobile}\n}";
        }

        $combined = implode("\n\n", array_filter($parts, static function (string $part): bool {
            return $part !== '';
        }));

        return $combined === '' ? '' : CssSanitizer::sanitize($combined);
    }

    /**
     * @return bool|\WP_Error
     */
    public function authorizeRequest(\WP_REST_Request $request): bool|\WP_Error {
        $nonce = $request->get_param('_wpnonce');

        if (!is_string($nonce) || $nonce === '') {
            $nonce = $request->get_header('x-wp-nonce');
        }

        $nonceProvided = is_string($nonce) && $nonce !== '';

        if ($nonceProvided) {
            $nonce = (string) wp_unslash($nonce);

            if (!wp_verify_nonce($nonce, 'wp_rest')) {
                return new \WP_Error(
                    'rest_forbidden',
                    __('Invalid nonce.', 'supersede-css-jlg'),
                    ['status' => 403]
                );
            }
        } elseif (!$this->requestHasNonCookieAuthentication($request)) {
            return new \WP_Error(
                'rest_forbidden',
                __('Invalid nonce.', 'supersede-css-jlg'),
                ['status' => 403]
            );
        }

        if (!current_user_can('manage_options')) {
            return new \WP_Error(
                'rest_forbidden',
                __('You are not allowed to access this endpoint.', 'supersede-css-jlg'),
                ['status' => 403]
            );
        }

        return true;
    }

    private function requestHasNonCookieAuthentication(\WP_REST_Request $request): bool
    {
        $authorizationHeader = $request->get_header('authorization');

        if (is_string($authorizationHeader) && $authorizationHeader !== '') {
            return true;
        }

        if (function_exists('apply_filters')) {
            return (bool) apply_filters('ssc_request_has_non_cookie_auth', false, $request);
        }

        return false;
    }

}

