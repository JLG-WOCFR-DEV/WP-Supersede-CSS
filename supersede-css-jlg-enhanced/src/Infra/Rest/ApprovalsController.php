<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Infra\Approvals\TokenApprovalStore;
use SSC\Infra\Capabilities\CapabilityManager;
use SSC\Infra\Activity\EventRecorder;
use SSC\Support\TokenRegistry;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ApprovalsController extends BaseController
{
    private TokenApprovalStore $store;

    public function __construct(?TokenApprovalStore $store = null)
    {
        $this->store = $store ?? new TokenApprovalStore();
    }

    public function registerRoutes(): void
    {
        register_rest_route('ssc/v1', '/approvals', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'getApprovals'],
            ],
            [
                'methods' => 'POST',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'requestApproval'],
            ],
        ]);

        register_rest_route('ssc/v1', '/approvals/(?P<id>[a-f0-9]{8,32})', [
            [
                'methods' => 'POST',
                'permission_callback' => [$this, 'authorizeDecision'],
                'callback' => [$this, 'decide'],
            ],
        ]);
    }

    public function getApprovals(WP_REST_Request $request): WP_REST_Response
    {
        $statusFilter = strtolower((string) $request->get_param('status'));
        $entries = $this->store->all();

        if ($statusFilter === '') {
            $statusFilter = 'pending';
        }

        if ($statusFilter !== 'all') {
            $entries = array_values(array_filter(
                $entries,
                static fn(array $entry): bool => strtolower((string) $entry['status']) === $statusFilter
            ));
        }

        $entries = array_map([$this, 'enrichEntry'], $entries);

        return new WP_REST_Response([
            'approvals' => $entries,
        ], 200);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function requestApproval(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }

        $tokenData = isset($payload['token']) && is_array($payload['token']) ? $payload['token'] : [];
        $name = isset($tokenData['name']) ? (string) $tokenData['name'] : '';
        $context = isset($tokenData['context']) ? (string) $tokenData['context'] : TokenRegistry::getDefaultContext();

        if ($name === '') {
            return new WP_Error(
                'ssc_invalid_token_name',
                __('Token name is required.', 'supersede-css-jlg'),
                ['status' => 400]
            );
        }

        $token = $this->findToken($name, $context);
        if ($token === null) {
            return new WP_Error(
                'ssc_token_not_found',
                __('The requested token was not found.', 'supersede-css-jlg'),
                ['status' => 404]
            );
        }

        $comment = isset($payload['comment']) ? sanitize_textarea_field((string) $payload['comment']) : '';
        $priority = isset($payload['priority']) ? TokenApprovalStore::sanitizePriority((string) $payload['priority']) : TokenApprovalStore::sanitizePriority('');
        $userId = get_current_user_id();
        $userId = is_int($userId) ? $userId : 0;

        TokenRegistry::updateTokenMetadata($token['name'], $token['context'], [
            'status' => 'draft',
        ]);

        $entry = $this->store->upsert($token['name'], $token['context'], $userId, $comment, $priority);
        $entry = $this->enrichEntry($entry);

        EventRecorder::record('token.approval_requested', [
            'entity_type' => 'token',
            'entity_id' => $this->buildEntityId($token['name'], $token['context']),
            'details' => [
                'name' => $token['name'],
                'context' => $token['context'],
                'comment' => $comment,
                'priority' => $entry['priority'],
            ],
        ]);

        return new WP_REST_Response([
            'approval' => $entry,
        ], 201);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function decide(WP_REST_Request $request)
    {
        $decision = strtolower((string) $request->get_param('decision'));
        if ($decision === '') {
            $payload = $request->get_json_params();
            if (is_array($payload) && isset($payload['decision'])) {
                $decision = strtolower((string) $payload['decision']);
            }
        }

        if (!in_array($decision, ['approve', 'changes_requested'], true)) {
            return new WP_Error(
                'ssc_invalid_decision',
                __('Decision must be "approve" or "changes_requested".', 'supersede-css-jlg'),
                ['status' => 400]
            );
        }

        $comment = (string) $request->get_param('comment');
        if ($comment === '') {
            $payload = $request->get_json_params();
            if (is_array($payload) && isset($payload['comment'])) {
                $comment = (string) $payload['comment'];
            }
        }
        $comment = sanitize_textarea_field($comment);

        if ($decision === 'changes_requested' && $comment === '') {
            return new WP_Error(
                'ssc_missing_decision_comment',
                __('A comment is required when requesting changes.', 'supersede-css-jlg'),
                ['status' => 400]
            );
        }

        $id = (string) $request->get_param('id');
        if ($id === '') {
            return new WP_Error(
                'ssc_invalid_approval_id',
                __('Approval identifier is missing.', 'supersede-css-jlg'),
                ['status' => 400]
            );
        }

        $requestEntry = $this->store->find($id);
        if ($requestEntry === null) {
            return new WP_Error(
                'ssc_approval_not_found',
                __('Approval request not found.', 'supersede-css-jlg'),
                ['status' => 404]
            );
        }

        $token = $this->findToken($requestEntry['token']['name'], $requestEntry['token']['context']);
        if ($token === null) {
            return new WP_Error(
                'ssc_token_not_found',
                __('The requested token was not found.', 'supersede-css-jlg'),
                ['status' => 404]
            );
        }

        $userId = get_current_user_id();
        $userId = is_int($userId) ? $userId : 0;

        if ($decision === 'approve') {
            $this->applyTokenStatus($token, 'ready');
            $updated = $this->store->complete($id, 'approved', $userId, $comment);

            EventRecorder::record('token.approved', [
                'entity_type' => 'token',
                'entity_id' => $this->buildEntityId($token['name'], $token['context']),
                'details' => [
                    'name' => $token['name'],
                    'context' => $token['context'],
                    'comment' => $comment,
                    'priority' => TokenApprovalStore::sanitizePriority($requestEntry['priority'] ?? ''),
                ],
            ]);
        } else {
            $this->applyTokenStatus($token, 'draft');
            $updated = $this->store->complete($id, 'changes_requested', $userId, $comment);

            EventRecorder::record('token.approval_changes_requested', [
                'entity_type' => 'token',
                'entity_id' => $this->buildEntityId($token['name'], $token['context']),
                'details' => [
                    'name' => $token['name'],
                    'context' => $token['context'],
                    'comment' => $comment,
                    'priority' => TokenApprovalStore::sanitizePriority($requestEntry['priority'] ?? ''),
                ],
            ]);
        }

        if ($updated === null) {
            return new WP_Error(
                'ssc_approval_not_found',
                __('Approval request not found.', 'supersede-css-jlg'),
                ['status' => 404]
            );
        }

        return new WP_REST_Response([
            'approval' => $this->enrichEntry($updated),
        ], 200);
    }

    /**
     * @return bool|WP_Error
     */
    public function authorizeDecision(WP_REST_Request $request)
    {
        $authorized = parent::authorizeRequest($request);
        if ($authorized !== true) {
            return $authorized;
        }

        $capability = CapabilityManager::getApprovalCapability();
        if (!current_user_can($capability)) {
            return new WP_Error(
                'rest_forbidden',
                __('You are not allowed to review approvals.', 'supersede-css-jlg'),
                ['status' => 403]
            );
        }

        return true;
    }

    private function applyTokenStatus(array $token, string $status): void
    {
        TokenRegistry::updateTokenMetadata($token['name'], $token['context'], [
            'status' => $status,
        ]);
    }

    private function findToken(string $name, string $context): ?array
    {
        $registry = TokenRegistry::getRegistry();
        $nameKey = strtolower(trim($name));
        $contextKey = strtolower(trim($context));

        foreach ($registry as $token) {
            if (strtolower($token['name']) === $nameKey && strtolower($token['context']) === $contextKey) {
                return $token;
            }
        }

        return null;
    }

    private function buildEntityId(string $name, string $context): string
    {
        return strtolower($context . '|' . $name);
    }

    /**
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private function enrichEntry(array $entry): array
    {
        $requestedBy = isset($entry['requested_by']) ? (int) $entry['requested_by'] : 0;
        $entry['requested_by_user'] = $this->normalizeUser($requestedBy);

        if (isset($entry['decision']) && is_array($entry['decision'])) {
            $userId = isset($entry['decision']['user_id']) ? (int) $entry['decision']['user_id'] : 0;
            $entry['decision_user'] = $this->normalizeUser($userId);
        } else {
            $entry['decision_user'] = null;
        }

        $entry['priority'] = isset($entry['priority'])
            ? TokenApprovalStore::sanitizePriority((string) $entry['priority'])
            : TokenApprovalStore::sanitizePriority('');

        return $entry;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeUser(int $userId): array
    {
        if ($userId <= 0) {
            return [
                'id' => 0,
                'name' => __('Compte inconnu', 'supersede-css-jlg'),
                'avatar' => '',
            ];
        }

        $user = get_userdata($userId);
        if ($user instanceof \WP_User) {
            return [
                'id' => $user->ID,
                'name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID, ['size' => 64]),
            ];
        }

        return [
            'id' => $userId,
            'name' => __('Compte inconnu', 'supersede-css-jlg'),
            'avatar' => '',
        ];
    }
}
