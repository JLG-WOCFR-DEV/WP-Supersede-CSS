<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;

if (!defined('ABSPATH')) {
    exit;
}

final class Dashboard extends AbstractPage
{
    public function render(): void
    {
        $this->render_view('dashboard', [
            'quick_links' => [
                'utilities'    => admin_url('admin.php?page=supersede-css-jlg-utilities'),
                'tokens'       => admin_url('admin.php?page=supersede-css-jlg-tokens'),
                'avatar'       => admin_url('admin.php?page=supersede-css-jlg-avatar'),
                'debug_center' => admin_url('admin.php?page=supersede-css-jlg-debug-center'),
            ],
        ]);
    }
}
