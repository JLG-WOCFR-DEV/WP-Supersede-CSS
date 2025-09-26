<?php declare(strict_types=1);
namespace SSC\Admin;

if (!defined('ABSPATH')) { exit; }

final class Admin
{
    private string $slug;
    private string $cap;

    public function __construct() {
        $this->slug = 'supersede-css-jlg';
        $this->cap  = 'manage_options';

        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
    }

    public function menu(): void {
        add_menu_page(
            __('Supersede CSS', 'supersede-css-jlg'),
            __('Supersede CSS', 'supersede-css-jlg'),
            $this->cap,
            $this->slug,
            [$this, 'renderDashboard'],
            'dashicons-art',
            58
        );
        
        // Tous les modules sont déclarés ici pour simplifier la maintenance.
        $modules = [
            [
                'slug'   => 'layout-builder',
                'label'  => __('Maquettage de Page', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\PageLayoutBuilder',
            ],
            [
                'slug'   => 'utilities',
                'label'  => __('Utilities', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\Utilities',
            ],
            [
                'slug'   => 'tokens',
                'label'  => __('Tokens Manager', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\Tokens',
            ],
            [
                'slug'   => 'effects',
                'label'  => __('Effets Visuels', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\VisualEffects',
            ],
            [
                'slug'   => 'filters',
                'label'  => __('Filtres & Verre', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\FilterEditor',
            ],
            [
                'slug'   => 'clip-path',
                'label'  => __('Découpe (Clip-Path)', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\ClipPathEditor',
            ],
            [
                'slug'   => 'typography',
                'label'  => __('Typographie Fluide', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\TypographyEditor',
            ],
            [
                'slug'   => 'tron',
                'label'  => __('Tron Grid', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\TronGrid',
            ],
            [
                'slug'   => 'avatar',
                'label'  => __('Avatar Glow', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\AvatarGlow',
            ],
            [
                'slug'   => 'anim',
                'label'  => __('Animation Studio', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\AnimationStudio',
            ],
            [
                'slug'   => 'grid',
                'label'  => __('Grid Editor', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\GridEditor',
            ],
            [
                'slug'   => 'shadow',
                'label'  => __('Shadow Editor', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\ShadowEditor',
            ],
            [
                'slug'   => 'gradient',
                'label'  => __('Gradient Editor', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\GradientEditor',
            ],
            [
                'slug'   => 'scope',
                'label'  => __('Scope Builder', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\ScopeBuilder',
            ],
            [
                'slug'   => 'preset',
                'label'  => __('Preset Designer', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\PresetDesigner',
            ],
            [
                'slug'   => 'import',
                'label'  => __('Import / Export', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\ImportExport',
            ],
            [
                'slug'   => 'css-viewer',
                'label'  => __('Visualiseur CSS', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\CssViewer',
            ],
            [
                'slug'   => 'debug-center',
                'label'  => __('Debug Center', 'supersede-css-jlg'),
                'class'  => '\\SSC\\Admin\\Pages\\DebugCenter',
            ],
        ];

        foreach ($modules as $module) {
            $this->submenu(
                $this->slug . '-' . $module['slug'],
                $module['label'],
                $module['class']
            );
        }
    }

    private function submenu(string $slug, string $label, string $fqcn): void {
        add_submenu_page($this->slug, $label, $label, $this->cap, $slug, function () use ($fqcn, $label, $slug): void {
            ob_start();
            try {
                if (class_exists($fqcn)) {
                    (new $fqcn())->render();
                } else {
                    $message = sprintf(
                        /* translators: %s: Supersede CSS module label. */
                        __('Error: The class for the "%s" module could not be found.', 'supersede-css-jlg'),
                        $label
                    );
                    echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
                }
            } catch (\Throwable $e) {
                if (class_exists('\SSC\Infra\Logger')) { \SSC\Infra\Logger::add('render_error', ['module' => $label, 'error' => $e->getMessage()]); }
                $error_message = sprintf(
                    /* translators: %s: Supersede CSS module label. */
                    __('Supersede CSS: The "%s" module failed to render.', 'supersede-css-jlg'),
                    $label
                );
                echo '<div class="notice notice-error"><p>' . esc_html($error_message) . '</p></div>';
                if (defined('WP_DEBUG') && WP_DEBUG) { echo '<pre>' . esc_html($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>'; }
            }
            $page_content = ob_get_clean();
            if (class_exists('\SSC\Admin\Layout')) {
                \SSC\Admin\Layout::render($page_content, $slug);
            } else {
                echo wp_kses($page_content, wp_kses_allowed_html('post'));
            }
        });
    }

    public function renderDashboard(): void {
        ob_start();
        if (class_exists('\SSC\Admin\Pages\Dashboard')) { (new \SSC\Admin\Pages\Dashboard())->render(); }
        $page_content = ob_get_clean();
        if (class_exists('\SSC\Admin\Layout')) {
            \SSC\Admin\Layout::render($page_content, $this->slug);
        } else {
            echo wp_kses($page_content, wp_kses_allowed_html('post'));
        }
    }

    /**
     * Charge les scripts et styles de l'extension uniquement sur ses écrans d'administration.
     *
     * @param string $hook Suffixe de la page actuelle fourni par WordPress (admin_enqueue_scripts),
     *                     utilisé pour ignorer les écrans n'appartenant pas à Supersede CSS.
     */
    public function assets(string $hook): void {
        if (strpos($hook, $this->slug) === false) {
            return;
        }

        $page_input = sanitize_text_field(filter_input(INPUT_GET, 'page', FILTER_UNSAFE_RAW));
        $page = is_string($page_input) ? sanitize_key($page_input) : '';
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
            wp_enqueue_script('ssc-sortable', SSC_PLUGIN_URL . 'assets/js/Sortable.min.js', [], SSC_VERSION, true);
        }

        // Scripts spécifiques aux sous-pages
        $scripts_by_page = [
            $this->slug.'-utilities'        => ['utilities'],
            $this->slug.'-preset'           => ['preset-designer'],
            $this->slug.'-shadow'           => ['shadow-editor'],
            $this->slug.'-gradient'         => ['gradient-editor'],
            $this->slug.'-debug-center'     => ['debug-center'],
            $this->slug.'-scope'            => ['scope-builder'],
            $this->slug.'-import'           => ['import-export'],
            $this->slug.'-avatar'           => ['effects-avatar'],
            $this->slug.'-grid'             => ['grid-editor'],
            $this->slug.'-tron'             => ['tron-grid'],
            $this->slug.'-effects'          => ['visual-effects'],
            $this->slug.'-anim'             => ['animation-studio'],
            $this->slug.'-tokens'           => ['tokens'],
            $this->slug.'-layout-builder'   => ['page-layout-builder'],
            $this->slug.'-filters'          => ['filter-editor'],
            $this->slug.'-clip-path'        => ['clip-path-editor'],
            $this->slug.'-typography'       => ['typography-editor'],
        ];

        if (isset($scripts_by_page[$page])) {
            foreach ($scripts_by_page[$page] as $handle) {
                $path = 'assets/js/' . $handle . '.js';
                if (is_file(SSC_PLUGIN_DIR . $path)) {
                    $dependencies = ['jquery'];
                    if ($handle === 'utilities') {
                        $dependencies[] = 'wp-i18n';
                    }

                    wp_enqueue_script('ssc-'.$handle, SSC_PLUGIN_URL.$path, $dependencies, SSC_VERSION, true);

                    if ($handle === 'utilities' && function_exists('wp_set_script_translations')) {
                        wp_set_script_translations('ssc-utilities', 'supersede-css-jlg');
                    }
                }
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

