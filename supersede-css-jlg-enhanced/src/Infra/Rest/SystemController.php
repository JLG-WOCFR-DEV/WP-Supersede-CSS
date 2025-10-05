<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Infra\Routes;
use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;
use Throwable;
use WP_REST_Response;

final class SystemController extends BaseController
{
    public function registerRoutes(): void
    {
        register_rest_route('ssc/v1', '/health', [
            'methods' => 'GET',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'healthCheck'],
        ]);
    }

    public function healthCheck(): WP_REST_Response
    {
        $assets_to_check = [
            'css/admin.css', 'css/ux.css', 'js/ux.js', 'js/utilities.js',
            'codemirror/lib/codemirror.js', 'codemirror/lib/codemirror.css'
        ];
        $asset_status = [];
        foreach ($assets_to_check as $asset) {
            $asset_status[$asset] = is_file(SSC_PLUGIN_DIR . 'assets/' . $asset) ? 'OK' : 'Manquant';
        }

        $data = [
            'plugin_version' => defined('SSC_VERSION') ? SSC_VERSION : 'N/A',
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'rest_api_status' => 'OK',
            'asset_files_exist' => $asset_status,
            'plugin_integrity' => [
                'classes' => [
                    TokenRegistry::class => $this->checkClassAvailability(TokenRegistry::class, 'getRegistry'),
                    CssSanitizer::class => $this->checkClassAvailability(CssSanitizer::class, 'sanitize'),
                    Routes::class => $this->checkClassAvailability(Routes::class, 'register'),
                ],
                'functions' => [
                    'ssc_get_cached_css' => $this->checkFunctionAvailability('ssc_get_cached_css'),
                    'ssc_invalidate_css_cache' => $this->checkFunctionAvailability('ssc_invalidate_css_cache'),
                ],
                'token_registry' => [
                    'status' => $this->describeTokenRegistryStatus(),
                ],
            ]
        ];

        return new WP_REST_Response($data, 200);
    }

    private function checkClassAvailability(string $class, ?string $method = null): string
    {
        if (!class_exists($class)) {
            return 'Non trouvé';
        }

        if ($method !== null && !method_exists($class, $method)) {
            return 'Erreur: méthode manquante';
        }

        return 'OK';
    }

    private function checkFunctionAvailability(string $function): string
    {
        return function_exists($function) ? 'OK' : 'Non trouvé';
    }

    private function describeTokenRegistryStatus(): string
    {
        try {
            $registry = TokenRegistry::getRegistry();

            if (!is_array($registry)) {
                return 'Erreur: structure inattendue';
            }

            return sprintf('OK (%d tokens)', count($registry));
        } catch (Throwable $exception) {
            return 'Erreur: ' . $exception->getMessage();
        }
    }
}
