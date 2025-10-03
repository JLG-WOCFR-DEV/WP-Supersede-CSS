<?php declare(strict_types=1);

namespace SSC\Blocks;

use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;

if (!defined('ABSPATH')) { exit; }

final class TokenPreview
{
    public static function register(): void
    {
        register_block_type(
            SSC_PLUGIN_DIR . 'blocks/token-preview',
            [
                'render_callback' => [self::class, 'render'],
            ]
        );
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public static function render(array $attributes = [], string $content = '', $block = null): string
    {
        $tokens = TokenRegistry::getRegistry();
        $cachedCss = ssc_get_cached_css();
        $baseCss = '.ssc-token-preview{display:grid;gap:1.25rem;margin:0;padding:0;}'
            .'.ssc-token-preview__items{display:grid;gap:0.75rem;}'
            .'.ssc-token-preview__item{display:grid;gap:0.35rem;padding:1rem;border-radius:0.85rem;border:1px solid rgba(15,23,42,0.12);background:rgba(255,255,255,0.9);box-shadow:0 1px 2px rgba(15,23,42,0.08);}'
            .'.ssc-token-preview__item code{font-size:0.85rem;font-weight:600;}'
            .'.ssc-token-preview__value{font-size:0.95rem;}'
            .'.ssc-token-preview__description{font-size:0.8rem;color:rgba(15,23,42,0.7);}'
            .'.ssc-token-preview__group{font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;color:rgba(15,23,42,0.6);}'
            .'.ssc-token-preview__swatch{display:block;height:40px;border-radius:0.75rem;border:1px solid rgba(15,23,42,0.15);}'
            .'.ssc-token-preview__empty{margin:0;font-style:italic;color:rgba(15,23,42,0.75);}';
        $css = CssSanitizer::sanitize(trim($baseCss . "\n" . $cachedCss));

        $items = array_map(static function (array $token): string {
            $name = isset($token['name']) && is_string($token['name']) ? $token['name'] : '';
            $value = isset($token['value']) && is_string($token['value']) ? $token['value'] : '';
            $type = isset($token['type']) && is_string($token['type']) ? $token['type'] : '';
            $description = isset($token['description']) && is_string($token['description']) ? $token['description'] : '';
            $group = isset($token['group']) && is_string($token['group']) ? $token['group'] : '';

            $parts = [];

            if ($type === 'color' && $value !== '') {
                $parts[] = sprintf(
                    '<span class="ssc-token-preview__swatch" aria-hidden="true" style="background:%s"></span>',
                    esc_attr($value)
                );
            }

            $parts[] = sprintf(
                '<code class="ssc-token-preview__name">%s</code>',
                esc_html($name)
            );

            if ($value !== '') {
                $parts[] = sprintf(
                    '<span class="ssc-token-preview__value">%s</span>',
                    esc_html($value)
                );
            }

            if ($description !== '') {
                $parts[] = sprintf(
                    '<span class="ssc-token-preview__description">%s</span>',
                    esc_html($description)
                );
            }

            if ($group !== '') {
                $parts[] = sprintf(
                    '<span class="ssc-token-preview__group">%s</span>',
                    esc_html($group)
                );
            }

            return sprintf(
                '<div class="ssc-token-preview__item">%s</div>',
                implode('', $parts)
            );
        }, $tokens);

        $body = $items !== []
            ? implode('', $items)
            : sprintf(
                '<p class="ssc-token-preview__empty">%s</p>',
                esc_html__('Aucun token n\'est défini pour le moment. Ajoutez vos premiers tokens dans Supersede CSS.', 'supersede-css-jlg')
            );

        $styleTag = $css !== ''
            ? sprintf('<style class="ssc-token-preview__inline-styles">%s</style>', $css)
            : '';

        return sprintf(
            '<div class="ssc-token-preview wp-block-supersede-token-preview">%s<div class="ssc-token-preview__items">%s</div></div>',
            $styleTag,
            $body
        );
    }
}
