<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use SSC\Support\TokenRegistry;
use WP_REST_Request;
use WP_REST_Response;

final class TokensController extends BaseController
{
    public function registerRoutes(): void
    {
        register_rest_route('ssc/v1', '/tokens', [
            [
                'methods' => 'GET',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'getTokens'],
            ],
            [
                'methods' => 'POST',
                'permission_callback' => [$this, 'authorizeRequest'],
                'callback' => [$this, 'saveTokens'],
            ],
        ]);
    }

    public function getTokens(): WP_REST_Response
    {
        $registry = TokenRegistry::getRegistry();

        return new WP_REST_Response([
            'tokens' => $registry,
            'css' => TokenRegistry::tokensToCss($registry),
            'types' => TokenRegistry::getSupportedTypes(),
            'contexts' => TokenRegistry::getSupportedContexts(),
            'defaultContext' => TokenRegistry::getDefaultContext(),
        ], 200);
    }

    public function saveTokens(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params();

        if (!is_array($payload)) {
            $rawTokens = $request->get_param('tokens');
            if (is_string($rawTokens)) {
                $decoded = json_decode(wp_unslash($rawTokens), true);
                $payload = ['tokens' => $decoded];
            } elseif (is_array($rawTokens)) {
                $payload = ['tokens' => $rawTokens];
            } else {
                $payload = [];
            }
        }

        $tokens = $payload['tokens'] ?? null;

        if (!is_array($tokens)) {
            return new WP_REST_Response([
                'ok' => false,
                'message' => __('Invalid tokens payload.', 'supersede-css-jlg'),
            ], 400);
        }

        $result = TokenRegistry::saveRegistry($tokens);

        if ($result['duplicates'] !== []) {
            return new WP_REST_Response([
                'ok' => false,
                'message' => __('Some tokens use the same name. Please choose unique names before saving.', 'supersede-css-jlg'),
                'duplicates' => $result['duplicates'],
            ], 422);
        }

        $sanitized = $result['tokens'];

        return new WP_REST_Response([
            'ok' => true,
            'tokens' => $sanitized,
            'css' => TokenRegistry::tokensToCss($sanitized),
            'contexts' => TokenRegistry::getSupportedContexts(),
            'defaultContext' => TokenRegistry::getDefaultContext(),
        ], 200);
    }
}
