<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;

if (!defined('ABSPATH')) {
    exit;
}

class DebugCenter extends AbstractPage
{
    public function render(): void
    {
        $log_entries = class_exists('\\SSC\\Infra\\Logger') ? \SSC\Infra\Logger::all() : [];

        $this->render_view('debug-center', [
            'system_info' => [
                'plugin_version'    => defined('SSC_VERSION') ? SSC_VERSION : 'N/A',
                'wordpress_version' => get_bloginfo('version'),
                'php_version'       => phpversion(),
            ],
            'log_entries' => $log_entries,
        ]);
    }
}
