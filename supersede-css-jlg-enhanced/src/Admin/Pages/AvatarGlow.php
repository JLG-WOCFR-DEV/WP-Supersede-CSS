<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;

if (!defined('ABSPATH')) {
    exit;
}

class AvatarGlow extends AbstractPage
{
    public function render(): void
    {
        $this->render_view('avatar-glow', [
            'avatar_placeholder' => SSC_PLUGIN_URL . 'assets/images/placeholder-avatar.png',
        ]);
    }
}
