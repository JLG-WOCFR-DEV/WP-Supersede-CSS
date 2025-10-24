<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth" id="ssc-ecg-app">
    <h2><?php esc_html_e('❤️ ECG / Battement de Cœur', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Créez un battement de cœur animé avec logo central et export CSS prêt à l’emploi.', 'supersede-css-jlg'); ?></p>
    <p class="description"><?php esc_html_e('L’aperçu respecte automatiquement la préférence système « réduire les animations ». Lorsque cette option est active, l’animation est figée pour afficher une version statique.', 'supersede-css-jlg'); ?></p>
    <div class="ssc-two ssc-two--align-start">
        <div class="ssc-pane">
            <h3><?php esc_html_e('Paramètres de l\'ECG', 'supersede-css-jlg'); ?></h3>
            <label for="ssc-ecg-preset" class="ssc-form-label"><?php esc_html_e('Preset de rythme', 'supersede-css-jlg'); ?></label>
            <select id="ssc-ecg-preset" class="regular-text">
                <option value="stable"><?php esc_html_e('Stable', 'supersede-css-jlg'); ?></option>
                <option value="fast"><?php esc_html_e('Rapide', 'supersede-css-jlg'); ?></option>
                <option value="critical"><?php esc_html_e('Critique', 'supersede-css-jlg'); ?></option>
            </select>
            <label for="ssc-ecg-color" class="ssc-form-label ssc-mt-200"><?php esc_html_e('Couleur de la ligne', 'supersede-css-jlg'); ?></label>
            <input type="color" id="ssc-ecg-color" value="#00ff00">
            <label for="ssc-ecg-top" class="ssc-form-label ssc-mt-200"><?php esc_html_e('Positionnement (top)', 'supersede-css-jlg'); ?></label>
            <input type="range" id="ssc-ecg-top" min="0" max="100" value="50" step="1">
            <span id="ssc-ecg-top-val"><?php echo esc_html__('50%', 'supersede-css-jlg'); ?></span>
            <label for="ssc-ecg-z-index" class="ssc-form-label ssc-mt-200"><?php esc_html_e('Superposition (z-index)', 'supersede-css-jlg'); ?></label>
            <input type="range" id="ssc-ecg-z-index" min="-10" max="10" value="1" step="1">
            <span id="ssc-ecg-z-index-val"><?php echo esc_html__('1', 'supersede-css-jlg'); ?></span>
            <hr>
            <label for="ssc-ecg-upload-btn" class="ssc-form-label"><?php esc_html_e('Logo/Image au centre', 'supersede-css-jlg'); ?></label>
            <button id="ssc-ecg-upload-btn" class="button"><?php esc_html_e('Choisir une image', 'supersede-css-jlg'); ?></button>
            <label for="ssc-ecg-logo-size" class="ssc-form-label ssc-mt-200"><?php esc_html_e('Taille du logo', 'supersede-css-jlg'); ?></label>
            <input type="range" id="ssc-ecg-logo-size" min="20" max="200" value="100" step="1">
            <span id="ssc-ecg-logo-size-val"><?php echo esc_html__('100px', 'supersede-css-jlg'); ?></span>
            <hr>
            <pre id="ssc-ecg-css" class="ssc-code ssc-code-small ssc-mt-200"></pre>
            <button id="ssc-ecg-apply" class="button button-primary ssc-mt-100"><?php esc_html_e('Appliquer l\'Effet', 'supersede-css-jlg'); ?></button>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Aperçu', 'supersede-css-jlg'); ?></h3>
            <div id="ssc-ecg-preview-container" class="ssc-ve-preview-box">
                <img id="ssc-ecg-logo-preview" src="" alt="<?php echo esc_attr__('Logo Preview', 'supersede-css-jlg'); ?>">
                <svg id="ssc-ecg-preview-svg" viewBox="0 0 400 60" preserveAspectRatio="none">
                    <path id="ssc-ecg-preview-path" class="ssc-ecg-path" d="M0,30 L100,30 L110,18 L120,42 L130,26 L140,30 L240,30 L250,20 L260,40 L270,28 L280,30 L400,30" />
                </svg>
            </div>
        </div>
    </div>
</div>
