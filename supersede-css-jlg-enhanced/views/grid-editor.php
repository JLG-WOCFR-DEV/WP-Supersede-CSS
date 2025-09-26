<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('📏 Visual Grid Editor', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Construisez des mises en page CSS Grid de manière intuitive, sans écrire de code.', 'supersede-css-jlg'); ?></p>
    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3><?php esc_html_e('Paramètres de la Grille', 'supersede-css-jlg'); ?></h3>

            <label><strong><?php esc_html_e('Nombre de colonnes', 'supersede-css-jlg'); ?></strong></label>
            <input type="range" id="ssc-grid-cols" min="1" max="12" value="3" step="1">
            <span id="ssc-grid-cols-val">3</span>

            <label style="margin-top:16px; display:block;"><strong><?php esc_html_e('Espacement (gap) en pixels', 'supersede-css-jlg'); ?></strong></label>
            <input type="range" id="ssc-grid-gap" min="0" max="100" value="16" step="1">
            <span id="ssc-grid-gap-val">16px</span>

            <div class="ssc-actions" style="margin-top:24px; border-top: 1px solid var(--ssc-border); padding-top: 16px;">
                <button id="ssc-grid-apply" class="button button-primary"><?php esc_html_e('Appliquer', 'supersede-css-jlg'); ?></button>
                <button id="ssc-grid-copy" class="button"><?php esc_html_e('Copier CSS', 'supersede-css-jlg'); ?></button>
            </div>

            <h3 style="margin-top:24px;"><?php esc_html_e('Code CSS Généré', 'supersede-css-jlg'); ?></h3>
            <p class="description"><?php echo wp_kses_post(__('Appliquez la classe <code>.ssc-grid-container</code> à votre conteneur.', 'supersede-css-jlg')); ?></p>
            <pre id="ssc-grid-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Aperçu en Direct', 'supersede-css-jlg'); ?></h3>
            <div id="ssc-grid-preview" style="display:grid; border:1px dashed var(--ssc-border); padding:10px; border-radius:8px;">
                <!-- Les éléments de la grille seront générés par JS -->
            </div>
        </div>
    </div>
</div>
