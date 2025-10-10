<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Infra\Activity\EventRecorder;
use SSC\Infra\Capabilities\CapabilityManager;
use SSC\Support\TokenRegistry;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ExportsController extends BaseController
{
    public function registerRoutes(): void
    {
        register_rest_route('ssc/v1', '/exports', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeExport'],
                'callback' => [$this, 'export'],
            ],
        ]);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function export(WP_REST_Request $request)
    {
        $format = strtolower((string) $request->get_param('format'));
        if ($format === '') {
            $format = 'style-dictionary';
        }

        $scope = strtolower((string) $request->get_param('scope'));
        if ($scope === '') {
            $scope = 'ready';
        }

        $tokens = $this->filterTokens(TokenRegistry::getRegistry(), $scope);

        switch ($format) {
            case 'style-dictionary':
                $payload = $this->toStyleDictionary($tokens);
                $response = new WP_REST_Response($payload, 200);
                break;

            case 'json':
                $response = new WP_REST_Response([
                    'format' => 'json',
                    'generated_at' => gmdate('c'),
                    'tokens' => $tokens,
                ], 200);
                break;

            case 'android':
                $xml = $this->toAndroidXml($tokens);
                $response = new WP_REST_Response($xml, 200);
                $response->set_headers([
                    'Content-Type' => 'application/xml; charset=utf-8',
                    'Content-Disposition' => 'attachment; filename="ssc-design-tokens-android.xml"',
                ]);
                break;

            case 'ios':
                $iosPayload = $this->toIosJson($tokens);
                $response = new WP_REST_Response($iosPayload, 200);
                break;

            default:
                return new WP_Error(
                    'ssc_invalid_export_format',
                    __('Unsupported export format.', 'supersede-css-jlg'),
                    ['status' => 400]
                );
        }

        EventRecorder::record('export.generated', [
            'entity_type' => 'export',
            'entity_id' => $format . '|' . $scope,
            'details' => [
                'format' => $format,
                'scope' => $scope,
                'count' => count($tokens),
            ],
        ]);

        return $response;
    }

    /**
     * @return bool|WP_Error
     */
    public function authorizeExport(WP_REST_Request $request)
    {
        $authorized = parent::authorizeRequest($request);
        if ($authorized !== true) {
            return $authorized;
        }

        $capability = CapabilityManager::getExportCapability();
        if (!current_user_can($capability)) {
            return new WP_Error(
                'rest_forbidden',
                __('You are not allowed to export tokens.', 'supersede-css-jlg'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @return array<int, array<string, mixed>>
     */
    private function filterTokens(array $tokens, string $scope): array
    {
        $scope = $scope !== '' ? $scope : 'ready';

        if ($scope === 'all') {
            return $tokens;
        }

        return array_values(array_filter($tokens, static function (array $token) use ($scope): bool {
            $status = isset($token['status']) ? strtolower((string) $token['status']) : 'draft';

            if ($scope === 'ready') {
                return $status === 'ready';
            }

            if ($scope === 'deprecated') {
                return $status === 'deprecated';
            }

            return true;
        }));
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @return array<string, mixed>
     */
    private function toStyleDictionary(array $tokens): array
    {
        $result = [];

        foreach ($tokens as $token) {
            $type = isset($token['type']) ? (string) $token['type'] : 'unknown';
            $group = $result[$type] ?? [];

            $name = isset($token['name']) ? (string) $token['name'] : '';
            $key = preg_replace('/^-+/', '', $name) ?? $name;
            $key = str_replace('-', '_', $key);

            $group[$key] = [
                'value' => $token['value'],
                'context' => $token['context'],
                'status' => $token['status'] ?? 'draft',
                'description' => $token['description'] ?? '',
                'version' => $token['version'] ?? '',
            ];

            if (!empty($token['linked_components'])) {
                $group[$key]['linked_components'] = $token['linked_components'];
            }

            $result[$type] = $group;
        }

        return [
            'format' => 'style-dictionary',
            'generated_at' => gmdate('c'),
            'tokens' => $result,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     */
    private function toAndroidXml(array $tokens): string
    {
        $lines = [
            '<?xml version="1.0" encoding="utf-8"?>',
            '<resources>',
        ];

        foreach ($tokens as $token) {
            $name = isset($token['name']) ? (string) $token['name'] : '';
            $slug = preg_replace('/[^a-z0-9_]+/i', '_', preg_replace('/^-+/', '', $name) ?? $name) ?? $name;
            $slug = trim($slug, '_');

            if ($slug === '') {
                $slug = 'token';
            }

            if (!preg_match('/^[a-z]/i', $slug)) {
                $slug = 'a_' . $slug;
            }

            $value = (string) ($token['value'] ?? '');
            $type = (string) ($token['type'] ?? 'text');

            $escapedSlug = htmlspecialchars($slug, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $escapedValue = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');

            if ($type === 'color') {
                $lines[] = sprintf('    <color name="%s">%s</color>', $escapedSlug, $escapedValue);
                continue;
            }

            if (in_array($type, ['spacing', 'dimension', 'number'], true)) {
                $lines[] = sprintf('    <dimen name="%s">%s</dimen>', $escapedSlug, $escapedValue);
            }
        }

        $lines[] = '</resources>';

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @return array<string, mixed>
     */
    private function toIosJson(array $tokens): array
    {
        $payload = [
            'format' => 'ios',
            'generated_at' => gmdate('c'),
            'colors' => [],
            'dimensions' => [],
            'typography' => [],
        ];

        foreach ($tokens as $token) {
            $name = isset($token['name']) ? (string) $token['name'] : '';
            $key = preg_replace('/^-+/', '', $name) ?? $name;
            $key = str_replace('-', '_', $key);

            $entry = [
                'value' => $token['value'],
                'context' => $token['context'],
                'status' => $token['status'] ?? 'draft',
            ];

            switch ($token['type'] ?? 'text') {
                case 'color':
                    $payload['colors'][$key] = $entry;
                    break;
                case 'spacing':
                case 'dimension':
                case 'number':
                    $payload['dimensions'][$key] = $entry;
                    break;
                default:
                    $payload['typography'][$key] = $entry;
                    break;
            }
        }

        return $payload;
    }
}
