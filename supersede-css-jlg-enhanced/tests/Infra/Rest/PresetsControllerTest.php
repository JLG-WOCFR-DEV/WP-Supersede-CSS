<?php declare(strict_types=1);

use SSC\Infra\Rest\PresetsController;
use SSC\Support\CssSanitizer;
use WP_REST_Request;
use WP_REST_Response;

final class PresetsControllerTest extends WP_UnitTestCase
{
    private PresetsController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new PresetsController();
        delete_option('ssc_presets');
        delete_option('ssc_avatar_glow_presets');
    }

    public function test_get_presets_sanitizes_output(): void
    {
        $raw = [
            ['name' => '<script>bad</script>', 'css' => 'body { color: red; }'],
        ];
        update_option('ssc_presets', $raw, false);

        $response = $this->controller->getPresets();
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertSame(CssSanitizer::sanitizePresetCollection($raw), $data);
    }

    public function test_save_presets_updates_option(): void
    {
        $payload = [
            'presets' => [
                ['name' => 'Example', 'css' => 'body { color: blue; }'],
            ],
        ];

        $request = new WP_REST_Request('POST', '/ssc/v1/presets');
        $request->set_body_params($payload);

        $response = $this->controller->savePresets($request);
        $this->assertSame(200, $response->get_status());

        $this->assertSame(
            CssSanitizer::sanitizePresetCollection($payload['presets']),
            get_option('ssc_presets')
        );
    }
}
