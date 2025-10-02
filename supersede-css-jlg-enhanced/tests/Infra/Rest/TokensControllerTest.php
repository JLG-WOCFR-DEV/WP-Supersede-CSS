<?php declare(strict_types=1);

use SSC\Infra\Rest\TokensController;
use SSC\Support\TokenRegistry;
use WP_REST_Request;
use WP_REST_Response;

final class TokensControllerTest extends WP_UnitTestCase
{
    private TokensController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TokensController();
        TokenRegistry::saveRegistry([]);
    }

    public function test_get_tokens_returns_registry_and_css(): void
    {
        $registry = [
            [
                'name' => '--primary-color',
                'value' => '#123456',
                'type' => 'color',
            ],
        ];

        TokenRegistry::saveRegistry($registry);

        $response = $this->controller->getTokens();

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('tokens', $data);
        $this->assertArrayHasKey('css', $data);
        $this->assertArrayHasKey('types', $data);
    }

    public function test_save_tokens_rejects_duplicates(): void
    {
        $request = new WP_REST_Request('POST', '/ssc/v1/tokens');
        $request->set_body_params([
            'tokens' => [
                ['name' => '--duplicate', 'value' => '10px', 'type' => 'size'],
                ['name' => '--duplicate', 'value' => '20px', 'type' => 'size'],
            ],
        ]);

        $response = $this->controller->saveTokens($request);
        $this->assertSame(422, $response->get_status());
    }
}
