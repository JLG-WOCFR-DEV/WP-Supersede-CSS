<?php declare(strict_types=1);

namespace SSC\Admin\Pages;

use SSC\Admin\AbstractPage;

if (!defined('ABSPATH')) {
    exit;
}

class CssPerformance extends AbstractPage
{
    private const LONG_SELECTOR_THRESHOLD = 80;
    private const MAX_COMPLEX_SELECTORS = 8;
    private const MAX_DUPLICATE_SELECTORS = 8;

    public function render(): void
    {
        $activeCss  = $this->getOptionValue('ssc_active_css');
        $tokensCss  = $this->getOptionValue('ssc_tokens_css');

        $activeMetrics = $this->analyzeCss($activeCss);
        $tokensMetrics = $this->analyzeCss($tokensCss);

        $combined = $this->combineMetrics($activeMetrics, $tokensMetrics);
        $warnings = $this->collectWarnings($activeMetrics, $tokensMetrics, $combined);
        $recommendations = $this->buildRecommendations($activeMetrics, $tokensMetrics, $combined);

        $this->render_view('css-performance', [
            'active_metrics'       => $activeMetrics,
            'tokens_metrics'       => $tokensMetrics,
            'combined_metrics'     => $combined,
            'warnings'             => $warnings,
            'recommendations'      => $recommendations,
        ]);
    }

    private function analyzeCss(string $css): array
    {
        $raw = $css;
        $css = trim($css);
        if ($css === '') {
            return [
                'empty'              => true,
                'size_bytes'         => 0,
                'size_readable'      => $this->formatBytes(0),
                'gzip_bytes'         => null,
                'gzip_readable'      => __('N/A', 'supersede-css-jlg'),
                'rule_count'         => 0,
                'selector_count'     => 0,
                'declaration_count'  => 0,
                'average_declarations' => 0,
                'important_count'    => 0,
                'import_count'       => 0,
                'atrule_count'       => 0,
                'long_selectors'     => [],
                'duplicate_selectors'=> [],
                'max_declarations'   => 0,
                'raw_sample'         => '',
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
        $atruleCount = preg_match_all('/@(media|supports|container|layer|keyframes|font-face)\b/i', $withoutComments, $atruleMatches) ?: 0;

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
        ];
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

    private function combineMetrics(array $active, array $tokens): array
    {
        $sizeBytes = $active['size_bytes'] + $tokens['size_bytes'];
        $gzipBytes = null;
        if ($active['gzip_bytes'] !== null || $tokens['gzip_bytes'] !== null) {
            $gzipBytes = (int) ($active['gzip_bytes'] ?? 0) + (int) ($tokens['gzip_bytes'] ?? 0);
        }

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
        ];
    }

    private function collectWarnings(array $active, array $tokens, array $combined): array
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

        return $warnings;
    }

    private function buildRecommendations(array $active, array $tokens, array $combined): array
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

        return $recommendations;
    }

    private function getOptionValue(string $option): string
    {
        $value = get_option($option, '');
        return is_string($value) ? $value : '';
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
}
