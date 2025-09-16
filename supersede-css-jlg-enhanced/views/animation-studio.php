<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2>🎬 Animation Studio</h2>
    <p>Choisissez un preset d'animation, personnalisez-le et appliquez-le à vos éléments.</p>
    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3>Paramètres de l'Animation</h3>
            <label><strong>Preset d'animation</strong></label>
            <select id="ssc-anim-preset" class="regular-text">
                <option value="bounce">Bounce (Rebond)</option>
                <option value="pulse">Pulse (Pulsation)</option>
                <option value="fade-in">Fade In (Apparition)</option>
                <option value="slide-in-left">Slide In Left (Glisse depuis la gauche)</option>
            </select>
            <label style="margin-top:16px; display:block;"><strong>Durée (secondes)</strong></label>
            <input type="range" id="ssc-anim-duration" min="0.1" max="5" value="1.5" step="0.1">
            <span id="ssc-anim-duration-val">1.5s</span>
            <div class="ssc-actions" style="margin-top:24px; border-top: 1px solid var(--ssc-border); padding-top: 16px;">
                <button id="ssc-anim-apply" class="button button-primary">Appliquer</button>
                <button id="ssc-anim-copy" class="button">Copier CSS</button>
            </div>
            <h3 style="margin-top:24px;">Code CSS Généré</h3>
            <p class="description">Appliquez la classe <code>.ssc-animated</code> et la classe du preset (ex: <code>.ssc-bounce</code>) à votre élément.</p>
            <pre id="ssc-anim-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3>Aperçu en Direct</h3>
            <div id="ssc-anim-preview-container" style="display:grid; place-items:center; height:200px;">
                <div id="ssc-anim-preview-box" style="width: 100px; height: 100px; background: var(--ssc-accent); border-radius: 12px;"></div>
            </div>
        </div>
    </div>
</div>
