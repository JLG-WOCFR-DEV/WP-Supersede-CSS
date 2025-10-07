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
