<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2>Preset Designer</h2>
    <div class="ssc-panel" style="margin-bottom: 16px;">
        <h3>Comment utiliser les Presets ?</h3>
        <p>Les presets sont des ensembles de styles réutilisables que vous pouvez créer une fois et appliquer à n'importe quel élément de votre site.</p>
        <ol style="margin-left: 20px;">
            <li><strong>Créer un Preset :</strong> Dans l'éditeur ci-dessous, donnez un nom (ex: "Bouton Principal Rouge"), un sélecteur CSS (<code>.btn-red</code>) et ajoutez les propriétés CSS (<code>background-color</code>, <code>color</code>, etc.).</li>
            <li><strong>Enregistrer :</strong> Cliquez sur "Enregistrer". Votre preset apparaît maintenant dans la liste des "Presets Existants".</li>
            <li><strong>Appliquer un Preset :</strong> Utilisez la section "Quick Apply" en haut. Cherchez votre preset, sélectionnez-le et cliquez sur "Appliquer". Le CSS du preset sera ajouté à votre feuille de style globale.</li>
        </ol>
    </div>

    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane" style="flex: 1.5;"><h3>Quick Apply</h3><div class="ssc-actions"><input type="search" id="ssc-qa-search" class="regular-text" placeholder="Rechercher..."><select id="ssc-qa-select" class="regular-text"></select><button id="ssc-qa-apply" class="button button-primary">Appliquer</button></div></div>
        <div class="ssc-pane" style="flex: 1;"><h3>Presets Existants</h3><ul id="ssc-presets-list" class="ssc-list"></ul></div>
    </div>
    <div class="ssc-panel" style="margin-top: 16px;">
        <h3>Créer / Modifier un Preset</h3>
        <div class="ssc-two">
            <div><label><strong>Nom</strong></label><input type="text" id="ssc-preset-name" class="regular-text" placeholder="ex: Bouton arrondi"></div>
            <div><label><strong>Sélecteur CSS</strong></label><input type="text" id="ssc-preset-scope" class="regular-text" placeholder=".btn:hover"></div>
        </div>
        <label style="margin-top: 12px; display: block;"><strong>Propriétés CSS</strong></label>
        <div id="ssc-preset-props-builder" class="ssc-kv-builder"></div>
        <button type="button" id="ssc-preset-add-prop" class="button" style="margin-top:8px;">+ Ajouter</button>
        <div class="ssc-actions" style="margin-top:16px; border-top: 1px solid #eee; padding-top: 16px;">
            <button id="ssc-save-preset" class="button button-primary">Enregistrer</button>
            <button id="ssc-delete-preset" class="button button-link-delete" style="display:none;">Supprimer</button>
        </div>
        <div id="ssc-preset-msg" class="ssc-muted"></div>
    </div>
</div>
