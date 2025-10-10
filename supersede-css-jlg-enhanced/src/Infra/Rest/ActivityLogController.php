<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Infra\Activity\ActivityLogRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ActivityLogController extends BaseController
{
    private ActivityLogRepository $repository;

    public function __construct(?ActivityLogRepository $repository = null)
    {
        $this->repository = $repository ?? new ActivityLogRepository();
    }

    public function registerRoutes(): void
    {
        register_rest_route('ssc/v1', '/activity-log', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'getLog'],
            ],
        ]);

        register_rest_route('ssc/v1', '/activity-log/export', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'export'],
            ],
        ]);
    }

    public function getLog(WP_REST_Request $request): WP_REST_Response
    {
        $perPage = (int) $request->get_param('per_page');
        if ($perPage <= 0) {
            $perPage = 20;
        }

        $page = (int) $request->get_param('page');
        if ($page <= 0) {
            $page = 1;
        }

        $filters = $this->extractFilters($request);
        $result = $this->repository->fetch($perPage, $page, $filters);

        return new WP_REST_Response([
            'entries' => $result['entries'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
        ], 200);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function export(WP_REST_Request $request)
    {
        $format = strtolower((string) $request->get_param('format'));
        if ($format === '') {
            $format = 'json';
        }

        $filters = $this->extractFilters($request);
        $entries = $this->repository->all($filters);

        switch ($format) {
            case 'json':
                return new WP_REST_Response([
                    'exported_at' => gmdate('c'),
                    'entries' => $entries,
                    'filters' => $filters,
                ], 200);

            case 'csv':
                $csv = $this->buildCsv($entries);
                $response = new WP_REST_Response($csv, 200);
                $response->set_headers([
                    'Content-Type' => 'text/csv; charset=utf-8',
                    'Content-Disposition' => 'attachment; filename="ssc-activity-log.csv"',
                ]);

                return $response;
        }

        return new WP_Error(
            'ssc_invalid_export_format',
            __('Unsupported export format.', 'supersede-css-jlg'),
            ['status' => 400]
        );
    }

    /**
     * @return array<string, string>
     */
    private function extractFilters(WP_REST_Request $request): array
    {
        $filters = [];

        $event = (string) $request->get_param('event');
        if ($event !== '') {
            $filters['event'] = preg_replace('/[^a-z0-9._-]/i', '', $event) ?? $event;
        }

        $entityType = (string) $request->get_param('entity_type');
        if ($entityType !== '') {
            $filters['entity_type'] = preg_replace('/[^a-z0-9._-]/i', '', $entityType) ?? $entityType;
        }

        $entityId = (string) $request->get_param('entity_id');
        if ($entityId !== '') {
            $filters['entity_id'] = sanitize_text_field($entityId);
        }

        $window = (string) $request->get_param('window');
        if ($window !== '') {
            $filters['window'] = strtolower($window);
        }

        return $filters;
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    private function buildCsv(array $entries): string
    {
        $handle = fopen('php://temp', 'w+');

        if ($handle === false) {
            return '';
        }

        fputcsv($handle, ['id', 'event', 'entity_type', 'entity_id', 'created_at', 'created_by', 'details']);

        foreach ($entries as $entry) {
            $author = $entry['created_by'] ?? null;
            $authorName = '';

            if (is_array($author) && isset($author['name'])) {
                $authorName = (string) $author['name'];
            }

            fputcsv($handle, [
                $entry['id'] ?? '',
                $entry['event'] ?? '',
                $entry['entity_type'] ?? '',
                $entry['entity_id'] ?? '',
                $entry['created_at'] ?? '',
                $authorName,
                isset($entry['details']) ? wp_json_encode($entry['details']) : '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return is_string($csv) ? $csv : '';
    }
}
