<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $tokens_css */
/** @var array<int, array{name: string, value: string, type: string, description: string, group: string}> $tokens_registry */
/** @var array<string, array{label: string, input: string}> $token_types */

if (function_exists('wp_localize_script')) {
    wp_localize_script('ssc-tokens', 'SSC_TOKENS_DATA', [
        'tokens' => $tokens_registry,
        'types' => $token_types,
        'css' => $tokens_css,
        'i18n' => [
            'addToken' => __('Ajouter un token', 'supersede-css-jlg'),
            'emptyState' => __('Aucun token pour le moment. Utilisez le bouton ci-dessous pour commencer.', 'supersede-css-jlg'),
            'groupLabel' => __('Groupe', 'supersede-css-jlg'),
            'nameLabel' => __('Nom', 'supersede-css-jlg'),
            'valueLabel' => __('Valeur', 'supersede-css-jlg'),
            'typeLabel' => __('Type', 'supersede-css-jlg'),
            'descriptionLabel' => __('Description', 'supersede-css-jlg'),
            'deleteLabel' => __('Supprimer', 'supersede-css-jlg'),
            'saveSuccess' => __('Tokens enregistrés', 'supersede-css-jlg'),
            'saveError' => __('Impossible d’enregistrer les tokens.', 'supersede-css-jlg'),
        ],
    ]);
}
?>
<div class="ssc-app ssc-fullwidth">
    <div class="ssc-panel">
        <h2>🚀 Bienvenue dans le Gestionnaire de Tokens</h2>
        <p>Cet outil vous aide à centraliser les valeurs fondamentales de votre design (couleurs, polices, espacements…) pour les réutiliser facilement et maintenir une cohérence parfaite sur votre site.</p>
    </div>

    <div class="ssc-two" style="margin-top:16px; align-items: flex-start;">
        <div class="ssc-pane">
            <h3>👨‍🏫 Qu'est-ce qu'un Token (ou Variable CSS) ?</h3>
            <p>Imaginez que vous décidiez d'utiliser une couleur bleue spécifique (<code>#3498db</code>) pour tous vos boutons et titres. Si un jour vous voulez changer ce bleu, vous devriez chercher et remplacer cette valeur partout dans votre code. C'est long et risqué !</p>
            <p>Un <strong>token</strong> est un « raccourci ». Vous donnez un nom facile à retenir à votre couleur, comme <code>--couleur-principale</code>. Ensuite, vous utilisez ce nom partout où vous avez besoin de ce bleu.</p>
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
            <p>Gérez vos tokens sous forme de fiches structurées : nom technique, valeur, type de champ, description et groupe d'appartenance. Chaque catégorie est listée séparément pour garder une vision claire de votre système de design.</p>

            <style>
                .ssc-token-builder { display: flex; flex-direction: column; gap: 16px; }
                .ssc-token-group { border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; background: #fff; }
                .ssc-token-group h4 { margin: 0 0 8px; }
                .ssc-token-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
                .ssc-token-field { display: flex; flex-direction: column; gap: 4px; flex: 1 1 180px; min-width: 180px; }
                .ssc-token-field__label { font-weight: 600; font-size: 13px; }
                .ssc-token-field-input { width: 100%; }
                .ssc-token-field textarea { resize: vertical; }
                .ssc-token-empty { margin: 0; font-style: italic; }
            </style>

            <div class="ssc-token-toolbar" style="margin-bottom:12px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <button id="ssc-token-add" class="button">+ Ajouter un Token</button>
            </div>

            <div id="ssc-token-builder" class="ssc-token-builder" aria-live="polite">
                <!-- Hydraté par JavaScript -->
            </div>

            <hr>

            <h3>📜 Code CSS généré (<code>:root</code>)</h3>
            <p>Le code ci-dessous est synchronisé automatiquement avec la configuration JSON. Il est proposé en lecture seule pour vérification ou copie rapide.</p>
            <textarea id="ssc-tokens" rows="10" class="large-text" readonly><?php echo esc_textarea($tokens_css); ?></textarea>
            <div class="ssc-actions" style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                <button id="ssc-tokens-save" class="button button-primary">Enregistrer les Tokens</button>
                <button id="ssc-tokens-copy" class="button">Copier le CSS</button>
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
