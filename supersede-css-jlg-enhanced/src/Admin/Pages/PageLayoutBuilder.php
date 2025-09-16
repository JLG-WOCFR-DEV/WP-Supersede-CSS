<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;

if (!defined('ABSPATH')) {
    exit;
}

class PageLayoutBuilder extends AbstractPage
{
    public function render(): void
    {
        $this->render_view('page-layout-builder', [
            'tokens_page_url' => admin_url('admin.php?page=supersede-css-jlg-tokens'),
        ]);
    }
}
