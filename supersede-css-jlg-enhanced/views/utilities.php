<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $css_desktop */
/** @var string $css_tablet */
/** @var string $css_mobile */
/** @var string $preview_url */
?>
<div class="ssc-wrap ssc-utilities-wrap">
    <div class="ssc-editor-layout">
        <div class="ssc-editor-column">
            <div class="ssc-editor-header">
                <h2><?php echo esc_html__('Éditeur CSS', 'supersede-css-jlg'); ?></h2>
                <div class="ssc-actions">
                    <button id="ssc-save-css" class="button button-primary"><?php echo esc_html__('Enregistrer le CSS', 'supersede-css-jlg'); ?></button>
                </div>
            </div>
            <div class="ssc-editor-tabs" role="tablist" aria-label="<?php echo esc_attr__('Modes d\'édition CSS', 'supersede-css-jlg'); ?>">
                <button type="button" class="ssc-editor-tab active" id="ssc-editor-tab-desktop" role="tab" aria-selected="true" aria-controls="ssc-editor-panel-desktop" data-tab="desktop"><?php esc_html_e('🖥️ Desktop', 'supersede-css-jlg'); ?></button>
                <button type="button" class="ssc-editor-tab" id="ssc-editor-tab-tablet" role="tab" aria-selected="false" aria-controls="ssc-editor-panel-tablet" data-tab="tablet"><?php esc_html_e('📲 Tablette', 'supersede-css-jlg'); ?></button>
                <button type="button" class="ssc-editor-tab" id="ssc-editor-tab-mobile" role="tab" aria-selected="false" aria-controls="ssc-editor-panel-mobile" data-tab="mobile"><?php esc_html_e('📱 Mobile', 'supersede-css-jlg'); ?></button>
                <button type="button" class="ssc-editor-tab" id="ssc-editor-tab-tutorial" role="tab" aria-selected="false" aria-controls="ssc-editor-panel-tutorial" data-tab="tutorial"><?php esc_html_e('💡 Tutoriel @media queries', 'supersede-css-jlg'); ?></button>
            </div>
            <div class="ssc-editor-container">
                <div id="ssc-editor-panel-desktop" class="ssc-editor-panel active" role="tabpanel" aria-labelledby="ssc-editor-tab-desktop" tabindex="0">
                    <textarea id="ssc-css-editor-desktop"><?php echo esc_textarea($css_desktop); ?></textarea>
                </div>
                <div id="ssc-editor-panel-tablet" class="ssc-editor-panel" role="tabpanel" aria-labelledby="ssc-editor-tab-tablet" tabindex="0" hidden>
                    <textarea id="ssc-css-editor-tablet"><?php echo esc_textarea($css_tablet); ?></textarea>
                </div>
                <div id="ssc-editor-panel-mobile" class="ssc-editor-panel" role="tabpanel" aria-labelledby="ssc-editor-tab-mobile" tabindex="0" hidden>
                    <textarea id="ssc-css-editor-mobile"><?php echo esc_textarea($css_mobile); ?></textarea>
                </div>
                <div id="ssc-editor-panel-tutorial" class="ssc-editor-panel ssc-tutorial-content" role="tabpanel" aria-labelledby="ssc-editor-tab-tutorial" tabindex="0" hidden>
                    <h3><?php esc_html_e('Le Principe : "Desktop First" Simplifié', 'supersede-css-jlg'); ?></h3>
                    <p><?php esc_html_e('Pensez à votre design comme à la construction d\'une maison :', 'supersede-css-jlg'); ?></p>
                    <ol>
                        <li><?php printf(wp_kses_post(__('%1$s C\'est ici que vous définissez tous les styles fondamentaux (couleurs, polices, espacements). Ces styles s\'appliquent par défaut à %2$s.', 'supersede-css-jlg')), '<strong>L\'onglet <code>Desktop</code> est le plan de base de la maison.</strong>', '<strong>toutes les tailles d\'écran</strong>'); ?></li>
                        <li><?php echo wp_kses_post(__('<strong>L\'onglet <code>Tablette</code> est l\'aménagement pour les pièces moyennes.</strong> Vous ne redessinez pas tout, vous spécifiez uniquement les changements. Par exemple, réduire la taille d\'un titre.', 'supersede-css-jlg')); ?></li>
                        <li><?php echo wp_kses_post(__('<strong>L\'onglet <code>Mobile</code> est pour les plus petites pièces.</strong> Vous faites les derniers ajustements pour que tout soit parfait sur un petit écran.', 'supersede-css-jlg')); ?></li>
                    </ol>
                    <p><?php printf(wp_kses_post(__('En coulisses, le plugin enveloppe automatiquement le code des onglets Tablette et Mobile dans des %s, vous faisant gagner du temps.', 'supersede-css-jlg')), '<strong>@media queries</strong>'); ?></p>
                    <hr>
                    <h4><?php esc_html_e('Exemple Concret : Un Titre Adaptatif', 'supersede-css-jlg'); ?></h4>
                    <p><?php printf(wp_kses_post(__('%1$s Un titre %2$s qui change de taille et d\'alignement.', 'supersede-css-jlg')), '<strong>Objectif :</strong>', '<code>.mon-titre</code>'); ?></p>
                    <p><?php echo wp_kses_post(__('<strong>1. Onglet <code>Desktop</code> (la base) :</strong>', 'supersede-css-jlg')); ?></p>
                    <pre class="ssc-code">.mon-titre {
  font-size: 48px;
  color: blue;
  font-weight: bold;
}</pre>
                    <p><?php echo wp_kses_post(__('<strong>2. Onglet <code>Tablette</code> (premier ajustement) :</strong>', 'supersede-css-jlg')); ?></p>
                    <pre class="ssc-code">.mon-titre {
  font-size: 36px;
}</pre>
                    <p><?php echo wp_kses_post(__('<strong>3. Onglet <code>Mobile</code> (ajustement final) :</strong>', 'supersede-css-jlg')); ?></p>
                    <pre class="ssc-code">.mon-titre {
  font-size: 24px;
  text-align: center;
}</pre>
                </div>
            </div>
        </div>
        <button type="button" id="ssc-preview-toggle" class="button ssc-preview-toggle" data-show="<?php echo esc_attr__('Afficher l\'aperçu', 'supersede-css-jlg'); ?>" data-hide="<?php echo esc_attr__('Masquer l\'aperçu', 'supersede-css-jlg'); ?>" aria-expanded="false" aria-controls="ssc-preview-column">
            <?php echo esc_html__('Afficher l\'aperçu', 'supersede-css-jlg'); ?>
        </button>
        <div class="ssc-preview-column" id="ssc-preview-column">
            <div class="ssc-preview-header">
                <div class="ssc-url-bar">
                    <input type="url" id="ssc-preview-url" value="<?php echo esc_url($preview_url); ?>">
                    <button class="button" id="ssc-preview-load"><?php echo esc_html__('Load', 'supersede-css-jlg'); ?></button>
                    <button
                        class="button"
                        id="ssc-element-picker-toggle"
                        title="<?php echo esc_attr__('Cibler un élément', 'supersede-css-jlg'); ?>"
                        aria-label="<?php echo esc_attr__('Cibler un élément', 'supersede-css-jlg'); ?>"
                    >🎯</button>
                </div>
                <div class="ssc-responsive-toggles">
                    <button
                        class="button button-primary"
                        data-vp="desktop"
                        title="<?php echo esc_attr__('Desktop', 'supersede-css-jlg'); ?>"
                        aria-label="<?php echo esc_attr__('Desktop', 'supersede-css-jlg'); ?>"
                    >🖥️</button>
                    <button
                        class="button"
                        data-vp="tablet"
                        title="<?php echo esc_attr__('Tablet', 'supersede-css-jlg'); ?>"
                        aria-label="<?php echo esc_attr__('Tablet', 'supersede-css-jlg'); ?>"
                    >📲</button>
                    <button
                        class="button"
                        data-vp="mobile"
                        title="<?php echo esc_attr__('Mobile', 'supersede-css-jlg'); ?>"
                        aria-label="<?php echo esc_attr__('Mobile', 'supersede-css-jlg'); ?>"
                    >📱</button>
                </div>
            </div>
            <div class="ssc-preview-frame-container">
                <div id="ssc-picker-overlay"></div>
                <div id="ssc-picker-tooltip"></div>
                <iframe id="ssc-preview-frame" sandbox="allow-same-origin allow-forms allow-scripts"></iframe>
            </div>
            <div style="padding-top: 8px;">
                <label><?php esc_html_e('Sélecteur Ciblé :', 'supersede-css-jlg'); ?></label>
                <input type="text" id="ssc-picked-selector" readonly class="large-text" placeholder="<?php echo esc_attr__('Cliquez sur 🎯 puis sur un élément dans l\'aperçu.', 'supersede-css-jlg'); ?>">
            </div>
        </div>
    </div>
</div>
