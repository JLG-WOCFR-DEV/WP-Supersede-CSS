<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
    #ssc-typo-preview { transition: font-size 0.1s linear; }
    .ssc-typo-vp-slider-container { width: 100%; background: var(--ssc-bg); padding: 10px; border-radius: 8px; margin-top: 10px; }
</style>
<div class="ssc-app ssc-fullwidth">
    <h2>📏 Typographie Fluide (Clamp)</h2>
    <p>Générez du texte qui s'adapte parfaitement à toutes les tailles d'écran, sans "sauts" disgracieux.</p>
    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3>Paramètres de la Police (en pixels)</h3>
            <div class="ssc-two">
                <div><label>Taille min. police</label><input type="number" id="ssc-typo-min-fs" value="16" class="small-text"></div>
                <div><label>Taille max. police</label><input type="number" id="ssc-typo-max-fs" value="48" class="small-text"></div>
            </div>
            <div class="ssc-two" style="margin-top: 12px;">
                <div><label>Taille min. viewport (px)</label><input type="number" id="ssc-typo-min-vp" value="320" class="small-text"></div>
                <div><label>Taille max. viewport (px)</label><input type="number" id="ssc-typo-max-vp" value="1280" class="small-text"></div>
            </div>
            <label style="margin-top: 16px;">Texte à prévisualiser</label>
            <input type="text" id="ssc-typo-text" class="large-text" value="Design fluide, lecture parfaite.">
            <div class="ssc-actions" style="margin-top: 16px; border-top: 1px solid var(--ssc-border); padding-top: 16px;">
                <button id="ssc-typo-generate" class="button button-primary">Générer</button>
                <button id="ssc-typo-copy" class="button">Copier le CSS</button>
            </div>
            <pre id="ssc-typo-css" class="ssc-code" style="margin-top: 16px;"></pre>
        </div>
        <div class="ssc-pane">
            <h3>Aperçu</h3>
            <div class="ssc-typo-vp-slider-container">
                <label>Largeur du viewport (px)</label>
                <input type="range" id="ssc-typo-vp-slider" min="320" max="1280" value="960">
                <span id="ssc-typo-vp-value">960px</span>
            </div>
            <div id="ssc-typo-preview" style="margin-top: 16px; font-size: clamp(16px, 3vw, 48px);">Design fluide, lecture parfaite.</div>
        </div>
    </div>
</div>
