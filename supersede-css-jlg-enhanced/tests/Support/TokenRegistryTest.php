<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SSC\Support\CssSanitizer;
use SSC\Support\TokenRegistry;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
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

if (!function_exists('wp_kses')) {
    function wp_kses(string $string, array $allowed_html = []): string
    {
        unset($allowed_html);

        return strip_tags($string);
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

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return sprintf('[%s] %s', $domain, $text);
    }
}

/** @var array<string, mixed> $ssc_options_store */
$ssc_options_store = [];

global $ssc_options_store;

/** @var int $ssc_css_invalidation_calls */
$ssc_css_invalidation_calls = 0;

global $ssc_css_invalidation_calls;

if (!function_exists('ssc_invalidate_css_cache')) {
    function ssc_invalidate_css_cache(): void
    {
        global $ssc_css_invalidation_calls;

        $ssc_css_invalidation_calls++;
    }
}

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

final class TokenRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        global $ssc_options_store, $ssc_css_invalidation_calls;
        $ssc_options_store = [];
        $ssc_css_invalidation_calls = 0;
    }

    public function testNormalizeRegistryPreservesOriginalCasing(): void
    {
        $normalized = TokenRegistry::normalizeRegistry([
            [
                'name' => '--BrandPrimary',
                'value' => '#3366ff',
                'type' => 'color',
                'description' => 'Primary brand color.',
                'group' => 'Brand',
            ],
        ]);

        $this->assertNotEmpty($normalized);
        $this->assertSame('--BrandPrimary', $normalized[0]['name']);
    }

    public function testSaveRegistryPersistsCssAndInvalidatesCache(): void
    {
        global $ssc_options_store, $ssc_css_invalidation_calls;

        $registry = TokenRegistry::saveRegistry([
            [
                'name' => '--BrandPrimary',
                'value' => '#3366ff',
                'type' => 'color',
                'description' => 'Primary brand color.',
                'group' => 'Brand',
            ],
        ]);

        $this->assertNotEmpty($registry);
        $this->assertSame('--BrandPrimary', $registry[0]['name']);
        $this->assertIsString($ssc_options_store['ssc_tokens_css'] ?? null);
        $this->assertStringContainsString('--BrandPrimary', $ssc_options_store['ssc_tokens_css']);
    }

    public function testGetRegistryRegeneratesMissingCss(): void
    {
        global $ssc_options_store, $ssc_css_invalidation_calls;

        TokenRegistry::saveRegistry([
            [
                'name' => '--BrandPrimary',
                'value' => '#3366ff',
                'type' => 'color',
                'description' => 'Primary brand color.',
                'group' => 'Brand',
            ],
        ]);

        unset($ssc_options_store['ssc_tokens_css']);

        $refreshedRegistry = TokenRegistry::getRegistry();
        $this->assertSame('--BrandPrimary', $refreshedRegistry[0]['name']);
        $this->assertStringContainsString('--BrandPrimary', $ssc_options_store['ssc_tokens_css']);

        $roundTripRegistry = TokenRegistry::convertCssToRegistry($ssc_options_store['ssc_tokens_css']);
        $this->assertSame('--BrandPrimary', $roundTripRegistry[0]['name']);
        $this->assertStringContainsString('--BrandPrimary', TokenRegistry::tokensToCss($roundTripRegistry));
    }

    public function testMergeMetadataCombinesIncomingAndExistingDetails(): void
    {
        $existingTokens = [
            [
                'name' => '--BrandPrimary',
                'value' => '#3366ff',
                'type' => 'color',
                'description' => 'Primary brand color.',
                'group' => 'Brand',
            ],
            [
                'name' => '--SpacingSmall',
                'value' => '4px',
                'type' => 'number',
                'description' => 'Small spacing token.',
                'group' => 'Spacing',
            ],
        ];

        $incomingTokens = [
            [
                'name' => '--BrandPrimary',
                'value' => '#123456',
                'type' => 'text',
                'description' => '',
                'group' => 'Legacy',
            ],
            [
                'name' => '--NewToken',
                'value' => 'value',
                'type' => 'text',
                'description' => '',
                'group' => 'Legacy',
            ],
        ];

        $mergedTokens = TokenRegistry::mergeMetadata($incomingTokens, $existingTokens);

        $this->assertCount(2, $mergedTokens);
        $this->assertSame('color', $mergedTokens[0]['type']);
        $this->assertSame('Brand', $mergedTokens[0]['group']);
        $this->assertSame('Primary brand color.', $mergedTokens[0]['description']);
        $this->assertSame('#123456', $mergedTokens[0]['value']);
        $this->assertSame('Legacy', $mergedTokens[1]['group']);
    }

    public function testSaveRegistryPreservesUnderscoresAndTranslations(): void
    {
        global $ssc_options_store;

        $underscoredTokens = [
            [
                'name' => '--spacing_small',
                'value' => '8px',
                'type' => 'text',
                'description' => 'Spacing token with underscore.',
                'group' => 'Spacing',
            ],
        ];

        $savedRegistry = TokenRegistry::saveRegistry($underscoredTokens);
        $this->assertSame('--spacing_small', $savedRegistry[0]['name']);

        $supportedTypes = TokenRegistry::getSupportedTypes();
        $this->assertSame('Couleur', $supportedTypes['color']['label'] ?? null);

        $storedRegistry = $ssc_options_store['ssc_tokens_registry'] ?? [];
        $this->assertSame('--spacing_small', $storedRegistry[0]['name'] ?? null);
        $this->assertStringContainsString('--spacing_small', $ssc_options_store['ssc_tokens_css'] ?? '');

        $roundTrip = TokenRegistry::getRegistry();
        $this->assertSame('--spacing_small', $roundTrip[0]['name'] ?? null);
        $this->assertStringContainsString('--spacing_small', TokenRegistry::tokensToCss($roundTrip));
    }

    public function testConvertCssToRegistryParsesTokensAfterComments(): void
    {
        global $ssc_options_store;

        $ssc_options_store = [];
        $cssWithLeadingComment = '/* initial token */ --comment-prefixed: 24px;';
        $registry = TokenRegistry::convertCssToRegistry($cssWithLeadingComment);
        $this->assertSame('--comment-prefixed', $registry[0]['name']);

        TokenRegistry::saveRegistry($registry);
        $this->assertStringContainsString('--comment-prefixed', $ssc_options_store['ssc_tokens_css']);

        $ssc_options_store = [];
        $annotatedCss = '/* note */ --my-token: value;';
        $annotatedRegistry = TokenRegistry::convertCssToRegistry($annotatedCss);
        $this->assertSame('--my-token', $annotatedRegistry[0]['name']);

        TokenRegistry::saveRegistry($annotatedRegistry);
        $this->assertStringContainsString('--my-token:value', $ssc_options_store['ssc_tokens_css']);
    }

    public function testConvertCssToRegistryHandlesValuesWithSemicolons(): void
    {
        $complexValueCss = ":root {\n    --with-semicolon: 'foo;bar';\n}";
        $sanitized = CssSanitizer::sanitize($complexValueCss);
        $registry = TokenRegistry::convertCssToRegistry($sanitized);

        $this->assertSame("'foo;bar'", $registry[0]['value']);
        $this->assertSame($registry, TokenRegistry::convertCssToRegistry(TokenRegistry::tokensToCss($registry)));
    }
}
