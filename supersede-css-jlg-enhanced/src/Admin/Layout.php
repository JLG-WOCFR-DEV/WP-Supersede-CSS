<?php declare(strict_types=1);
namespace SSC\Admin;

if (!defined('ABSPATH')) { exit; }

class Layout {
    /**
     * Returns the list of allowed HTML tags for admin page rendering.
     */
    public static function allowed_tags(): array {
        $allowed = wp_kses_allowed_html('post');

        foreach ($allowed as $tag => $attributes) {
            if (!is_array($attributes)) {
                continue;
            }

            $attributes['style']  = true;
            $attributes['data-*'] = true;

            $allowed[$tag] = $attributes;
        }

        $allowed['style'] = [
            'id'     => true,
            'class'  => true,
            'media'  => true,
            'scoped' => true,
            'title'  => true,
            'type'   => true,
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

        return $allowed;
    }

    public static function render(string $page_content, string $current_page_slug): void {
        
        $menu_items = [
            'Fondamentaux' => [
                'supersede-css-jlg'                => 'Dashboard',
                'supersede-css-jlg-utilities'      => 'Utilities (Éditeur CSS)',
                'supersede-css-jlg-tokens'         => 'Tokens Manager',
                'supersede-css-jlg-preset'         => 'Preset Designer',
            ],
            'Générateurs Visuels' => [
                'supersede-css-jlg-layout-builder' => 'Maquettage de Page',
                'supersede-css-jlg-grid'           => 'Grid Editor',
                'supersede-css-jlg-shadow'         => 'Shadow Editor',
                'supersede-css-jlg-gradient'       => 'Gradient Editor',
                'supersede-css-jlg-typography'     => 'Typographie Fluide',
                'supersede-css-jlg-clip-path'      => 'Découpe (Clip-Path)',
                'supersede-css-jlg-filters'        => 'Filtres & Verre',
            ],
            'Effets & Animations' => [
                'supersede-css-jlg-anim'           => 'Animation Studio',
                'supersede-css-jlg-effects'        => 'Effets Visuels',
                'supersede-css-jlg-tron'           => 'Tron Grid',
                'supersede-css-jlg-avatar'         => 'Avatar Glow',
            ],
            'Outils & Maintenance' => [
                'supersede-css-jlg-scope'          => 'Scope Builder',
                'supersede-css-jlg-css-viewer'     => 'Visualiseur CSS',
                'supersede-css-jlg-import'         => 'Import/Export',
                'supersede-css-jlg-debug-center'   => 'Debug Center',
            ],
        ];
        ?>
        <div class="ssc-shell">
            <header class="ssc-topbar">
                <a href="<?php echo esc_url(admin_url('index.php')); ?>" class="ssc-back-to-admin button">
                    <span class="dashicons dashicons-arrow-left-alt"></span> WP Admin
                </a>
                <span class="ssc-title">Supersede CSS</span><span class="ssc-spacer"></span>
                <button class="button" id="ssc-theme">🌓 Thème</button>
                <button class="button button-primary" id="ssc-cmdk">⌘K Commande</button>
            </header>
            <div class="ssc-layout">
                <aside><nav class="ssc-sidebar">
                    <?php foreach ($menu_items as $group_label => $items): ?>
                        <div class="ssc-sidebar-group">
                            <h4 class="ssc-sidebar-heading"><?php echo esc_html($group_label); ?></h4>
                            <?php foreach ($items as $slug => $label): ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>" class="<?php echo esc_attr( $current_page_slug === $slug ? 'active' : '' ); ?>">
                                    <?php echo esc_html($label); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </nav></aside>
                <main class="ssc-main-content">
                    <?php echo wp_kses( $page_content, self::allowed_tags() ); ?>
                </main>
            </div>
        </div>
        <?php
    }
}

