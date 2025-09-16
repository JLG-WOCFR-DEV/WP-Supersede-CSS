<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;

if (!defined('ABSPATH')) {
    exit;
}

class ShadowEditor extends AbstractPage
{
    public function render(): void
    {
        $this->render_view('shadow-editor');
    }
}
