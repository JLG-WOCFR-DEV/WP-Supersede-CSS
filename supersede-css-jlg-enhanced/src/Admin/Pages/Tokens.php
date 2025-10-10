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
        $collaborators = [];

        if (function_exists('get_users')) {
            $users = get_users([
                'orderby' => 'display_name',
                'order' => 'ASC',
                'fields' => ['ID', 'display_name'],
                'number' => 100,
            ]);

            foreach ($users as $user) {
                if (!isset($user->ID)) {
                    continue;
                }

                $collaborators[] = [
                    'id' => (int) $user->ID,
                    'name' => $user->display_name,
                    'avatar' => get_avatar_url($user->ID, ['size' => 32]),
                ];
            }
        }

        $requiredCapability = function_exists('ssc_get_required_capability') ? ssc_get_required_capability() : 'manage_options';
        $canManageTokens = current_user_can($requiredCapability);

        $this->render_view('tokens', [
            'tokens_registry' => $registry,
            'tokens_css' => TokenRegistry::tokensToCss($registry),
            'token_types' => TokenRegistry::getSupportedTypes(),
            'token_contexts' => TokenRegistry::getSupportedContexts(),
            'default_context' => TokenRegistry::getDefaultContext(),
            'token_statuses' => TokenRegistry::getSupportedStatuses(),
            'token_approvals' => $approvalStore->all(),
            'collaborators' => $collaborators,
            'can_manage_tokens' => $canManageTokens,
        ]);
    }
}
