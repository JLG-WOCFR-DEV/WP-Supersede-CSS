<?php declare(strict_types=1);

namespace SSC\Infra\Rest;

use WP_REST_Response;

final class LogsController extends BaseController
{
    public function registerRoutes(): void
    {
        register_rest_route('ssc/v1', '/clear-log', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'clearLog'],
        ]);
    }

    public function clearLog(): WP_REST_Response
    {
        if (class_exists('\\SSC\\Infra\\Logger')) {
            \SSC\Infra\Logger::clear();
        }

        return new WP_REST_Response(['ok' => true], 200);
    }
}
