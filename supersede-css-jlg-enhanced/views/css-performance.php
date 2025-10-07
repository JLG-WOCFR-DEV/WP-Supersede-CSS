<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var array $active_metrics */
/** @var array $tokens_metrics */
/** @var array $combined_metrics */
/** @var array $warnings */
/** @var array $recommendations */

$format_int = static function (int $value): string {
    if (function_exists('number_format_i18n')) {
        return number_format_i18n($value);
    }

    return number_format($value, 0, '.', ' ');
};

$format_float = static function (float $value, int $precision = 1): string {
    if (function_exists('number_format_i18n')) {
        return number_format_i18n($value, $precision);
    }

    return number_format($value, $precision, '.', ' ');
};

$specificity_top = $combined_metrics['specificity_top'] ?? [];
$specificity_average = isset($combined_metrics['specificity_average']) ? (float) $combined_metrics['specificity_average'] : 0.0;
$specificity_max = isset($combined_metrics['specificity_max']) ? (int) $combined_metrics['specificity_max'] : 0;
$custom_property_names = $combined_metrics['custom_property_names'] ?? [];
$custom_property_preview = array_slice($custom_property_names, 0, 6);
$custom_property_definitions = (int) ($combined_metrics['custom_property_definitions'] ?? 0);
$custom_property_references = (int) ($combined_metrics['custom_property_references'] ?? 0);
$custom_property_unique = (int) ($combined_metrics['custom_property_unique_count'] ?? count($custom_property_names));
$custom_property_ratio = $custom_property_definitions > 0 ? $custom_property_references / max(1, $custom_property_definitions) : 0.0;
$vendor_prefixes = array_slice($combined_metrics['vendor_prefixes'] ?? [], 0, 6);
$vendor_prefix_total = (int) ($combined_metrics['vendor_prefix_total'] ?? 0);
?>
<div class="ssc-app ssc-fullwidth ssc-css-audit">
    <h2><?php esc_html_e('📈 Analyse de performance CSS', 'supersede-css-jlg'); ?></h2>
    <p class="description">
        <?php esc_html_e('Surveillez la taille et la complexité du CSS généré par Supersede afin d’anticiper les points de friction sur les Core Web Vitals et la maintenabilité.', 'supersede-css-jlg'); ?>
    </p>

    <?php if (!empty($warnings)) : ?>
        <div class="ssc-callout ssc-callout--warning" role="alert">
            <h3><?php esc_html_e('Points de vigilance détectés', 'supersede-css-jlg'); ?></h3>
            <ul>
                <?php foreach ($warnings as $warning) : ?>
                    <li><?php echo esc_html($warning); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($recommendations)) : ?>
        <div class="ssc-callout ssc-callout--info">
            <h3><?php esc_html_e('Recommandations d’optimisation', 'supersede-css-jlg'); ?></h3>
            <ul>
                <?php foreach ($recommendations as $recommendation) : ?>
                    <li><?php echo esc_html($recommendation); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="ssc-metrics-grid">
        <section class="ssc-metric-card">
            <header>
                <span class="ssc-metric-badge ssc-metric-badge--primary"><?php esc_html_e('Vue globale', 'supersede-css-jlg'); ?></span>
                <h3><?php esc_html_e('Total livré', 'supersede-css-jlg'); ?></h3>
            </header>
            <dl>
                <div>
                    <dt><?php esc_html_e('Taille brute', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($combined_metrics['size_readable']); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Taille gzip', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($combined_metrics['gzip_readable']); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Règles · Déclarations', 'supersede-css-jlg'); ?></dt>
                    <dd><?php printf('%s · %s', esc_html($format_int((int) $combined_metrics['rule_count'])), esc_html($format_int((int) $combined_metrics['declaration_count']))); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Sélecteurs uniques', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($format_int((int) $combined_metrics['selector_count'])); ?></dd>
                </div>
            </dl>
        </section>

        <section class="ssc-metric-card">
            <header>
                <span class="ssc-metric-badge"><?php esc_html_e('Option : ssc_active_css', 'supersede-css-jlg'); ?></span>
                <h3><?php esc_html_e('CSS actif', 'supersede-css-jlg'); ?></h3>
            </header>
            <dl>
                <div>
                    <dt><?php esc_html_e('Taille brute', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($active_metrics['size_readable']); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Taille gzip', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($active_metrics['gzip_readable']); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Règles', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($format_int((int) $active_metrics['rule_count'])); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Déclarations / règle', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($active_metrics['average_declarations']); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('!important', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($format_int((int) $active_metrics['important_count'])); ?></dd>
                </div>
            </dl>
            <?php if (!empty($active_metrics['raw_sample'])) : ?>
                <p class="ssc-metric-sample">
                    <span><?php esc_html_e('Extrait brut', 'supersede-css-jlg'); ?> :</span>
                    <code><?php echo esc_html($active_metrics['raw_sample']); ?></code>
                </p>
            <?php endif; ?>
        </section>

        <section class="ssc-metric-card">
            <header>
                <span class="ssc-metric-badge"><?php esc_html_e('Option : ssc_tokens_css', 'supersede-css-jlg'); ?></span>
                <h3><?php esc_html_e('Tokens CSS', 'supersede-css-jlg'); ?></h3>
            </header>
            <dl>
                <div>
                    <dt><?php esc_html_e('Taille brute', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($tokens_metrics['size_readable']); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Taille gzip', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($tokens_metrics['gzip_readable']); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Règles', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($format_int((int) $tokens_metrics['rule_count'])); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Sélecteurs', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($format_int((int) $tokens_metrics['selector_count'])); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('!important', 'supersede-css-jlg'); ?></dt>
                    <dd><?php echo esc_html($format_int((int) $tokens_metrics['important_count'])); ?></dd>
                </div>
            </dl>
            <?php if (!empty($tokens_metrics['raw_sample'])) : ?>
                <p class="ssc-metric-sample">
                    <span><?php esc_html_e('Extrait brut', 'supersede-css-jlg'); ?> :</span>
                    <code><?php echo esc_html($tokens_metrics['raw_sample']); ?></code>
                </p>
            <?php endif; ?>
        </section>
    </div>

    <div class="ssc-panel">
        <h3><?php esc_html_e('Indice de spécificité', 'supersede-css-jlg'); ?></h3>
        <div class="ssc-two ssc-two--align-start">
            <div>
                <p class="description">
                    <?php esc_html_e('Un score élevé signale des règles difficiles à surcharger. Les meilleures pratiques des Design Systems modernes visent un score moyen < 100.', 'supersede-css-jlg'); ?>
                </p>
                <ul class="ssc-stat-list">
                    <li>
                        <strong><?php esc_html_e('Score moyen', 'supersede-css-jlg'); ?>:</strong>
                        <?php echo esc_html($format_float($specificity_average, 1)); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Score maximum', 'supersede-css-jlg'); ?>:</strong>
                        <?php echo esc_html($format_int($specificity_max)); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Sélecteurs analysés', 'supersede-css-jlg'); ?>:</strong>
                        <?php echo esc_html($format_int((int) $combined_metrics['selector_count'])); ?>
                    </li>
                </ul>
            </div>
            <div>
                <h4><?php esc_html_e('Sélecteurs à surveiller', 'supersede-css-jlg'); ?></h4>
                <?php if (!empty($specificity_top)) : ?>
                    <ul class="ssc-selector-list">
                        <?php foreach ($specificity_top as $entry) : ?>
                            <li>
                                <code><?php echo esc_html($entry['selector']); ?></code>
                                <span class="ssc-selector-meta"><?php printf(esc_html__('Score %1$s · %2$s', 'supersede-css-jlg'), esc_html($format_int((int) $entry['score'])), esc_html($entry['vector'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="description"><?php esc_html_e('La spécificité est homogène, aucun pic critique détecté.', 'supersede-css-jlg'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ssc-panel">
        <h3><?php esc_html_e('Complexité des sélecteurs', 'supersede-css-jlg'); ?></h3>
        <div class="ssc-two ssc-two--align-start">
            <div>
                <h4><?php esc_html_e('Sélecteurs les plus longs', 'supersede-css-jlg'); ?></h4>
                <?php if (!empty($active_metrics['long_selectors']) || !empty($tokens_metrics['long_selectors'])) : ?>
                    <ul class="ssc-selector-list">
                        <?php foreach (array_merge($active_metrics['long_selectors'], $tokens_metrics['long_selectors']) as $selector) : ?>
                            <li>
                                <code><?php echo esc_html($selector['selector']); ?></code>
                                <span class="ssc-selector-meta"><?php printf(esc_html__('%d caractères', 'supersede-css-jlg'), (int) $selector['length']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="description"><?php esc_html_e('Aucun sélecteur complexe détecté.', 'supersede-css-jlg'); ?></p>
                <?php endif; ?>
            </div>
            <div>
                <h4><?php esc_html_e('Doublons potentiels', 'supersede-css-jlg'); ?></h4>
                <?php $duplicates = array_merge($active_metrics['duplicate_selectors'], $tokens_metrics['duplicate_selectors']); ?>
                <?php if (!empty($duplicates)) : ?>
                    <ul class="ssc-selector-list">
                        <?php foreach ($duplicates as $duplicate) : ?>
                            <li>
                                <code><?php echo esc_html($duplicate['selector']); ?></code>
                                <span class="ssc-selector-meta"><?php printf(esc_html__('%dx', 'supersede-css-jlg'), (int) $duplicate['count']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="description"><?php esc_html_e('Aucun doublon repéré pour l’instant.', 'supersede-css-jlg'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ssc-panel">
        <h3><?php esc_html_e('Tokens & variables CSS', 'supersede-css-jlg'); ?></h3>
        <div class="ssc-two ssc-two--align-start">
            <div>
                <ul class="ssc-stat-list">
                    <li>
                        <strong><?php esc_html_e('Définitions détectées', 'supersede-css-jlg'); ?>:</strong>
                        <?php echo esc_html($format_int($custom_property_definitions)); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Variables uniques', 'supersede-css-jlg'); ?>:</strong>
                        <?php echo esc_html($format_int($custom_property_unique)); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Appels var()', 'supersede-css-jlg'); ?>:</strong>
                        <?php echo esc_html($format_int($custom_property_references)); ?>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Utilisation moyenne', 'supersede-css-jlg'); ?>:</strong>
                        <?php echo esc_html($format_float($custom_property_ratio, 2)); ?>
                        <span class="ssc-selector-meta"><?php esc_html_e('var() par token défini', 'supersede-css-jlg'); ?></span>
                    </li>
                </ul>
            </div>
            <div>
                <h4><?php esc_html_e('Aperçu des tokens', 'supersede-css-jlg'); ?></h4>
                <?php if (!empty($custom_property_preview)) : ?>
                    <ul class="ssc-selector-list">
                        <?php foreach ($custom_property_preview as $token_name) : ?>
                            <li><code><?php echo esc_html($token_name); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($custom_property_unique > count($custom_property_preview)) : ?>
                        <?php $remaining_tokens = $custom_property_unique - count($custom_property_preview); ?>
                        <p class="description">
                            <?php printf(esc_html__('… et %s autres tokens.', 'supersede-css-jlg'), esc_html($format_int($remaining_tokens))); ?>
                        </p>
                    <?php endif; ?>
                <?php else : ?>
                    <p class="description"><?php esc_html_e('Aucune variable CSS locale détectée dans ce build.', 'supersede-css-jlg'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ssc-panel">
        <h3><?php esc_html_e('Compatibilité navigateurs', 'supersede-css-jlg'); ?></h3>
        <p class="description"><?php esc_html_e('Analyse des préfixes propriétaires pour vérifier la configuration Autoprefixer/Browserslist.', 'supersede-css-jlg'); ?></p>
        <?php if (!empty($vendor_prefixes)) : ?>
            <ul class="ssc-selector-list">
                <?php foreach ($vendor_prefixes as $prefix) : ?>
                    <li>
                        <code><?php echo esc_html($prefix['prefix']); ?></code>
                        <span class="ssc-selector-meta"><?php printf(esc_html__('%dx', 'supersede-css-jlg'), (int) $prefix['count']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="description">
                <?php printf(esc_html__('Total relevé : %s préfixes propriétaires.', 'supersede-css-jlg'), esc_html($format_int($vendor_prefix_total))); ?>
            </p>
        <?php else : ?>
            <p class="description"><?php esc_html_e('Aucun préfixe propriétaire détecté. Le CSS est prêt pour la production moderne.', 'supersede-css-jlg'); ?></p>
        <?php endif; ?>
    </div>

    <div class="ssc-panel">
        <h3><?php esc_html_e('Statistiques avancées', 'supersede-css-jlg'); ?></h3>
        <ul class="ssc-stat-list">
            <li>
                <strong><?php esc_html_e('Règles @import', 'supersede-css-jlg'); ?>:</strong>
                <?php echo esc_html($format_int((int) $combined_metrics['import_count'])); ?>
            </li>
            <li>
                <strong><?php esc_html_e('At-règles (media, supports, keyframes…)', 'supersede-css-jlg'); ?>:</strong>
                <?php echo esc_html($format_int((int) $combined_metrics['atrule_count'])); ?>
            </li>
            <li>
                <strong><?php esc_html_e('Déclarations max par règle', 'supersede-css-jlg'); ?>:</strong>
                <?php echo esc_html($format_int((int) max($active_metrics['max_declarations'], $tokens_metrics['max_declarations']))); ?>
            </li>
        </ul>
    </div>
</div>
