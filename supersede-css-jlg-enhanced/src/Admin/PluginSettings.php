<?php declare(strict_types=1);

namespace SSC\Admin;

if (!defined('ABSPATH')) {
    exit;
}

final class PluginSettings
{
    public const OPTION_GROUP = 'ssc_settings_group';
    public const OPTION_NAME = 'ssc_settings';
    public const PAGE_SLUG = 'ssc_settings';

    public static function register(): void
    {
        add_action('admin_init', [self::class, 'registerSettings']);
        add_filter('option_page_capability_' . self::OPTION_GROUP, [self::class, 'capability']);
    }

    public static function capability(): string
    {
        return function_exists('ssc_get_required_capability')
            ? ssc_get_required_capability()
            : 'manage_options';
    }

    public static function defaults(): array
    {
        return [
            'inject_editor_css' => true,
        ];
    }

    /**
     * @param mixed $value
     * @return array{inject_editor_css: bool}
     */
    public static function sanitize($value): array
    {
        if (!is_array($value)) {
            return self::defaults();
        }

        return [
            'inject_editor_css' => !empty($value['inject_editor_css']),
        ];
    }

    /**
     * @return array{inject_editor_css: bool}
     */
    public static function get(): array
    {
        $defaults = self::defaults();
        $stored = [];

        if (function_exists('get_option')) {
            $option = get_option(self::OPTION_NAME, null);
            if (is_array($option)) {
                $stored = $option;
            }
        }

        if ($stored === []) {
            return $defaults;
        }

        return [
            'inject_editor_css' => array_key_exists('inject_editor_css', $stored)
                ? (bool) $stored['inject_editor_css']
                : $defaults['inject_editor_css'],
        ];
    }

    public static function injectEditorCssEnabled(): bool
    {
        return self::get()['inject_editor_css'];
    }

    public static function registerSettings(): void
    {
        register_setting(self::OPTION_GROUP, self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize'],
            'default' => self::defaults(),
            'show_in_rest' => false,
        ]);

        add_settings_section(
            'ssc_editor_section',
            __('Canevas de l’éditeur', 'supersede-css-jlg'),
            [self::class, 'renderEditorSection'],
            self::PAGE_SLUG
        );

        add_settings_field(
            'inject_editor_css',
            __('CSS dans Gutenberg', 'supersede-css-jlg'),
            [self::class, 'renderInjectEditorCssField'],
            self::PAGE_SLUG,
            'ssc_editor_section',
            [
                'label_for' => 'ssc_inject_editor_css',
            ]
        );
    }

    public static function renderEditorSection(): void
    {
        echo '<p class="description">' . esc_html__('Ces options passent par l’API Réglages de WordPress (options.php).', 'supersede-css-jlg') . '</p>';
    }

    public static function renderInjectEditorCssField(): void
    {
        $enabled = self::injectEditorCssEnabled();

        printf(
            '<label for="ssc_inject_editor_css"><input type="checkbox" id="ssc_inject_editor_css" name="%1$s[inject_editor_css]" value="1" %2$s /> %3$s</label>',
            esc_attr(self::OPTION_NAME),
            checked($enabled, true, false),
            esc_html__('Appliquer le CSS Supersede dans le canevas de l’éditeur de blocs (iframe WordPress 7.1).', 'supersede-css-jlg')
        );
        echo '<p class="description">' . esc_html__('Décochez pour ne plus injecter le CSS généré dans l’aperçu Gutenberg. Le site public n’est pas concerné.', 'supersede-css-jlg') . '</p>';
    }

    public static function renderForm(): void
    {
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                self::OPTION_NAME,
                'ssc_settings_updated',
                __('Réglages enregistrés.', 'supersede-css-jlg'),
                'success'
            );
        }

        settings_errors(self::OPTION_NAME);

        echo '<p class="description">' . esc_html__('Ces options passent par l’API Réglages de WordPress (options.php).', 'supersede-css-jlg') . '</p>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::OPTION_GROUP);
        echo '<table class="form-table" role="presentation">';
        do_settings_fields(self::PAGE_SLUG, 'ssc_editor_section');
        echo '</table>';
        submit_button();
        echo '</form>';
    }
}
