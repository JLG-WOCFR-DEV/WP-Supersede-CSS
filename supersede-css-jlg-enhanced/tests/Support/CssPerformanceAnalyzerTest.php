<?php declare(strict_types=1);

namespace SSC\Tests\Support;

use PHPUnit\Framework\TestCase;
use SSC\Support\CssPerformanceAnalyzer;

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        unset($domain);

        return $text;
    }
}

if (!function_exists('number_format_i18n')) {
    function number_format_i18n($number, int $decimals = 0)
    {
        return number_format((float) $number, $decimals, '.', ' ');
    }
}

final class CssPerformanceAnalyzerTest extends TestCase
{
    private CssPerformanceAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new CssPerformanceAnalyzer();
    }

    public function testAnalyzeEmptyCssReturnsZeroedMetrics(): void
    {
        $metrics = $this->analyzer->analyze('   ');

        self::assertTrue($metrics['empty']);
        self::assertSame(0, $metrics['size_bytes']);
        self::assertSame('0 B', $metrics['size_readable']);
        self::assertNull($metrics['gzip_bytes']);
        self::assertSame(__('N/A', 'supersede-css-jlg'), $metrics['gzip_readable']);
        self::assertSame(0, $metrics['rule_count']);
        self::assertSame([], $metrics['long_selectors']);
        self::assertSame([], $metrics['duplicate_selectors']);
        self::assertSame('', $metrics['raw_sample']);
    }

    public function testAnalyzeCollectsSelectorsCustomPropertiesAndPrefixes(): void
    {
        $css = <<<'CSS'
        @import url("legacy.css");

        .card {
            color: red !important;
            --card-bg: #fff;
            padding: 12px;
        }

        .card, .card--alt {
            background: var(--card-bg);
        }

        #dashboard .card.super-long-selector-name-for-testing-purposes-only-example-example-example {
            -webkit-transform: translateZ(0);
            display: flex;
        }

        .card--alt {
            margin: 0;
        }
        CSS;

        $metrics = $this->analyzer->analyze($css);

        self::assertFalse($metrics['empty']);
        self::assertSame(4, $metrics['rule_count']);
        self::assertSame(5, $metrics['selector_count']);
        self::assertSame(7, $metrics['declaration_count']);
        self::assertSame('1.8', $metrics['average_declarations']);
        self::assertSame(1, $metrics['important_count']);
        self::assertSame(1, $metrics['import_count']);
        self::assertGreaterThan(0, $metrics['vendor_prefix_total']);
        self::assertNotEmpty($metrics['vendor_prefixes']);
        self::assertNotEmpty($metrics['custom_property_names']);
        self::assertSame(1, $metrics['custom_property_definitions']);
        self::assertSame(1, $metrics['custom_property_references']);
        self::assertNotEmpty($metrics['long_selectors']);
        self::assertNotEmpty($metrics['duplicate_selectors']);
        self::assertSame('.card--alt', $metrics['duplicate_selectors'][0]['selector']);
        self::assertNotSame('', $metrics['raw_sample']);
        self::assertGreaterThan(0, $metrics['specificity_max']);
    }

    public function testWarningsAndRecommendationsReactToThresholds(): void
    {
        $baseCss = '.foo { color: blue !important; --token: 1; } .foo { background: red; }';
        $active  = $this->analyzer->analyze($baseCss);
        $tokens  = $this->analyzer->analyze('@import url("a.css"); .bar { -webkit-transform: translateZ(0); } .bar { display: block; }');
        $combined = $this->analyzer->combine($active, $tokens);

        $warnings = $this->analyzer->collectWarnings($active, $tokens, $combined);

        self::assertNotEmpty($warnings);
        self::assertTrue($this->arrayContainsString($warnings, '@import'));
        self::assertTrue($this->arrayContainsString($warnings, 'sélecteurs'));

        $recommendations = $this->analyzer->buildRecommendations($active, $tokens, $combined);

        self::assertTrue($this->arrayContainsString($recommendations, '!important'));
        self::assertTrue($this->arrayContainsString($recommendations, 'tokens CSS'));
        self::assertTrue($this->arrayContainsString($recommendations, 'Autoprefixer'));
    }

    /**
     * @param list<string> $messages
     */
    private function arrayContainsString(array $messages, string $needle): bool
    {
        foreach ($messages as $message) {
            if (strpos($message, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}

