<?php declare(strict_types=1);

namespace SSC\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class CssPerformanceReportExporter
{
    private const METRIC_KEYS = [
        'empty',
        'size_bytes',
        'size_readable',
        'gzip_bytes',
        'gzip_readable',
        'rule_count',
        'selector_count',
        'declaration_count',
        'average_declarations',
        'important_count',
        'import_count',
        'atrule_count',
        'long_selectors',
        'duplicate_selectors',
        'max_declarations',
        'specificity_average',
        'specificity_max',
        'specificity_top',
        'custom_property_definitions',
        'custom_property_names',
        'custom_property_unique_count',
        'custom_property_references',
        'vendor_prefix_total',
        'vendor_prefixes',
    ];

    private const COMPARISON_META = [
        'size_bytes' => ['label' => 'Poids brut', 'type' => 'bytes'],
        'gzip_bytes' => ['label' => 'Poids gzip', 'type' => 'bytes'],
        'selector_count' => ['label' => 'Sélecteurs', 'type' => 'int'],
        'declaration_count' => ['label' => 'Déclarations', 'type' => 'int'],
        'important_count' => ['label' => '!important', 'type' => 'int'],
        'specificity_average' => ['label' => 'Spécificité moyenne', 'type' => 'float'],
        'specificity_max' => ['label' => 'Spécificité max', 'type' => 'int'],
        'custom_property_unique_count' => ['label' => 'Tokens CSS uniques', 'type' => 'int'],
        'vendor_prefix_total' => ['label' => 'Préfixes propriétaires', 'type' => 'int'],
    ];

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public static function buildPayload(array $context): array
    {
        $timestamp = isset($context['generated_at']) ? (int) $context['generated_at'] : time();

        $siteContext = is_array($context['site'] ?? null) ? $context['site'] : [];
        $siteName = isset($siteContext['name']) && is_string($siteContext['name']) ? $siteContext['name'] : '';
        $siteUrl  = isset($siteContext['url']) && is_string($siteContext['url']) ? $siteContext['url'] : '';

        $payload = [
            'generated_at'      => $timestamp,
            'generated_at_iso'  => gmdate('c', $timestamp),
            'site'              => [
                'name' => $siteName,
                'url'  => $siteUrl,
            ],
            'metrics'           => [
                'active'   => self::filterMetrics($context['active'] ?? []),
                'tokens'   => self::filterMetrics($context['tokens'] ?? []),
                'combined' => self::filterMetrics($context['combined'] ?? []),
            ],
            'warnings'          => self::normalizeList($context['warnings'] ?? []),
            'recommendations'   => self::normalizeList($context['recommendations'] ?? []),
            'comparison'        => self::normalizeComparison($context['comparison'] ?? null),
            'snapshot_meta'     => self::normalizeSnapshotMeta($context['snapshot_meta'] ?? null),
        ];

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function buildMarkdown(array $payload): string
    {
        $lines = [];

        $lines[] = '# Rapport de performance CSS';
        $lines[] = '';

        $siteName = trim((string) ($payload['site']['name'] ?? ''));
        $siteUrl  = trim((string) ($payload['site']['url'] ?? ''));
        $generatedAt = isset($payload['generated_at']) ? (int) $payload['generated_at'] : time();
        $generatedLabel = gmdate('Y-m-d H:i \U\T\C', $generatedAt);

        if ($siteName !== '') {
            $lines[] = sprintf('- Site : %s', $siteName);
        }

        if ($siteUrl !== '') {
            $lines[] = sprintf('- URL : %s', $siteUrl);
        }

        $lines[] = sprintf('- Généré le : %s', $generatedLabel);
        $lines[] = '';

        $combined = is_array($payload['metrics']['combined'] ?? null) ? $payload['metrics']['combined'] : [];
        $lines[] = '## Résumé';
        $lines[] = '';
        $lines[] = sprintf('- Poids total : %s (%s gzip)', self::formatBytes((int) ($combined['size_bytes'] ?? 0)), self::formatBytes((int) ($combined['gzip_bytes'] ?? 0)));
        $lines[] = sprintf('- Règles / Déclarations : %s / %s', self::formatNumber((int) ($combined['rule_count'] ?? 0)), self::formatNumber((int) ($combined['declaration_count'] ?? 0)));
        $lines[] = sprintf('- Sélecteurs uniques : %s', self::formatNumber((int) ($combined['selector_count'] ?? 0)));
        $lines[] = sprintf('- Occurrences de !important : %s', self::formatNumber((int) ($combined['important_count'] ?? 0)));
        $lines[] = '';

        $warnings = is_array($payload['warnings'] ?? null) ? $payload['warnings'] : [];
        $lines[] = '## Points de vigilance';
        $lines[] = '';
        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                $lines[] = sprintf('- %s', $warning);
            }
        } else {
            $lines[] = '- Aucun avertissement critique détecté.';
        }
        $lines[] = '';

        $recommendations = is_array($payload['recommendations'] ?? null) ? $payload['recommendations'] : [];
        $lines[] = '## Recommandations';
        $lines[] = '';
        if (!empty($recommendations)) {
            foreach ($recommendations as $recommendation) {
                $lines[] = sprintf('- %s', $recommendation);
            }
        } else {
            $lines[] = '- Aucune recommandation supplémentaire à ce stade.';
        }
        $lines[] = '';

        $comparison = is_array($payload['comparison'] ?? null) ? $payload['comparison'] : null;
        if (!empty($comparison)) {
            $alerts = is_array($comparison['alerts'] ?? null) ? $comparison['alerts'] : [];
            $lines[] = '## Comparaison avec le snapshot précédent';
            $lines[] = '';
            if (!empty($alerts)) {
                $lines[] = '### Variations notables';
                $lines[] = '';
                foreach ($alerts as $alert) {
                    $lines[] = sprintf('- %s', $alert);
                }
                $lines[] = '';
            }

            $rows = self::buildComparisonRows($comparison);
            if (!empty($rows)) {
                $lines[] = '| Indicateur | Précédent | Actuel | Écart | Variation % |';
                $lines[] = '| --- | --- | --- | --- | --- |';
                foreach ($rows as $row) {
                    $lines[] = sprintf(
                        '| %s | %s | %s | %s | %s |',
                        $row['label'],
                        $row['previous'],
                        $row['current'],
                        $row['delta'],
                        $row['percent']
                    );
                }
                $lines[] = '';
            }
        }

        $specificityTop = is_array($combined['specificity_top'] ?? null) ? $combined['specificity_top'] : [];
        if (!empty($specificityTop)) {
            $lines[] = '## Sélecteurs à forte spécificité';
            $lines[] = '';
            foreach ($specificityTop as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $selector = isset($entry['selector']) ? (string) $entry['selector'] : '';
                if ($selector === '') {
                    continue;
                }

                $score   = isset($entry['score']) ? self::formatNumber((int) $entry['score']) : '0';
                $vector  = isset($entry['vector']) ? (string) $entry['vector'] : '';

                if ($vector !== '') {
                    $lines[] = sprintf('- `%s` — score %s (%s)', $selector, $score, $vector);
                } else {
                    $lines[] = sprintf('- `%s` — score %s', $selector, $score);
                }
            }
            $lines[] = '';
        }

        $customProperties = (int) ($combined['custom_property_unique_count'] ?? 0);
        $customUsage      = (int) ($combined['custom_property_definitions'] ?? 0);
        $customReferences = (int) ($combined['custom_property_references'] ?? 0);
        $lines[] = '## Tokens & variables CSS';
        $lines[] = '';
        $lines[] = sprintf('- Tokens uniques détectés : %s', self::formatNumber($customProperties));
        $lines[] = sprintf('- Définitions locales : %s', self::formatNumber($customUsage));
        $lines[] = sprintf('- Références var() : %s', self::formatNumber($customReferences));
        $lines[] = '';

        $prefixes = is_array($combined['vendor_prefixes'] ?? null) ? $combined['vendor_prefixes'] : [];
        if (!empty($prefixes)) {
            $lines[] = '## Préfixes propriétaires relevés';
            $lines[] = '';
            foreach ($prefixes as $prefix) {
                if (!is_array($prefix)) {
                    continue;
                }

                $label = isset($prefix['prefix']) ? (string) $prefix['prefix'] : '';
                if ($label === '') {
                    continue;
                }

                $count = isset($prefix['count']) ? self::formatNumber((int) $prefix['count']) : '0';
                $lines[] = sprintf('- `%s` : %s occurrences', $label, $count);
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $metrics
     *
     * @return array<string, mixed>
     */
    private static function filterMetrics($metrics): array
    {
        if (!is_array($metrics)) {
            return [];
        }

        $filtered = [];

        foreach (self::METRIC_KEYS as $key) {
            if (!array_key_exists($key, $metrics)) {
                continue;
            }

            $filtered[$key] = $metrics[$key];
        }

        return $filtered;
    }

    /**
     * @param mixed $list
     *
     * @return list<string>
     */
    private static function normalizeList($list): array
    {
        if (!is_array($list)) {
            return [];
        }

        $items = [];

        foreach ($list as $entry) {
            if (!is_string($entry)) {
                continue;
            }

            $value = trim($entry);
            if ($value === '') {
                continue;
            }

            $items[] = $value;
        }

        return array_values(array_unique($items));
    }

    /**
     * @param mixed $comparison
     *
     * @return array<string, mixed>|null
     */
    private static function normalizeComparison($comparison): ?array
    {
        if (!is_array($comparison)) {
            return null;
        }

        $normalized = [];

        foreach (self::COMPARISON_META as $key => $meta) {
            if (!is_array($comparison[$key] ?? null)) {
                continue;
            }

            $entry = $comparison[$key];
            $normalized[$key] = [
                'previous' => $entry['previous'] ?? 0,
                'current'  => $entry['current'] ?? 0,
                'delta'    => $entry['delta'] ?? 0,
                'percent'  => $entry['percent'] ?? null,
            ];
        }

        if (!empty($comparison['alerts']) && is_array($comparison['alerts'])) {
            $normalized['alerts'] = self::normalizeList($comparison['alerts']);
        }

        if (empty($normalized)) {
            return null;
        }

        return $normalized;
    }

    /**
     * @param mixed $snapshot
     *
     * @return array<string, mixed>|null
     */
    private static function normalizeSnapshotMeta($snapshot): ?array
    {
        if (!is_array($snapshot)) {
            return null;
        }

        $timestamp = isset($snapshot['timestamp']) ? (int) $snapshot['timestamp'] : null;
        $active    = isset($snapshot['active_hash']) ? (string) $snapshot['active_hash'] : null;
        $tokens    = isset($snapshot['tokens_hash']) ? (string) $snapshot['tokens_hash'] : null;

        if ($timestamp === null && $active === null && $tokens === null) {
            return null;
        }

        return array_filter([
            'timestamp'   => $timestamp,
            'active_hash' => $active,
            'tokens_hash' => $tokens,
        ], static fn($value) => $value !== null && $value !== '');
    }

    /**
     * @param array<string, mixed> $comparison
     *
     * @return list<array{label:string, previous:string, current:string, delta:string, percent:string}>
     */
    private static function buildComparisonRows(array $comparison): array
    {
        $rows = [];

        foreach (self::COMPARISON_META as $key => $meta) {
            if (!is_array($comparison[$key] ?? null)) {
                continue;
            }

            $entry = $comparison[$key];

            $rows[] = [
                'label'   => $meta['label'],
                'previous'=> self::formatValueForType($entry['previous'] ?? 0, $meta['type']),
                'current' => self::formatValueForType($entry['current'] ?? 0, $meta['type']),
                'delta'   => self::formatDelta($entry['delta'] ?? 0, $meta['type']),
                'percent' => self::formatPercent($entry['percent'] ?? null),
            ];
        }

        return $rows;
    }

    /**
     * @param string $type
     * @param mixed  $value
     */
    private static function formatValueForType($value, string $type): string
    {
        if ($type === 'bytes') {
            return self::formatBytes((int) round((float) $value));
        }

        if ($type === 'float') {
            return self::formatFloat((float) $value, 1);
        }

        return self::formatNumber((int) round((float) $value));
    }

    /**
     * @param mixed  $delta
     * @param string $type
     */
    private static function formatDelta($delta, string $type): string
    {
        $value = (float) $delta;

        if (abs($value) < 0.0001) {
            return '±0';
        }

        $prefix = $value > 0 ? '+' : '−';

        if ($type === 'bytes') {
            return $prefix . self::formatBytes((int) round(abs($value)));
        }

        if ($type === 'float') {
            return $prefix . self::formatFloat(abs($value), 1);
        }

        return $prefix . self::formatNumber((int) round(abs($value)));
    }

    /**
     * @param mixed $percent
     */
    private static function formatPercent($percent): string
    {
        if (!is_numeric($percent)) {
            return 'N/A';
        }

        $value = (float) $percent;
        if (abs($value) < 0.0001) {
            return '0.0%';
        }

        $prefix = $value > 0 ? '+' : '−';

        return sprintf('%s%s%%', $prefix, self::formatFloat(abs($value), 1));
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB'];
        $power = (int) floor(log($bytes, 1024));
        $power = max(0, min($power, count($units) - 1));

        if ($power === 0) {
            return sprintf('%s %s', self::formatNumber($bytes), $units[$power]);
        }

        $value = $bytes / (1024 ** $power);

        return sprintf('%s %s', self::formatFloat($value, 2), $units[$power]);
    }

    private static function formatNumber(int $value): string
    {
        return number_format($value, 0, '.', ' ');
    }

    private static function formatFloat(float $value, int $precision): string
    {
        return number_format($value, $precision, '.', ' ');
    }
}

