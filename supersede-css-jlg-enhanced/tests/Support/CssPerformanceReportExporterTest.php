<?php declare(strict_types=1);

namespace SSC\Tests\Support;

use PHPUnit\Framework\TestCase;
use SSC\Support\CssPerformanceReportExporter;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

final class CssPerformanceReportExporterTest extends TestCase
{
    public function testBuildPayloadNormalizesData(): void
    {
        $context = [
            'active' => [
                'size_bytes'      => 1024,
                'size_readable'   => '1 KB',
                'rule_count'      => 10,
                'selector_count'  => 12,
                'important_count' => 2,
            ],
            'tokens' => [
                'size_bytes'     => 2048,
                'rule_count'     => 20,
                'selector_count' => 30,
            ],
            'combined' => [
                'size_bytes'      => 3072,
                'gzip_bytes'      => 1200,
                'rule_count'      => 30,
                'selector_count'  => 42,
                'declaration_count' => 90,
                'important_count' => 4,
                'specificity_top' => [
                    ['selector' => '.card', 'score' => 120, 'vector' => '0,3,1'],
                ],
                'custom_property_unique_count' => 6,
                'custom_property_references'   => 12,
                'vendor_prefixes' => [
                    ['prefix' => '-webkit-', 'count' => 3],
                ],
            ],
            'warnings'        => ['  Vérifier les @import  ', ''],
            'recommendations' => ['Réduire les !important'],
            'comparison'      => [
                'size_bytes' => ['previous' => 2500, 'current' => 3072, 'delta' => 572, 'percent' => 22.88],
                'alerts'     => ['Le poids total a augmenté'],
            ],
            'snapshot_meta' => [
                'timestamp'   => 1_700_000_000,
                'active_hash' => 'abc',
            ],
            'site' => [
                'name' => 'Demo Site',
                'url'  => 'https://example.test',
            ],
            'generated_at' => 1_700_000_000,
        ];

        $payload = CssPerformanceReportExporter::buildPayload($context);

        self::assertSame(1_700_000_000, $payload['generated_at']);
        self::assertSame('Demo Site', $payload['site']['name']);
        self::assertSame('https://example.test', $payload['site']['url']);
        self::assertCount(1, $payload['warnings']);
        self::assertCount(1, $payload['recommendations']);
        self::assertNotEmpty($payload['comparison']);
        self::assertSame(3072, $payload['metrics']['combined']['size_bytes']);
        self::assertSame(42, $payload['metrics']['combined']['selector_count']);
        self::assertSame(6, $payload['metrics']['combined']['custom_property_unique_count']);
        self::assertSame('abc', $payload['snapshot_meta']['active_hash']);
    }

    public function testBuildMarkdownRendersReadableReport(): void
    {
        $payload = [
            'generated_at' => 1_700_000_000,
            'site' => [
                'name' => 'Demo Site',
                'url'  => 'https://example.test',
            ],
            'metrics' => [
                'combined' => [
                    'size_bytes'      => 4096,
                    'gzip_bytes'      => 2048,
                    'rule_count'      => 64,
                    'selector_count'  => 80,
                    'declaration_count' => 180,
                    'important_count' => 5,
                    'specificity_top' => [
                        ['selector' => '#main .card', 'score' => 230, 'vector' => '1,2,1'],
                    ],
                    'custom_property_unique_count' => 10,
                    'custom_property_definitions'  => 12,
                    'custom_property_references'   => 24,
                    'vendor_prefixes' => [
                        ['prefix' => '-webkit-', 'count' => 4],
                    ],
                ],
            ],
            'warnings' => ['Attention aux @import'],
            'recommendations' => ['Configurer Autoprefixer'],
            'comparison' => [
                'size_bytes' => ['previous' => 3000, 'current' => 4096, 'delta' => 1096, 'percent' => 36.53],
                'alerts'     => ['Le poids total a augmenté sensiblement.'],
            ],
        ];

        $markdown = CssPerformanceReportExporter::buildMarkdown($payload);

        self::assertStringContainsString('# Rapport de performance CSS', $markdown);
        self::assertStringContainsString('Demo Site', $markdown);
        self::assertStringContainsString('Poids total', $markdown);
        self::assertStringContainsString('Points de vigilance', $markdown);
        self::assertStringContainsString('Recommandations', $markdown);
        self::assertStringContainsString('| Indicateur | Précédent | Actuel |', $markdown);
        self::assertStringContainsString('Préfixes propriétaires', $markdown);
    }
}

