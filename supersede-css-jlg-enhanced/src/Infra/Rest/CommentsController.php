<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Infra\Comments\CommentStore;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

final class CommentsController extends BaseController
{
    public function __construct(private readonly CommentStore $store = new CommentStore())
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route('ssc/v1', '/comments', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'index'],
            ],
            [
                'methods' => 'POST',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'create'],
            ],
        ]);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $entityType = (string) $request->get_param('entity_type');
        if ($entityType === '') {
            return new WP_REST_Response([
                'comments' => [],
            ], 200);
        }

        $entityId = $request->get_param('entity_id');
        $entityId = is_string($entityId) ? $entityId : null;

        $comments = $this->store->getComments($entityType, $entityId);

        return new WP_REST_Response([
            'comments' => $comments,
        ], 200);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function create(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload) || $payload === []) {
            $payload = $request->get_params();
        }

        $entityType = isset($payload['entity_type']) ? (string) $payload['entity_type'] : '';
        $entityId = isset($payload['entity_id']) ? (string) $payload['entity_id'] : '';
        $message = isset($payload['message']) ? (string) $payload['message'] : '';
        $mentions = isset($payload['mentions']) && is_array($payload['mentions']) ? $payload['mentions'] : [];

        if ($entityType === '' || $entityId === '') {
            return new WP_Error(
                'ssc_invalid_comment_entity',
                __('Missing entity information.', 'supersede-css-jlg'),
                ['status' => 400]
            );
        }

        if (trim($message) === '') {
            return new WP_Error(
                'ssc_invalid_comment_message',
                __('The comment message cannot be empty.', 'supersede-css-jlg'),
                ['status' => 400]
            );
        }

        try {
            $comment = $this->store->addComment($entityType, $entityId, $message, $mentions);
        } catch (\Throwable $exception) {
            if (class_exists('\\SSC\\Infra\\Logger')) {
                \SSC\Infra\Logger::add('comments_store_failure', [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'exception' => get_class($exception),
                    'error' => $exception->getMessage(),
                ]);
            }

            return new WP_Error(
                'ssc_comment_creation_failed',
                __('Unable to store the comment.', 'supersede-css-jlg'),
                [
                    'status' => 500,
                ]
            );
        }

        return new WP_REST_Response([
            'comment' => $comment,
        ], 201);
    }
}
