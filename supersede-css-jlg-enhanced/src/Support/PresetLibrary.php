<?php declare(strict_types=1);

namespace SSC\Support;

use function __;
use function get_option;
use function is_array;
use function update_option;

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
        return [
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
            ],
        ];
    }
}
