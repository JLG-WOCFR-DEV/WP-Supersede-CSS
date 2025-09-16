<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $tokens_css */
?>
<div class="ssc-app ssc-fullwidth">
    <div class="ssc-panel">
        <h2>🚀 Bienvenue dans le Gestionnaire de Tokens</h2>
        <p>Cet outil vous aide à centraliser les valeurs fondamentales de votre design (couleurs, polices, etc.) pour les réutiliser facilement et maintenir une cohérence parfaite sur votre site.</p>
    </div>

    <div class="ssc-two" style="margin-top:16px; align-items: flex-start;">
        <div class="ssc-pane">
            <h3>👨‍🏫 Qu'est-ce qu'un Token (ou Variable CSS) ?</h3>
            <p>Imaginez que vous décidiez d'utiliser une couleur bleue spécifique (`#3498db`) pour tous vos boutons et titres. Si un jour vous voulez changer ce bleu, vous devriez chercher et remplacer cette valeur partout dans votre code. C'est long et risqué !</p>
            <p>Un <strong>token</strong> est un "raccourci". Vous donnez un nom facile à retenir à votre couleur, comme <code>--couleur-principale</code>. Ensuite, vous utilisez ce nom partout où vous avez besoin de ce bleu.</p>
            <p><strong>Le jour où vous voulez changer de couleur, il suffit de modifier la valeur du token en un seul endroit, et la modification s'applique partout !</strong></p>
            <hr>
            <h4>Exemple Concret</h4>
            <p><strong>1. Définition du Token :</strong><br>On définit le token une seule fois, généralement sur l'élément <code>:root</code> (la racine de votre page).</p>
            <pre class="ssc-code">:root {
   --couleur-principale: #3498db;
   --radius-arrondi: 8px;
}</pre>
            <p><strong>2. Utilisation des Tokens :</strong><br>Ensuite, on utilise la fonction <code>var()</code> pour appeler la valeur du token.</p>
            <pre class="ssc-code">.mon-bouton {
   background-color: var(--couleur-principale);
   border-radius: var(--radius-arrondi);
   color: white;
}

.mon-titre {
   color: var(--couleur-principale);
}</pre>
        </div>
        <div class="ssc-pane">
            <h3>🎨 Éditeur Visuel de Tokens</h3>
            <p>Utilisez cet éditeur pour créer et gérer vos tokens sans écrire de code. Les modifications apparaîtront dans la zone de texte ci-dessous.</p>

            <div id="ssc-token-builder">
                <!-- Les tokens seront ajoutés ici par JavaScript -->
            </div>

            <div class="ssc-actions" style="margin-top:12px;">
                <button id="ssc-token-add" class="button">+ Ajouter un Token</button>
            </div>

            <hr>

            <h3>📜 Code CSS des Tokens (`:root`)</h3>
            <p>C'est ici que le code généré par l'éditeur visuel apparaît. Vous pouvez aussi y coller directement votre propre code.</p>
            <textarea id="ssc-tokens" rows="10" class="large-text"><?php echo esc_textarea($tokens_css); ?></textarea>
            <div class="ssc-actions" style="margin-top:8px;">
                <button id="ssc-tokens-apply" class="button button-primary">Appliquer les Tokens sur le site</button>
                <button id="ssc-tokens-copy" class="button">Copier le Code</button>
            </div>
        </div>
    </div>

    <div class="ssc-panel" style="margin-top:16px;">
        <h3>👁️ Aperçu en Direct</h3>
        <p>Voyez comment vos tokens affectent les éléments. Le style de cet aperçu est directement contrôlé par le code CSS ci-dessus.</p>
        <style id="ssc-tokens-preview-style"></style>
        <div id="ssc-tokens-preview" style="padding: 24px; border: 2px dashed var(--couleur-principale, #ccc); border-radius: var(--radius-moyen, 8px); background: #fff;">
            <button class="button button-primary" style="background-color: var(--couleur-principale); border-radius: var(--radius-moyen);">Bouton Principal</button>
            <a href="#" style="color: var(--couleur-principale); margin-left: 16px;">Lien Principal</a>
        </div>
    </div>
</div>
