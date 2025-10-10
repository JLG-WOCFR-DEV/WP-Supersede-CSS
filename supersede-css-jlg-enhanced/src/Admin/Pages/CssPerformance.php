<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;
use SSC\Support\CssPerformanceAnalyzer;

if (!defined('ABSPATH')) {
    exit;
}

class CssPerformance extends AbstractPage
{
    private CssPerformanceAnalyzer $analyzer;

    public function __construct(?CssPerformanceAnalyzer $analyzer = null)
    {
        $this->analyzer = $analyzer ?? new CssPerformanceAnalyzer();
    }

    public function render(): void
    {
        $activeCss  = $this->getOptionValue('ssc_active_css');
        $tokensCss  = $this->getOptionValue('ssc_tokens_css');

        $report = $this->analyzer->analyzePair($activeCss, $tokensCss);

        $this->render_view('css-performance', [
            'active_metrics'       => $report['active'],
            'tokens_metrics'       => $report['tokens'],
            'combined_metrics'     => $report['combined'],
            'warnings'             => $report['warnings'],
            'recommendations'      => $report['recommendations'],
        ]);
    }

    private function getOptionValue(string $option): string
    {
        $value = get_option($option, '');
        return is_string($value) ? $value : '';
    }
}
