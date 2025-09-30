<?php declare(strict_types=1);

use SSC\Infra\Routes;
use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;
use WP_REST_Request;
use WP_REST_Response;

class RoutesSaveCssTest extends WP_UnitTestCase
{
    private Routes $routes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routes = new Routes();

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

        $response = $this->routes->saveCss($request);

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

        TokenRegistry::saveRegistry($initialRegistry);

        $tokenCss = ":root {\n    --first-token: #123456;\n    --second-token: 1.5rem;\n    --with-semicolon: 'foo;bar'\n}";

        $request = $this->createRequest([
            'option_name' => 'ssc_tokens_css',
            'css' => $tokenCss,
        ]);

        $response = $this->routes->saveCss($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $sanitizedCss = CssSanitizer::sanitize($tokenCss);
        $convertedRegistry = TokenRegistry::convertCssToRegistry($sanitizedCss);
        $normalizedInitial = TokenRegistry::normalizeRegistry($initialRegistry);
        $expectedRegistry = TokenRegistry::normalizeRegistry(
            TokenRegistry::mergeMetadata($convertedRegistry, $normalizedInitial)
        );
        $expectedRegistryCss = TokenRegistry::tokensToCss($expectedRegistry);

        $this->assertSame($expectedRegistry, get_option('ssc_tokens_registry'));
        $this->assertSame($expectedRegistryCss, get_option('ssc_tokens_css'));

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

        $response = $this->routes->saveCss($request);

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
}
