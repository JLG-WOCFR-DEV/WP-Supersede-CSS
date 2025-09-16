<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2>📏 Visual Grid Editor</h2>
    <p>Construisez des mises en page CSS Grid de manière intuitive, sans écrire de code.</p>
    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3>Paramètres de la Grille</h3>

            <label><strong>Nombre de colonnes</strong></label>
            <input type="range" id="ssc-grid-cols" min="1" max="12" value="3" step="1">
            <span id="ssc-grid-cols-val">3</span>

            <label style="margin-top:16px; display:block;"><strong>Espacement (gap) en pixels</strong></label>
            <input type="range" id="ssc-grid-gap" min="0" max="100" value="16" step="1">
            <span id="ssc-grid-gap-val">16px</span>

            <div class="ssc-actions" style="margin-top:24px; border-top: 1px solid var(--ssc-border); padding-top: 16px;">
                <button id="ssc-grid-apply" class="button button-primary">Appliquer</button>
                <button id="ssc-grid-copy" class="button">Copier CSS</button>
            </div>

            <h3 style="margin-top:24px;">Code CSS Généré</h3>
            <p class="description">Appliquez la classe <code>.ssc-grid-container</code> à votre conteneur.</p>
            <pre id="ssc-grid-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3>Aperçu en Direct</h3>
            <div id="ssc-grid-preview" style="display:grid; border:1px dashed var(--ssc-border); padding:10px; border-radius:8px;">
                <!-- Les éléments de la grille seront générés par JS -->
            </div>
        </div>
    </div>
</div>
