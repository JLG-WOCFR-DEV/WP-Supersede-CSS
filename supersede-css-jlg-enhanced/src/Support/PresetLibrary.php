<?php declare(strict_types=1);

namespace SSC\Support;

use function __;
use function apply_filters;
use function esc_url_raw;
use function get_option;
use function is_array;
use function sanitize_text_field;
use function update_option;

/**
 * Catalog management for Supersede CSS presets.
 *
 * The roadmap highlights the need for professional-grade preset bundles that can be
 * distributed to editorial teams and external tooling.  The PresetLibrary now stores
 * enriched metadata describing each curated preset so that the REST catalog endpoint
 * can expose the information in a structured way (name, family, focus, token
 * priorities…).
 */

final class PresetLibrary
{
    private const OPTION_KEY = 'ssc_presets';

    public static function ensureDefaults(): void
    {
        $existing = get_option(self::OPTION_KEY, null);

        if (is_array($existing)) {
            $sanitizedExisting = CssSanitizer::sanitizePresetCollection($existing);

            if ($sanitizedExisting !== []) {
                if ($sanitizedExisting !== $existing) {
                    update_option(self::OPTION_KEY, $sanitizedExisting, false);
                }

                return;
            }
        }

        $defaults = CssSanitizer::sanitizePresetCollection(self::getDefaults());
        if ($defaults === []) {
            return;
        }

        update_option(self::OPTION_KEY, $defaults, false);
    }

    /**
     * @return array<string, array{name: string, scope: string, props: array<string, string>}>
     */
    public static function getDefaults(): array
    {
        $definitions = self::getCatalogDefinitions();

        $defaults = [];
        foreach ($definitions as $id => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $defaults[$id] = [
                'name' => isset($definition['name']) ? (string) $definition['name'] : '',
                'scope' => isset($definition['scope']) ? (string) $definition['scope'] : '',
                'props' => isset($definition['props']) && is_array($definition['props'])
                    ? $definition['props']
                    : [],
            ];
        }

        return $defaults;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getCatalogDefinitions(): array
    {
        $definitions = [
            'headless_ui_minimal' => [
                'name' => __('Headless UI Minimal', 'supersede-css-jlg'),
                'scope' => ':root[data-ssc-preset="headless-ui"]',
                'props' => [
                    '--surface-base' => '#ffffff',
                    '--surface-muted' => '#f8fafc',
                    '--surface-inverse' => '#0f172a',
                    '--radius-md' => '0.75rem',
                    '--focus-ring' => '0 0 0 3px rgba(79, 70, 229, 0.35)',
                    '--shadow-soft' => '0 20px 35px -20px rgba(15, 23, 42, 0.45)',
                    '--transition-fast' => '150ms cubic-bezier(0.4, 0, 0.2, 1)',
                    '--spacing-compact' => '0.75rem',
                    '--spacing-cozy' => '1.25rem',
                    '--font-family-base' => 'Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                ],
                'meta' => [
                    'family' => __('Minimal & Composable', 'supersede-css-jlg'),
                    'focus' => __('Unstyled, accessibility-first primitives.', 'supersede-css-jlg'),
                    'token_priorities' => [
                        __('Spacing scale', 'supersede-css-jlg'),
                        __('Focus rings', 'supersede-css-jlg'),
                        __('Semantic state colors', 'supersede-css-jlg'),
                    ],
                    'component_ideas' => [
                        __('Dialog', 'supersede-css-jlg'),
                        __('Tabs', 'supersede-css-jlg'),
                        __('Combobox', 'supersede-css-jlg'),
                        __('Menu', 'supersede-css-jlg'),
                        __('Switch', 'supersede-css-jlg'),
                    ],
                    'customization_hooks' => __('Slot driven parts (`--dialog-backdrop`, `--dialog-panel`) to inject brand gradients or glassmorphism.', 'supersede-css-jlg'),
                    'tags' => ['accessibility', 'headless', 'minimal'],
                    'preview' => [
                        'headline' => __('Focus on semantic, composable primitives.', 'supersede-css-jlg'),
                    ],
                ],
            ],
            'shadcn_essentials' => [
                'name' => __('shadcn/ui Essentials', 'supersede-css-jlg'),
                'scope' => ':root[data-ssc-preset="shadcn"]',
                'props' => [
                    '--surface-base' => '#111827',
                    '--surface-muted' => '#1f2937',
                    '--surface-elevated' => '#151c2c',
                    '--text-color' => '#e2e8f0',
                    '--accent' => '#22d3ee',
                    '--shadow-layer-1' => '0 10px 30px -15px rgba(15, 23, 42, 0.5)',
                    '--shadow-layer-2' => '0 25px 50px -25px rgba(15, 23, 42, 0.65)',
                    '--border-radius-lg' => '1rem',
                    '--transition-medium' => '220ms cubic-bezier(0.4, 0, 0.2, 1)',
                    '--overlay-color' => 'rgba(15, 23, 42, 0.75)',
                ],
                'meta' => [
                    'family' => __('Minimal & Composable', 'supersede-css-jlg'),
                    'focus' => __('Modern neutrals with subtle radii and shadows.', 'supersede-css-jlg'),
                    'token_priorities' => [
                        __('Neutral gray palette (50–900)', 'supersede-css-jlg'),
                        __('Overlay colors', 'supersede-css-jlg'),
                        __('Medium radius scale', 'supersede-css-jlg'),
                        __('Transition durations', 'supersede-css-jlg'),
                    ],
                    'component_ideas' => [
                        __('Card', 'supersede-css-jlg'),
                        __('Command palette', 'supersede-css-jlg'),
                        __('Data table', 'supersede-css-jlg'),
                        __('Toast', 'supersede-css-jlg'),
                    ],
                    'customization_hooks' => __('Layered box-shadow tokens and optional `backdrop-filter` for frosted glass surfaces.', 'supersede-css-jlg'),
                    'tags' => ['dark', 'modern', 'neutral'],
                    'preview' => [
                        'headline' => __('Modern dark UI baseline with accent cyan.', 'supersede-css-jlg'),
                    ],
                ],
            ],
            'radix_adaptive' => [
                'name' => __('Radix UI Adaptive', 'supersede-css-jlg'),
                'scope' => ':root[data-ssc-preset="radix"]',
                'props' => [
                    '--accent-color' => '#7c3aed',
                    '--accent-contrast' => '#ffffff',
                    '--success-color' => '#10b981',
                    '--destructive-color' => '#ef4444',
                    '--radius-tight' => '0.375rem',
                    '--radius-cozy' => '0.625rem',
                    '--radius-spacious' => '0.75rem',
                    '--shadow-focus' => '0 0 0 2px rgba(124, 58, 237, 0.45)',
                    '--motion-snap' => '180ms cubic-bezier(0.25, 0.9, 0.3, 1.4)',
                    '--density-compact' => '0.5rem',
                    '--density-spacious' => '1.4rem',
                ],
                'meta' => [
                    'family' => __('Minimal & Composable', 'supersede-css-jlg'),
                    'focus' => __('Highly configurable primitives with state-driven styling.', 'supersede-css-jlg'),
                    'token_priorities' => [
                        __('Accent, success and destructive color pairs', 'supersede-css-jlg'),
                        __('Adaptive spacing scale', 'supersede-css-jlg'),
                        __('Motion curves', 'supersede-css-jlg'),
                    ],
                    'component_ideas' => [
                        __('Dropdown menu', 'supersede-css-jlg'),
                        __('Tooltip', 'supersede-css-jlg'),
                        __('Slider', 'supersede-css-jlg'),
                        __('Collapsible sections', 'supersede-css-jlg'),
                    ],
                    'customization_hooks' => __('CSS variables per component part (e.g. `--slider-track`, `--slider-thumb`) and density toggles.', 'supersede-css-jlg'),
                    'tags' => ['adaptive', 'states', 'product'],
                    'preview' => [
                        'headline' => __('State-aware tokens ideal for product teams.', 'supersede-css-jlg'),
                    ],
                ],
            ],
            'bootstrap_revival' => [
                'name' => __('Bootstrap Revival', 'supersede-css-jlg'),
                'scope' => ':root[data-ssc-preset="bootstrap"]',
                'props' => [
                    '--brand-primary' => '#0d6efd',
                    '--brand-secondary' => '#6c757d',
                    '--brand-success' => '#198754',
                    '--brand-warning' => '#ffc107',
                    '--brand-danger' => '#dc3545',
                    '--font-family-base' => '"Helvetica Neue", Arial, sans-serif',
                    '--border-radius-base' => '0.5rem',
                    '--border-width-base' => '1px',
                    '--grid-gutter' => '1.5rem',
                    '--shadow-sm' => '0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)',
                    '--shadow-lg' => '0 1rem 3rem rgba(0, 0, 0, 0.175)',
                ],
                'meta' => [
                    'family' => __('Classic Framework Energy', 'supersede-css-jlg'),
                    'focus' => __('Utility-driven responsive layout with bold accents.', 'supersede-css-jlg'),
                    'token_priorities' => [
                        __('Brand color scale', 'supersede-css-jlg'),
                        __('Typographic scale (h1–h6)', 'supersede-css-jlg'),
                        __('Breakpoint map', 'supersede-css-jlg'),
                        __('Grid gutters', 'supersede-css-jlg'),
                    ],
                    'component_ideas' => [
                        __('Navbar', 'supersede-css-jlg'),
                        __('Buttons', 'supersede-css-jlg'),
                        __('Alerts', 'supersede-css-jlg'),
                        __('Badges', 'supersede-css-jlg'),
                        __('Form controls', 'supersede-css-jlg'),
                    ],
                    'customization_hooks' => __('Toggleable rounded vs. square shapes and adjustable border widths.', 'supersede-css-jlg'),
                    'tags' => ['marketing', 'classic', 'responsive'],
                    'preview' => [
                        'headline' => __('A familiar baseline for marketing sites and documentation.', 'supersede-css-jlg'),
                    ],
                ],
            ],
            'semantic_ui_friendly' => [
                'name' => __('Semantic UI Friendly', 'supersede-css-jlg'),
                'scope' => ':root[data-ssc-preset="semantic"]',
                'props' => [
                    '--color-positive' => '#21ba45',
                    '--color-info' => '#31ccec',
                    '--color-warning' => '#fbbd08',
                    '--color-negative' => '#db2828',
                    '--shadow-floating' => '0 12px 30px -12px rgba(36, 41, 46, 0.25)',
                    '--shadow-raised' => '0 22px 45px -20px rgba(36, 41, 46, 0.3)',
                    '--transition-primary' => '260ms cubic-bezier(0.23, 1, 0.32, 1)',
                    '--transition-subtle' => '180ms cubic-bezier(0.4, 0, 0.2, 1)',
                    '--border-radius-pill' => '9999px',
                    '--font-family-heading' => '"Lato", "Helvetica Neue", Arial, sans-serif',
                ],
                'meta' => [
                    'family' => __('Classic Framework Energy', 'supersede-css-jlg'),
                    'focus' => __('Friendly semantics and smooth animations.', 'supersede-css-jlg'),
                    'token_priorities' => [
                        __('Warm color palette', 'supersede-css-jlg'),
                        __('Large shadow presets', 'supersede-css-jlg'),
                        __('Transition timing tokens', 'supersede-css-jlg'),
                    ],
                    'component_ideas' => [
                        __('Feed', 'supersede-css-jlg'),
                        __('Comment thread', 'supersede-css-jlg'),
                        __('Statistic cards', 'supersede-css-jlg'),
                        __('Progress bars', 'supersede-css-jlg'),
                    ],
                    'customization_hooks' => __('Named color aliases (e.g. `--color-positive`) and animation presets for fade and slide transitions.', 'supersede-css-jlg'),
                    'tags' => ['community', 'animation', 'friendly'],
                    'preview' => [
                        'headline' => __('Warm, animated presets ideal for communities.', 'supersede-css-jlg'),
                    ],
                ],
            ],
            'anime_motion_storytelling' => [
                'name' => __('Anime.js Motion Storytelling', 'supersede-css-jlg'),
                'scope' => ':root[data-ssc-preset="animejs"]',
                'props' => [
                    '--motion-emphasis' => 'cubic-bezier(0.22, 1, 0.36, 1)',
                    '--motion-subtle' => 'cubic-bezier(0.4, 0, 0.2, 1)',
                    '--motion-stagger' => '120ms',
                    '--motion-loop' => 'infinite',
                    '--motion-perspective' => '900px',
                    '--motion-depth' => 'translateZ(0)',
                    '--surface-base' => '#050815',
                    '--surface-contrast' => '#f8fafc',
                    '--accent-primary' => '#f472b6',
                    '--accent-secondary' => '#38bdf8',
                    '--text-glow' => '0 0 18px rgba(56, 189, 248, 0.55)',
                ],
                'meta' => [
                    'family' => __('Motion & Micro-Interactions', 'supersede-css-jlg'),
                    'focus' => __('Motion-driven storytelling and kinetic typography.', 'supersede-css-jlg'),
                    'token_priorities' => [
                        __('Easing curves', 'supersede-css-jlg'),
                        __('Stagger delays', 'supersede-css-jlg'),
                        __('Perspective depths', 'supersede-css-jlg'),
                    ],
                    'component_ideas' => [
                        __('Scroll-triggered hero', 'supersede-css-jlg'),
                        __('Animated counters', 'supersede-css-jlg'),
                        __('Icon morphs', 'supersede-css-jlg'),
                    ],
                    'customization_hooks' => __('Declarative animation maps (`--motion-emphasis`, `--motion-subtle`) ready for CSS keyframes or JS timelines.', 'supersede-css-jlg'),
                    'tags' => ['motion', 'storytelling', 'creative'],
                    'preview' => [
                        'headline' => __('Energetic storytelling preset with neon accents.', 'supersede-css-jlg'),
                    ],
                ],
            ],
        ];

        if (function_exists('apply_filters')) {
            $definitions = apply_filters('ssc_presets_catalog_definitions', $definitions);
        }

        return is_array($definitions) ? $definitions : [];
    }

    /**
     * Build catalog-ready entries combining stored presets with curated metadata.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getCatalogEntries(): array
    {
        $stored = get_option(self::OPTION_KEY, null);

        if (!is_array($stored) || $stored === []) {
            $stored = self::getDefaults();
        }

        $sanitized = CssSanitizer::sanitizePresetCollection($stored);

        $definitions = self::getCatalogDefinitions();

        $entries = [];

        foreach ($sanitized as $id => $preset) {
            $definition = $definitions[$id] ?? null;
            $meta = self::normalizeCatalogMeta($definition['meta'] ?? []);
            $entries[$id] = self::buildCatalogEntry($id, $preset, $definition ? 'core' : 'custom', $meta);
        }

        foreach ($definitions as $id => $definition) {
            if (isset($entries[$id])) {
                continue;
            }

            $fallback = CssSanitizer::sanitizePresetCollection([
                $id => [
                    'name' => isset($definition['name']) ? (string) $definition['name'] : '',
                    'scope' => isset($definition['scope']) ? (string) $definition['scope'] : '',
                    'props' => isset($definition['props']) && is_array($definition['props'])
                        ? $definition['props']
                        : [],
                ],
            ]);

            if (!isset($fallback[$id])) {
                continue;
            }

            $entries[$id] = self::buildCatalogEntry(
                $id,
                $fallback[$id],
                'core',
                self::normalizeCatalogMeta($definition['meta'] ?? [])
            );
        }

        $entries = array_values($entries);

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('ssc_presets_catalog_entries', $entries);
            if (is_array($filtered)) {
                $entries = $filtered;
            }
        }

        return $entries;
    }

    /**
     * @param array<string, mixed> $preset
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private static function buildCatalogEntry(string $id, array $preset, string $source, array $meta): array
    {
        $name = isset($preset['name']) ? (string) $preset['name'] : '';
        $scope = isset($preset['scope']) ? (string) $preset['scope'] : '';
        $props = isset($preset['props']) && is_array($preset['props']) ? $preset['props'] : [];

        $css = self::renderPresetCss($scope, $props);

        $checksumPayload = json_encode([
            'scope' => $scope,
            'props' => $props,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $checksum = is_string($checksumPayload) ? sha1($checksumPayload) : '';

        return [
            'id' => $id,
            'name' => $name,
            'scope' => $scope,
            'props' => $props,
            'css' => $css,
            'checksum' => $checksum,
            'token_count' => count($props),
            'source' => $source,
            'meta' => $meta,
        ];
    }

    /**
     * @param array<string, string> $props
     */
    public static function renderPresetCss(string $scope, array $props): string
    {
        $scope = trim($scope);

        if ($scope === '') {
            $scope = ':root';
        }

        if ($props === []) {
            return '';
        }

        $lines = [];
        foreach ($props as $prop => $value) {
            $prop = (string) $prop;
            $value = (string) $value;

            if ($prop === '' || $value === '') {
                continue;
            }

            $lines[] = sprintf('    %s: %s;', $prop, $value);
        }

        if ($lines === []) {
            return '';
        }

        $css = $scope . " {\n" . implode("\n", $lines) . "\n}";

        $css = CssSanitizer::sanitize($css);

        return $css;
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    public static function renderCatalogStylesheet(array $entries): string
    {
        $chunks = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $css = isset($entry['css']) ? trim((string) $entry['css']) : '';
            if ($css === '') {
                continue;
            }

            $label = isset($entry['name']) ? (string) $entry['name'] : '';
            if ($label === '') {
                $label = isset($entry['id']) ? (string) $entry['id'] : '';
            }

            if ($label === '') {
                $label = 'Preset';
            }

            $chunks[] = sprintf("/* %s */\n%s", $label, $css);
        }

        return implode("\n\n", $chunks);
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private static function normalizeCatalogMeta($meta): array
    {
        $normalized = [
            'family' => '',
            'focus' => '',
            'token_priorities' => [],
            'component_ideas' => [],
            'customization_hooks' => '',
            'tags' => [],
            'preview' => [],
            'documentation_url' => '',
        ];

        if (!is_array($meta)) {
            return $normalized;
        }

        foreach (['family', 'focus', 'customization_hooks'] as $key) {
            if (isset($meta[$key]) && $meta[$key] !== '') {
                $normalized[$key] = sanitize_text_field((string) $meta[$key]);
            }
        }

        foreach (['token_priorities', 'component_ideas', 'tags'] as $key) {
            if (!isset($meta[$key]) || !is_array($meta[$key])) {
                continue;
            }

            $normalized[$key] = [];

            foreach ($meta[$key] as $value) {
                if (!is_string($value) && !is_numeric($value)) {
                    continue;
                }

                $clean = sanitize_text_field((string) $value);
                if ($clean === '') {
                    continue;
                }

                $normalized[$key][] = $clean;
            }
        }

        if (isset($meta['preview']) && is_array($meta['preview'])) {
            $preview = [];
            foreach ($meta['preview'] as $previewKey => $previewValue) {
                if (!is_string($previewKey)) {
                    continue;
                }

                if (!is_string($previewValue) && !is_numeric($previewValue)) {
                    continue;
                }

                $clean = sanitize_text_field((string) $previewValue);
                if ($clean === '') {
                    continue;
                }

                $preview[$previewKey] = $clean;
            }

            if ($preview !== []) {
                $normalized['preview'] = $preview;
            }
        }

        if (isset($meta['documentation_url']) && is_string($meta['documentation_url'])) {
            $url = esc_url_raw($meta['documentation_url']);
            if ($url !== '') {
                $normalized['documentation_url'] = $url;
            }
        }

        return $normalized;
    }
}
