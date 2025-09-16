<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;

if (!defined('ABSPATH')) {
    exit;
}

class Utilities extends AbstractPage
{
    public function render(): void
    {
        $this->render_view('utilities', [
            'css_desktop' => get_option('ssc_css_desktop', ''),
            'css_tablet'  => get_option('ssc_css_tablet', ''),
            'css_mobile'  => get_option('ssc_css_mobile', ''),
            'preview_url' => get_home_url(),
        ]);
    }
}
