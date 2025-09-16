<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;

if (!defined('ABSPATH')) {
    exit;
}

class Tokens extends AbstractPage
{
    private const DEFAULT_CSS = ":root {\n  --couleur-principale: #4f46e5;\n  --radius-moyen: 8px;\n}";

    public function render(): void
    {
        $this->render_view('tokens', [
            'tokens_css' => get_option('ssc_tokens_css', self::DEFAULT_CSS),
        ]);
    }
}
