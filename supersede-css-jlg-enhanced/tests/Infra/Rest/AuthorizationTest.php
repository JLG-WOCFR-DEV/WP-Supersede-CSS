<?php declare(strict_types=1);

use SSC\Infra\Rest\TokensController;
use WP_REST_Request;

final class AuthorizationTest extends WP_UnitTestCase
{
    private TokensController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TokensController();
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
    }

    public function test_request_with_valid_nonce_is_authorized(): void
    {
        $nonce = wp_create_nonce('wp_rest');
        $request = new WP_REST_Request('GET', '/ssc/v1/tokens');
        $request->set_param('_wpnonce', $nonce);

        $result = $this->controller->authorizeRequest($request);

        $this->assertTrue($result);
    }

    public function test_request_with_header_authentication_is_authorized(): void
    {
        $request = new WP_REST_Request('GET', '/ssc/v1/tokens');
        $request->set_header('Authorization', 'Basic Zm9vOmJhcg==');

        $result = $this->controller->authorizeRequest($request);

        $this->assertTrue($result);
    }

    public function test_missing_nonce_and_capability_returns_error(): void
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));
        $request = new WP_REST_Request('GET', '/ssc/v1/tokens');
        $request->set_header('Authorization', 'Basic Zm9vOmJhcg==');

        $result = $this->controller->authorizeRequest($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('rest_forbidden', $result->get_error_code());
    }
}
