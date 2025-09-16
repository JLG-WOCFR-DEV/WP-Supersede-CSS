<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $preview_background */
?>
<style>
    #ssc-filter-preview-bg {
        background-image: url('<?php echo esc_url($preview_background); ?>');
        background-size: cover;
        border-radius: 12px;
        padding: 24px;
        display: grid;
        place-items: center;
    }
    #ssc-filter-preview-box {
        transition: all 0.2s ease-in-out;
        width: 80%;
        height: 250px;
        color: white;
        font-size: 24px;
        font-weight: bold;
        text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        display: grid;
        place-items: center;
        border-radius: 16px;
    }
    .ssc-glassmorphism-preview {
        background: rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
</style>
<div class="ssc-app ssc-fullwidth">
    <h2>🎨 Éditeur de Filtres & Effets de Verre</h2>
    <p>Appliquez des filtres visuels à vos images et conteneurs, ou créez un effet "Glassmorphism" tendance.</p>

    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3>Filtres CSS (<code>filter</code>)</h3>
            <div class="ssc-two">
                <div><label>Flou (Blur)</label><input type="range" class="ssc-filter-prop" data-prop="blur" min="0" max="20" value="0" step="1"> <span id="val-blur">0px</span></div>
                <div><label>Luminosité</label><input type="range" class="ssc-filter-prop" data-prop="brightness" min="0" max="200" value="100" step="5"> <span id="val-brightness">100%</span></div>
                <div><label>Contraste</label><input type="range" class="ssc-filter-prop" data-prop="contrast" min="0" max="200" value="100" step="5"> <span id="val-contrast">100%</span></div>
                <div><label>Niveaux de gris</label><input type="range" class="ssc-filter-prop" data-prop="grayscale" min="0" max="100" value="0" step="5"> <span id="val-grayscale">0%</span></div>
                <div><label>Rotation de teinte</label><input type="range" class="ssc-filter-prop" data-prop="hue-rotate" min="0" max="360" value="0" step="15"> <span id="val-hue-rotate">0deg</span></div>
                <div><label>Saturation</label><input type="range" class="ssc-filter-prop" data-prop="saturate" min="0" max="200" value="100" step="5"> <span id="val-saturate">100%</span></div>
            </div>
            <hr>
            <h3>Effet Verre (<code>backdrop-filter</code>)</h3>
            <label><input type="checkbox" id="ssc-glass-enable"> <strong>Activer le Glassmorphism</strong></label>
            <pre id="ssc-filter-css" class="ssc-code" style="margin-top:16px;"></pre>
            <div class="ssc-actions"><button id="ssc-filter-copy" class="button">Copier le CSS</button></div>
        </div>
        <div class="ssc-pane">
            <h3>Aperçu en Direct</h3>
            <div id="ssc-filter-preview-bg">
                <div id="ssc-filter-preview-box">
                    Votre Contenu Ici
                </div>
            </div>
        </div>
    </div>
</div>
