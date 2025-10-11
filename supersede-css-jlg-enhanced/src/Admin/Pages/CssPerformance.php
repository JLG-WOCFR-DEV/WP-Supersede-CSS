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

        $previousSnapshot = $this->getPreviousSnapshot();
        $comparison       = null;
        $snapshotMeta     = null;

        if ($previousSnapshot !== null) {
            $comparison   = $this->analyzer->compareSnapshots($previousSnapshot['combined'], $report['combined']);
            $snapshotMeta = $previousSnapshot['meta'];
        }

        $this->storeSnapshot($report['combined'], $activeCss, $tokensCss, $previousSnapshot);

        $this->render_view('css-performance', [
            'active_metrics'       => $report['active'],
            'tokens_metrics'       => $report['tokens'],
            'combined_metrics'     => $report['combined'],
            'warnings'             => $report['warnings'],
            'recommendations'      => $report['recommendations'],
            'comparison'           => $comparison,
            'snapshot_meta'        => $snapshotMeta,
        ]);
    }

    private function getOptionValue(string $option): string
    {
        $value = get_option($option, '');
        return is_string($value) ? $value : '';
    }

    private function getPreviousSnapshot(): ?array
    {
        if (!function_exists('get_option')) {
            return null;
        }

        $stored = get_option('ssc_css_performance_snapshot');
        if (!is_array($stored) || empty($stored['combined']) || !is_array($stored['combined'])) {
            return null;
        }

        return [
            'combined' => $stored['combined'],
            'meta'     => [
                'timestamp'   => isset($stored['timestamp']) ? (int) $stored['timestamp'] : null,
                'active_hash' => isset($stored['active_hash']) ? (string) $stored['active_hash'] : null,
                'tokens_hash' => isset($stored['tokens_hash']) ? (string) $stored['tokens_hash'] : null,
            ],
        ];
    }

    private function storeSnapshot(array $combined, string $activeCss, string $tokensCss, ?array $previous): void
    {
        if (!function_exists('update_option')) {
            return;
        }

        $payload = [
            'combined'    => $combined,
            'timestamp'   => time(),
            'active_hash' => hash('sha256', $activeCss),
            'tokens_hash' => hash('sha256', $tokensCss),
        ];

        $existingHashes = $previous['meta'] ?? [];
        $hasChanged = true;

        if (!empty($existingHashes)) {
            $hasChanged = ($existingHashes['active_hash'] ?? null) !== $payload['active_hash']
                || ($existingHashes['tokens_hash'] ?? null) !== $payload['tokens_hash'];
        }

        if ($hasChanged) {
            update_option('ssc_css_performance_snapshot', $payload, false);
        }
    }
}
