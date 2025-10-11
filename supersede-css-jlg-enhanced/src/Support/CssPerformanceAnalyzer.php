<?php declare(strict_types=1);

namespace SSC\Support;

if (!defined('ABSPATH')) {
    exit;
}

class CssPerformanceAnalyzer
{
    private const LONG_SELECTOR_THRESHOLD = 80;
    private const MAX_COMPLEX_SELECTORS   = 8;
    private const MAX_DUPLICATE_SELECTORS = 8;
    private const MAX_SPECIFICITY_ITEMS   = 5;

    /** @var string[] */
    private const VENDOR_PREFIXES = ['-webkit-', '-moz-', '-ms-', '-o-'];

    public function analyzePair(string $activeCss, string $tokensCss): array
    {
        $active  = $this->analyze($activeCss);
        $tokens  = $this->analyze($tokensCss);
        $combined = $this->combine($active, $tokens);

        return [
            'active'          => $active,
            'tokens'          => $tokens,
            'combined'        => $combined,
            'warnings'        => $this->collectWarnings($active, $tokens, $combined),
            'recommendations' => $this->buildRecommendations($active, $tokens, $combined),
        ];
    }

    public function analyze(string $css): array
    {
        $raw = $css;
        $css = trim($css);
        if ($css === '') {
            return [
                'empty'                    => true,
                'size_bytes'               => 0,
                'size_readable'            => $this->formatBytes(0),
                'gzip_bytes'               => null,
                'gzip_readable'            => __('N/A', 'supersede-css-jlg'),
                'rule_count'               => 0,
                'selector_count'           => 0,
                'declaration_count'        => 0,
                'average_declarations'     => 0,
                'important_count'          => 0,
                'import_count'             => 0,
                'atrule_count'             => 0,
                'long_selectors'           => [],
                'duplicate_selectors'      => [],
                'max_declarations'         => 0,
                'raw_sample'               => '',
                'specificity_top'          => [],
                'specificity_average'      => 0.0,
                'specificity_max'          => 0,
                'specificity_sum'          => 0.0,
                'custom_property_definitions' => 0,
                'custom_property_names'       => [],
                'custom_property_references'  => 0,
                'custom_property_unique_count'=> 0,
                'vendor_prefixes'          => [],
                'vendor_prefix_total'      => 0,
            ];
        }

        $sizeBytes = strlen($raw);
        $gzipBytes = function_exists('gzencode') ? strlen((string) gzencode($raw, 9)) : null;

        $withoutComments = preg_replace('~/\*.*?\*/~s', '', $raw);
        if (!is_string($withoutComments)) {
            $withoutComments = $raw;
        }

        $ruleCount        = 0;
        $selectorCount    = 0;
        $declarationCount = 0;
        $importantCount   = 0;
        $maxDeclarations  = 0;
        $longSelectors    = [];
        $selectorUsage    = [];
        $specificitySum   = 0.0;
        $specificityMax   = 0;
        $specificityEntries = [];
        $customPropertyDefinitions = 0;
        $customPropertyNames       = [];
        $customPropertyReferences  = 0;
        $vendorPrefixUsage         = array_fill_keys(self::VENDOR_PREFIXES, 0);
        $vendorPrefixTotal         = 0;

        $pattern = '/([^\{]+)\{([^\{\}]*)\}/m';
        if (preg_match_all($pattern, $withoutComments, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $rawSelectors = trim($match[1]);
                $block        = trim($match[2]);

                if ($rawSelectors === '') {
                    continue;
                }

                $selectors = array_values(array_filter(array_map('trim', explode(',', $rawSelectors)), static fn($selector) => $selector !== ''));

                if (empty($selectors)) {
                    continue;
                }

                $ruleCount++;

                $declarations = preg_split('/;/', $block) ?: [];
                $ruleDeclarations = 0;

                foreach ($declarations as $declaration) {
                    $declaration = trim($declaration);
                    if ($declaration === '' || strpos($declaration, ':') === false) {
                        continue;
                    }

                    $ruleDeclarations++;
                    $declarationCount++;

                    if (stripos($declaration, '!important') !== false) {
                        $importantCount++;
                    }

                    [$property, $value] = array_pad(explode(':', $declaration, 2), 2, '');
                    $property = trim($property);
                    $value    = trim($value);

                    if ($property !== '' && str_starts_with($property, '--')) {
                        $customPropertyDefinitions++;
                        $customPropertyNames[$property] = true;
                    }

                    if ($value !== '' && stripos($value, 'var(') !== false) {
                        $customPropertyReferences++;
                    }

                    foreach (self::VENDOR_PREFIXES as $prefix) {
                        if (($property !== '' && str_starts_with($property, $prefix)) || ($value !== '' && str_contains($value, $prefix))) {
                            $vendorPrefixUsage[$prefix]++;
                            $vendorPrefixTotal++;
                        }
                    }
                }

                if ($ruleDeclarations > $maxDeclarations) {
                    $maxDeclarations = $ruleDeclarations;
                }

                foreach ($selectors as $selector) {
                    $selectorCount++;
                    $length = strlen($selector);
                    if ($length >= self::LONG_SELECTOR_THRESHOLD && count($longSelectors) < self::MAX_COMPLEX_SELECTORS) {
                        $longSelectors[] = [
                            'selector' => $selector,
                            'length'   => $length,
                        ];
                    }

                    $key = strtolower($selector);
                    if (!isset($selectorUsage[$key])) {
                        $selectorUsage[$key] = [
                            'count' => 0,
                            'label' => $selector,
                        ];
                    }
                    $selectorUsage[$key]['count']++;

                    $specificityData   = $this->computeSpecificity($selector);
                    $specificitySum   += $specificityData['score'];
                    $specificityMax    = max($specificityMax, $specificityData['score']);
                    $specificityEntries[] = array_merge(['selector' => $selector], $specificityData);
                }
            }
        }

        $duplicateSelectors = [];
        foreach ($selectorUsage as $selectorData) {
            if ($selectorData['count'] <= 1) {
                continue;
            }

            if (count($duplicateSelectors) >= self::MAX_DUPLICATE_SELECTORS) {
                break;
            }

            $duplicateSelectors[] = [
                'selector' => $selectorData['label'],
                'count'    => $selectorData['count'],
            ];
        }

        $importCount = preg_match_all('/@import\b/i', $withoutComments, $importMatches) ?: 0;
        unset($importMatches);
        $atruleCount = preg_match_all('/@(media|supports|container|layer|keyframes|font-face)\b/i', $withoutComments, $atruleMatches) ?: 0;
        unset($atruleMatches);

        $specificityTop = $this->limitSpecificityEntries($specificityEntries);

        $vendorPrefixes = [];
        foreach ($vendorPrefixUsage as $prefix => $count) {
            if ($count <= 0) {
                continue;
            }

            $vendorPrefixes[] = [
                'prefix' => $prefix,
                'count'  => $count,
            ];
        }

        usort($vendorPrefixes, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);

        $customNames = array_keys($customPropertyNames);

        return [
            'empty'               => false,
            'size_bytes'          => $sizeBytes,
            'size_readable'       => $this->formatBytes($sizeBytes),
            'gzip_bytes'          => $gzipBytes,
            'gzip_readable'       => $gzipBytes === null ? __('N/A', 'supersede-css-jlg') : $this->formatBytes($gzipBytes),
            'rule_count'          => $ruleCount,
            'selector_count'      => $selectorCount,
            'declaration_count'   => $declarationCount,
            'average_declarations'=> $ruleCount > 0 ? $this->formatRatio($declarationCount / $ruleCount) : '0',
            'important_count'     => $importantCount,
            'import_count'        => $importCount,
            'atrule_count'        => $atruleCount,
            'long_selectors'      => $longSelectors,
            'duplicate_selectors' => $duplicateSelectors,
            'max_declarations'    => $maxDeclarations,
            'raw_sample'          => $this->buildSample($raw),
            'specificity_top'     => $specificityTop,
            'specificity_average' => $selectorCount > 0 ? $specificitySum / $selectorCount : 0.0,
            'specificity_max'     => $specificityMax,
            'specificity_sum'     => $specificitySum,
            'custom_property_definitions' => $customPropertyDefinitions,
            'custom_property_names'       => $customNames,
            'custom_property_references'  => $customPropertyReferences,
            'custom_property_unique_count'=> count($customNames),
            'vendor_prefixes'     => $vendorPrefixes,
            'vendor_prefix_total' => $vendorPrefixTotal,
        ];
    }

    public function combine(array $active, array $tokens): array
    {
        $sizeBytes = $active['size_bytes'] + $tokens['size_bytes'];
        $gzipBytes = null;
        if ($active['gzip_bytes'] !== null || $tokens['gzip_bytes'] !== null) {
            $gzipBytes = (int) ($active['gzip_bytes'] ?? 0) + (int) ($tokens['gzip_bytes'] ?? 0);
        }

        $customPropertyNames = array_values(array_unique(array_merge($active['custom_property_names'], $tokens['custom_property_names'])));

        return [
            'size_bytes'        => $sizeBytes,
            'size_readable'     => $this->formatBytes($sizeBytes),
            'gzip_bytes'        => $gzipBytes,
            'gzip_readable'     => $gzipBytes === null ? __('N/A', 'supersede-css-jlg') : $this->formatBytes($gzipBytes),
            'rule_count'        => $active['rule_count'] + $tokens['rule_count'],
            'selector_count'    => $active['selector_count'] + $tokens['selector_count'],
            'declaration_count' => $active['declaration_count'] + $tokens['declaration_count'],
            'important_count'   => $active['important_count'] + $tokens['important_count'],
            'import_count'      => $active['import_count'] + $tokens['import_count'],
            'atrule_count'      => $active['atrule_count'] + $tokens['atrule_count'],
            'specificity_sum'   => $active['specificity_sum'] + $tokens['specificity_sum'],
            'specificity_average'=> ($active['selector_count'] + $tokens['selector_count']) > 0
                ? ($active['specificity_sum'] + $tokens['specificity_sum']) / ($active['selector_count'] + $tokens['selector_count'])
                : 0.0,
            'specificity_max'   => max($active['specificity_max'], $tokens['specificity_max']),
            'specificity_top'   => $this->mergeSpecificityLists($active['specificity_top'], $tokens['specificity_top']),
            'custom_property_definitions' => $active['custom_property_definitions'] + $tokens['custom_property_definitions'],
            'custom_property_references'  => $active['custom_property_references'] + $tokens['custom_property_references'],
            'custom_property_names'       => $customPropertyNames,
            'custom_property_unique_count'=> count($customPropertyNames),
            'vendor_prefix_total'=> $active['vendor_prefix_total'] + $tokens['vendor_prefix_total'],
            'vendor_prefixes'   => $this->mergeVendorPrefixes($active['vendor_prefixes'], $tokens['vendor_prefixes']),
        ];
    }

    public function compareSnapshots(array $baseline, array $candidate): array
    {
        $diff = [
            'size_bytes'                 => $this->buildDelta($baseline, $candidate, 'size_bytes'),
            'gzip_bytes'                 => $this->buildDelta($baseline, $candidate, 'gzip_bytes'),
            'selector_count'             => $this->buildDelta($baseline, $candidate, 'selector_count'),
            'declaration_count'          => $this->buildDelta($baseline, $candidate, 'declaration_count'),
            'important_count'            => $this->buildDelta($baseline, $candidate, 'important_count'),
            'specificity_average'        => $this->buildDelta($baseline, $candidate, 'specificity_average', true),
            'specificity_max'            => $this->buildDelta($baseline, $candidate, 'specificity_max'),
            'custom_property_unique_count'=> $this->buildDelta($baseline, $candidate, 'custom_property_unique_count'),
            'vendor_prefix_total'        => $this->buildDelta($baseline, $candidate, 'vendor_prefix_total'),
        ];

        $diff['alerts'] = $this->buildComparisonAlerts($diff);

        return $diff;
    }

    /**
     * @return list<string>
     */
    public function collectWarnings(array $active, array $tokens, array $combined): array
    {
        $warnings = [];

        if ($combined['size_bytes'] > 180 * 1024) {
            $warnings[] = __('Le CSS total dépasse 180 Ko. Pensez à supprimer les règles inutilisées ou à fractionner vos feuilles de style.', 'supersede-css-jlg');
        }

        if ($active['import_count'] > 0 || $tokens['import_count'] > 0) {
            $warnings[] = __('La présence de règles @import peut dégrader les performances car elles bloquent le rendu. Envisagez d’intégrer ces fichiers dans le build principal.', 'supersede-css-jlg');
        }

        if ($active['important_count'] + $tokens['important_count'] > 12) {
            $warnings[] = __('Vous utilisez de nombreux !important. Essayez de renforcer la spécificité ou d’ajuster l’ordre de vos déclarations plutôt que de multiplier ces overrides.', 'supersede-css-jlg');
        }

        if (!empty($active['long_selectors']) || !empty($tokens['long_selectors'])) {
            $warnings[] = __('Certains sélecteurs dépassent 80 caractères. Des sélecteurs trop complexes sont difficiles à maintenir et peuvent impacter les performances du moteur CSS.', 'supersede-css-jlg');
        }

        if (!empty($active['duplicate_selectors']) || !empty($tokens['duplicate_selectors'])) {
            $warnings[] = __('Des sélecteurs apparaissent plusieurs fois. Vérifiez s’il est possible de factoriser ces règles pour réduire le poids du CSS.', 'supersede-css-jlg');
        }

        if (($combined['specificity_max'] ?? 0) >= 400) {
            $warnings[] = __('Certains sélecteurs dépassent un score de spécificité de 400. Les overrides seront difficiles à maintenir sans réorganisation de la cascade.', 'supersede-css-jlg');
        }

        if (($combined['specificity_average'] ?? 0) > 120) {
            $warnings[] = __('La spécificité moyenne est élevée. Envisagez de simplifier vos sélecteurs ou d’introduire une architecture BEM/ITCSS.', 'supersede-css-jlg');
        }

        if (($combined['vendor_prefix_total'] ?? 0) > 12) {
            $warnings[] = __('De nombreux préfixes propriétaires sont présents. Vérifiez votre pipeline PostCSS/Autoprefixer pour éviter la régression de compatibilité.', 'supersede-css-jlg');
        }

        if (($combined['custom_property_references'] ?? 0) > 0 && ($combined['custom_property_definitions'] ?? 0) === 0) {
            $warnings[] = __('Des appels à var() existent sans définition de tokens locaux. Confirmez que les variables globales sont chargées avant ce CSS.', 'supersede-css-jlg');
        }

        return $warnings;
    }

    /**
     * @return list<string>
     */
    public function buildRecommendations(array $active, array $tokens, array $combined): array
    {
        $recommendations = [];

        if ($combined['size_bytes'] > 150 * 1024) {
            $recommendations[] = __('Activez la purge des classes inutilisées dans votre thème ou vos builds Tailwind/Supersede pour réduire le CSS livré.', 'supersede-css-jlg');
        }

        if ($combined['important_count'] > 0) {
            $recommendations[] = __('Cartographiez les composants qui utilisent !important pour vérifier si une meilleure architecture de tokens ou d’ordonnancement résout les conflits.', 'supersede-css-jlg');
        }

        if ($combined['atrule_count'] > 0 && $combined['selector_count'] > 0) {
            $ratio = $combined['atrule_count'] / max(1, $combined['selector_count']);
            if ($ratio > 0.3) {
                $recommendations[] = __('Vos feuilles contiennent beaucoup de media queries. Assurez-vous que les breakpoints sont mutualisés via les tokens responsive.', 'supersede-css-jlg');
            }
        }

        if (!$active['empty'] && $active['rule_count'] === 0) {
            $recommendations[] = __('Le CSS actif est non structuré ou invalide. Lancez une validation CSS pour détecter les erreurs de syntaxe.', 'supersede-css-jlg');
        }

        if (!$tokens['empty'] && $tokens['rule_count'] === 0 && $tokens['selector_count'] === 0) {
            $recommendations[] = __('Vos tokens ne génèrent pas de règles directes. Pensez à vérifier leur injection dans vos presets ou modules Utilities.', 'supersede-css-jlg');
        }

        if (($combined['specificity_average'] ?? 0) > 100) {
            $recommendations[] = __('Cartographiez les composants critiques et introduisez des couches (ITCSS, Cascade Layers) pour contenir la spécificité.', 'supersede-css-jlg');
        }

        if (($combined['custom_property_definitions'] ?? 0) > 0) {
            $recommendations[] = __('Documentez vos tokens CSS (noms, portée, fallback) et alimentez le Design System afin que les équipes puissent les réutiliser.', 'supersede-css-jlg');
        }

        if (($combined['vendor_prefix_total'] ?? 0) > 0) {
            $recommendations[] = __('Configurez Autoprefixer/Browserslist sur vos builds Supersede pour n’émettre que les préfixes nécessaires aux navigateurs ciblés.', 'supersede-css-jlg');
        }

        return $recommendations;
    }

    private function buildDelta(array $baseline, array $candidate, string $key, bool $allowFloat = false): array
    {
        $previous = $this->extractNumericValue($baseline[$key] ?? 0, $allowFloat);
        $current  = $this->extractNumericValue($candidate[$key] ?? 0, $allowFloat);
        $delta    = $current - $previous;
        $percent  = $this->calculatePercentChange($previous, $delta);

        return [
            'previous' => $allowFloat ? $previous : (int) round($previous),
            'current'  => $allowFloat ? $current : (int) round($current),
            'delta'    => $allowFloat ? $delta : (int) round($delta),
            'percent'  => $percent,
        ];
    }

    private function extractNumericValue($value, bool $allowFloat): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value) && $value !== '') {
            $normalized = str_replace([' ', ','], ['', '.'], $value);
            if (is_numeric($normalized)) {
                return (float) $normalized;
            }
        }

        return 0.0;
    }

    private function calculatePercentChange(float $previous, float $delta): ?float
    {
        if (abs($delta) < 0.0001 && abs($previous) < 0.0001) {
            return 0.0;
        }

        if (abs($previous) < 0.0001) {
            return null;
        }

        return ($delta / $previous) * 100.0;
    }

    private function buildComparisonAlerts(array $diff): array
    {
        $alerts = [];

        $size = $diff['size_bytes'];
        if ($size['delta'] > 8 * 1024 || ($size['percent'] !== null && $size['percent'] > 10)) {
            $alerts[] = __('Le poids total a augmenté sensiblement depuis le dernier snapshot. Identifiez les composants récents et planifiez une purge ciblée.', 'supersede-css-jlg');
        }

        $gzip = $diff['gzip_bytes'];
        if ($gzip['delta'] > 4 * 1024 || ($gzip['percent'] !== null && $gzip['percent'] > 10)) {
            $alerts[] = __('Le poids gzip progresse fortement, pensez à purger les classes inutilisées ou à activer la minification côté build.', 'supersede-css-jlg');
        }

        $selectors = $diff['selector_count'];
        if ($selectors['delta'] > 50) {
            $alerts[] = __('Beaucoup de sélecteurs supplémentaires ont été ajoutés. Vérifiez l’impact des nouveaux presets ou modules activés.', 'supersede-css-jlg');
        }

        $declarations = $diff['declaration_count'];
        if ($declarations['delta'] > 100) {
            $alerts[] = __('Le nombre de déclarations explose. Assurez-vous que les composants sont factorisés et que la génération ne duplique pas les styles.', 'supersede-css-jlg');
        }

        $important = $diff['important_count'];
        if ($important['delta'] > 3) {
            $alerts[] = __('Les overrides !important se multiplient. Analysez la cascade pour éviter des conflits durables.', 'supersede-css-jlg');
        }

        $specificityMax = $diff['specificity_max'];
        if ($specificityMax['delta'] > 50) {
            $alerts[] = __('La spécificité maximale progresse. Cartographiez les composants concernés avant que la dette ne devienne ingérable.', 'supersede-css-jlg');
        }

        $specificityAverage = $diff['specificity_average'];
        if ($specificityAverage['percent'] !== null && $specificityAverage['percent'] > 15) {
            $alerts[] = __('La spécificité moyenne augmente rapidement. Songez à introduire des layers ou une architecture utilitaire.', 'supersede-css-jlg');
        }

        $vendorPrefixes = $diff['vendor_prefix_total'];
        if ($vendorPrefixes['delta'] > 10) {
            $alerts[] = __('Davantage de préfixes propriétaires sont émis. Vérifiez votre configuration Browserslist/Autoprefixer.', 'supersede-css-jlg');
        }

        $customProperties = $diff['custom_property_unique_count'];
        if ($customProperties['delta'] < -10) {
            $alerts[] = __('Des tokens CSS semblent avoir disparu. Confirmez que les presets attendus sont toujours synchronisés.', 'supersede-css-jlg');
        }

        return $alerts;
    }

    private function buildSample(string $css): string
    {
        $css = trim($css);
        if ($css === '') {
            return '';
        }

        $normalized = preg_replace('~\s+~', ' ', $css);
        if (!is_string($normalized)) {
            $normalized = $css;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($normalized) : strlen($normalized);
        $snippet = function_exists('mb_substr') ? mb_substr($normalized, 0, 320) : substr($normalized, 0, 320);

        return $snippet . ($length > 320 ? '…' : '');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return sprintf('%s %s', $this->formatNumber($value), $units[$power]);
    }

    private function formatNumber(float $number): string
    {
        if (abs($number - round($number)) < 0.01) {
            $number = round($number);
        } else {
            $number = round($number, 2);
        }

        $decimals = is_float($number) && floor($number) !== $number ? 2 : 0;

        if (function_exists('number_format_i18n')) {
            return number_format_i18n($number, $decimals);
        }

        return number_format($number, $decimals, '.', ' ');
    }

    private function formatRatio(float $value): string
    {
        $value = round($value, 1);
        $decimals = 1;

        if (function_exists('number_format_i18n')) {
            return number_format_i18n($value, $decimals);
        }

        return number_format($value, $decimals, '.', ' ');
    }

    private function computeSpecificity(string $selector): array
    {
        $selector = trim($selector);
        if ($selector === '') {
            return [
                'ids'     => 0,
                'classes' => 0,
                'types'   => 0,
                'score'   => 0,
                'vector'  => '0,0,0',
            ];
        }

        $working = preg_replace('/::[\w-]+/', ' ', $selector);
        if (!is_string($working)) {
            $working = $selector;
        }

        $idCount = preg_match_all('/#[A-Za-z0-9_-]+/', $working, $idMatches) ?: 0;
        unset($idMatches);
        $classLikeCount = preg_match_all('/(\.[A-Za-z0-9_-]+|\[[^\]]+\]|:[A-Za-z0-9_-]+(?:\([^\)]*\))?)/', $working, $classMatches) ?: 0;
        unset($classMatches);

        $withoutClassLike = preg_replace('/(\.[A-Za-z0-9_-]+|\[[^\]]+\]|:[A-Za-z0-9_-]+(?:\([^\)]*\))?)/', ' ', $working);
        if (!is_string($withoutClassLike)) {
            $withoutClassLike = $working;
        }

        $withoutIds = preg_replace('/#[A-Za-z0-9_-]+/', ' ', $withoutClassLike);
        if (!is_string($withoutIds)) {
            $withoutIds = $withoutClassLike;
        }

        $withoutUniversal = preg_replace('/\*/', ' ', $withoutIds);
        if (!is_string($withoutUniversal)) {
            $withoutUniversal = $withoutIds;
        }

        $tokens = preg_split('/[\s>+~]+/', trim((string) $withoutUniversal)) ?: [];
        $typeCount = 0;

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '' || $token === '&') {
                continue;
            }

            $token = (string) preg_replace('/^[A-Za-z0-9_-]+\|/', '', $token);
            if ($token === '') {
                continue;
            }

            if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $token)) {
                continue;
            }

            $typeCount++;
        }

        $score = ($idCount * 100) + ($classLikeCount * 10) + $typeCount;

        return [
            'ids'     => $idCount,
            'classes' => $classLikeCount,
            'types'   => $typeCount,
            'score'   => $score,
            'vector'  => sprintf('%d,%d,%d', $idCount, $classLikeCount, $typeCount),
        ];
    }

    private function limitSpecificityEntries(array $entries): array
    {
        if (empty($entries)) {
            return [];
        }

        usort($entries, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice($entries, 0, self::MAX_SPECIFICITY_ITEMS);
    }

    private function mergeSpecificityLists(array $active, array $tokens): array
    {
        $merged = [];

        foreach ([$active, $tokens] as $list) {
            foreach ($list as $entry) {
                $key = $entry['selector'];
                if (!isset($merged[$key]) || $merged[$key]['score'] < $entry['score']) {
                    $merged[$key] = $entry;
                }
            }
        }

        return $this->limitSpecificityEntries(array_values($merged));
    }

    private function mergeVendorPrefixes(array $active, array $tokens): array
    {
        $counts = [];

        foreach ([$active, $tokens] as $list) {
            foreach ($list as $item) {
                $prefix = $item['prefix'];
                $counts[$prefix] = ($counts[$prefix] ?? 0) + (int) $item['count'];
            }
        }

        $results = [];
        foreach ($counts as $prefix => $count) {
            if ($count <= 0) {
                continue;
            }

            $results[] = [
                'prefix' => $prefix,
                'count'  => $count,
            ];
        }

        usort($results, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);

        return $results;
    }
}

