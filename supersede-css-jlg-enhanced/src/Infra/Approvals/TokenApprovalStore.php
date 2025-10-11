<?php declare(strict_types=1);

namespace SSC\Infra\Approvals;

use function __;

if (!defined('ABSPATH')) {
    exit;
}

final class TokenApprovalStore
{
    private const OPTION = 'ssc_token_approval_queue';

    /**
     * @var array<string, array{label: string, description: string, tone: string, default?: bool}>
     */
    private const SUPPORTED_PRIORITIES = [
        'low' => [
            'label' => 'Faible',
            'description' => 'Peut être traité lors du prochain cycle de publication.',
            'tone' => 'muted',
        ],
        'normal' => [
            'label' => 'Normale',
            'description' => 'Flux standard avec revue lors du prochain comité design.',
            'tone' => 'info',
            'default' => true,
        ],
        'high' => [
            'label' => 'Haute',
            'description' => 'À prioriser : impact production ou campagne imminente.',
            'tone' => 'danger',
        ],
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $stored = get_option(self::OPTION, []);

        if (!is_array($stored)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $id = isset($item['id']) ? (string) $item['id'] : '';
            $token = isset($item['token']) && is_array($item['token']) ? $item['token'] : [];
            $name = isset($token['name']) ? (string) $token['name'] : '';
            $context = isset($token['context']) ? (string) $token['context'] : '';

            if ($id === '' || $name === '' || $context === '') {
                return null;
            }

            $item['id'] = $id;
            $item['token'] = [
                'name' => $name,
                'context' => $context,
            ];

            $item['status'] = isset($item['status']) ? (string) $item['status'] : 'pending';
            $item['requested_at'] = isset($item['requested_at']) ? (string) $item['requested_at'] : gmdate('c');
            $item['requested_by'] = isset($item['requested_by']) ? (int) $item['requested_by'] : 0;
            $item['comment'] = isset($item['comment']) ? sanitize_textarea_field((string) $item['comment']) : '';
            $item['decision'] = isset($item['decision']) && is_array($item['decision']) ? $item['decision'] : null;
            $item['priority'] = isset($item['priority'])
                ? self::sanitizePriority((string) $item['priority'])
                : self::getDefaultPriority();

            return $item;
        }, $stored)));
    }

    public function save(array $requests): void
    {
        update_option(self::OPTION, array_values($requests), false);
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $request) {
            if ($request['id'] === $id) {
                return $request;
            }
        }

        return null;
    }

    public function upsert(string $name, string $context, int $userId, string $comment = '', string $priority = ''): array
    {
        $normalizedName = $this->normalizeKey($name);
        $normalizedContext = trim($context) !== '' ? $context : ':root';
        $requests = $this->all();
        $identifier = $this->buildIdentifier($normalizedName, $normalizedContext);
        $existingIndex = null;

        foreach ($requests as $index => $request) {
            if ($request['id'] === $identifier) {
                $existingIndex = $index;
                break;
            }
        }

        $entry = [
            'id' => $identifier,
            'token' => [
                'name' => $normalizedName,
                'context' => $normalizedContext,
            ],
            'requested_by' => $userId,
            'requested_at' => gmdate('c'),
            'status' => 'pending',
            'comment' => $comment,
            'decision' => null,
            'priority' => self::sanitizePriority($priority),
        ];

        if ($existingIndex !== null) {
            $requests[$existingIndex] = $entry;
        } else {
            $requests[] = $entry;
        }

        $this->save($requests);

        return $entry;
    }

    public function complete(string $id, string $status, int $userId, string $comment = ''): ?array
    {
        $requests = $this->all();

        foreach ($requests as $index => $request) {
            if ($request['id'] !== $id) {
                continue;
            }

            $requests[$index]['status'] = $status;
            $requests[$index]['decision'] = [
                'user_id' => $userId,
                'comment' => $comment,
                'decided_at' => gmdate('c'),
            ];

            $this->save($requests);

            return $requests[$index];
        }

        return null;
    }

    public function remove(string $id): void
    {
        $requests = array_values(array_filter(
            $this->all(),
            static fn(array $item): bool => $item['id'] !== $id
        ));

        $this->save($requests);
    }

    private function buildIdentifier(string $name, string $context): string
    {
        $raw = strtolower($context . '|' . $name);

        return substr(hash('sha256', $raw), 0, 32);
    }

    /**
     * @return array<int, array{value: string, label: string, description: string, tone: string, default: bool}>
     */
    public static function getSupportedPriorities(): array
    {
        $priorities = [];

        foreach (self::SUPPORTED_PRIORITIES as $value => $meta) {
            $priorities[] = [
                'value' => $value,
                'label' => __($meta['label'], 'supersede-css-jlg'),
                'description' => __($meta['description'], 'supersede-css-jlg'),
                'tone' => $meta['tone'],
                'default' => isset($meta['default']) ? (bool) $meta['default'] : false,
            ];
        }

        return $priorities;
    }

    public static function sanitizePriority(string $priority): string
    {
        $priority = strtolower(trim($priority));

        if ($priority !== '' && isset(self::SUPPORTED_PRIORITIES[$priority])) {
            return $priority;
        }

        return self::getDefaultPriority();
    }

    private static function getDefaultPriority(): string
    {
        foreach (self::SUPPORTED_PRIORITIES as $value => $meta) {
            if (isset($meta['default']) && $meta['default'] === true) {
                return $value;
            }
        }

        return 'normal';
    }

    private function normalizeKey(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return $name;
        }

        if (strpos($name, '--') !== 0) {
            $name = '--' . ltrim($name, '-');
        }

        return strtolower($name);
    }
}
