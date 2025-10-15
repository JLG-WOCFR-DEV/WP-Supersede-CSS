<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('🎬 Générateur d\'Effets Visuels', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Une collection d\'effets visuels avancés pour animer vos fonds, images et conteneurs.', 'supersede-css-jlg'); ?></p>
    <p class="description"><?php esc_html_e('Les aperçus respectent automatiquement la préférence système « réduire les animations ». Lorsque cette option est active, les effets sont figés pour afficher une version statique.', 'supersede-css-jlg'); ?></p>
    <div class="ssc-ve-tabs" role="tablist" aria-label="<?php echo esc_attr__('Types d\'effets visuels', 'supersede-css-jlg'); ?>">
        <button type="button" class="ssc-ve-tab active" id="ssc-ve-tab-backgrounds" role="tab" aria-selected="true" aria-controls="ssc-ve-panel-backgrounds" data-tab="backgrounds"><?php esc_html_e('🌌 Fonds Animés', 'supersede-css-jlg'); ?></button>
        <button type="button" class="ssc-ve-tab" id="ssc-ve-tab-ecg" role="tab" aria-selected="false" aria-controls="ssc-ve-panel-ecg" data-tab="ecg"><?php esc_html_e('❤️ ECG / Battement de Cœur', 'supersede-css-jlg'); ?></button>
        <button type="button" class="ssc-ve-tab" id="ssc-ve-tab-crt" role="tab" aria-selected="false" aria-controls="ssc-ve-panel-crt" data-tab="crt"><?php esc_html_e('📺 Effet CRT (Scanline)', 'supersede-css-jlg'); ?></button>
    </div>

    <div id="ssc-ve-panel-crt" class="ssc-ve-panel" role="tabpanel" aria-labelledby="ssc-ve-tab-crt" tabindex="0" hidden>
        <div class="ssc-two ssc-two--align-start">
            <div class="ssc-pane">
                <h3><?php esc_html_e('Paramètres de l\'effet CRT', 'supersede-css-jlg'); ?></h3>
                <p class="description"><?php esc_html_e('Cet effet est purement décoratif et ne génère pas de CSS à exporter.', 'supersede-css-jlg'); ?></p>
                <div class="ssc-grid-three">
                    <div>
                        <label for="scanlineColor"><?php esc_html_e('Couleur Scanline', 'supersede-css-jlg'); ?></label>
                        <input type="color" class="ssc-crt-control" id="scanlineColor" value="#00ff00">
                    </div>
                    <div>
                        <label for="scanlineOpacity"><?php esc_html_e('Opacité Scanline', 'supersede-css-jlg'); ?></label>
                        <input type="range" class="ssc-crt-control" id="scanlineOpacity" min="0" max="1" value="0.4" step="0.05">
                    </div>
                    <div>
                        <label for="scanlineSpeed"><?php esc_html_e('Vitesse Scanline', 'supersede-css-jlg'); ?></label>
                        <input type="range" class="ssc-crt-control" id="scanlineSpeed" min="0.1" max="2" value="0.5" step="0.1">
                    </div>
                    <div>
                        <label for="noiseIntensity"><?php esc_html_e('Intensité du bruit', 'supersede-css-jlg'); ?></label>
                        <input type="range" class="ssc-crt-control" id="noiseIntensity" min="0" max="0.5" value="0.1" step="0.02">
                    </div>
                    <div>
                        <label for="chromaticAberration"><?php esc_html_e('Aberration chromatique', 'supersede-css-jlg'); ?></label>
                        <input type="range" class="ssc-crt-control" id="chromaticAberration" min="0" max="5" value="1" step="0.5">
                    </div>
                </div>
            </div>
            <div class="ssc-pane">
                <h3><?php esc_html_e('Aperçu', 'supersede-css-jlg'); ?></h3>
                <div class="ssc-ve-preview-box"><canvas id="ssc-crt-canvas"></canvas></div>
            </div>
        </div>
    </div>

    <div id="ssc-ve-panel-ecg" class="ssc-ve-panel" role="tabpanel" aria-labelledby="ssc-ve-tab-ecg" tabindex="0" hidden>
         <div class="ssc-two ssc-two--align-start">
            <div class="ssc-pane">
                <h3><?php esc_html_e('Paramètres de l\'ECG', 'supersede-css-jlg'); ?></h3>
                <label for="ssc-ecg-preset" class="ssc-form-label"><?php esc_html_e('Preset de rythme', 'supersede-css-jlg'); ?></label>
                <select id="ssc-ecg-preset" class="regular-text"><option value="stable"><?php esc_html_e('Stable', 'supersede-css-jlg'); ?></option><option value="fast"><?php esc_html_e('Rapide', 'supersede-css-jlg'); ?></option><option value="critical"><?php esc_html_e('Critique', 'supersede-css-jlg'); ?></option></select>
                <label for="ssc-ecg-color" class="ssc-form-label ssc-mt-200"><?php esc_html_e('Couleur de la ligne', 'supersede-css-jlg'); ?></label>
                <input type="color" id="ssc-ecg-color" value="#00ff00">
                <label for="ssc-ecg-top" class="ssc-form-label ssc-mt-200"><?php esc_html_e('Positionnement (top)', 'supersede-css-jlg'); ?></label>
                <input type="range" id="ssc-ecg-top" min="0" max="100" value="50" step="1"><span id="ssc-ecg-top-val"><?php echo esc_html__('50%', 'supersede-css-jlg'); ?></span>
                <label for="ssc-ecg-z-index" class="ssc-form-label ssc-mt-200"><?php esc_html_e('Superposition (z-index)', 'supersede-css-jlg'); ?></label>
                <input type="range" id="ssc-ecg-z-index" min="-10" max="10" value="1" step="1"><span id="ssc-ecg-z-index-val"><?php echo esc_html__('1', 'supersede-css-jlg'); ?></span>
                <hr>
                <label for="ssc-ecg-upload-btn" class="ssc-form-label"><?php esc_html_e('Logo/Image au centre', 'supersede-css-jlg'); ?></label>
                <button id="ssc-ecg-upload-btn" class="button"><?php esc_html_e('Choisir une image', 'supersede-css-jlg'); ?></button>
                <label for="ssc-ecg-logo-size" class="ssc-form-label ssc-mt-200"><?php esc_html_e('Taille du logo', 'supersede-css-jlg'); ?></label>
                <input type="range" id="ssc-ecg-logo-size" min="20" max="200" value="100" step="1"><span id="ssc-ecg-logo-size-val"><?php echo esc_html__('100px', 'supersede-css-jlg'); ?></span>
                <hr>
                <pre id="ssc-ecg-css" class="ssc-code ssc-code-small ssc-mt-200"></pre>
                <button id="ssc-ecg-apply" class="button button-primary ssc-mt-100"><?php esc_html_e('Appliquer l\'Effet', 'supersede-css-jlg'); ?></button>
            </div>
            <div class="ssc-pane">
                <h3><?php esc_html_e('Aperçu', 'supersede-css-jlg'); ?></h3>
                <div id="ssc-ecg-preview-container" class="ssc-ve-preview-box">
                    <img id="ssc-ecg-logo-preview" src="" alt="<?php echo esc_attr__('Logo Preview', 'supersede-css-jlg'); ?>">
                    <svg id="ssc-ecg-preview-svg" viewBox="0 0 400 60" preserveAspectRatio="none"><path id="ssc-ecg-preview-path" class="ssc-ecg-path" d="M0,30 L100,30 L110,18 L120,42 L130,26 L140,30 L240,30 L250,20 L260,40 L270,28 L280,30 L400,30"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div id="ssc-ve-panel-backgrounds" class="ssc-ve-panel active" role="tabpanel" aria-labelledby="ssc-ve-tab-backgrounds" tabindex="0">
         <div class="ssc-two ssc-two--align-start">
            <div class="ssc-pane">
                <h3><?php esc_html_e('Paramètres du Fond', 'supersede-css-jlg'); ?></h3>
                <label for="ssc-bg-type" class="screen-reader-text"><?php esc_html_e('Type de fond', 'supersede-css-jlg'); ?></label>
                <select id="ssc-bg-type" class="regular-text"><option value="stars"><?php esc_html_e('Étoiles', 'supersede-css-jlg'); ?></option><option value="gradient"><?php esc_html_e('Dégradé', 'supersede-css-jlg'); ?></option></select>
                <div id="ssc-bg-controls-stars"><label for="starColor"><?php esc_html_e('Couleur', 'supersede-css-jlg'); ?></label><input type="color" id="starColor" value="#FFFFFF"><label for="starCount"><?php esc_html_e('Nombre', 'supersede-css-jlg'); ?></label><input type="range" id="starCount" min="50" max="500" value="200" step="10"></div>
                <div id="ssc-bg-controls-gradient" class="ssc-ve-gradient-controls ssc-hidden">
                    <div class="ssc-gradient-angle">
                        <label for="gradientAngle"><?php esc_html_e('Angle du dégradé', 'supersede-css-jlg'); ?></label>
                        <input type="number" id="gradientAngle" min="0" max="360" step="1" value="135" class="small-text">°
                    </div>
                    <div class="ssc-gradient-stops-wrapper">
                        <div class="ssc-gradient-stops-header">
                            <label for="ssc-gradient-stops-list"><?php esc_html_e('Arrêts de couleur', 'supersede-css-jlg'); ?></label>
                            <button type="button" class="button" id="ssc-add-gradient-stop"><?php esc_html_e('Ajouter un arrêt', 'supersede-css-jlg'); ?></button>
                        </div>
                        <div id="ssc-gradient-stops-list" class="ssc-gradient-stops" role="list"></div>
                        <p class="description"><?php esc_html_e('Chaque arrêt doit avoir une position entre 0% et 100%. Un minimum de deux arrêts est requis.', 'supersede-css-jlg'); ?></p>
                        <div id="ssc-gradient-errors" class="notice notice-error ssc-ve-gradient-errors"></div>
                    </div>
                    <label for="gradientSpeed"><?php esc_html_e('Vitesse', 'supersede-css-jlg'); ?></label>
                    <input type="range" id="gradientSpeed" min="2" max="20" value="10" step="1">
                </div>
                <label for="ssc-bg-preset-name" class="ssc-form-label ssc-mt-200"><?php esc_html_e('Nom du preset', 'supersede-css-jlg'); ?></label>
                <div class="ssc-ve-preset-save">
                    <input type="text" id="ssc-bg-preset-name" class="regular-text ssc-ve-preset-save__input" placeholder="<?php echo esc_attr__('Nom du preset…', 'supersede-css-jlg'); ?>">
                    <button type="button" id="ssc-bg-save-preset" class="button button-secondary"><?php esc_html_e('Enregistrer le preset', 'supersede-css-jlg'); ?></button>
                </div>
                <div class="ssc-ve-code-actions">
                    <pre id="ssc-bg-css" class="ssc-code"></pre>
                    <button type="button" id="ssc-bg-copy-css" class="button button-secondary"><?php esc_html_e('Copier le CSS', 'supersede-css-jlg'); ?></button>
                </div>
                <button id="ssc-bg-apply" class="button button-primary ssc-mt-100">
                    <?php esc_html_e('Appliquer', 'supersede-css-jlg'); ?>
                </button>
                <div class="ssc-ve-presets-section">
                    <h4><?php esc_html_e('Presets enregistrés', 'supersede-css-jlg'); ?></h4>
                    <p id="ssc-bg-presets-empty" class="description ssc-ve-presets-empty">
                        <?php esc_html_e('Aucun preset enregistré pour le moment.', 'supersede-css-jlg'); ?>
                    </p>
                    <table class="widefat striped ssc-ve-presets-table ssc-mt-150">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Nom', 'supersede-css-jlg'); ?></th>
                                <th scope="col" class="ssc-ve-presets-table__col ssc-ve-presets-table__col--small">
                                    <?php esc_html_e('Type', 'supersede-css-jlg'); ?>
                                </th>
                                <th scope="col" class="ssc-ve-presets-table__col ssc-ve-presets-table__col--medium">
                                    <?php esc_html_e('Actions', 'supersede-css-jlg'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="ssc-bg-presets-list"></tbody>
                    </table>
                </div>
            </div>
            <div class="ssc-pane">
                <h3><?php esc_html_e('Aperçu', 'supersede-css-jlg'); ?></h3>
                <div id="ssc-bg-preview" class="ssc-ve-preview-box"></div>
            </div>
        </div>
    </div>
</div>
