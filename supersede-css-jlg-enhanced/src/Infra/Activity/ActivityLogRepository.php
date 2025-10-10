<?php declare(strict_types=1);

namespace SSC\Infra\Activity;

if (!defined('ABSPATH')) {
    exit;
}

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use WP_User;
use wpdb;

final class ActivityLogRepository
{
    private wpdb $wpdb;

    public function __construct(?wpdb $wpdb = null)
    {
        $wpdb = $wpdb ?? $GLOBALS['wpdb'] ?? null;

        if (!$wpdb instanceof wpdb) {
            throw new \RuntimeException('wpdb instance not available');
        }

        $this->wpdb = $wpdb;
    }

    /**
     * @param array{event?: string, entity_type?: string, entity_id?: string, window?: string} $filters
     * @return array{entries: array<int, array<string, mixed>>, pagination: array{total: int, total_pages: int, page: int}}
     */
    public function fetch(int $perPage, int $page, array $filters = []): array
    {
        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);

        [$whereSql, $whereParams] = $this->buildWhere($filters);

        $table = EventRecorder::getTableName($this->wpdb);
        $offset = ($page - 1) * $perPage;

        $entriesSql = sprintf(
            'SELECT id, event, entity_type, entity_id, details, created_at, created_by
            FROM %1$s %2$s
            ORDER BY created_at DESC, id DESC
            LIMIT %%d OFFSET %%d',
            $table,
            $whereSql
        );

        $entriesParams = array_merge($whereParams, [$perPage, $offset]);
        $entriesQuery = $this->wpdb->prepare($entriesSql, ...$entriesParams);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $entriesQuery !== false ? $this->wpdb->get_results($entriesQuery, ARRAY_A) : [];

        $entries = array_map([$this, 'normalizeRow'], $rows);

        $countSql = sprintf('SELECT COUNT(*) FROM %1$s %2$s', $table, $whereSql);
        $countQuery = $whereParams === []
            ? $countSql
            : $this->wpdb->prepare($countSql, ...$whereParams);

        $total = (int) ($whereParams === []
            ? $this->wpdb->get_var($countSql)
            : $this->wpdb->get_var($countQuery));
        $totalPages = (int) ceil($total / $perPage);

        return [
            'entries' => $entries,
            'pagination' => [
                'total' => $total,
                'total_pages' => $totalPages > 0 ? $totalPages : 1,
                'page' => $page,
            ],
        ];
    }

    /**
     * @param array{event?: string, entity_type?: string, entity_id?: string, window?: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public function all(array $filters = []): array
    {
        [$whereSql, $whereParams] = $this->buildWhere($filters);
        $table = EventRecorder::getTableName($this->wpdb);

        $sql = sprintf(
            'SELECT id, event, entity_type, entity_id, details, created_at, created_by
            FROM %1$s %2$s
            ORDER BY created_at DESC, id DESC',
            $table,
            $whereSql
        );

        $query = $whereParams === [] ? $sql : $this->wpdb->prepare($sql, ...$whereParams);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $query !== false ? $this->wpdb->get_results($query, ARRAY_A) : [];

        return array_map([$this, 'normalizeRow'], $rows);
    }

    /**
     * @param array{event?: string, entity_type?: string, entity_id?: string, window?: string} $filters
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function buildWhere(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (isset($filters['event']) && $filters['event'] !== '') {
            $clauses[] = 'event = %s';
            $params[] = $filters['event'];
        }

        if (isset($filters['entity_type']) && $filters['entity_type'] !== '') {
            $clauses[] = 'entity_type = %s';
            $params[] = $filters['entity_type'];
        }

        if (isset($filters['entity_id']) && $filters['entity_id'] !== '') {
            $clauses[] = 'entity_id = %s';
            $params[] = $filters['entity_id'];
        }

        if (isset($filters['window']) && $filters['window'] !== '') {
            $threshold = $this->computeWindowThreshold($filters['window']);
            if ($threshold !== null) {
                $clauses[] = 'created_at >= %s';
                $params[] = $threshold;
            }
        }

        $sql = $clauses !== [] ? 'WHERE ' . implode(' AND ', $clauses) : '';

        return [$sql, $params];
    }

    private function computeWindowThreshold(string $window): ?string
    {
        $window = strtolower($window);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $interval = null;

        switch ($window) {
            case '24h':
                $interval = new DateInterval('PT24H');
                break;
            case '7d':
                $interval = new DateInterval('P7D');
                break;
            case '30d':
                $interval = new DateInterval('P30D');
                break;
            default:
                return null;
        }

        $threshold = $now->sub($interval);

        return $threshold->format('Y-m-d H:i:s');
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $details = [];
        if (isset($row['details'])) {
            $decoded = json_decode((string) $row['details'], true);
            if (is_array($decoded)) {
                $details = $decoded;
            }
        }

        $user = null;
        if (isset($row['created_by']) && is_numeric($row['created_by'])) {
            $user = get_userdata((int) $row['created_by']);
        }

        $row['details'] = $details;
        $row['created_by'] = $user instanceof WP_User
            ? [
                'id' => $user->ID,
                'name' => $user->display_name,
            ]
            : null;

        return $row;
    }
}
