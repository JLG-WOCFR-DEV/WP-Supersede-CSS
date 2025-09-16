<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2>🌐 Tron Grid Animator</h2>
    <p>Générez un fond de grille animé et personnalisable. Idéal pour des bannières ou des fonds de section futuristes.</p>

    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3>Paramètres de la Grille</h3>

            <label><strong>Couleur des lignes</strong></label>
            <input type="color" id="ssc-tron-line-color" value="#00ffff">

            <label style="margin-top:16px; display:block;"><strong>Couleur de fond (Dégradé)</strong></label>
            <div class="ssc-actions">
                <span>Haut :</span> <input type="color" id="ssc-tron-bg1" value="#0a0a23">
                <span>Bas :</span> <input type="color" id="ssc-tron-bg2" value="#000000">
            </div>

            <label style="margin-top:16px; display:block;"><strong>Taille de la grille (pixels)</strong></label>
            <input type="range" id="ssc-tron-size" min="20" max="200" value="50" step="5">
            <span id="ssc-tron-size-val">50px</span>

            <label style="margin-top:16px; display:block;"><strong>Épaisseur des lignes (pixels)</strong></label>
            <input type="range" id="ssc-tron-thickness" min="1" max="5" value="1" step="1">
            <span id="ssc-tron-thickness-val">1px</span>

            <label style="margin-top:16px; display:block;"><strong>Vitesse de l'animation (secondes)</strong></label>
            <input type="range" id="ssc-tron-speed" min="1" max="30" value="10" step="1">
            <span id="ssc-tron-speed-val">10s</span>

            <div class="ssc-actions" style="margin-top:24px; border-top: 1px solid var(--ssc-border); padding-top: 16px;">
                <button id="ssc-tron-apply" class="button button-primary">Appliquer sur le site</button>
                <button id="ssc-tron-copy" class="button">Copier le CSS</button>
            </div>

            <h3 style="margin-top:24px;">Comment utiliser cet effet ?</h3>
            <p class="description">
                Le bouton <strong>"Appliquer sur le site"</strong> ajoute le code CSS généré à la feuille de style globale de votre site. Vous pouvez ensuite utiliser la classe <code>.ssc-tron-grid-bg</code> sur n'importe quel élément (div, section, etc.) pour lui appliquer ce fond.
            </p>
            <p class="description">
                <strong>Pour créer plusieurs grilles différentes :</strong>
            </p>
            <ol style="padding-left: 20px;" class="description">
                <li>Personnalisez votre première grille ici.</li>
                <li>Cliquez sur <strong>"Copier CSS"</strong>.</li>
                <li>Allez dans le module <strong>"Utilities"</strong> (l'éditeur CSS principal).</li>
                <li>Collez le code et renommez la classe principale, par exemple en <code>.ma-grille-bleue</code>.</li>
                <li>Revenez ici, créez une autre variation, et répétez l'opération avec un nouveau nom de classe (ex: <code>.ma-grille-rouge</code>).</li>
            </ol>

            <pre id="ssc-tron-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3>Aperçu en Direct</h3>
            <div id="ssc-tron-preview" style="height: 300px; border-radius: 12px; border: 1px solid var(--ssc-border); overflow: hidden;"></div>
        </div>
    </div>
</div>
