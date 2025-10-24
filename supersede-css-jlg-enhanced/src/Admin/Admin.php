<?php declare(strict_types=1);
namespace SSC\Admin;

if (!defined('ABSPATH')) { exit; }

final class Admin
{
    private string $slug;
    private string $cap;

    public function __construct() {
        $this->slug = ModuleRegistry::BASE_SLUG;
        $this->cap  = \ssc_get_required_capability();

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
        
        foreach (ModuleRegistry::submenuModules() as $module) {
            $this->registerSubmenu($module);
        }
    }

    /**
     * @param array<string, mixed> $module
     */
    private function registerSubmenu(array $module): void {
        $label = $module['label'];
        $menu_label = $module['menu_label'] ?? $label;
        $page_slug = $module['page_slug'];
        $fqcn = $module['class'];

        add_submenu_page($this->slug, $menu_label, $menu_label, $this->cap, $page_slug, function () use ($fqcn, $label, $page_slug): void {
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
                \SSC\Admin\Layout::render($page_content, $page_slug);
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

        $module = ModuleRegistry::findByPageSlug($page);
        if ($module === null) {
            $module = ModuleRegistry::modules()['dashboard'] ?? null;
        }
        if ($module === null) {
            return;
        }

        // Activation de l'uploader média sur les pages concernées
        if (!empty($module['enqueue_media'])) {
            wp_enqueue_media();
        }

        wp_enqueue_style('ssc-foundation', SSC_PLUGIN_URL . 'assets/css/foundation.css', [], SSC_VERSION);
        wp_enqueue_style('ssc-ux', SSC_PLUGIN_URL . 'assets/css/ux.css', ['ssc-foundation'], SSC_VERSION);
        wp_enqueue_style('ssc-admin', SSC_PLUGIN_URL . 'assets/css/admin.css', ['ssc-foundation'], SSC_VERSION);
        wp_enqueue_script('ssc-error-guards', SSC_PLUGIN_URL . 'assets/js/error-guards.js', [], SSC_VERSION, false);
        wp_enqueue_script('ssc-ux', SSC_PLUGIN_URL . 'assets/js/ux.js', ['jquery'], SSC_VERSION, true);

        // Register heavy assets so modules can request them when needed.
        wp_register_style('ssc-codemirror-style', SSC_PLUGIN_URL . 'assets/codemirror/lib/codemirror.css', [], SSC_VERSION);
        wp_register_script('ssc-codemirror', SSC_PLUGIN_URL . 'assets/codemirror/lib/codemirror.js', [], SSC_VERSION, true);
        wp_register_script('ssc-codemirror-css', SSC_PLUGIN_URL . 'assets/codemirror/mode/css/css.js', ['ssc-codemirror'], SSC_VERSION, true);

        $requires_codemirror = !empty($module['requires_codemirror']);

        if (!$requires_codemirror) {
            foreach ($module['assets']['scripts'] ?? [] as $script) {
                if (!empty($script['requires_codemirror'])) {
                    $requires_codemirror = true;
                    break;
                }
            }
        }

        if ($requires_codemirror) {
            wp_enqueue_style('ssc-codemirror-style');
            wp_enqueue_script('ssc-codemirror');
            wp_enqueue_script('ssc-codemirror-css');
        }

        foreach ($module['assets']['scripts'] ?? [] as $script) {
            if (empty($script['path'])) {
                continue;
            }

            $deps = $script['deps'] ?? ['jquery'];
            $in_footer = $script['in_footer'] ?? true;
            $handle = $script['handle'] ?? 'ssc-' . sanitize_key(str_replace(['.min', '.'], ['-', '-'], basename($script['path'], '.js')));
            $full_path = SSC_PLUGIN_DIR . $script['path'];

            if (!is_file($full_path)) {
                continue;
            }

            wp_enqueue_script(
                $handle,
                SSC_PLUGIN_URL . $script['path'],
                $deps,
                SSC_VERSION,
                $in_footer
            );

            if (!empty($script['translations']) && function_exists('wp_set_script_translations')) {
                wp_set_script_translations(
                    $handle,
                    'supersede-css-jlg',
                    SSC_PLUGIN_DIR . 'languages'
                );
            }
        }

        foreach ($module['assets']['styles'] ?? [] as $style) {
            if (empty($style['path'])) {
                continue;
            }

            $deps = $style['deps'] ?? [];
            $handle = $style['handle'] ?? 'ssc-' . sanitize_key(str_replace(['.min', '.'], ['-', '-'], basename($style['path'], '.css'))) . '-style';
            $full_path = SSC_PLUGIN_DIR . $style['path'];

            if (!is_file($full_path)) {
                continue;
            }

            wp_enqueue_style(
                $handle,
                SSC_PLUGIN_URL . $style['path'],
                $deps,
                SSC_VERSION
            );
        }

        wp_localize_script('ssc-ux', 'SSC', [
            'pluginUrl' => SSC_PLUGIN_URL,
            'rest' => [
                'root'  => esc_url_raw(rest_url('ssc/v1/')),
                'nonce' => wp_create_nonce('wp_rest')
            ],
            'i18n' => [
                'commandPaletteTitle' => esc_attr__('Supersede CSS command palette', 'supersede-css-jlg'),
                'commandPaletteSearchPlaceholder' => esc_attr__('Navigate or run an action…', 'supersede-css-jlg'),
                'commandPaletteSearchLabel' => esc_html__('Command palette search', 'supersede-css-jlg'),
                'commandPaletteResultsAnnouncement' => esc_html__('%d résultat(s) disponibles.', 'supersede-css-jlg'),
                'commandPaletteEmptyState' => esc_html__('Aucun résultat ne correspond à votre recherche.', 'supersede-css-jlg'),
                'mobileMenuShowLabel' => esc_attr__('Afficher le menu', 'supersede-css-jlg'),
                'mobileMenuHideLabel' => esc_attr__('Masquer le menu', 'supersede-css-jlg'),
                'mobileMenuToggleSrLabel' => esc_html__('Menu', 'supersede-css-jlg'),
                /* translators: Toast displayed after successfully copying content to the clipboard. */
                'clipboardSuccess' => esc_html__('Texte copié !', 'supersede-css-jlg'),
                /* translators: Toast displayed when copying to the clipboard fails. */
                'clipboardError' => esc_html__('Impossible de copier le texte.', 'supersede-css-jlg'),
                /* translators: Label announced by assistive tech for the toast notifications history log. */
                'toastHistoryLabel' => esc_html__('Historique des notifications Supersede CSS', 'supersede-css-jlg'),
                /* translators: Description for each toast entry in the assistive history log. 1: timestamp, 2: toast message. */
                'toastHistoryEntry' => esc_html__('Notification enregistrée à %1$s : %2$s', 'supersede-css-jlg'),
                /* translators: Accessible label for the button that dismisses toast notifications. */
                'toastDismissLabel' => esc_html__('Dismiss notification', 'supersede-css-jlg'),
            ],
        ]);
    }
}

