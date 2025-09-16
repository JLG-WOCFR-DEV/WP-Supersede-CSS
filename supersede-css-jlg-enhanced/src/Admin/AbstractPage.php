<?php declare(strict_types=1);

namespace SSC\Admin;

if (!defined('ABSPATH')) {
    exit;
}

abstract class AbstractPage
{
    /**
     * Render a view file located in the plugin's views directory.
     */
    protected function render_view(string $view, array $data = []): void
    {
        if (!defined('SSC_PLUGIN_DIR')) {
            return;
        }

        $sanitized_view = trim($view, "/\t\n\r\0\x0B");
        $sanitized_view = str_replace(['..', '\\'], '', $sanitized_view);

        $view_file = SSC_PLUGIN_DIR . 'views/' . $sanitized_view . '.php';

        if (!is_readable($view_file)) {
            return;
        }

        if (!empty($data)) {
            extract($data, EXTR_SKIP);
        }

        include $view_file;
    }
}
