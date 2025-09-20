<?php declare(strict_types=1);

use SSC\Infra\Routes;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        public function __construct(
            private string $code = '',
            private string $message = '',
            private array $data = []
        ) {
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        public function get_error_data(): array
        {
            return $this->data;
        }
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        /** @var array<string, mixed> */
        private array $params;

        /** @var array<string, string> */
        private array $headers;

        /**
         * @param array<string, mixed> $params
         * @param array<string, string> $headers
         */
        public function __construct(array $params = [], array $headers = [])
        {
            $this->params = $params;
            $this->headers = [];

            foreach ($headers as $key => $value) {
                $this->headers[strtolower($key)] = $value;
            }
        }

        public function get_param(string $key)
        {
            return $this->params[$key] ?? null;
        }

        public function get_header(string $key): string
        {
            $lowerKey = strtolower($key);

            return $this->headers[$lowerKey] ?? '';
        }
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return $value;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce(string $nonce, string $action): bool
    {
        return $nonce === 'valid-nonce' && $action === 'wp_rest';
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability): bool
    {
        global $current_user_can_manage_options;

        if ($capability !== 'manage_options') {
            return false;
        }

        return $current_user_can_manage_options;
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        return $text;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, $value)
    {
        return $value;
    }
}

require_once __DIR__ . '/../../src/Infra/Routes.php';

$routesReflection = new ReflectionClass(Routes::class);
$routes = $routesReflection->newInstanceWithoutConstructor();

global $current_user_can_manage_options;
$current_user_can_manage_options = true;

$validNonceRequest = new WP_REST_Request(['_wpnonce' => 'valid-nonce']);
$result = $routes->authorizeRequest($validNonceRequest);

if ($result !== true) {
    fwrite(STDERR, 'Expected request with a valid nonce to be authorized.' . PHP_EOL);
    exit(1);
}

$browserRequest = new WP_REST_Request();
$result = $routes->authorizeRequest($browserRequest);

if (!$result instanceof WP_Error || $result->get_error_message() !== 'Invalid nonce.') {
    fwrite(STDERR, 'Expected browser-style request without nonce to be rejected.' . PHP_EOL);
    exit(1);
}

$authenticatedRequest = new WP_REST_Request([], ['Authorization' => 'Basic Zm9vOmJhcg==']);
$result = $routes->authorizeRequest($authenticatedRequest);

if ($result !== true) {
    fwrite(STDERR, 'Expected authenticated header request without nonce to be accepted.' . PHP_EOL);
    exit(1);
}

$current_user_can_manage_options = false;
$result = $routes->authorizeRequest($authenticatedRequest);

if (!$result instanceof WP_Error || $result->get_error_message() !== 'You are not allowed to access this endpoint.') {
    fwrite(STDERR, 'Expected capability check to run for authenticated requests.' . PHP_EOL);
    exit(1);
}
