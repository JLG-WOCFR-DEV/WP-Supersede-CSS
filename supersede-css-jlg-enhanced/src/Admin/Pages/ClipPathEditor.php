<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;

if (!defined('ABSPATH')) {
    exit;
}

class ClipPathEditor extends AbstractPage
{
    public function render(): void
    {
        $this->render_view('clip-path-editor', [
            'preview_background' => SSC_PLUGIN_URL . 'assets/images/preview-bg.jpg',
        ]);
    }
}
