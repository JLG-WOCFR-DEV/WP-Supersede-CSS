<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;
use SSC\Support\TokenRegistry;

if (!defined('ABSPATH')) {
    exit;
}

class Tokens extends AbstractPage
{
    public function render(): void
    {
        $registry = TokenRegistry::getRegistry();

        $this->render_view('tokens', [
            'tokens_registry' => $registry,
            'tokens_css' => TokenRegistry::tokensToCss($registry),
            'token_types' => TokenRegistry::getSupportedTypes(),
        ]);
    }
}
