<?php declare(strict_types=1);

use SSC\Infra\Import\Sanitizer;
use SSC\Infra\Rest\CssController;
use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;
use WP_REST_Request;
use WP_REST_Response;

final class CssControllerTest extends WP_UnitTestCase
{
    private CssController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new CssController(new Sanitizer());

        foreach ([
            'ssc_active_css',
            'ssc_tokens_css',
            'ssc_tokens_registry',
            'ssc_css_desktop',
            'ssc_css_tablet',
            'ssc_css_mobile',
            'ssc_css_cache',
            'ssc_css_cache_meta',
            'ssc_css_revisions',
        ] as $option) {
            delete_option($option);
        }
    }

    private function createRequest(array $params): WP_REST_Request
    {
        $request = new WP_REST_Request('POST', '/ssc/v1/save-css');
        foreach ($params as $key => $value) {
            $request->set_param($key, $value);
        }

        return $request;
    }

    public function test_save_css_with_non_string_option_defaults_to_active_css(): void
    {
        $request = $this->createRequest([
            'option_name' => ['unexpected'],
            'css' => 'body { color: red; }',
        ]);

        $response = $this->controller->saveCss($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $this->assertSame(
            CssSanitizer::sanitize('body { color: red; }'),
            get_option('ssc_active_css')
        );
        $this->assertSame('', get_option('ssc_tokens_css', ''));
    }

    public function test_save_css_tokens_updates_registry_and_css(): void
    {
        $initialRegistry = [
            [
                'name' => '--first-token',
                'value' => '#abcdef',
                'type' => 'color',
                'description' => 'Existing color token.',
                'group' => 'Palette',
            ],
            [
                'name' => '--second-token',
                'value' => '8px',
                'type' => 'text',
                'description' => 'Existing size token.',
                'group' => 'Spacing',
            ],
        ];

        $initialSave = TokenRegistry::saveRegistry($initialRegistry);
        $this->assertSame([], $initialSave['duplicates']);

        $tokenCss = ":root {\n    --first-token: #123456;\n    --second-token: 1.5rem;\n    --with-semicolon: 'foo;bar'\n}\n\n[data-theme=\"dark\"] {\n    --first-token: #0f172a\n}";

        $request = $this->createRequest([
            'option_name' => 'ssc_tokens_css',
            'css' => $tokenCss,
        ]);

        $response = $this->controller->saveCss($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $sanitizedCss = CssSanitizer::sanitize($tokenCss);
        $convertedRegistry = TokenRegistry::convertCssToRegistry($sanitizedCss);
        $normalizedInitialResult = TokenRegistry::normalizeRegistry($initialRegistry);
        $this->assertSame([], $normalizedInitialResult['duplicates']);
        $normalizedInitial = $normalizedInitialResult['tokens'];
        $expectedRegistryResult = TokenRegistry::normalizeRegistry(
            TokenRegistry::mergeMetadata($convertedRegistry, $normalizedInitial)
        );
        $this->assertSame([], $expectedRegistryResult['duplicates']);
        $expectedRegistry = $expectedRegistryResult['tokens'];
        $expectedRegistryCss = TokenRegistry::tokensToCss($expectedRegistry);

        $this->assertSame($expectedRegistry, get_option('ssc_tokens_registry'));
        $this->assertSame($expectedRegistryCss, get_option('ssc_tokens_css'));

        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('tokens', $data);
        $this->assertArrayHasKey('css', $data);
        $this->assertSame($expectedRegistryCss, $data['css']);

        $darkContextToken = null;
        foreach ($data['tokens'] as $token) {
            if (($token['name'] ?? '') === '--first-token' && ($token['context'] ?? '') === '[data-theme="dark"]') {
                $darkContextToken = $token;
                break;
            }
        }

        $this->assertNotNull($darkContextToken, 'Response should include context-specific token metadata.');

        $withSemicolon = null;
        foreach ($expectedRegistry as $token) {
            if (($token['name'] ?? null) === '--with-semicolon') {
                $withSemicolon = $token;
                break;
            }
        }

        $this->assertNotNull($withSemicolon, 'Converted registry should retain the complex token.');
        $this->assertSame("'foo;bar'", $withSemicolon['value']);
        $this->assertSame(
            $expectedRegistry,
            TokenRegistry::convertCssToRegistry(TokenRegistry::tokensToCss($expectedRegistry))
        );
    }

    public function test_save_css_sanitizes_legacy_tablet_option_and_invalidates_cache(): void
    {
        $legacyCss = "body { color: red; }<script>alert('xss');</script>";
        update_option('ssc_css_tablet', $legacyCss, false);
        update_option('ssc_css_cache', 'cached', false);
        update_option('ssc_css_cache_meta', ['version' => 'old'], false);

        $request = $this->createRequest([
            'option_name' => 'ssc_active_css',
            'css' => 'body { color: blue; }',
        ]);

        $response = $this->controller->saveCss($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $this->assertSame(
            CssSanitizer::sanitize($legacyCss),
            get_option('ssc_css_tablet')
        );
        $this->assertSame(
            CssSanitizer::sanitize('body { color: blue; }'),
            get_option('ssc_active_css')
        );
        $this->assertFalse(get_option('ssc_css_cache'));
        $this->assertFalse(get_option('ssc_css_cache_meta'));
    }

    public function test_save_css_skips_revision_and_cache_invalidation_when_unchanged(): void
    {
        $css = 'body { color: #222; }';
        $initialResponse = $this->controller->saveCss($this->createRequest([
            'css' => $css,
        ]));

        $this->assertSame(200, $initialResponse->get_status());

        $revisionsBefore = get_option('ssc_css_revisions', []);
        update_option('ssc_css_cache', 'cached', false);
        update_option('ssc_css_cache_meta', ['version' => 'legacy'], false);

        $secondResponse = $this->controller->saveCss($this->createRequest([
            'css' => $css,
        ]));

        $this->assertSame(200, $secondResponse->get_status());
        $data = $secondResponse->get_data();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('ok', $data);
        $this->assertTrue($data['ok']);
        $this->assertArrayHasKey('unchanged', $data);
        $this->assertTrue($data['unchanged']);
        $this->assertSame($revisionsBefore, get_option('ssc_css_revisions', []));
        $this->assertSame('cached', get_option('ssc_css_cache'));
        $this->assertSame(['version' => 'legacy'], get_option('ssc_css_cache_meta'));
    }

    public function test_save_tokens_skips_processing_when_css_matches_existing(): void
    {
        $tokenCss = ":root {\n    --primary-color: #112233;\n}";

        $firstResponse = $this->controller->saveCss($this->createRequest([
            'option_name' => 'ssc_tokens_css',
            'css' => $tokenCss,
        ]));

        $this->assertSame(200, $firstResponse->get_status());

        $registryBefore = get_option('ssc_tokens_registry');
        $tokensCssBefore = get_option('ssc_tokens_css');
        $revisionsBefore = get_option('ssc_css_revisions', []);
        update_option('ssc_css_cache', 'cached', false);
        update_option('ssc_css_cache_meta', ['version' => 'legacy'], false);

        $secondResponse = $this->controller->saveCss($this->createRequest([
            'option_name' => 'ssc_tokens_css',
            'css' => $tokenCss,
        ]));

        $this->assertSame(200, $secondResponse->get_status());
        $data = $secondResponse->get_data();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('unchanged', $data);
        $this->assertTrue($data['unchanged']);
        $this->assertSame($registryBefore, get_option('ssc_tokens_registry'));
        $this->assertSame($tokensCssBefore, get_option('ssc_tokens_css'));
        $this->assertSame($revisionsBefore, get_option('ssc_css_revisions', []));
        $this->assertSame('cached', get_option('ssc_css_cache'));
        $this->assertSame(['version' => 'legacy'], get_option('ssc_css_cache_meta'));
    }
}
