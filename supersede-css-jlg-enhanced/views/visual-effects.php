<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('🎬 Générateur d\'Effets Visuels', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Une collection d\'effets visuels avancés pour animer vos fonds, images et conteneurs.', 'supersede-css-jlg'); ?></p>
    <div class="ssc-ve-tabs" role="tablist" aria-label="<?php echo esc_attr__('Types d\'effets visuels', 'supersede-css-jlg'); ?>">
        <button type="button" class="ssc-ve-tab active" id="ssc-ve-tab-backgrounds" role="tab" aria-selected="true" aria-controls="ssc-ve-panel-backgrounds" data-tab="backgrounds"><?php esc_html_e('🌌 Fonds Animés', 'supersede-css-jlg'); ?></button>
        <button type="button" class="ssc-ve-tab" id="ssc-ve-tab-ecg" role="tab" aria-selected="false" aria-controls="ssc-ve-panel-ecg" data-tab="ecg"><?php esc_html_e('❤️ ECG / Battement de Cœur', 'supersede-css-jlg'); ?></button>
        <button type="button" class="ssc-ve-tab" id="ssc-ve-tab-crt" role="tab" aria-selected="false" aria-controls="ssc-ve-panel-crt" data-tab="crt"><?php esc_html_e('📺 Effet CRT (Scanline)', 'supersede-css-jlg'); ?></button>
    </div>

    <div id="ssc-ve-panel-crt" class="ssc-ve-panel" role="tabpanel" aria-labelledby="ssc-ve-tab-crt" tabindex="0" hidden>
        <div class="ssc-two" style="align-items: flex-start;">
            <div class="ssc-pane">
                <h3><?php esc_html_e('Paramètres de l\'effet CRT', 'supersede-css-jlg'); ?></h3>
                <p class="description"><?php esc_html_e('Cet effet est purement décoratif et ne génère pas de CSS à exporter.', 'supersede-css-jlg'); ?></p>
                <div class="ssc-grid-three">
                    <div><label><?php esc_html_e('Couleur Scanline', 'supersede-css-jlg'); ?></label><input type="color" class="ssc-crt-control" id="scanlineColor" value="#00ff00"></div>
                    <div><label><?php esc_html_e('Opacité Scanline', 'supersede-css-jlg'); ?></label><input type="range" class="ssc-crt-control" id="scanlineOpacity" min="0" max="1" value="0.4" step="0.05"></div>
                    <div><label><?php esc_html_e('Vitesse Scanline', 'supersede-css-jlg'); ?></label><input type="range" class="ssc-crt-control" id="scanlineSpeed" min="0.1" max="2" value="0.5" step="0.1"></div>
                    <div><label><?php esc_html_e('Intensité Bruit', 'supersede-css-jlg'); ?></label><input type="range" class="ssc-crt-control" id="noiseIntensity" min="0" max="0.5" value="0.1" step="0.02"></div>
                    <div><label><?php esc_html_e('Aberration Chromatique', 'supersede-css-jlg'); ?></label><input type="range" class="ssc-crt-control" id="chromaticAberration" min="0" max="5" value="1" step="0.5"></div>
                </div>
            </div>
            <div class="ssc-pane">
                <h3><?php esc_html_e('Aperçu', 'supersede-css-jlg'); ?></h3>
                <div class="ssc-ve-preview-box"><canvas id="ssc-crt-canvas"></canvas></div>
            </div>
        </div>
    </div>

    <div id="ssc-ve-panel-ecg" class="ssc-ve-panel" role="tabpanel" aria-labelledby="ssc-ve-tab-ecg" tabindex="0" hidden>
         <div class="ssc-two" style="align-items: flex-start;">
            <div class="ssc-pane">
                <h3><?php esc_html_e('Paramètres de l\'ECG', 'supersede-css-jlg'); ?></h3>
                <label><strong><?php esc_html_e('Preset de Rythme', 'supersede-css-jlg'); ?></strong></label>
                <select id="ssc-ecg-preset" class="regular-text"><option value="stable"><?php esc_html_e('Stable', 'supersede-css-jlg'); ?></option><option value="fast"><?php esc_html_e('Rapide', 'supersede-css-jlg'); ?></option><option value="critical"><?php esc_html_e('Critique', 'supersede-css-jlg'); ?></option></select>
                <label style="margin-top:16px;"><strong><?php esc_html_e('Couleur de la ligne', 'supersede-css-jlg'); ?></strong></label>
                <input type="color" id="ssc-ecg-color" value="#00ff00">
                <label style="margin-top:16px;"><strong><?php esc_html_e('Positionnement (top)', 'supersede-css-jlg'); ?></strong></label>
                <input type="range" id="ssc-ecg-top" min="0" max="100" value="50" step="1"><span id="ssc-ecg-top-val"><?php echo esc_html__('50%', 'supersede-css-jlg'); ?></span>
                <label style="margin-top:16px;"><strong><?php esc_html_e('Superposition (z-index)', 'supersede-css-jlg'); ?></strong></label>
                <input type="range" id="ssc-ecg-z-index" min="-10" max="10" value="1" step="1"><span id="ssc-ecg-z-index-val"><?php echo esc_html__('1', 'supersede-css-jlg'); ?></span>
                <hr>
                <label><strong><?php esc_html_e('Logo/Image au centre', 'supersede-css-jlg'); ?></strong></label>
                <button id="ssc-ecg-upload-btn" class="button"><?php esc_html_e('Choisir une image', 'supersede-css-jlg'); ?></button>
                <label style="margin-top:16px;"><strong><?php esc_html_e('Taille du logo', 'supersede-css-jlg'); ?></strong></label>
                <input type="range" id="ssc-ecg-logo-size" min="20" max="200" value="100" step="1"><span id="ssc-ecg-logo-size-val"><?php echo esc_html__('100px', 'supersede-css-jlg'); ?></span>
                <hr>
                <pre id="ssc-ecg-css" class="ssc-code ssc-code-small" style="margin-top:16px;"></pre>
                <button id="ssc-ecg-apply" class="button button-primary" style="margin-top:8px;"><?php esc_html_e('Appliquer l\'Effet', 'supersede-css-jlg'); ?></button>
            </div>
            <div class="ssc-pane">
                <h3><?php esc_html_e('Aperçu', 'supersede-css-jlg'); ?></h3>
                <div id="ssc-ecg-preview-container" class="ssc-ve-preview-box">
                    <img id="ssc-ecg-logo-preview" src="" alt="<?php echo esc_attr__('Logo Preview', 'supersede-css-jlg'); ?>" style="display:none;">
                    <svg id="ssc-ecg-preview-svg" viewBox="0 0 400 60" preserveAspectRatio="none"><path id="ssc-ecg-preview-path" class="ssc-ecg-path" d="M0,30 L100,30 L110,18 L120,42 L130,26 L140,30 L240,30 L250,20 L260,40 L270,28 L280,30 L400,30"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div id="ssc-ve-panel-backgrounds" class="ssc-ve-panel active" role="tabpanel" aria-labelledby="ssc-ve-tab-backgrounds" tabindex="0">
         <div class="ssc-two" style="align-items: flex-start;">
            <div class="ssc-pane">
                <h3><?php esc_html_e('Paramètres du Fond', 'supersede-css-jlg'); ?></h3>
                <select id="ssc-bg-type" class="regular-text"><option value="stars"><?php esc_html_e('Étoiles', 'supersede-css-jlg'); ?></option><option value="gradient"><?php esc_html_e('Dégradé', 'supersede-css-jlg'); ?></option></select>
                <div id="ssc-bg-controls-stars"><label><?php esc_html_e('Couleur', 'supersede-css-jlg'); ?></label><input type="color" id="starColor" value="#FFFFFF"><label><?php esc_html_e('Nombre', 'supersede-css-jlg'); ?></label><input type="range" id="starCount" min="50" max="500" value="200" step="10"></div>
                <div id="ssc-bg-controls-gradient" style="display:none;"><label><?php esc_html_e('Vitesse', 'supersede-css-jlg'); ?></label><input type="range" id="gradientSpeed" min="2" max="20" value="10" step="1"></div>
                 <pre id="ssc-bg-css" class="ssc-code"></pre>
                <button id="ssc-bg-apply" class="button button-primary"><?php esc_html_e('Appliquer', 'supersede-css-jlg'); ?></button>
            </div>
            <div class="ssc-pane">
                <h3><?php esc_html_e('Aperçu', 'supersede-css-jlg'); ?></h3>
                <div id="ssc-bg-preview" class="ssc-ve-preview-box"></div>
            </div>
        </div>
    </div>
</div>
