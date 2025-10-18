<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;
use SSC\Admin\ModuleRegistry;
use SSC\Support\CssPerformanceAnalyzer;

if (!defined('ABSPATH')) {
    exit;
}

class CssPerformance extends AbstractPage
{
    private const PAGE_SLUG = ModuleRegistry::BASE_SLUG . '-css-performance';

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

        if ($this->maybeHandleExport($report, $comparison, $snapshotMeta)) {
            return;
        }

        $exportNonce = $this->getExportNonce();

        $this->render_view('css-performance', [
            'active_metrics'       => $report['active'],
            'tokens_metrics'       => $report['tokens'],
            'combined_metrics'     => $report['combined'],
            'warnings'             => $report['warnings'],
            'recommendations'      => $report['recommendations'],
            'comparison'           => $comparison,
            'snapshot_meta'        => $snapshotMeta,
            'export_urls'          => $this->buildExportUrls($exportNonce),
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

    private function maybeHandleExport(array $report, ?array $comparison, ?array $snapshotMeta): bool
    {
        $export = isset($_GET['ssc_export']) ? sanitize_key((string) $_GET['ssc_export']) : '';

        if ($export === '') {
            return false;
        }

        $capability = function_exists('ssc_get_required_capability') ? ssc_get_required_capability() : 'manage_options';

        if (!function_exists('current_user_can') || !current_user_can($capability)) {
            wp_die(__('Vous n’avez pas l’autorisation d’exporter ce rapport.', 'supersede-css-jlg'));
        }

        $nonce = isset($_GET['ssc_export_nonce']) ? (string) $_GET['ssc_export_nonce'] : '';
        if (function_exists('wp_verify_nonce') && !wp_verify_nonce($nonce, 'ssc_css_performance_export')) {
            wp_die(__('Jeton de sécurité invalide pour l’export.', 'supersede-css-jlg'));
        }

        $siteName = function_exists('get_bloginfo') ? (string) get_bloginfo('name', 'display') : '';
        $siteUrl  = function_exists('home_url') ? (string) home_url('/') : '';
        $timestamp = time();

        $payload = \SSC\Support\CssPerformanceReportExporter::buildPayload([
            'active'          => $report['active'],
            'tokens'          => $report['tokens'],
            'combined'        => $report['combined'],
            'warnings'        => $report['warnings'],
            'recommendations' => $report['recommendations'],
            'comparison'      => $comparison,
            'snapshot_meta'   => $snapshotMeta,
            'site'            => [
                'name' => $siteName,
                'url'  => $siteUrl,
            ],
            'generated_at'    => $timestamp,
        ]);

        $filename = $this->buildFilename($export, $timestamp, $siteName);

        if (function_exists('nocache_headers')) {
            nocache_headers();
        }

        if ($export === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
            echo function_exists('wp_json_encode') ? wp_json_encode($payload, $jsonFlags) : json_encode($payload, $jsonFlags);
            exit;
        }

        if ($export === 'markdown' || $export === 'md') {
            header('Content-Type: text/markdown; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo \SSC\Support\CssPerformanceReportExporter::buildMarkdown($payload);
            exit;
        }

        return false;
    }

    private function buildExportUrls(string $nonce): array
    {
        if (!function_exists('admin_url') || !function_exists('add_query_arg')) {
            return [
                'markdown' => '',
                'json'     => '',
            ];
        }

        $baseArgs = [
            'page' => self::PAGE_SLUG,
        ];

        if ($nonce !== '') {
            $baseArgs['ssc_export_nonce'] = $nonce;
        }

        return [
            'markdown' => add_query_arg(array_merge($baseArgs, ['ssc_export' => 'markdown']), admin_url('admin.php')),
            'json'     => add_query_arg(array_merge($baseArgs, ['ssc_export' => 'json']), admin_url('admin.php')),
        ];
    }

    private function getExportNonce(): string
    {
        return function_exists('wp_create_nonce') ? wp_create_nonce('ssc_css_performance_export') : '';
    }

    private function buildFilename(string $format, int $timestamp, string $siteName): string
    {
        $slug = $this->sanitizeFileSlug($siteName !== '' ? $siteName : 'supersede-css');
        $date = gmdate('Ymd-His', $timestamp);
        $extension = $format === 'json' ? 'json' : 'md';

        return sprintf('%s-css-performance-%s.%s', $slug, $date, $extension);
    }

    private function sanitizeFileSlug(string $label): string
    {
        if (function_exists('sanitize_title')) {
            $sanitized = sanitize_title($label);
            if ($sanitized !== '') {
                return $sanitized;
            }
        }

        $fallback = strtolower(preg_replace('~[^A-Za-z0-9]+~', '-', $label) ?? '');
        $fallback = trim($fallback, '-');

        return $fallback !== '' ? $fallback : 'supersede-css';
    }
}
