<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Infra\Import\Sanitizer;
use SSC\Infra\Logger;
use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ImportExportController extends BaseController
{
    /**
     * @var array<string, array{label: string, options: list<string>}> | null
     */
    private static ?array $configModules = null;

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

    public function __construct(private readonly Sanitizer $sanitizer)
    {
    }

    public function registerRoutes(): void
    {
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

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function exportConfig(WP_REST_Request $request)
    {
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
                if (class_exists('\\SSC\\Infra\\Logger')) {
                    \SSC\Infra\Logger::add('export_config_db_error', ['message' => $last_error]);
                }

                return new WP_Error(
                    'ssc_export_config_db_error',
                    __('Unable to export configuration due to a database error.', 'supersede-css-jlg'),
                    ['status' => 500]
                );
            }

            return new WP_REST_Response([], 200);
        }

        foreach ($results as $result) {
            $options[$result->option_name] = maybe_unserialize($result->option_value);
        }

        $rawModules = $request->get_param('modules');
        $modules = $this->normalizeModules($rawModules);

        if ($modules === []) {
            return new WP_Error(
                'ssc_export_config_invalid_modules',
                __('No valid Supersede CSS modules were selected for export.', 'supersede-css-jlg'),
                ['status' => 400]
            );
        }

        $options = $this->filterOptionsByModules($options, $modules, $rawModules !== null);

        return new WP_REST_Response($options, 200);
    }

    public function exportCss(): WP_REST_Response
    {
        $tokensCss = get_option('ssc_tokens_css', '');
        $activeCss = get_option('ssc_active_css', '');

        $tokensCss = is_string($tokensCss) ? $tokensCss : '';
        $activeCss = is_string($activeCss) ? $activeCss : '';

        if ($tokensCss === '' && $activeCss === '') {
            return new WP_REST_Response(['css' => '/* Aucun CSS actif trouvé. */'], 200);
        }

        $combinedCss = CssSanitizer::sanitize($tokensCss . "\n" . $activeCss);

        return new WP_REST_Response(['css' => $combinedCss], 200);
    }

    public function importConfig(WP_REST_Request $request): WP_REST_Response
    {
        $json = $request->get_json_params();

        if (!is_array($json)) {
            return new WP_REST_Response([
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
            return new WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid import format.', 'supersede-css-jlg'),
                'applied' => [],
                'skipped' => [],
            ], 400);
        }

        $originalOptionKeys = array_map(static fn($key): string => (string) $key, array_keys($options));

        if ($modules === []) {
            return new WP_REST_Response([
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
            return new WP_REST_Response([
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
            return new WP_REST_Response([
                'ok' => false,
                'message' => __('No valid Supersede CSS options were found in this import.', 'supersede-css-jlg'),
                'applied' => $result['applied'],
                'skipped' => $result['skipped'],
            ], 400);
        }

        if (class_exists(Logger::class)) {
            Logger::add('config_imported', [
                'applied' => (string) count($result['applied']),
                'skipped' => (string) count($result['skipped']),
            ]);
        }

        return new WP_REST_Response([
            'ok' => true,
            'applied' => $result['applied'],
            'skipped' => $result['skipped'],
        ], 200);
    }

    /**
     * @return array<string, array{label: string, options: list<string>}>|null
     */
    public static function getConfigModules(): ?array
    {
        if (self::$configModules === null) {
            self::$configModules = [
                'css' => [
                    'label' => __('CSS actif & variantes responsives', 'supersede-css-jlg'),
                    'options' => [
                        'ssc_active_css',
                        'ssc_css_desktop',
                        'ssc_css_tablet',
                        'ssc_css_mobile',
                    ],
                ],
                'tokens' => [
                    'label' => __('Design Tokens', 'supersede-css-jlg'),
                    'options' => [
                        'ssc_tokens_css',
                        'ssc_tokens_registry',
                    ],
                ],
                'presets' => [
                    'label' => __('Presets & collections', 'supersede-css-jlg'),
                    'options' => [
                        'ssc_presets',
                    ],
                ],
                'avatar' => [
                    'label' => __('Presets Avatar Glow', 'supersede-css-jlg'),
                    'options' => [
                        'ssc_avatar_glow_presets',
                    ],
                ],
                'settings' => [
                    'label' => __('Paramètres généraux', 'supersede-css-jlg'),
                    'options' => [
                        'ssc_settings',
                        'ssc_modules_enabled',
                        'ssc_optimization_settings',
                        'ssc_secret',
                        'ssc_safe_mode',
                    ],
                ],
                'logs' => [
                    'label' => __("Journal d'administration", 'supersede-css-jlg'),
                    'options' => [
                        'ssc_admin_log',
                    ],
                ],
            ];
        }

        return self::$configModules;
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    private function normalizeModules($raw): array
    {
        $modules = self::getConfigModules();
        $allModules = $modules ? array_keys($modules) : [];

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

            if ($modules !== null && isset($modules[$key])) {
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
        $configModules = self::getConfigModules() ?? [];

        foreach ($modules as $module) {
            if (!isset($configModules[$module])) {
                continue;
            }

            foreach ($configModules[$module]['options'] as $optionName) {
                $options[$optionName] = true;
            }
        }

        return array_keys($options);
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

            $this->sanitizer->resetDuplicateWarnings();
            $sanitizedValue = $this->invokeSanitizer($handler, $value);
            $duplicateWarnings = $this->sanitizer->consumeDuplicateWarnings();

            if ($sanitizedValue === null) {
                $skipped[] = $optionName;
                foreach ($duplicateWarnings as $duplicatePath) {
                    $skipped[] = sprintf('%s (duplicate key: %s)', $optionName, $duplicatePath);
                }
                continue;
            }

            if ($optionName === 'ssc_tokens_css') {
                $tokens = TokenRegistry::convertCssToRegistry($sanitizedValue);
                $existingRegistry = get_option('ssc_tokens_registry', []);
                if (!is_array($existingRegistry)) {
                    $existingRegistry = [];
                }
                $tokensWithMetadata = TokenRegistry::mergeMetadata($tokens, $existingRegistry);
                $savedTokens = TokenRegistry::saveRegistry($tokensWithMetadata);
                if ($savedTokens['duplicates'] !== []) {
                    $duplicateLabels = array_map(static function (array $duplicate): string {
                        $variants = $duplicate['variants'] ?? [];
                        if (is_array($variants)) {
                            $variants = array_values(array_filter(array_map('strval', $variants)));
                        } else {
                            $variants = [];
                        }

                        if ($variants !== []) {
                            return implode(' / ', $variants);
                        }

                        return isset($duplicate['canonical']) ? (string) $duplicate['canonical'] : '';
                    }, $savedTokens['duplicates']);
                    $duplicateSummary = implode(', ', array_filter($duplicateLabels));
                    $skipped[] = sprintf(
                        '%s (%s: %s)',
                        $optionName,
                        __('duplicate token names', 'supersede-css-jlg'),
                        $duplicateSummary
                    );
                    foreach ($duplicateWarnings as $duplicatePath) {
                        $skipped[] = sprintf('%s (duplicate key: %s)', $optionName, $duplicatePath);
                    }
                    continue;
                }
                if (function_exists('ssc_invalidate_css_cache')) {
                    ssc_invalidate_css_cache();
                }
                $applied[] = $optionName;
                foreach ($duplicateWarnings as $duplicatePath) {
                    $skipped[] = sprintf('%s (duplicate key: %s)', $optionName, $duplicatePath);
                }
                continue;
            }

            if ($optionName === 'ssc_tokens_registry') {
                if (!is_array($sanitizedValue)) {
                    $skipped[] = $optionName;
                    foreach ($duplicateWarnings as $duplicatePath) {
                        $skipped[] = sprintf('%s (duplicate key: %s)', $optionName, $duplicatePath);
                    }
                    continue;
                }

                $savedTokens = TokenRegistry::saveRegistry($sanitizedValue);
                if ($savedTokens['duplicates'] !== []) {
                    $duplicateLabels = array_map(static function (array $duplicate): string {
                        $variants = $duplicate['variants'] ?? [];
                        if (is_array($variants)) {
                            $variants = array_values(array_filter(array_map('strval', $variants)));
                        } else {
                            $variants = [];
                        }

                        if ($variants !== []) {
                            return implode(' / ', $variants);
                        }

                        return isset($duplicate['canonical']) ? (string) $duplicate['canonical'] : '';
                    }, $savedTokens['duplicates']);
                    $duplicateSummary = implode(', ', array_filter($duplicateLabels));
                    $skipped[] = sprintf(
                        '%s (%s: %s)',
                        $optionName,
                        __('duplicate token names', 'supersede-css-jlg'),
                        $duplicateSummary
                    );
                    foreach ($duplicateWarnings as $duplicatePath) {
                        $skipped[] = sprintf('%s (duplicate key: %s)', $optionName, $duplicatePath);
                    }
                    continue;
                }
                if (function_exists('ssc_invalidate_css_cache')) {
                    ssc_invalidate_css_cache();
                }
                $applied[] = $optionName;
                foreach ($duplicateWarnings as $duplicatePath) {
                    $skipped[] = sprintf('%s (duplicate key: %s)', $optionName, $duplicatePath);
                }
                continue;
            }

            update_option($optionName, $sanitizedValue, false);
            if (
                function_exists('ssc_invalidate_css_cache')
                && in_array($optionName, ['ssc_active_css', 'ssc_css_desktop', 'ssc_css_tablet', 'ssc_css_mobile'], true)
            ) {
                ssc_invalidate_css_cache();
            }
            $applied[] = $optionName;
            foreach ($duplicateWarnings as $duplicatePath) {
                $skipped[] = sprintf('%s (duplicate key: %s)', $optionName, $duplicatePath);
            }
        }

        return [
            'applied' => array_values(array_unique($applied)),
            'skipped' => array_values(array_unique($skipped)),
        ];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function invokeSanitizer(string $method, $value)
    {
        if (!method_exists($this->sanitizer, $method)) {
            return null;
        }

        return $this->sanitizer->{$method}($value);
    }
}
