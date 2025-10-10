<?php declare(strict_types=1);

namespace SSC\Infra\Comments;

use DateTimeImmutable;
use DateTimeZone;
use SSC\Infra\Activity\EventRecorder;
use WP_User;

if (!defined('ABSPATH')) {
    exit;
}

final class CommentStore
{
    private const OPTION_KEY = 'ssc_entity_comments';
    private const MAX_COMMENTS_PER_ENTITY = 200;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getComments(string $entityType, ?string $entityId = null): array
    {
        $entityType = $this->sanitizeEntityType($entityType);
        $entityId = $entityId !== null ? sanitize_text_field($entityId) : null;

        $all = $this->read();
        $typed = $all[$entityType] ?? [];

        if ($entityId !== null) {
            $comments = $typed[$entityId] ?? [];
        } else {
            $comments = [];
            foreach ($typed as $entries) {
                if (!is_array($entries)) {
                    continue;
                }
                foreach ($entries as $entry) {
                    $comments[] = $entry;
                }
            }
        }

        return array_map([$this, 'hydrateComment'], $comments);
    }

    /**
     * @param array<int, int> $mentions
     * @return array<string, mixed>
     */
    public function addComment(string $entityType, string $entityId, string $message, array $mentions = [], ?int $userId = null): array
    {
        $entityType = $this->sanitizeEntityType($entityType);
        $entityId = sanitize_text_field($entityId);
        $message = trim(wp_kses_post($message));

        if ($message === '') {
            throw new \InvalidArgumentException('Empty comment message.');
        }

        if ($userId === null || $userId <= 0) {
            $userId = get_current_user_id();
        }

        $userId = (int) $userId;
        $mentions = $this->sanitizeMentions($mentions);

        $comments = $this->read();
        $entityComments = $comments[$entityType][$entityId] ?? [];

        $commentId = $this->generateId($entityType, $entityId);
        $timezone = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC');
        $timestamp = (new DateTimeImmutable('now', $timezone))->format('c');

        $newComment = [
            'id' => $commentId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'message' => $message,
            'mentions' => $mentions,
            'created_by' => $userId,
            'created_at' => $timestamp,
        ];

        $entityComments[] = $newComment;
        if (count($entityComments) > self::MAX_COMMENTS_PER_ENTITY) {
            $entityComments = array_slice($entityComments, -self::MAX_COMMENTS_PER_ENTITY);
        }

        if (!isset($comments[$entityType])) {
            $comments[$entityType] = [];
        }
        $comments[$entityType][$entityId] = $entityComments;

        $this->persist($comments);

        EventRecorder::record('comment.created', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => [
                'comment_id' => $commentId,
                'mentions' => $mentions,
            ],
        ]);

        return $this->hydrateComment($newComment);
    }

    /**
     * @return array<string, array<string, array<int, array<string, mixed>>>>
     */
    private function read(): array
    {
        $stored = get_option(self::OPTION_KEY, []);
        if (!is_array($stored)) {
            return [];
        }

        foreach ($stored as $type => $entities) {
            if (!is_array($entities)) {
                unset($stored[$type]);
                continue;
            }

            foreach ($entities as $entityId => $entries) {
                if (!is_array($entries)) {
                    unset($stored[$type][$entityId]);
                }
            }
        }

        return $stored;
    }

    /**
     * @param array<string, array<string, array<int, array<string, mixed>>>> $data
     */
    private function persist(array $data): void
    {
        update_option(self::OPTION_KEY, $data, false);
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private function hydrateComment(array $raw): array
    {
        $author = null;
        $userId = isset($raw['created_by']) ? (int) $raw['created_by'] : 0;

        if ($userId > 0) {
            $user = get_userdata($userId);
            if ($user instanceof WP_User) {
                $author = [
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'avatar' => get_avatar_url($user->ID, ['size' => 32]),
                ];
            }
        }

        $mentions = [];
        if (isset($raw['mentions']) && is_array($raw['mentions'])) {
            foreach ($raw['mentions'] as $mentionId) {
                $mentionId = (int) $mentionId;
                if ($mentionId <= 0) {
                    continue;
                }
                $user = get_userdata($mentionId);
                if ($user instanceof WP_User) {
                    $mentions[] = [
                        'id' => $user->ID,
                        'name' => $user->display_name,
                        'avatar' => get_avatar_url($user->ID, ['size' => 24]),
                    ];
                }
            }
        }

        return [
            'id' => isset($raw['id']) ? (string) $raw['id'] : '',
            'entity_type' => isset($raw['entity_type']) ? (string) $raw['entity_type'] : '',
            'entity_id' => isset($raw['entity_id']) ? (string) $raw['entity_id'] : '',
            'message' => isset($raw['message']) ? (string) $raw['message'] : '',
            'created_at' => isset($raw['created_at']) ? (string) $raw['created_at'] : '',
            'created_by' => $author,
            'mentions' => $mentions,
        ];
    }

    /**
     * @param array<int, int|string> $mentions
     * @return array<int, int>
     */
    private function sanitizeMentions(array $mentions): array
    {
        $sanitized = [];
        foreach ($mentions as $mention) {
            $id = is_int($mention) ? $mention : (int) $mention;
            if ($id <= 0) {
                continue;
            }
            $user = get_userdata($id);
            if ($user instanceof WP_User) {
                $sanitized[] = $user->ID;
            }
        }

        return array_values(array_unique($sanitized));
    }

    private function generateId(string $entityType, string $entityId): string
    {
        $seed = $entityType . '|' . $entityId . '|' . microtime(true) . '|' . wp_generate_uuid4();

        return substr(md5($seed), 0, 12);
    }

    private function sanitizeEntityType(string $entityType): string
    {
        $clean = preg_replace('/[^a-z0-9._-]/i', '', $entityType) ?? $entityType;

        return $clean !== '' ? strtolower($clean) : 'general';
    }
}
