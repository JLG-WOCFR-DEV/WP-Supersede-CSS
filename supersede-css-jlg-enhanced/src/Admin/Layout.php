<?php declare(strict_types=1);
namespace SSC\Admin;

use SSC\Support\CssSanitizer;

if (!defined('ABSPATH')) { exit; }

class Layout {
    private static ?array $allowedTagsCache = null;

    /**
     * Returns the list of allowed HTML tags for admin page rendering.
     */
    public static function allowed_tags(): array {
        if (self::$allowedTagsCache !== null) {
            return self::$allowedTagsCache;
        }

        $allowed = wp_kses_allowed_html('post');

        $containerTags = [
            'article', 'aside', 'div', 'figcaption', 'figure', 'footer', 'header', 'li', 'main', 'nav', 'p', 'pre',
            'section', 'span', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'ul', 'ol', 'dl', 'dt', 'dd',
        ];

        foreach ($allowed as $tag => $attributes) {
            if (!is_array($attributes)) {
                continue;
            }

            $attributes['style']  = true;
            $attributes['data-*'] = true;

            if (in_array($tag, $containerTags, true)) {
                $attributes['id'] = true;
                $attributes['class'] = true;
            }

            $allowed[$tag] = $attributes;
        }

        foreach ($containerTags as $containerTag) {
            if (!isset($allowed[$containerTag]) || !is_array($allowed[$containerTag])) {
                $allowed[$containerTag] = [];
            }

            $allowed[$containerTag]['id'] = true;

            if (!array_key_exists('class', $allowed[$containerTag])) {
                $allowed[$containerTag]['class'] = true;
            }
        }

        $allowed['table']['role'] = true;
        if (!isset($allowed['th']) || !is_array($allowed['th'])) {
            $allowed['th'] = [];
        }
        $allowed['th']['scope'] = true;

        $allowed['style'] = [
            'id'     => true,
            'class'  => true,
            'media'  => true,
            'scoped' => true,
            'title'  => true,
            'type'   => true,
        ];

        $allowed['form'] = [
            'accept-charset' => true,
            'action'         => true,
            'autocomplete'   => true,
            'class'          => true,
            'data-*'         => true,
            'enctype'        => true,
            'id'             => true,
            'method'         => true,
            'name'           => true,
            'novalidate'     => true,
            'target'         => true,
        ];

        $allowed['input'] = [
            'accept'        => true,
            'aria-*'        => true,
            'autocomplete'  => true,
            'checked'       => true,
            'class'         => true,
            'data-*'        => true,
            'disabled'      => true,
            'form'          => true,
            'formaction'    => true,
            'formenctype'   => true,
            'formmethod'    => true,
            'formnovalidate'=> true,
            'formtarget'    => true,
            'id'            => true,
            'list'          => true,
            'max'           => true,
            'maxlength'     => true,
            'min'           => true,
            'minlength'     => true,
            'multiple'      => true,
            'name'          => true,
            'pattern'       => true,
            'placeholder'   => true,
            'readonly'      => true,
            'required'      => true,
            'size'          => true,
            'step'          => true,
            'type'          => true,
            'value'         => true,
        ];

        $allowed['textarea'] = [
            'aria-*'       => true,
            'autocomplete' => true,
            'class'        => true,
            'cols'         => true,
            'data-*'       => true,
            'form'         => true,
            'id'           => true,
            'maxlength'    => true,
            'minlength'    => true,
            'name'         => true,
            'placeholder'  => true,
            'readonly'     => true,
            'required'     => true,
            'rows'         => true,
            'spellcheck'   => true,
            'wrap'         => true,
        ];

        $allowed['select'] = [
            'aria-*'       => true,
            'autocomplete' => true,
            'class'        => true,
            'data-*'       => true,
            'disabled'     => true,
            'form'         => true,
            'id'           => true,
            'multiple'     => true,
            'name'         => true,
            'required'     => true,
            'size'         => true,
        ];

        $allowed['option'] = [
            'value'    => true,
            'label'    => true,
            'selected' => true,
            'disabled' => true,
            'data-*'   => true,
        ];

        $allowed['optgroup'] = [
            'label'    => true,
            'disabled' => true,
            'data-*'   => true,
        ];

        $allowed['datalist'] = [
            'id'     => true,
            'class'  => true,
            'name'   => true,
            'data-*' => true,
        ];

        $allowed['label'] = [
            'class'   => true,
            'data-*'  => true,
            'for'     => true,
            'form'    => true,
            'id'      => true,
        ];

        $allowed['button'] = [
            'aria-*'       => true,
            'autocomplete' => true,
            'class'        => true,
            'data-*'       => true,
            'disabled'     => true,
            'form'         => true,
            'formaction'   => true,
            'formenctype'  => true,
            'formmethod'   => true,
            'formnovalidate'=> true,
            'formtarget'   => true,
            'id'           => true,
            'name'         => true,
            'type'         => true,
            'value'        => true,
        ];

        $allowed['fieldset'] = [
            'id'      => true,
            'name'    => true,
            'class'   => true,
            'disabled'=> true,
            'form'    => true,
            'data-*'  => true,
        ];

        $allowed['legend'] = [
            'id'     => true,
            'class'  => true,
            'data-*' => true,
        ];

        $allowed['iframe'] = [
            'allow'             => true,
            'allowfullscreen'   => true,
            'class'             => true,
            'height'            => true,
            'loading'           => true,
            'name'              => true,
            'referrerpolicy'    => true,
            'sandbox'           => true,
            'src'               => true,
            'title'             => true,
            'width'             => true,
            'aria-describedby'  => true,
            'aria-label'        => true,
            'aria-labelledby'   => true,
            'data-*'            => true,
        ];

        $allowed['canvas'] = [
            'class'   => true,
            'height'  => true,
            'id'      => true,
            'style'   => true,
            'width'   => true,
            'data-*'  => true,
        ];

        $svg_tags = array_unique([
            'svg', 'g', 'defs', 'symbol', 'pattern', 'mask', 'clippath', 'use', 'path', 'circle', 'ellipse',
            'line', 'polyline', 'polygon', 'rect', 'lineargradient', 'radialgradient', 'stop', 'filter',
            'fegaussianblur', 'feoffset', 'feblend', 'fecolormatrix', 'fecomponenttransfer', 'fefunca',
            'fefuncb', 'fefuncg', 'fefuncr', 'fecomposite', 'feimage', 'femerge', 'femergenode', 'fespotlight',
            'feturbulence', 'feconvolvematrix', 'text', 'tspan', 'foreignobject', 'animate', 'animatemotion',
            'animatetransform',
        ]);

        $svg_attributes = [
            'aria-hidden'           => true,
            'class'                 => true,
            'clip-path'             => true,
            'cliprule'              => true,
            'd'                     => true,
            'data-*'                => true,
            'fill'                  => true,
            'filterunits'           => true,
            'focusable'             => true,
            'gradienttransform'     => true,
            'gradientunits'         => true,
            'height'                => true,
            'href'                  => true,
            'id'                    => true,
            'in'                    => true,
            'in2'                   => true,
            'marker-end'            => true,
            'marker-mid'            => true,
            'marker-start'          => true,
            'maskcontentunits'      => true,
            'maskunits'             => true,
            'offset'                => true,
            'opacity'               => true,
            'patterncontentunits'   => true,
            'patternunits'          => true,
            'points'                => true,
            'preserveaspectratio'   => true,
            'r'                     => true,
            'rx'                    => true,
            'ry'                    => true,
            'stroke'                => true,
            'stroke-dasharray'      => true,
            'stroke-dashoffset'     => true,
            'stroke-linecap'        => true,
            'stroke-linejoin'       => true,
            'stroke-miterlimit'     => true,
            'stroke-width'          => true,
            'style'                 => true,
            'transform'             => true,
            'viewbox'               => true,
            'width'                 => true,
            'x'                     => true,
            'x1'                    => true,
            'x2'                    => true,
            'xlink:href'            => true,
            'xml:space'             => true,
            'xmlns'                 => true,
            'xmlns:xlink'           => true,
            'y'                     => true,
            'y1'                    => true,
            'y2'                    => true,
        ];

        foreach ($svg_tags as $tag) {
            $allowed[$tag] = $svg_attributes;
        }

        self::$allowedTagsCache = $allowed;

        return self::$allowedTagsCache;
    }

    public static function render(string $page_content, string $current_page_slug): void {
        $page_content = self::sanitize_style_blocks($page_content);

        $menu_items = ModuleRegistry::groupedMenu();
        $active_group_key = ModuleRegistry::groupKeyForPage($current_page_slug) ?? 'fundamentals';
        $current_group_items = $menu_items[$active_group_key]['items'] ?? [];
        $command_button_label = esc_html__('Palette de commandes', 'supersede-css-jlg');
        $command_button_aria_label = esc_attr__('Ouvrir la palette de commandes', 'supersede-css-jlg');

        ?>
        <div class="wrap">
            <a class="ssc-skip-link" href="#ssc-main-content"><?php echo esc_html__('Passer au contenu principal', 'supersede-css-jlg'); ?></a>
            <h1 class="wp-heading-inline"><?php echo esc_html__('Supersede CSS', 'supersede-css-jlg'); ?></h1>
            <button type="button" class="page-title-action" id="ssc-cmdk" aria-label="<?php echo $command_button_aria_label; ?>">
                <?php echo $command_button_label; ?>
            </button>
            <hr class="wp-header-end" />
            <nav class="nav-tab-wrapper wp-clearfix ssc-admin-nav" aria-label="<?php echo esc_attr__('Navigation Supersede CSS', 'supersede-css-jlg'); ?>">
                <?php foreach ($menu_items as $group): ?>
                    <?php
                    if (empty($group['items'])) {
                        continue;
                    }
                    $is_active = ($active_group_key === $group['key']);
                    $tab_slug = $is_active ? $current_page_slug : ModuleRegistry::firstPageSlugForGroup($group['key']);
                    $classes = 'nav-tab' . ($is_active ? ' nav-tab-active' : '');
                    ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $tab_slug)); ?>" class="<?php echo esc_attr($classes); ?>">
                        <?php echo esc_html($group['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <?php if ($current_group_items !== []): ?>
                <?php
                $item_slugs = array_keys($current_group_items);
                $last_slug = (string) end($item_slugs);
                ?>
                <ul class="subsubsub">
                    <?php foreach ($current_group_items as $slug => $label): ?>
                        <?php $is_current = ($current_page_slug === $slug); ?>
                        <li>
                            <a
                                href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>"
                                class="<?php echo $is_current ? 'current' : ''; ?>"
                                <?php if ($is_current): ?>aria-current="page"<?php endif; ?>
                            ><?php echo esc_html($label); ?></a><?php echo $slug !== $last_slug ? ' |' : ''; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <br class="clear" />
            <?php endif; ?>
            <div class="ssc-main-content" id="ssc-main-content" tabindex="-1">
                <?php echo wp_kses($page_content, self::allowed_tags()); ?>
            </div>
        </div>
        <?php
    }

    private static function sanitize_style_blocks(string $html): string
    {
        return (string) preg_replace_callback(
            '#<style\b([^>]*)>(.*?)</style>#is',
            static function (array $matches): string {
                $attributes = $matches[1] ?? '';
                $decodeFlags = ENT_QUOTES;
                if (defined('ENT_SUBSTITUTE')) {
                    $decodeFlags |= ENT_SUBSTITUTE;
                }

                $css = html_entity_decode($matches[2] ?? '', $decodeFlags, 'UTF-8');
                $sanitizedCss = CssSanitizer::sanitize($css);

                return sprintf('<style%s>%s</style>', $attributes, $sanitizedCss);
            },
            $html
        );
    }
}

