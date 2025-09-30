<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SSC\Infra\Routes;
use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback): void {}
}

if (!function_exists('register_rest_route')) {
    function register_rest_route(...$args): void {}
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        /** @var mixed */
        private $data;

        private int $status;

        public function __construct($data = null, int $status = 200)
        {
            $this->data = $data;
            $this->status = $status;
        }

        public function get_status(): int
        {
            return $this->status;
        }

        /** @return mixed */
        public function get_data()
        {
            return $this->data;
        }
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        /** @var array<string, mixed> */
        private array $params;

        /** @var array<string, mixed>|null */
        private ?array $json;

        /**
         * @param array<string, mixed> $params
         * @param array<string, mixed>|null $json
         */
        public function __construct(array $params = [], ?array $json = null)
        {
            $this->params = $params;
            $this->json = $json;
        }

        public function get_param(string $key)
        {
            return $this->params[$key] ?? null;
        }

        /**
         * @return array<string, mixed>|null
         */
        public function get_json_params(): ?array
        {
            return $this->json ?? $this->params;
        }
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        $key = strtolower((string) $key);

        return preg_replace('/[^a-z0-9_]/', '', $key);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        return trim(strip_tags((string) $value));
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($value)
    {
        return trim(strip_tags((string) $value));
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($value, $options = 0, $depth = 512)
    {
        return json_encode($value, $options, $depth);
    }
}

if (!function_exists('wp_check_invalid_utf8')) {
    function wp_check_invalid_utf8($string)
    {
        return trim(strip_tags((string) $string));
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        unset($domain);

        return $text;
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses($string, $allowed_html)
    {
        unset($allowed_html);

        return strip_tags((string) $string);
    }
}

if (!function_exists('wp_kses_bad_protocol')) {
    function wp_kses_bad_protocol(string $string, array $allowed_protocols): string
    {
        unset($allowed_protocols);

        return $string;
    }
}

if (!function_exists('wp_allowed_protocols')) {
    function wp_allowed_protocols(): array
    {
        return ['http', 'https'];
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user()
    {
        return (object) [
            'ID' => 1,
            'user_login' => 'tester',
        ];
    }
}

if (!function_exists('absint')) {
    function absint($value)
    {
        return abs((int) $value);
    }
}

/** @var array<string, mixed> $ssc_options_store */
$ssc_options_store = [];

global $ssc_options_store;

if (!function_exists('get_option')) {
    function get_option($name, $default = false)
    {
        global $ssc_options_store;

        return $ssc_options_store[$name] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($name, $value, $autoload = false)
    {
        unset($autoload);
        global $ssc_options_store;

        $ssc_options_store[$name] = $value;

        return true;
    }
}

final class RoutesImportTest extends TestCase
{
    private Routes $routes;

    private ReflectionMethod $applyImportedOptions;

    private ReflectionMethod $sanitizeTokens;

    protected function setUp(): void
    {
        parent::setUp();

        global $ssc_options_store;
        $ssc_options_store = [
            'ssc_admin_log' => [
                [
                    't' => '2024-01-01T00:00:00Z',
                    'user' => 'alice',
                    'action' => 'existing',
                    'data' => ['note' => 'original'],
                ],
            ],
        ];

        $reflection = new \ReflectionClass(Routes::class);
        $this->routes = $reflection->newInstanceWithoutConstructor();

        $this->applyImportedOptions = $reflection->getMethod('applyImportedOptions');
        $this->applyImportedOptions->setAccessible(true);

        $this->sanitizeTokens = $reflection->getMethod('sanitizeImportTokens');
        $this->sanitizeTokens->setAccessible(true);
    }

    public function testInvalidAdminLogImportIsSkipped(): void
    {
        global $ssc_options_store;
        $originalLog = $ssc_options_store['ssc_admin_log'];

        $result = $this->apply(['ssc_admin_log' => 'not-an-array']);

        $this->assertSame($originalLog, $ssc_options_store['ssc_admin_log']);
        $this->assertContains('ssc_admin_log', $result['skipped']);
    }

    public function testTokenCssImportUpdatesRegistryAndCss(): void
    {
        global $ssc_options_store;
        $ssc_options_store['ssc_tokens_css'] = '';
        $ssc_options_store['ssc_tokens_registry'] = [];

        $tokensCss = ":root {\n    --primary-color: #123456;\n    --spacing-md: 16px;\n}";
        $result = $this->apply(['ssc_tokens_css' => $tokensCss]);

        $sanitizedCss = CssSanitizer::sanitize($tokensCss);
        $expectedTokens = TokenRegistry::convertCssToRegistry($sanitizedCss);
        $expectedCss = TokenRegistry::tokensToCss($expectedTokens);

        $this->assertNotEmpty($expectedTokens);
        $this->assertContains('ssc_tokens_css', $result['applied']);
        $this->assertSame($expectedTokens, $ssc_options_store['ssc_tokens_registry']);
        $this->assertSame($expectedCss, $ssc_options_store['ssc_tokens_css']);
    }

    public function testTokenRegistryConversionHandlesSingleAndMissingSemicolons(): void
    {
        $singleTokenCss = ":root {\n    --solitary-token: #bada55\n}";
        $singleTokenRegistry = TokenRegistry::convertCssToRegistry(CssSanitizer::sanitize($singleTokenCss));

        $this->assertCount(1, $singleTokenRegistry);
        $this->assertSame('--solitary-token', $singleTokenRegistry[0]['name']);
        $this->assertSame('#bada55', $singleTokenRegistry[0]['value']);

        $noSemicolonCss = ":root {\n    --first-token: 10px;\n    --last-token: 1rem\n}";
        $noSemicolonRegistry = TokenRegistry::convertCssToRegistry(CssSanitizer::sanitize($noSemicolonCss));

        $this->assertCount(2, $noSemicolonRegistry);
        $this->assertSame('--first-token', $noSemicolonRegistry[0]['name']);
        $this->assertSame('10px', $noSemicolonRegistry[0]['value']);
        $this->assertSame('--last-token', $noSemicolonRegistry[1]['name']);
        $this->assertSame('1rem', $noSemicolonRegistry[1]['value']);
    }

    public function testDuplicateTokenCssIsDeduplicatedDuringImport(): void
    {
        global $ssc_options_store;
        $ssc_options_store['ssc_tokens_css'] = '';
        $ssc_options_store['ssc_tokens_registry'] = [];

        $duplicateTokensCss = ":root {\n    --duplicate-token: 4px;\n    --duplicate-token: 8px;\n}";

        $duplicateRegistry = TokenRegistry::convertCssToRegistry(CssSanitizer::sanitize($duplicateTokensCss));
        $this->assertCount(1, $duplicateRegistry);
        $this->assertSame('8px', $duplicateRegistry[0]['value']);

        $result = $this->apply(['ssc_tokens_css' => $duplicateTokensCss]);
        $this->assertContains('ssc_tokens_css', $result['applied']);

        $storedRegistry = $ssc_options_store['ssc_tokens_registry'];
        $this->assertIsArray($storedRegistry);
        $this->assertCount(1, $storedRegistry);
        $this->assertSame('8px', $storedRegistry[0]['value']);
        $this->assertSame(TokenRegistry::tokensToCss($storedRegistry), $ssc_options_store['ssc_tokens_css']);
    }

    public function testSanitizeImportTokensNormalizesPayloadWithoutSideEffects(): void
    {
        global $ssc_options_store;
        $ssc_options_store['ssc_tokens_css'] = '__original_css__';
        $ssc_options_store['ssc_tokens_registry'] = '__original_registry__';

        $rawRegistryPayload = [
            [
                'name' => 'Primary Color',
                'value' => '#FFFFFF',
                'type' => 'color',
                'description' => '<strong>Important</strong>',
                'group' => 'Colors',
            ],
            [
                'name' => '',
                'value' => 'should-be-ignored',
                'type' => 'text',
                'description' => 'Unused',
                'group' => '',
            ],
        ];

        $sanitizedRegistryPayload = $this->sanitizeTokens->invoke($this->routes, $rawRegistryPayload);

        $this->assertIsArray($sanitizedRegistryPayload);
        $this->assertCount(1, $sanitizedRegistryPayload);
        $this->assertSame('__original_registry__', $ssc_options_store['ssc_tokens_registry']);
        $this->assertSame('__original_css__', $ssc_options_store['ssc_tokens_css']);
    }

    public function testTokenRegistryImportsRegenerateCss(): void
    {
        global $ssc_options_store;
        $rawRegistryPayload = [
            [
                'name' => 'Primary Color',
                'value' => '#FFFFFF',
                'type' => 'color',
                'description' => '<strong>Important</strong>',
                'group' => 'Colors',
            ],
        ];

        $result = $this->apply(['ssc_tokens_registry' => $rawRegistryPayload]);
        $this->assertContains('ssc_tokens_registry', $result['applied']);

        $storedRegistry = $ssc_options_store['ssc_tokens_registry'];
        $this->assertIsArray($storedRegistry);
        $this->assertCount(1, $storedRegistry);
        $storedToken = $storedRegistry[0];

        $this->assertSame('--Primary-Color', $storedToken['name']);
        $this->assertSame('#FFFFFF', $storedToken['value']);
        $this->assertSame('Colors', $storedToken['group']);
        $this->assertSame('Important', $storedToken['description']);

        $expectedRegistryCss = TokenRegistry::tokensToCss($storedRegistry);
        $this->assertSame($expectedRegistryCss, $ssc_options_store['ssc_tokens_css']);
    }

    public function testCombinedRegistryAndCssImportPreservesMetadata(): void
    {
        global $ssc_options_store;
        $ssc_options_store['ssc_tokens_registry'] = [];
        $ssc_options_store['ssc_tokens_css'] = '';

        $registryWithMetadata = [
            [
                'name' => '--brand-primary',
                'value' => '#112233',
                'type' => 'color',
                'description' => 'Primary brand color',
                'group' => 'Brand',
            ],
        ];

        $result = $this->apply([
            'ssc_tokens_registry' => $registryWithMetadata,
            'ssc_tokens_css' => ":root {\n    --brand-primary: #112233;\n}",
        ]);

        $this->assertContains('ssc_tokens_registry', $result['applied']);
        $this->assertContains('ssc_tokens_css', $result['applied']);

        $storedCombinedRegistry = $ssc_options_store['ssc_tokens_registry'];
        $this->assertIsArray($storedCombinedRegistry);
        $this->assertCount(1, $storedCombinedRegistry);

        $storedCombinedToken = $storedCombinedRegistry[0];
        $this->assertSame('color', $storedCombinedToken['type']);
        $this->assertSame('Brand', $storedCombinedToken['group']);
        $this->assertSame('Primary brand color', $storedCombinedToken['description']);
    }

    public function testCssOnlyImportsMergeWithExistingRegistry(): void
    {
        global $ssc_options_store;
        $existingRegistry = TokenRegistry::saveRegistry([
            [
                'name' => '--spacing-large',
                'value' => '32px',
                'type' => 'number',
                'description' => 'Large spacing token',
                'group' => 'Spacing',
            ],
        ]);
        $ssc_options_store['ssc_tokens_registry'] = $existingRegistry;

        $result = $this->apply(['ssc_tokens_css' => ":root {\n    --spacing-large: 40px;\n}"]);

        $this->assertContains('ssc_tokens_css', $result['applied']);
        $storedRegistry = $ssc_options_store['ssc_tokens_registry'];

        $this->assertIsArray($storedRegistry);
        $this->assertCount(1, $storedRegistry);

        $storedToken = $storedRegistry[0];
        $this->assertSame('40px', $storedToken['value']);
        $this->assertSame('number', $storedToken['type']);
        $this->assertSame('Spacing', $storedToken['group']);
        $this->assertSame('Large spacing token', $storedToken['description']);
    }

    public function testEmptyTokenCssImportResetsRegistry(): void
    {
        global $ssc_options_store;
        TokenRegistry::saveRegistry([
            [
                'name' => '--existing-token',
                'value' => '#abcdef',
                'type' => 'color',
                'description' => '',
                'group' => 'Legacy',
            ],
        ]);

        $result = $this->apply(['ssc_tokens_css' => '']);

        $this->assertContains('ssc_tokens_css', $result['applied']);
        $this->assertSame([], $ssc_options_store['ssc_tokens_registry']);
        $this->assertSame(TokenRegistry::tokensToCss([]), $ssc_options_store['ssc_tokens_css']);
    }

    public function testObjectPayloadsAreSanitized(): void
    {
        global $ssc_options_store;
        $ssc_options_store['ssc_settings'] = [];

        $objectPayload = new stdClass();
        $objectPayload->title = '<strong>Title</strong>';
        $objectPayload->count = 5;
        $objectPayload->nested = new stdClass();
        $objectPayload->nested->note = '<em>Nested</em>';

        $jsonOnlyObject = new class() implements \JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return ['danger' => '<script>alert(1)</script>'];
            }
        };

        $result = $this->apply([
            'ssc_settings' => [
                'object_payload' => $objectPayload,
                'json_only_object' => $jsonOnlyObject,
            ],
        ]);

        $expectedSettings = [
            'object_payload' => [
                'title' => 'Title',
                'count' => 5,
                'nested' => [
                    'note' => 'Nested',
                ],
            ],
            'json_only_object' => '{"danger":"alert(1)"}',
        ];

        $this->assertSame($expectedSettings, $ssc_options_store['ssc_settings']);
        $this->assertContains('ssc_settings', $result['applied']);
    }

    public function testDuplicateKeysAreReportedAndFirstValueIsKept(): void
    {
        global $ssc_options_store;
        $ssc_options_store['ssc_settings'] = [];

        $payload = [
            'options' => [
                'ssc_settings' => [
                    'Name One' => 'First Value',
                    'name-one' => 'Second Value',
                ],
            ],
        ];

        $response = $this->routes->importConfig(new WP_REST_Request([], [], $payload));

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $data = $response->get_data();

        $this->assertTrue($data['ok']);
        $this->assertContains('ssc_settings', $data['applied']);
        $this->assertContains('ssc_settings (duplicate key: nameone)', $data['skipped']);
        $this->assertSame(['nameone' => 'First Value'], $ssc_options_store['ssc_settings']);
    }

    public function testImportConfigRejectsUnknownModules(): void
    {
        global $ssc_options_store;
        $ssc_options_store['ssc_active_css'] = 'original-css';

        $request = new WP_REST_Request(
            ['modules' => ['totally-unknown']],
            [],
            [
                'modules' => ['totally-unknown'],
                'options' => [
                    'ssc_active_css' => 'body { color: red; }',
                ],
            ]
        );

        $response = $this->routes->importConfig($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(400, $response->get_status());

        $data = $response->get_data();
        $this->assertFalse($data['ok']);
        $this->assertSame('No valid Supersede CSS modules were selected for import.', $data['message']);
        $this->assertSame(['ssc_active_css'], $data['skipped']);
        $this->assertSame('original-css', $ssc_options_store['ssc_active_css']);
    }

    /**
     * @param array<string, mixed> $options
     * @return array{applied: array<int, string>, skipped: array<int, string>} 
     */
    private function apply(array $options): array
    {
        /** @var array{applied: array<int, string>, skipped: array<int, string>} $result */
        $result = $this->applyImportedOptions->invoke($this->routes, $options);

        return $result;
    }
}
