<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
    .ssc-ve-tabs { display: flex; border-bottom: 1px solid var(--ssc-border); margin-bottom: 16px; }
    .ssc-ve-tab { padding: 10px 16px; cursor: pointer; border-bottom: 2px solid transparent; }
    .ssc-ve-tab.active { color: var(--ssc-accent); border-bottom-color: var(--ssc-accent); font-weight: 600; }
    .ssc-ve-panel { display: none; }
    .ssc-ve-panel.active { display: block; }
    .ssc-ve-preview-box { height: 300px; border-radius: 12px; border: 1px solid var(--ssc-border); overflow: hidden; position: relative; background: #000; }
    #ssc-crt-canvas { width: 100%; height: 100%; }
    .ssc-ecg-path { fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; transition: all 0.3s; }
    #ssc-ecg-preview-container { position: relative; background: #0b1020; display: grid; place-items: center; }
    #ssc-ecg-preview-svg { position: absolute; width: 100%; height: auto; }
    #ssc-ecg-logo-preview { max-width: 100px; max-height: 100px; z-index: 5; }
    .ssc-grid-three { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; }
</style>
<div class="ssc-app ssc-fullwidth">
    <h2>🎬 Générateur d'Effets Visuels</h2>
    <p>Une collection d'effets visuels avancés pour animer vos fonds, images et conteneurs.</p>
    <div class="ssc-ve-tabs">
        <div class="ssc-ve-tab active" data-tab="backgrounds">🌌 Fonds Animés</div>
        <div class="ssc-ve-tab" data-tab="ecg">❤️ ECG / Battement de Cœur</div>
        <div class="ssc-ve-tab" data-tab="crt">📺 Effet CRT (Scanline)</div>
    </div>

    <div id="ssc-ve-panel-crt" class="ssc-ve-panel">
        <div class="ssc-two" style="align-items: flex-start;">
            <div class="ssc-pane">
                <h3>Paramètres de l'effet CRT</h3>
                <p class="description">Cet effet est purement décoratif et ne génère pas de CSS à exporter.</p>
                <div class="ssc-grid-three">
                    <div><label>Couleur Scanline</label><input type="color" class="ssc-crt-control" id="scanlineColor" value="#00ff00"></div>
                    <div><label>Opacité Scanline</label><input type="range" class="ssc-crt-control" id="scanlineOpacity" min="0" max="1" value="0.4" step="0.05"></div>
                    <div><label>Vitesse Scanline</label><input type="range" class="ssc-crt-control" id="scanlineSpeed" min="0.1" max="2" value="0.5" step="0.1"></div>
                    <div><label>Intensité Bruit</label><input type="range" class="ssc-crt-control" id="noiseIntensity" min="0" max="0.5" value="0.1" step="0.02"></div>
                    <div><label>Aberration Chromatique</label><input type="range" class="ssc-crt-control" id="chromaticAberration" min="0" max="5" value="1" step="0.5"></div>
                </div>
            </div>
            <div class="ssc-pane">
                <h3>Aperçu</h3>
                <div class="ssc-ve-preview-box"><canvas id="ssc-crt-canvas"></canvas></div>
            </div>
        </div>
    </div>

    <div id="ssc-ve-panel-ecg" class="ssc-ve-panel">
         <div class="ssc-two" style="align-items: flex-start;">
            <div class="ssc-pane">
                <h3>Paramètres de l'ECG</h3>
                <label><strong>Preset de Rythme</strong></label>
                <select id="ssc-ecg-preset" class="regular-text"><option value="stable">Stable</option><option value="fast">Rapide</option><option value="critical">Critique</option></select>
                <label style="margin-top:16px;"><strong>Couleur de la ligne</strong></label>
                <input type="color" id="ssc-ecg-color" value="#00ff00">
                <label style="margin-top:16px;"><strong>Positionnement (top)</strong></label>
                <input type="range" id="ssc-ecg-top" min="0" max="100" value="50" step="1"><span id="ssc-ecg-top-val">50%</span>
                <label style="margin-top:16px;"><strong>Superposition (z-index)</strong></label>
                <input type="range" id="ssc-ecg-z-index" min="-10" max="10" value="1" step="1"><span id="ssc-ecg-z-index-val">1</span>
                <hr>
                <label><strong>Logo/Image au centre</strong></label>
                <button id="ssc-ecg-upload-btn" class="button">Choisir une image</button>
                <label style="margin-top:16px;"><strong>Taille du logo</strong></label>
                <input type="range" id="ssc-ecg-logo-size" min="20" max="200" value="100" step="1"><span id="ssc-ecg-logo-size-val">100px</span>
                <hr>
                <pre id="ssc-ecg-css" class="ssc-code ssc-code-small" style="margin-top:16px;"></pre>
                <button id="ssc-ecg-apply" class="button button-primary" style="margin-top:8px;">Appliquer l'Effet</button>
            </div>
            <div class="ssc-pane">
                <h3>Aperçu</h3>
                <div id="ssc-ecg-preview-container" class="ssc-ve-preview-box">
                    <img id="ssc-ecg-logo-preview" src="" alt="Logo Preview" style="display:none;">
                    <svg id="ssc-ecg-preview-svg" viewBox="0 0 400 60" preserveAspectRatio="none"><path id="ssc-ecg-preview-path" class="ssc-ecg-path" d="M0,30 L100,30 L110,18 L120,42 L130,26 L140,30 L240,30 L250,20 L260,40 L270,28 L280,30 L400,30"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div id="ssc-ve-panel-backgrounds" class="ssc-ve-panel">
         <div class="ssc-two" style="align-items: flex-start;">
            <div class="ssc-pane">
                <h3>Paramètres du Fond</h3>
                <select id="ssc-bg-type" class="regular-text"><option value="stars">Étoiles</option><option value="gradient">Dégradé</option></select>
                <div id="ssc-bg-controls-stars"><label>Couleur</label><input type="color" id="starColor" value="#FFFFFF"><label>Nombre</label><input type="range" id="starCount" min="50" max="500" value="200" step="10"></div>
                <div id="ssc-bg-controls-gradient" style="display:none;"><label>Vitesse</label><input type="range" id="gradientSpeed" min="2" max="20" value="10" step="1"></div>
                 <pre id="ssc-bg-css" class="ssc-code"></pre>
                <button id="ssc-bg-apply" class="button button-primary">Appliquer</button>
            </div>
            <div class="ssc-pane">
                <h3>Aperçu</h3>
                <div id="ssc-bg-preview" class="ssc-ve-preview-box"></div>
            </div>
        </div>
    </div>
</div>
