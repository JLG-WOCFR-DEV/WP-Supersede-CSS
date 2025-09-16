<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;

if (!defined('ABSPATH')) {
    exit;
}

class CssViewer extends AbstractPage
{
    private const EMPTY_MESSAGE = '/* Cette option est vide. */';

    public function render(): void
    {
        $this->render_view('css-viewer', [
            'active_css' => get_option('ssc_active_css', self::EMPTY_MESSAGE),
            'tokens_css' => get_option('ssc_tokens_css', self::EMPTY_MESSAGE),
        ]);
    }
}
