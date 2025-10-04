<?php declare(strict_types=1);

use SSC\Infra\Import\Sanitizer;
use SSC\Infra\Rest\ImportExportController;
use SSC\Support\CssSanitizer;
use WP_REST_Request;
use WP_REST_Response;

final class ImportExportControllerTest extends WP_UnitTestCase
{
    private ImportExportController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ImportExportController(new Sanitizer());
        delete_option('ssc_active_css');
        delete_option('ssc_css_desktop');
        delete_option('ssc_tokens_css');
        delete_option('ssc_tokens_registry');
        delete_option('ssc_settings');
    }

    public function test_export_css_combines_tokens_and_active(): void
    {
        update_option('ssc_tokens_css', '/* tokens */');
        update_option('ssc_active_css', 'body { color: blue; }');

        $response = $this->controller->exportCss();
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $this->assertSame(
            ['css' => CssSanitizer::sanitize("/* tokens */\nbody { color: blue; }")],
            $response->get_data()
        );
    }

    public function test_import_config_applies_options(): void
    {
        $payload = [
            'modules' => ['css'],
            'options' => [
                'ssc_active_css' => 'body { color: green; }',
                'ssc_css_desktop' => 'body { color: red; }',
            ],
        ];

        $request = new WP_REST_Request('POST', '/ssc/v1/import-config');
        $request->set_body(wp_json_encode($payload));

        $response = $this->controller->importConfig($request);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['ok']);
        $this->assertContains('ssc_active_css', $data['applied']);
        $this->assertSame(
            CssSanitizer::sanitize('body { color: green; }'),
            get_option('ssc_active_css')
        );
    }

    public function test_import_config_applies_tokens_registry(): void
    {
        $payload = [
            'modules' => ['tokens'],
            'options' => [
                'ssc_tokens_registry' => [
                    [
                        'name' => 'Spacing Large',
                        'value' => ' 24px ',
                        'type' => 'spacing',
                        'description' => 'Spacing value',
                        'group' => 'Layout',
                    ],
                ],
            ],
        ];

        $request = new WP_REST_Request('POST', '/ssc/v1/import-config');
        $request->set_body(wp_json_encode($payload));

        $response = $this->controller->importConfig($request);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['ok']);
        $this->assertContains('ssc_tokens_registry', $data['applied']);
        $this->assertSame([], $data['skipped']);

        $stored = get_option('ssc_tokens_registry');
        $this->assertIsArray($stored);
        $this->assertSame([
            [
                'name' => '--Spacing-Large',
                'value' => '24px',
                'type' => 'spacing',
                'description' => 'Spacing value',
                'group' => 'Layout',
            ],
        ], $stored);
    }

    public function test_import_config_skips_duplicate_tokens(): void
    {
        $payload = [
            'modules' => ['tokens'],
            'options' => [
                'ssc_tokens_css' => ":root {\n    --duplicate: 10px;\n}\n:root {\n    --duplicate: 12px;\n}",
            ],
        ];

        $request = new WP_REST_Request('POST', '/ssc/v1/import-config');
        $request->set_body(wp_json_encode($payload));

        $response = $this->controller->importConfig($request);
        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['ok']);
        $this->assertSame([], $data['applied']);
        $this->assertNotEmpty($data['skipped']);
    }

    public function test_import_config_handles_nested_settings_arrays(): void
    {
        $nestedSettings = [
            'appearance' => [
                'palette' => [
                    'primary' => [
                        'value' => '#ff0000',
                        'enabled' => true,
                    ],
                ],
            ],
            'features' => [
                'flags' => [
                    [
                        'name' => 'beta-feature',
                        'enabled' => true,
                        'metadata' => [
                            'rollout' => ['percentage' => 25],
                        ],
                    ],
                ],
            ],
        ];

        $payload = [
            'modules' => ['settings'],
            'options' => [
                'ssc_settings' => $nestedSettings,
            ],
        ];

        $request = new WP_REST_Request('POST', '/ssc/v1/import-config');
        $request->set_body(wp_json_encode($payload));

        $response = $this->controller->importConfig($request);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['ok']);
        $this->assertContains('ssc_settings', $data['applied']);

        $stored = get_option('ssc_settings');
        $this->assertIsArray($stored);
        $this->assertArrayHasKey('appearance', $stored);
        $this->assertArrayHasKey('features', $stored);
        $this->assertIsArray($stored['appearance']['palette']);
        $this->assertIsArray($stored['features']['flags']);
    }
}
