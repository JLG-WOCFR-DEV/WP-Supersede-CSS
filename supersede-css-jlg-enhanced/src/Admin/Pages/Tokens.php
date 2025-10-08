<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;
use SSC\Infra\Approvals\TokenApprovalStore;
use SSC\Support\TokenRegistry;

if (!defined('ABSPATH')) {
    exit;
}

class Tokens extends AbstractPage
{
    public function render(): void
    {
        $registry = TokenRegistry::getRegistry();
        $approvalStore = new TokenApprovalStore();

        $this->render_view('tokens', [
            'tokens_registry' => $registry,
            'tokens_css' => TokenRegistry::tokensToCss($registry),
            'token_types' => TokenRegistry::getSupportedTypes(),
            'token_contexts' => TokenRegistry::getSupportedContexts(),
            'default_context' => TokenRegistry::getDefaultContext(),
            'token_statuses' => TokenRegistry::getSupportedStatuses(),
            'token_approvals' => $approvalStore->all(),
        ]);
    }
}
