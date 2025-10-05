<?php declare(strict_types=1);

use SSC\Infra\Rest\SystemController;
use SSC\Support\TokenRegistry;
use WP_REST_Response;

final class SystemControllerTest extends WP_UnitTestCase
{
    private SystemController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SystemController();
    }

    public function test_health_check_reports_integrity_information(): void
    {
        $response = $this->controller->healthCheck();

        $this->assertInstanceOf(WP_REST_Response::class, $response);

        $data = $response->get_data();

        $this->assertArrayHasKey('plugin_integrity', $data);
        $integrity = $data['plugin_integrity'];
        $this->assertIsArray($integrity);

        $this->assertArrayHasKey('classes', $integrity);
        $this->assertArrayHasKey('functions', $integrity);
        $this->assertArrayHasKey('token_registry', $integrity);

        $this->assertArrayHasKey(TokenRegistry::class, $integrity['classes']);
        $this->assertSame('OK', $integrity['classes'][TokenRegistry::class]);

        $this->assertArrayHasKey('ssc_get_cached_css', $integrity['functions']);
        $this->assertSame('OK', $integrity['functions']['ssc_get_cached_css']);

        $this->assertArrayHasKey('status', $integrity['token_registry']);
        $this->assertIsString($integrity['token_registry']['status']);
        $this->assertStringStartsWith('OK', $integrity['token_registry']['status']);
    }
}
