<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('📏 Visual Grid Editor', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Construisez des mises en page CSS Grid de manière intuitive, sans écrire de code.', 'supersede-css-jlg'); ?></p>
    <div class="ssc-two ssc-two--align-start">
        <div class="ssc-pane">
            <h3><?php esc_html_e('Paramètres de la Grille', 'supersede-css-jlg'); ?></h3>

            <div class="ssc-form-field">
                <label class="ssc-form-label" for="ssc-grid-cols"><?php esc_html_e('Nombre de colonnes', 'supersede-css-jlg'); ?></label>
                <div class="ssc-range-control">
                    <input type="range" id="ssc-grid-cols" min="1" max="12" value="3" step="1">
                    <span id="ssc-grid-cols-val" class="ssc-range-output"><?php echo esc_html__('3', 'supersede-css-jlg'); ?></span>
                </div>
            </div>

            <div class="ssc-form-field">
                <label class="ssc-form-label" for="ssc-grid-gap"><?php esc_html_e('Espacement (gap) en pixels', 'supersede-css-jlg'); ?></label>
                <div class="ssc-range-control">
                    <input type="range" id="ssc-grid-gap" min="0" max="100" value="16" step="1">
                    <span id="ssc-grid-gap-val" class="ssc-range-output"><?php echo esc_html__('16px', 'supersede-css-jlg'); ?></span>
                </div>
            </div>

            <div class="ssc-form-actions ssc-form-actions--separated">
                <button id="ssc-grid-apply" class="button button-primary"><?php esc_html_e('Appliquer', 'supersede-css-jlg'); ?></button>
                <button id="ssc-grid-copy" class="button"><?php esc_html_e('Copier CSS', 'supersede-css-jlg'); ?></button>
            </div>

            <h3 class="ssc-section-heading"><?php esc_html_e('Code CSS Généré', 'supersede-css-jlg'); ?></h3>
            <p class="description"><?php printf(wp_kses_post(__('Appliquez la classe %s à votre conteneur.', 'supersede-css-jlg')), '<code>.ssc-grid-container</code>'); ?></p>
            <pre id="ssc-grid-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Aperçu en Direct', 'supersede-css-jlg'); ?></h3>
            <div id="ssc-grid-preview" class="ssc-grid-preview" role="presentation">
                <!-- Les éléments de la grille seront générés par JS -->
            </div>
        </div>
    </div>
</div>
