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
     * @var array<string, array{deadline_hours: int, escalations: array<int, array{level: int, after_hours: int}>}>
     */
    private const SLA_RULES = [
        'low' => [
            'deadline_hours' => 120,
            'escalations' => [
                ['level' => 1, 'after_hours' => 96],
            ],
        ],
        'normal' => [
            'deadline_hours' => 48,
            'escalations' => [
                ['level' => 1, 'after_hours' => 36],
                ['level' => 2, 'after_hours' => 44],
            ],
        ],
        'high' => [
            'deadline_hours' => 12,
            'escalations' => [
                ['level' => 1, 'after_hours' => 6],
                ['level' => 2, 'after_hours' => 10],
            ],
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

        return array_values(array_filter(array_map([$this, 'normalizeEntry'], $stored)));
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

        $requestedAt = gmdate('c');

        $entry = [
            'id' => $identifier,
            'token' => [
                'name' => $normalizedName,
                'context' => $normalizedContext,
            ],
            'requested_by' => $userId,
            'requested_at' => $requestedAt,
            'status' => 'pending',
            'comment' => $comment,
            'decision' => null,
            'priority' => self::sanitizePriority($priority),
        ];

        $entry['sla'] = $this->buildSlaMetadata($entry['priority'], $requestedAt);

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
            $decidedAt = gmdate('c');

            $requests[$index]['decision'] = [
                'user_id' => $userId,
                'comment' => $comment,
                'decided_at' => $decidedAt,
            ];

            $requests[$index]['sla'] = $this->markSlaCompleted(
                isset($requests[$index]['sla']) && is_array($requests[$index]['sla'])
                    ? $requests[$index]['sla']
                    : $this->buildSlaMetadata(
                        isset($requests[$index]['priority']) ? (string) $requests[$index]['priority'] : self::getDefaultPriority(),
                        isset($requests[$index]['requested_at']) ? (string) $requests[$index]['requested_at'] : gmdate('c')
                    ),
                $decidedAt
            );

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

    /**
     * @return array<string, array{deadline_hours: int, escalations: array<int, array{level: int, after_hours: int}>}>
     */
    public static function getSlaRules(): array
    {
        return self::SLA_RULES;
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

    /**
     * @param array<string, mixed> $item
     */
    private function normalizeEntry($item): ?array
    {
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

        $priority = isset($item['priority'])
            ? self::sanitizePriority((string) $item['priority'])
            : self::getDefaultPriority();

        $requestedAt = isset($item['requested_at']) && is_string($item['requested_at'])
            ? $this->sanitizeIsoDate($item['requested_at'])
            : gmdate('c');

        $normalized = [
            'id' => $id,
            'token' => [
                'name' => $name,
                'context' => $context,
            ],
            'status' => isset($item['status']) ? (string) $item['status'] : 'pending',
            'requested_at' => $requestedAt,
            'requested_by' => isset($item['requested_by']) ? (int) $item['requested_by'] : 0,
            'comment' => isset($item['comment']) ? sanitize_textarea_field((string) $item['comment']) : '',
            'decision' => isset($item['decision']) && is_array($item['decision']) ? $this->normalizeDecision($item['decision']) : null,
            'priority' => $priority,
        ];

        $normalized['sla'] = $this->buildSlaMetadata(
            $priority,
            $requestedAt,
            isset($item['sla']) && is_array($item['sla']) ? $item['sla'] : []
        );

        return $normalized;
    }

    /**
     * @param array<string, mixed> $decision
     * @return array<string, mixed>
     */
    private function normalizeDecision(array $decision): array
    {
        $decidedAt = isset($decision['decided_at']) && is_string($decision['decided_at'])
            ? $this->sanitizeIsoDate($decision['decided_at'])
            : gmdate('c');

        return [
            'user_id' => isset($decision['user_id']) ? (int) $decision['user_id'] : 0,
            'comment' => isset($decision['comment']) ? sanitize_textarea_field((string) $decision['comment']) : '',
            'decided_at' => $decidedAt,
        ];
    }

    /**
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function buildSlaMetadata(string $priority, string $requestedAt, array $existing = []): array
    {
        $priority = self::sanitizePriority($priority);
        $requestedTimestamp = $this->parseIsoDate($requestedAt) ?? time();

        $rules = self::SLA_RULES[$priority] ?? null;
        $deadlineHours = is_array($rules) && isset($rules['deadline_hours'])
            ? (int) $rules['deadline_hours']
            : 48;

        $deadlineTimestamp = $requestedTimestamp + ($deadlineHours * HOUR_IN_SECONDS);

        $escalations = [];
        $currentLevel = 0;

        if (is_array($rules) && isset($rules['escalations']) && is_array($rules['escalations'])) {
            foreach ($rules['escalations'] as $definition) {
                if (!is_array($definition)) {
                    continue;
                }

                $level = isset($definition['level']) ? (int) $definition['level'] : 0;
                $afterHours = isset($definition['after_hours']) ? (int) $definition['after_hours'] : 0;

                if ($level <= 0 || $afterHours <= 0) {
                    continue;
                }

                $triggerTimestamp = $requestedTimestamp + ($afterHours * HOUR_IN_SECONDS);
                $existingEscalation = $this->findExistingEscalation($existing, $level);
                $notifiedAt = $existingEscalation !== null && isset($existingEscalation['notified_at'])
                    ? $this->sanitizeIsoDate((string) $existingEscalation['notified_at'])
                    : '';

                if ($notifiedAt !== '') {
                    $currentLevel = max($currentLevel, $level);
                }

                $escalations[] = [
                    'level' => $level,
                    'trigger_at' => gmdate('c', $triggerTimestamp),
                    'notified_at' => $notifiedAt,
                ];
            }
        }

        $breachedAt = isset($existing['breached_at']) ? $this->sanitizeIsoDate((string) $existing['breached_at']) : '';
        $completedAt = isset($existing['completed_at']) ? $this->sanitizeIsoDate((string) $existing['completed_at']) : '';

        if ($breachedAt !== '') {
            $deadlineTimestamp = min($deadlineTimestamp, $this->parseIsoDate($breachedAt) ?? $deadlineTimestamp);
        }

        if ($completedAt !== '') {
            $deadlineTimestamp = min($deadlineTimestamp, $this->parseIsoDate($completedAt) ?? $deadlineTimestamp);
        }

        $metadata = [
            'priority' => $priority,
            'deadline_at' => gmdate('c', $deadlineTimestamp),
            'breached_at' => $breachedAt,
            'completed_at' => $completedAt,
            'current_level' => $currentLevel,
            'escalations' => $escalations,
        ];

        if (isset($existing['last_notified_at'])) {
            $metadata['last_notified_at'] = $this->sanitizeIsoDate((string) $existing['last_notified_at']);
        }

        return $metadata;
    }

    private function markSlaCompleted(array $sla, string $decidedAt): array
    {
        $sla['completed_at'] = $this->sanitizeIsoDate($decidedAt);

        return $sla;
    }

    /**
     * @param array<string, mixed> $existing
     * @return array<string, mixed>|null
     */
    private function findExistingEscalation(array $existing, int $level): ?array
    {
        if (!isset($existing['escalations']) || !is_array($existing['escalations'])) {
            return null;
        }

        foreach ($existing['escalations'] as $escalation) {
            if (!is_array($escalation)) {
                continue;
            }

            if ((int) ($escalation['level'] ?? 0) === $level) {
                return $escalation;
            }
        }

        return null;
    }

    private function sanitizeIsoDate(string $value): string
    {
        $timestamp = $this->parseIsoDate($value);

        if ($timestamp === null) {
            return gmdate('c');
        }

        return gmdate('c', $timestamp);
    }

    private function parseIsoDate(string $value): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null;
        }

        return $timestamp;
    }
}
