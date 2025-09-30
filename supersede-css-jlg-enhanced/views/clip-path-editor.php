<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $preview_background */
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('✂️ Générateur de Formes (Clip-Path)', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Découpez vos conteneurs et images dans des formes géométriques pour des designs plus dynamiques.', 'supersede-css-jlg'); ?></p>
    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3><?php esc_html_e('Formes Prédéfinies', 'supersede-css-jlg'); ?></h3>
            <select id="ssc-clip-preset">
                <option value="none"><?php esc_html_e('Aucune (Rectangle)', 'supersede-css-jlg'); ?></option>
                <option value="circle(50% at 50% 50%)"><?php esc_html_e('Cercle', 'supersede-css-jlg'); ?></option>
                <option value="ellipse(50% 30% at 50% 50%)"><?php esc_html_e('Ellipse', 'supersede-css-jlg'); ?></option>
                <option value="polygon(50% 0%, 0% 100%, 100% 100%)"><?php esc_html_e('Triangle', 'supersede-css-jlg'); ?></option>
                <option value="polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%)"><?php esc_html_e('Hexagone', 'supersede-css-jlg'); ?></option>
                <option value="polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)"><?php esc_html_e('Étoile', 'supersede-css-jlg'); ?></option>
                <option value="polygon(0 15%, 15% 15%, 15% 0, 85% 0, 85% 15%, 100% 15%, 100% 85%, 85% 85%, 85% 100%, 15% 100%, 15% 85%, 0 85%)"><?php esc_html_e('Croix', 'supersede-css-jlg'); ?></option>
            </select>
            <label style="margin-top:16px; display:block;"><strong><?php esc_html_e("Taille de l'aperçu:", 'supersede-css-jlg'); ?> <span id="ssc-clip-size-val"><?php echo esc_html__('300px', 'supersede-css-jlg'); ?></span></strong></label>
            <input type="range" id="ssc-clip-preview-size" min="100" max="500" value="300" step="10" style="width:100%;">
            <h3 style="margin-top:16px;"><?php esc_html_e('Code CSS Généré', 'supersede-css-jlg'); ?></h3>
            <pre id="ssc-clip-css" class="ssc-code"></pre>
            <div class="ssc-actions"><button id="ssc-clip-copy" class="button"><?php esc_html_e('Copier le CSS', 'supersede-css-jlg'); ?></button></div>
        </div>
        <div class="ssc-pane">
             <h3><?php esc_html_e('Aperçu', 'supersede-css-jlg'); ?></h3>
             <div id="ssc-clip-preview-wrapper">
                <div id="ssc-clip-preview" style="background-image: url('<?php echo esc_url($preview_background); ?>');"></div>
             </div>
        </div>
    </div>
</div>
