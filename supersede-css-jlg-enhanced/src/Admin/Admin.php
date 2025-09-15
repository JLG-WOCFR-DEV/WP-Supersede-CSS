<?php declare(strict_types=1);
namespace SSC\Admin;

if (!defined('ABSPATH')) { exit; }

final class Admin
{
    private $slug = 'supersede-css-jlg';
    private $cap  = 'manage_options';

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
    }

    public function menu(): void {
        add_menu_page('Supersede CSS', 'Supersede CSS', $this->cap, $this->slug, [$this, 'renderDashboard'], 'dashicons-art', 58);
        
        // Tous les modules sont maintenant correctement enregistrés
        $this->submenu($this->slug.'-layout-builder', 'Maquettage de Page', '\SSC\Admin\Pages\PageLayoutBuilder');
        $this->submenu($this->slug.'-utilities', 'Utilities', '\SSC\Admin\Pages\Utilities');
        $this->submenu($this->slug.'-tokens', 'Tokens Manager', '\SSC\Admin\Pages\Tokens');
        $this->submenu($this->slug.'-effects', 'Effets Visuels', '\SSC\Admin\Pages\VisualEffects');
        $this->submenu($this->slug.'-filters', 'Filtres & Verre', '\SSC\Admin\Pages\FilterEditor');
        $this->submenu($this->slug.'-clip-path', 'Découpe (Clip-Path)', '\SSC\Admin\Pages\ClipPathEditor');
        $this->submenu($this->slug.'-typography', 'Typographie Fluide', '\SSC\Admin\Pages\TypographyEditor');
        $this->submenu($this->slug.'-tron', 'Tron Grid', '\SSC\Admin\Pages\TronGrid');
        $this->submenu($this->slug.'-avatar', 'Avatar Glow', '\SSC\Admin\Pages\AvatarGlow');
        $this->submenu($this->slug.'-anim', 'Animation Studio', '\SSC\Admin\Pages\AnimationStudio');
        $this->submenu($this->slug.'-grid', 'Grid Editor', '\SSC\Admin\Pages\GridEditor');
        $this->submenu($this->slug.'-shadow', 'Shadow Editor', '\SSC\Admin\Pages\ShadowEditor');
        $this->submenu($this->slug.'-gradient', 'Gradient Editor', '\SSC\Admin\Pages\GradientEditor');
        $this->submenu($this->slug.'-scope', 'Scope Builder', '\SSC\Admin\Pages\ScopeBuilder');
        $this->submenu($this->slug.'-preset', 'Preset Designer', '\SSC\Admin\Pages\PresetDesigner');
        $this->submenu($this->slug.'-import', 'Import / Export', '\SSC\Admin\Pages\ImportExport');
        $this->submenu($this->slug.'-css-viewer', 'Visualiseur CSS', '\SSC\Admin\Pages\CssViewer');
        $this->submenu($this->slug.'-debug-center', 'Debug Center', '\SSC\Admin\Pages\DebugCenter');
    }

    private function submenu(string $slug, string $label, string $fqcn): void {
        add_submenu_page($this->slug, $label, $label, $this->cap, $slug, function () use ($fqcn, $label, $slug): void {
            ob_start();
            try {
                if (class_exists($fqcn)) { (new $fqcn())->render(); }
                else { echo '<div class="notice notice-error"><p>Erreur : La classe pour le module <strong>' . esc_html($label) . '</strong> est introuvable.</p></div>'; }
            } catch (\Throwable $e) {
                if (class_exists('\SSC\Infra\Logger')) { \SSC\Infra\Logger::add('render_error', ['module' => $label, 'error' => $e->getMessage()]); }
                echo '<div class="notice notice-error"><p><strong>Supersede CSS:</strong> Le module <em>' . esc_html($label) . '</em> a échoué.</p></div>';
                if (defined('WP_DEBUG') && WP_DEBUG) { echo '<pre>' . esc_html($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>'; }
            }
            $page_content = ob_get_clean();
            if (class_exists('\SSC\Admin\Layout')) { \SSC\Admin\Layout::render($page_content, $slug); } else { echo $page_content; }
        });
    }

    public function renderDashboard(): void {
        ob_start();
        if (class_exists('\SSC\Admin\Pages\Dashboard')) { (new \SSC\Admin\Pages\Dashboard())->render(); }
        $page_content = ob_get_clean();
        if (class_exists('\SSC\Admin\Layout')) { \SSC\Admin\Layout::render($page_content, $this->slug); } else { echo $page_content; }
    }

    public function assets($hook): void {
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        if (strpos($page, $this->slug) !== 0) return;

        // Activation de l'uploader média sur les pages concernées
        if ($page === $this->slug.'-avatar' || $page === $this->slug.'-effects') {
            wp_enqueue_media();
        }

        wp_enqueue_style('ssc-ux', SSC_PLUGIN_URL . 'assets/css/ux.css', [], SSC_VERSION);
        wp_enqueue_style('ssc-admin', SSC_PLUGIN_URL . 'assets/css/admin.css', [], SSC_VERSION);
        wp_enqueue_script('ssc-ux', SSC_PLUGIN_URL . 'assets/js/ux.js', ['jquery'], SSC_VERSION, true);

        // CodeMirror
        wp_enqueue_style('ssc-codemirror-style', SSC_PLUGIN_URL . 'assets/codemirror/lib/codemirror.css', [], SSC_VERSION);
        wp_enqueue_script('ssc-codemirror', SSC_PLUGIN_URL . 'assets/codemirror/lib/codemirror.js', [], SSC_VERSION, true);
        wp_enqueue_script('ssc-codemirror-css', SSC_PLUGIN_URL . 'assets/codemirror/mode/css/css.js', ['ssc-codemirror'], SSC_VERSION, true);

        // SortableJS for Drag & Drop
        if ($page === $this->slug.'-shadow') {
            wp_enqueue_script('ssc-sortable', SSC_PLUGIN_URL . 'assets/js/Sortable.min.js', [], null, true);
        }

        // Tous les scripts des modules sont maintenant chargés
        $scripts = [ 
            'utilities', 'preset-designer', 'shadow-editor', 'gradient-editor', 'debug-center', 
            'scope-builder', 'import-export', 'effects-avatar', 'grid-editor', 'tron-grid', 
            'visual-effects', 'animation-studio', 'tokens', 'page-layout-builder',
            'filter-editor', 'clip-path-editor', 'typography-editor'
        ];
        
        foreach ($scripts as $handle) {
            $path = 'assets/js/' . $handle . '.js';
            if (is_file(SSC_PLUGIN_DIR . $path)) {
                wp_enqueue_script('ssc-'.$handle, SSC_PLUGIN_URL.$path, ['jquery'], SSC_VERSION, true);
            }
        }

        wp_localize_script('ssc-ux', 'SSC', [
            'pluginUrl' => SSC_PLUGIN_URL,
            'rest' => [
                'root'  => esc_url_raw(rest_url('ssc/v1/')),
                'nonce' => wp_create_nonce('wp_rest')
            ]
        ]);
    }
}