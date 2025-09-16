<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;

if (!defined('ABSPATH')) {
    exit;
}

class PresetDesigner extends AbstractPage
{
    public function render(): void
    {
        $this->render_view('preset-designer');
    }
}
