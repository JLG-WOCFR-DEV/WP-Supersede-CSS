<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $active_css */
/** @var string $tokens_css */
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('🔍 Visualiseur de CSS Actif', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Ce module affiche le contenu brut des options CSS de Supersede telles qu\'elles sont enregistrées dans votre base de données. C\'est un outil de débogage utile pour voir le code final appliqué à votre site.', 'supersede-css-jlg'); ?></p>

    <div class="ssc-panel ssc-mt-200">
        <div class="ssc-stack">
            <h3><?php printf(wp_kses_post(__('Contenu de : %s', 'supersede-css-jlg')), '<code>ssc_active_css</code>'); ?></h3>
            <p class="description"><?php esc_html_e('C\'est la feuille de style principale où la plupart des modules (Utilities, effets visuels, etc.) enregistrent leur code.', 'supersede-css-jlg'); ?></p>
            <pre class="ssc-code ssc-code--scrollable ssc-code--xl" aria-label="<?php esc_attr_e('CSS actif', 'supersede-css-jlg'); ?>"><?php echo esc_html($active_css); ?></pre>
        </div>
    </div>

    <div class="ssc-panel ssc-mt-200">
        <div class="ssc-stack">
            <h3><?php printf(wp_kses_post(__('Contenu de : %s', 'supersede-css-jlg')), '<code>ssc_tokens_css</code>'); ?></h3>
            <p class="description"><?php esc_html_e('Cette option contient les variables CSS (Tokens) que vous avez définies dans le "Tokens Manager".', 'supersede-css-jlg'); ?></p>
            <pre class="ssc-code ssc-code--scrollable ssc-code--lg" aria-label="<?php esc_attr_e('CSS des tokens', 'supersede-css-jlg'); ?>"><?php echo esc_html($tokens_css); ?></pre>
        </div>
    </div>
</div>
