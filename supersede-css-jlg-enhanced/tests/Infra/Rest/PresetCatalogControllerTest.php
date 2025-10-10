<?php declare(strict_types=1);

use SSC\Infra\Rest\PresetCatalogController;

final class PresetCatalogControllerTest extends WP_UnitTestCase
{
    private PresetCatalogController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new PresetCatalogController();
        delete_option('ssc_presets');
    }

    public function test_get_catalog_returns_payload(): void
    {
        $request = new WP_REST_Request('GET', '/ssc/v1/presets/catalog');
        $response = $this->controller->getCatalog($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $this->assertSame('catalog', $data['format']);
        $this->assertArrayHasKey('presets', $data);
        $this->assertNotEmpty($data['presets']);

        $first = $data['presets'][0];
        $this->assertArrayHasKey('css', $first);
        $this->assertArrayHasKey('meta', $first);
    }

    public function test_get_catalog_css_format_streams_stylesheet(): void
    {
        $request = new WP_REST_Request('GET', '/ssc/v1/presets/catalog');
        $request->set_param('format', 'css');

        $response = $this->controller->getCatalog($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $headers = $response->get_headers();
        $this->assertSame('text/css; charset=utf-8', $headers['Content-Type']);
        $this->assertStringContainsString(':root', $response->get_data());
    }

    public function test_invalid_format_returns_error(): void
    {
        $request = new WP_REST_Request('GET', '/ssc/v1/presets/catalog');
        $request->set_param('format', 'zip');

        $response = $this->controller->getCatalog($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertSame(400, $response->get_error_data()['status']);
    }
}
