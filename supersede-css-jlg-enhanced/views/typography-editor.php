<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('📏 Typographie Fluide (Clamp)', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Générez du texte qui s\'adapte parfaitement à toutes les tailles d\'écran, sans "sauts" disgracieux.', 'supersede-css-jlg'); ?></p>
    <div class="ssc-two ssc-two--align-start">
        <div class="ssc-pane">
            <h3><?php esc_html_e('Paramètres de la Police (en pixels)', 'supersede-css-jlg'); ?></h3>
            <div class="ssc-two">
                <div><label class="ssc-form-label"><?php esc_html_e('Taille min. police', 'supersede-css-jlg'); ?></label><input type="number" id="ssc-typo-min-fs" value="16" class="small-text"></div>
                <div><label class="ssc-form-label"><?php esc_html_e('Taille max. police', 'supersede-css-jlg'); ?></label><input type="number" id="ssc-typo-max-fs" value="48" class="small-text"></div>
            </div>
            <div class="ssc-two ssc-mt-150">
                <div><label class="ssc-form-label"><?php esc_html_e('Taille min. viewport (px)', 'supersede-css-jlg'); ?></label><input type="number" id="ssc-typo-min-vp" value="320" class="small-text"></div>
                <div><label class="ssc-form-label"><?php esc_html_e('Taille max. viewport (px)', 'supersede-css-jlg'); ?></label><input type="number" id="ssc-typo-max-vp" value="1280" class="small-text"></div>
            </div>
            <label class="ssc-form-label ssc-mt-200"><?php esc_html_e('Texte à prévisualiser', 'supersede-css-jlg'); ?></label>
            <input type="text" id="ssc-typo-text" class="large-text" value="<?php echo esc_attr__('Design fluide, lecture parfaite.', 'supersede-css-jlg'); ?>">
            <div class="ssc-form-actions ssc-form-actions--separated">
                <button id="ssc-typo-generate" class="button button-primary"><?php esc_html_e('Générer', 'supersede-css-jlg'); ?></button>
                <button id="ssc-typo-copy" class="button"><?php esc_html_e('Copier le CSS', 'supersede-css-jlg'); ?></button>
            </div>
            <pre id="ssc-typo-css" class="ssc-code ssc-mt-200"></pre>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Aperçu', 'supersede-css-jlg'); ?></h3>
            <div class="ssc-typo-vp-slider-container">
                <label><?php esc_html_e('Largeur du viewport (px)', 'supersede-css-jlg'); ?></label>
                <input type="range" id="ssc-typo-vp-slider" min="320" max="1280" value="960">
                <span id="ssc-typo-vp-value"><?php echo esc_html__('960px', 'supersede-css-jlg'); ?></span>
            </div>
            <div id="ssc-typo-preview" class="ssc-typo-preview ssc-mt-200"><?php esc_html_e('Design fluide, lecture parfaite.', 'supersede-css-jlg'); ?></div>
        </div>
    </div>
</div>
