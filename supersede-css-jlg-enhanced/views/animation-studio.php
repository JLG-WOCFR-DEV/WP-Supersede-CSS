<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('🎬 Animation Studio', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e("Choisissez un preset d'animation, personnalisez-le et appliquez-le à vos éléments.", 'supersede-css-jlg'); ?></p>
    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3><?php esc_html_e("Paramètres de l'Animation", 'supersede-css-jlg'); ?></h3>
            <label><strong><?php esc_html_e("Preset d'animation", 'supersede-css-jlg'); ?></strong></label>
            <select id="ssc-anim-preset" class="regular-text">
                <option value="bounce"><?php esc_html_e('Bounce (Rebond)', 'supersede-css-jlg'); ?></option>
                <option value="pulse"><?php esc_html_e('Pulse (Pulsation)', 'supersede-css-jlg'); ?></option>
                <option value="fade-in"><?php esc_html_e('Fade In (Apparition)', 'supersede-css-jlg'); ?></option>
                <option value="slide-in-left"><?php esc_html_e('Slide In Left (Glisse depuis la gauche)', 'supersede-css-jlg'); ?></option>
            </select>
            <label style="margin-top:16px; display:block;"><strong><?php esc_html_e('Durée (secondes)', 'supersede-css-jlg'); ?></strong></label>
            <input type="range" id="ssc-anim-duration" min="0.1" max="5" value="1.5" step="0.1">
            <span id="ssc-anim-duration-val">1.5s</span>
            <div class="ssc-actions" style="margin-top:24px; border-top: 1px solid var(--ssc-border); padding-top: 16px;">
                <button id="ssc-anim-apply" class="button button-primary"><?php esc_html_e('Appliquer', 'supersede-css-jlg'); ?></button>
                <button id="ssc-anim-copy" class="button"><?php esc_html_e('Copier CSS', 'supersede-css-jlg'); ?></button>
            </div>
            <h3 style="margin-top:24px;"><?php esc_html_e('Code CSS Généré', 'supersede-css-jlg'); ?></h3>
            <p class="description"><?php printf(wp_kses_post(__('Appliquez la classe %1$s et la classe du preset (ex: %2$s) à votre élément.', 'supersede-css-jlg')), '<code>.ssc-animated</code>', '<code>.ssc-bounce</code>'); ?></p>
            <pre id="ssc-anim-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Aperçu en Direct', 'supersede-css-jlg'); ?></h3>
            <div id="ssc-anim-preview-container" style="display:grid; place-items:center; height:200px;">
                <div id="ssc-anim-preview-box" style="width: 100px; height: 100px; background: var(--ssc-accent); border-radius: 12px;"></div>
            </div>
        </div>
    </div>
</div>
