<?php declare(strict_types=1);

use SSC\Infra\Rest\SystemController;
use SSC\Support\TokenRegistry;

final class SystemControllerTest extends WP_UnitTestCase
{
    private SystemController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        delete_transient(SystemController::CACHE_KEY);
        $this->controller = new SystemController();
    }

    protected function tearDown(): void
    {
        delete_transient(SystemController::CACHE_KEY);
        remove_all_filters('ssc_health_check_cache_ttl');
        parent::tearDown();
    }

    public function test_health_check_reports_integrity_information(): void
    {
        $response = $this->controller->healthCheck();

        $this->assertInstanceOf(WP_REST_Response::class, $response);

        $data = $response->get_data();

        $this->assertArrayHasKey('meta', $data);
        $this->assertIsArray($data['meta']);
        $this->assertArrayHasKey('cache_hit', $data['meta']);
        $this->assertFalse($data['meta']['cache_hit']);
        $this->assertArrayHasKey('cache_ttl', $data['meta']);
        $this->assertGreaterThanOrEqual(0, $data['meta']['cache_ttl']);
        $this->assertArrayHasKey('generated_at', $data['meta']);
        $this->assertNotEmpty($data['meta']['generated_at']);

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

    public function test_health_check_uses_cached_payload_between_calls(): void
    {
        add_filter('ssc_health_check_cache_ttl', static fn () => 300);

        $first = $this->controller->healthCheck();
        $firstData = $first->get_data();

        $second = $this->controller->healthCheck();
        $secondData = $second->get_data();

        $this->assertTrue($secondData['meta']['cache_hit']);
        $this->assertSame($firstData['plugin_integrity'], $secondData['plugin_integrity']);
        $this->assertSame($firstData['asset_files_exist'], $secondData['asset_files_exist']);
        $this->assertSame($firstData['plugin_version'], $secondData['plugin_version']);
    }

    public function test_health_check_respects_zero_ttl(): void
    {
        add_filter('ssc_health_check_cache_ttl', static fn () => 0);

        $first = $this->controller->healthCheck()->get_data();
        $second = $this->controller->healthCheck()->get_data();

        $this->assertFalse($second['meta']['cache_hit']);
        $this->assertArrayHasKey('expires_timestamp', $second['meta']);
        $this->assertNull($second['meta']['expires_timestamp']);
        $this->assertGreaterThanOrEqual($first['meta']['generated_timestamp'], $second['meta']['generated_timestamp']);
    }
}
