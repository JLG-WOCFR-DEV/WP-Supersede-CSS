<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Infra\Activity\EventRecorder;
use SSC\Support\PresetLibrary;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class PresetCatalogController extends BaseController
{
    public function registerRoutes(): void
    {
        register_rest_route('ssc/v1', '/presets/catalog', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'getCatalog'],
            ],
        ]);
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function getCatalog(WP_REST_Request $request)
    {
        $format = strtolower((string) $request->get_param('format'));
        if ($format === '') {
            $format = 'json';
        }

        $entries = PresetLibrary::getCatalogEntries();

        switch ($format) {
            case 'json':
                $payload = [
                    'format' => 'catalog',
                    'generated_at' => gmdate('c'),
                    'version' => defined('SSC_VERSION') ? constant('SSC_VERSION') : 'dev',
                    'count' => count($entries),
                    'presets' => $entries,
                ];

                $response = new WP_REST_Response($payload, 200);
                break;

            case 'css':
                $css = PresetLibrary::renderCatalogStylesheet($entries);
                $response = new WP_REST_Response($css, 200);
                $response->set_headers([
                    'Content-Type' => 'text/css; charset=utf-8',
                    'Content-Disposition' => 'attachment; filename="ssc-preset-catalog.css"',
                ]);
                break;

            default:
                return new WP_Error(
                    'ssc_invalid_catalog_format',
                    __('Unsupported catalog export format.', 'supersede-css-jlg'),
                    ['status' => 400]
                );
        }

        EventRecorder::record('preset.catalog.generated', [
            'entity_type' => 'preset_catalog',
            'entity_id' => $format,
            'details' => [
                'format' => $format,
                'count' => count($entries),
            ],
        ]);

        return $response;
    }
}
