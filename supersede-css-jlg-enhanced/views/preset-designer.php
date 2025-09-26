<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('Preset Designer', 'supersede-css-jlg'); ?></h2>
    <div class="ssc-panel" style="margin-bottom: 16px;">
        <h3><?php esc_html_e('Comment utiliser les Presets ?', 'supersede-css-jlg'); ?></h3>
        <p><?php esc_html_e("Les presets sont des ensembles de styles réutilisables que vous pouvez créer une fois et appliquer à n'importe quel élément de votre site.", 'supersede-css-jlg'); ?></p>
        <ol style="margin-left: 20px;">
            <li><?php echo wp_kses_post(__('<strong>Créer un Preset :</strong> Dans l\'éditeur ci-dessous, donnez un nom (ex: "Bouton Principal Rouge"), un sélecteur CSS (<code>.btn-red</code>) et ajoutez les propriétés CSS (<code>background-color</code>, <code>color</code>, etc.).', 'supersede-css-jlg')); ?></li>
            <li><?php echo wp_kses_post(__('<strong>Enregistrer :</strong> Cliquez sur "Enregistrer". Votre preset apparaît maintenant dans la liste des "Presets Existants".', 'supersede-css-jlg')); ?></li>
            <li><?php echo wp_kses_post(__('<strong>Appliquer un Preset :</strong> Utilisez la section "Quick Apply" en haut. Cherchez votre preset, sélectionnez-le et cliquez sur "Appliquer". Le CSS du preset sera ajouté à votre feuille de style globale.', 'supersede-css-jlg')); ?></li>
        </ol>
    </div>

    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane" style="flex: 1.5;"><h3><?php esc_html_e('Quick Apply', 'supersede-css-jlg'); ?></h3><div class="ssc-actions"><input type="search" id="ssc-qa-search" class="regular-text" placeholder="<?php echo esc_attr__('Rechercher...', 'supersede-css-jlg'); ?>"><select id="ssc-qa-select" class="regular-text"></select><button id="ssc-qa-apply" class="button button-primary"><?php esc_html_e('Appliquer', 'supersede-css-jlg'); ?></button></div></div>
        <div class="ssc-pane" style="flex: 1;"><h3><?php esc_html_e('Presets Existants', 'supersede-css-jlg'); ?></h3><ul id="ssc-presets-list" class="ssc-list"></ul></div>
    </div>
    <div class="ssc-panel" style="margin-top: 16px;">
        <h3><?php esc_html_e('Créer / Modifier un Preset', 'supersede-css-jlg'); ?></h3>
        <div class="ssc-two">
            <div><label><strong><?php esc_html_e('Nom', 'supersede-css-jlg'); ?></strong></label><input type="text" id="ssc-preset-name" class="regular-text" placeholder="<?php echo esc_attr__('ex: Bouton arrondi', 'supersede-css-jlg'); ?>"></div>
            <div><label><strong><?php esc_html_e('Sélecteur CSS', 'supersede-css-jlg'); ?></strong></label><input type="text" id="ssc-preset-scope" class="regular-text" placeholder="<?php echo esc_attr__('.btn:hover', 'supersede-css-jlg'); ?>"></div>
        </div>
        <label style="margin-top: 12px; display: block;"><strong><?php esc_html_e('Propriétés CSS', 'supersede-css-jlg'); ?></strong></label>
        <div id="ssc-preset-props-builder" class="ssc-kv-builder"></div>
        <button type="button" id="ssc-preset-add-prop" class="button" style="margin-top:8px;"><?php esc_html_e('+ Ajouter', 'supersede-css-jlg'); ?></button>
        <div class="ssc-actions" style="margin-top:16px; border-top: 1px solid #eee; padding-top: 16px;">
            <button id="ssc-save-preset" class="button button-primary"><?php esc_html_e('Enregistrer', 'supersede-css-jlg'); ?></button>
            <button id="ssc-delete-preset" class="button button-link-delete" style="display:none;"><?php esc_html_e('Supprimer', 'supersede-css-jlg'); ?></button>
        </div>
        <div id="ssc-preset-msg" class="ssc-muted"></div>
    </div>
</div>
