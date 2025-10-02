<?php declare(strict_types=1);

use SSC\Infra\Import\Sanitizer;

final class SanitizerTest extends WP_UnitTestCase
{
    public function test_combine_responsive_css_wraps_segments(): void
    {
        $sanitizer = new Sanitizer();

        $combined = $sanitizer->combineResponsiveCss([
            'desktop' => '.desktop { color: red; }',
            'tablet' => '.tablet { color: blue; }',
            'mobile' => '.mobile { color: green; }',
        ]);

        $this->assertStringContainsString('@media (max-width: 782px)', $combined);
        $this->assertStringContainsString('@media (max-width: 480px)', $combined);
    }

    public function test_sanitize_import_array_limits_depth_and_items(): void
    {
        $sanitizer = new Sanitizer();

        $payload = [
            'allowed' => ['key' => '<script>bad</script> value'],
            'nested' => ['inner' => ['more' => ['depth' => 'value']]],
        ];

        $sanitized = $sanitizer->sanitizeImportArray($payload);

        $this->assertIsArray($sanitized);
        $this->assertSame('bad value', $sanitized['allowed']['key']);
    }

    public function test_sanitize_import_tokens_detects_duplicates(): void
    {
        $sanitizer = new Sanitizer();
        $sanitizer->resetDuplicateWarnings();

        $result = $sanitizer->sanitizeImportTokens([
            ['name' => '--duplicate', 'value' => '10px', 'type' => 'size'],
            ['name' => '--duplicate', 'value' => '12px', 'type' => 'size'],
        ]);

        $this->assertNull($result);
        $duplicates = $sanitizer->consumeDuplicateWarnings();
        $this->assertNotEmpty($duplicates);
    }
}
