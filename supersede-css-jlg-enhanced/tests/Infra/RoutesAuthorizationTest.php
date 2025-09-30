<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
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

        /** @var array<string, mixed> */
        private array $json;

        /**
         * @param array<string, mixed> $params
         * @param array<string, string> $headers
         * @param array<string, mixed>|null $json
         */
        public function __construct(array $params = [], array $headers = [], ?array $json = null)
        {
            $this->params = $params;
            $this->headers = [];

            foreach ($headers as $key => $value) {
                $this->headers[strtolower($key)] = $value;
            }

            $this->json = $json ?? [];
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

        /**
         * @return array<string, mixed>
         */
        public function get_json_params(): array
        {
            return $this->json;
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
        unset($domain);

        return $text;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, $value)
    {
        unset($hook);

        return $value;
    }
}

global $current_user_can_manage_options;
$current_user_can_manage_options = true;

final class RoutesAuthorizationTest extends TestCase
{
    private Routes $routes;

    protected function setUp(): void
    {
        parent::setUp();

        $reflection = new \ReflectionClass(Routes::class);
        $this->routes = $reflection->newInstanceWithoutConstructor();

        global $current_user_can_manage_options;
        $current_user_can_manage_options = true;
    }

    public function testAuthorizeRequestWithValidNonce(): void
    {
        $request = new WP_REST_Request(['_wpnonce' => 'valid-nonce']);
        $this->assertTrue($this->routes->authorizeRequest($request));
    }

    public function testAuthorizeRequestWithoutNonceFromBrowserIsRejected(): void
    {
        $browserRequest = new WP_REST_Request();
        $result = $this->routes->authorizeRequest($browserRequest);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('Invalid nonce.', $result->get_error_message());
    }

    public function testAuthorizeRequestAcceptsAuthenticatedHeader(): void
    {
        $authenticatedRequest = new WP_REST_Request([], ['Authorization' => 'Basic Zm9vOmJhcg==']);
        $this->assertTrue($this->routes->authorizeRequest($authenticatedRequest));
    }

    public function testAuthorizeRequestChecksCapabilities(): void
    {
        global $current_user_can_manage_options;
        $current_user_can_manage_options = false;

        $authenticatedRequest = new WP_REST_Request([], ['Authorization' => 'Basic Zm9vOmJhcg==']);
        $result = $this->routes->authorizeRequest($authenticatedRequest);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('You are not allowed to access this endpoint.', $result->get_error_message());
    }
}
