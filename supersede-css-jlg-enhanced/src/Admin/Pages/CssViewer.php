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
        $active_css = get_option('ssc_active_css', self::EMPTY_MESSAGE);
        if (!is_string($active_css)) {
            $active_css = self::EMPTY_MESSAGE;
        }

        $tokens_css = get_option('ssc_tokens_css', self::EMPTY_MESSAGE);
        if (!is_string($tokens_css)) {
            $tokens_css = self::EMPTY_MESSAGE;
        }

        $this->render_view('css-viewer', [
            'active_css' => $active_css,
            'tokens_css' => $tokens_css,
        ]);
    }
}
