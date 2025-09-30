<?php declare(strict_types=1);
namespace SSC\Infra;

use SSC\Support\CssRevisions;
use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;

if (!class_exists('\\SSC\\Support\\CssRevisions') && is_readable(__DIR__ . '/../Support/CssRevisions.php')) {
    require_once __DIR__ . '/../Support/CssRevisions.php';
}

if (!defined('ABSPATH')) { exit; }

final class Routes {
    private const IMPORT_MAX_DEPTH = 20;
    private const IMPORT_MAX_ITEMS = 5000;
    /** @var list<string> */
    private array $importDuplicateWarnings = [];
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

        register_rest_route('ssc/v1', '/css-revisions/(?P<revision>[A-Za-z0-9_-]+)/restore', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'restoreCssRevision'],
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

            $pendingUpdates = [];

            foreach ($segments_config as $key => $config) {
                $raw_value = $request->get_param($config['param']);
                if ($raw_value !== null) {
                    $segment_payload = true;
                    if (!is_string($raw_value)) {
                        return new \WP_REST_Response(['ok' => false, 'message' => 'Invalid CSS segment.'], 400);
                    }

                    $sanitized_value = $this->sanitizeCssSegment($raw_value);
                    $pendingUpdates[$config['option']] = $sanitized_value;
                    $sanitized_segments[$key] = $sanitized_value;
                } else {
                    $existing_value = get_option($config['option'], '');
                    $existing_value = is_string($existing_value) ? $existing_value : '';
                    $sanitized_value = CssSanitizer::sanitize($existing_value);
                    $sanitized_segments[$key] = $sanitized_value;

                    if ($sanitized_value !== $existing_value) {
                        update_option($config['option'], $sanitized_value, false);

                        if (function_exists('\\ssc_invalidate_css_cache')) {
                            \ssc_invalidate_css_cache();
                        }
                    }
                }
            }

            foreach ($pendingUpdates as $option => $value) {
                update_option($option, $value, false);
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
            $existingRegistry = TokenRegistry::getRegistry();
            $tokensWithMetadata = TokenRegistry::mergeMetadata($tokens, $existingRegistry);
            $sanitizedTokens = TokenRegistry::saveRegistry($tokensWithMetadata);
            $css_to_store = TokenRegistry::tokensToCss($sanitizedTokens);
        } else {
            update_option($option_name, $css_to_store, false);
        }

        $revisionContext = [];
        if ($option_name === 'ssc_active_css') {
            $revisionContext['segments'] = $sanitized_segments;
        }

        CssRevisions::record($option_name, $css_to_store, $revisionContext);

        if (class_exists('\SSC\Infra\Logger')) {
            \SSC\Infra\Logger::add('css_saved', ['size' => strlen($css_to_store) . ' bytes', 'option' => $option_name]);
        }

        if (function_exists('\ssc_invalidate_css_cache')) {
            \ssc_invalidate_css_cache();
        }
        return new \WP_REST_Response(['ok' => true], 200);
    }

    public function restoreCssRevision(\WP_REST_Request $request): \WP_REST_Response
    {
        $revisionId = $request->get_param('revision');
        if (!is_string($revisionId)) {
            return new \WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid revision identifier.', 'supersede-css-jlg'),
            ], 400);
        }

        $restored = CssRevisions::restore($revisionId);
        if ($restored === null) {
            return new \WP_REST_Response([
                'ok' => false,
                'message' => __('Revision not found.', 'supersede-css-jlg'),
            ], 404);
        }

        $context = [];
        if (($restored['option'] ?? '') === 'ssc_active_css' && isset($restored['segments']) && is_array($restored['segments'])) {
            $context['segments'] = $restored['segments'];
        }

        CssRevisions::record($restored['option'], $restored['css'], $context);

        if (class_exists('\SSC\Infra\Logger')) {
            \SSC\Infra\Logger::add('css_revision_restored', [
                'revision' => $restored['id'],
                'option' => $restored['option'],
            ]);
        }

        return new \WP_REST_Response([
            'ok' => true,
            'revision' => $restored['id'],
        ], 200);
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
        $payload = $request->get_json_params();

        if (!is_array($payload) || !array_key_exists('presets', $payload)) {
            $raw_presets = $request->get_param('presets');
            if (is_string($raw_presets)) {
                $decoded = json_decode(wp_unslash($raw_presets), true);
                $payload = ['presets' => $decoded];
            } elseif (is_array($raw_presets)) {
                $payload = ['presets' => $raw_presets];
            } else {
                $payload = [];
            }
        }

        $presets = $payload['presets'] ?? null;

        if (!is_array($presets)) {
            return new \WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid presets payload.', 'supersede-css-jlg'),
            ], 400);
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

        if (function_exists('\ssc_invalidate_css_cache')) {
            \ssc_invalidate_css_cache();
        }

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
        $payload = $request->get_json_params();

        if (!is_array($payload) || !array_key_exists('presets', $payload)) {
            $raw_presets = $request->get_param('presets');
            if (is_string($raw_presets)) {
                $decoded = json_decode(wp_unslash($raw_presets), true);
                $payload = ['presets' => $decoded];
            } elseif (is_array($raw_presets)) {
                $payload = ['presets' => $raw_presets];
            } else {
                $payload = [];
            }
        }

        $presets = $payload['presets'] ?? null;

        if (!is_array($presets)) {
            return new \WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid presets payload.', 'supersede-css-jlg'),
            ], 400);
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
                // Mirror savePresets() by unslashing the raw payload before decoding JSON.
                $decoded = json_decode(wp_unslash($rawTokens), true);
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

        $rawModules = $request->get_param('modules');
        $modules = $this->normalizeModules($rawModules);

        if ($modules === []) {
            return new \WP_Error(
                'ssc_export_config_invalid_modules',
                __('No valid Supersede CSS modules were selected for export.', 'supersede-css-jlg'),
                ['status' => 400]
            );
        }

        $options = $this->filterOptionsByModules($options, $modules, $rawModules !== null);

        return new \WP_REST_Response($options, 200);
    }

    public function exportCss(): \WP_REST_Response {
        $tokensCss = get_option('ssc_tokens_css', '');
        $activeCss = get_option('ssc_active_css', '');

        $tokensCss = is_string($tokensCss) ? $tokensCss : '';
        $activeCss = is_string($activeCss) ? $activeCss : '';

        if ($tokensCss === '' && $activeCss === '') {
            return new \WP_REST_Response(['css' => '/* Aucun CSS actif trouvé. */'], 200);
        }

        $combinedCss = CssSanitizer::sanitize($tokensCss . "\n" . $activeCss);

        return new \WP_REST_Response(['css' => $combinedCss], 200);
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

        $rawModules = $json['modules'] ?? $request->get_param('modules');
        $modules = $this->normalizeModules($rawModules);
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

        $options = $this->filterOptionsByModules($options, $modules, $rawModules !== null);
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
    private function filterOptionsByModules(array $options, array $modules, bool $selectionProvided = true): array
    {
        if ($modules === []) {
            return [];
        }

        if (!$selectionProvided) {
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
    /**
     * @param array<mixed> $options
     * @return array{applied: list<string>, skipped: list<string>}
     */
    private function applyImportedOptions(array $options): array
    {
        $applied = [];
        $skipped = [];
        $deferredValues = [];
        $deferredDuplicateWarnings = [];

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
            $this->resetImportDuplicateWarnings();
            $sanitizedValue = $this->$handler($value);
            $duplicateWarnings = $this->consumeImportDuplicateWarnings();

            if ($sanitizedValue === null) {
                $skipped[] = $optionName;
                foreach ($duplicateWarnings as $duplicatePath) {
                    $skipped[] = sprintf('%s (duplicate key: %s)', $optionName, $duplicatePath);
                }
                continue;
            }

            if ($optionName === 'ssc_tokens_css') {
                $deferredValues[$optionName] = $sanitizedValue;
                $deferredDuplicateWarnings[$optionName] = $duplicateWarnings;
                continue;
            }

            if ($optionName === 'ssc_tokens_registry') {
                $deferredValues[$optionName] = $sanitizedValue;
                $deferredDuplicateWarnings[$optionName] = $duplicateWarnings;
                continue;
            }

            update_option($optionName, $sanitizedValue, false);
            if (function_exists('\ssc_invalidate_css_cache') && in_array($optionName, ['ssc_active_css', 'ssc_css_desktop', 'ssc_css_tablet', 'ssc_css_mobile'], true)) {
                \ssc_invalidate_css_cache();
            }
            $applied[] = $optionName;
            foreach ($duplicateWarnings as $duplicatePath) {
                $skipped[] = sprintf('%s (duplicate key: %s)', $optionName, $duplicatePath);
            }
        }

        $finalRegistry = null;
        $registryProvided = array_key_exists('ssc_tokens_registry', $deferredValues);
        $cssProvided = array_key_exists('ssc_tokens_css', $deferredValues);
        $normalizedRegistry = null;

        if ($registryProvided) {
            /** @var array<int, array{name: string, value: string, type: string, description: string, group: string}> $registryValue */
            $registryValue = $deferredValues['ssc_tokens_registry'];
            $normalizedRegistry = TokenRegistry::normalizeRegistry($registryValue);
        }

        if ($cssProvided) {
            /** @var string $cssValue */
            $cssValue = $deferredValues['ssc_tokens_css'];
            $existingMetadata = $normalizedRegistry ?? TokenRegistry::getRegistry();
            $tokensFromCss = TokenRegistry::convertCssToRegistry($cssValue);
            $finalRegistry = TokenRegistry::mergeMetadata($tokensFromCss, $existingMetadata);

            if (!in_array('ssc_tokens_css', $applied, true)) {
                $applied[] = 'ssc_tokens_css';
            }

            if ($registryProvided && !in_array('ssc_tokens_registry', $applied, true)) {
                $applied[] = 'ssc_tokens_registry';
            }
        } elseif ($normalizedRegistry !== null) {
            $finalRegistry = $normalizedRegistry;

            if (!in_array('ssc_tokens_registry', $applied, true)) {
                $applied[] = 'ssc_tokens_registry';
            }
        }

        if ($finalRegistry !== null) {
            TokenRegistry::saveRegistry($finalRegistry);
            if (function_exists('\ssc_invalidate_css_cache')) {
                \ssc_invalidate_css_cache();
            }
        }

        foreach ($deferredDuplicateWarnings as $optionName => $warnings) {
            foreach ($warnings as $duplicatePath) {
                $skipped[] = sprintf('%s (duplicate key: %s)', $optionName, $duplicatePath);
            }
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

        return TokenRegistry::normalizeRegistry($value);
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
    private function sanitizeImportArray($value, int $depth = 0, ?\SplObjectStorage $objectStack = null, ?int &$itemBudget = null, string $parentPath = ''): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        if ($depth > self::IMPORT_MAX_DEPTH) {
            return null;
        }

        if ($objectStack === null) {
            $objectStack = new \SplObjectStorage();
        }

        if ($itemBudget === null) {
            $itemBudget = self::IMPORT_MAX_ITEMS;
        }

        if ($itemBudget <= 0) {
            return null;
        }

        $sanitized = [];

        foreach ($value as $key => $item) {
            $sanitizedKey = $this->sanitizeImportKey($key, $depth);
            if ($sanitizedKey === null) {
                continue;
            }

            if (array_key_exists($sanitizedKey, $sanitized)) {
                $this->recordImportDuplicateWarning($this->formatDuplicateKeyPath($parentPath, $sanitizedKey));
                continue;
            }

            if ($itemBudget <= 0) {
                break;
            }

            $itemBudget--;

            if (is_array($item)) {
                if ($depth + 1 > self::IMPORT_MAX_DEPTH) {
                    continue;
                }

                $nested = $this->sanitizeImportArray(
                    $item,
                    $depth + 1,
                    $objectStack,
                    $itemBudget,
                    $this->formatDuplicateKeyPath($parentPath, $sanitizedKey)
                );
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
                $sanitized[$sanitizedKey] = $this->sanitizeImportStringValue($item, $depth, $objectStack, $itemBudget);
                continue;
            }

            if (is_object($item)) {
                if ($objectStack->contains($item)) {
                    continue;
                }

                $objectStack->attach($item);
                $objectVars = get_object_vars($item);
                if (is_array($objectVars) && $objectVars !== []) {
                    $nested = $this->sanitizeImportArray(
                        $objectVars,
                        $depth + 1,
                        $objectStack,
                        $itemBudget,
                        $this->formatDuplicateKeyPath($parentPath, $sanitizedKey)
                    );
                    if ($nested !== null) {
                        $objectStack->detach($item);
                        $sanitized[$sanitizedKey] = $nested;
                        continue;
                    }
                }

                $objectStack->detach($item);

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

                $sanitized[$sanitizedKey] = $this->sanitizeImportStringValue($encoded, $depth, $objectStack, $itemBudget);
                continue;
            }

            $sanitized[$sanitizedKey] = $this->sanitizeImportStringValue((string) $item, $depth, $objectStack, $itemBudget);
        }

        return $sanitized;
    }

    private function resetImportDuplicateWarnings(): void
    {
        $this->importDuplicateWarnings = [];
    }

    private function recordImportDuplicateWarning(string $path): void
    {
        if ($path === '') {
            return;
        }

        $this->importDuplicateWarnings[] = $path;
    }

    /**
     * @return list<string>
     */
    private function consumeImportDuplicateWarnings(): array
    {
        if ($this->importDuplicateWarnings === []) {
            return [];
        }

        $warnings = array_values(array_unique($this->importDuplicateWarnings));
        $this->importDuplicateWarnings = [];

        return $warnings;
    }

    private function formatDuplicateKeyPath(string $parentPath, int|string $key): string
    {
        $keyPart = (string) $key;

        return $parentPath === '' ? $keyPart : $parentPath . '.' . $keyPart;
    }

    /**
     * @param mixed $key
     * @return int|string|null
     */
    private function sanitizeImportKey($key, int $depth)
    {
        if (is_int($key)) {
            return $key;
        }

        if (is_string($key)) {
            $sanitized = sanitize_key($key);

            if ($sanitized === '') {
                $fallback = preg_replace('/[^a-z0-9_\-]+/i', '-', strtolower($key));
                $fallback = is_string($fallback) ? trim($fallback, '-_') : '';
                $sanitized = $fallback;
            }

            return $sanitized !== '' ? $sanitized : null;
        }

        $casted = (string) $key;
        if ($casted === '') {
            return null;
        }

        $sanitized = sanitize_key($casted);
        if ($sanitized === '') {
            $fallback = preg_replace('/[^a-z0-9_\-]+/i', '-', strtolower($casted));
            $fallback = is_string($fallback) ? trim($fallback, '-_') : '';
            $sanitized = $fallback;
        }

        return $sanitized !== '' ? $sanitized : null;
    }

    private function sanitizeImportStringValue(string $value, int $depth = 0, ?\SplObjectStorage $objectStack = null, ?int &$itemBudget = null): string
    {
        if (function_exists('wp_check_invalid_utf8')) {
            $value = wp_check_invalid_utf8($value);
        }

        if ($value === false) {
            return '';
        }

        $value = (string) $value;

        $trimmed = trim($value);

        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            $decoded = json_decode($trimmed, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $sanitized = $this->sanitizeImportArray($decoded, $depth + 1, $objectStack, $itemBudget);

                if ($sanitized !== null) {
                    $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

                    if (defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
                        $jsonOptions |= JSON_PARTIAL_OUTPUT_ON_ERROR;
                    }

                    $encoded = function_exists('wp_json_encode')
                        ? wp_json_encode($sanitized, $jsonOptions)
                        : json_encode($sanitized, $jsonOptions);

                    if (is_string($encoded)) {
                        $value = $encoded;
                    }
                } else {
                    $value = '';
                }
            }
        }

        $value = wp_kses($value, []);

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', '', $value);

        if (!is_string($value)) {
            $value = '';
        }

        return trim($value);
    }

    /**
     * @param mixed $value
     */
    private function sanitizeImportString($value): ?string
    {
        if (is_string($value)) {
            return $this->sanitizeImportStringValue($value);
        }

        if (is_scalar($value)) {
            return $this->sanitizeImportStringValue((string) $value);
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

        return $combined === '' ? '' : trim($combined);
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

        $required_capability = \ssc_get_required_capability();

        if (!current_user_can($required_capability)) {
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

