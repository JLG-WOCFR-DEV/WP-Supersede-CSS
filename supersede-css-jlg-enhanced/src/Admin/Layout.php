<?php declare(strict_types=1);
namespace SSC\Admin;

if (!defined('ABSPATH')) { exit; }

class Layout {
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
                                <a href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>" class="<?php echo $current_page_slug === $slug ? 'active' : ''; ?>">
                                    <?php echo esc_html($label); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </nav></aside>
                <main class="ssc-main-content">
                    <?php echo wp_kses_post( $page_content ); ?>
                </main>
            </div>
        </div>
        <?php
    }
}