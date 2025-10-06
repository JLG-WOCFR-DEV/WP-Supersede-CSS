<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $avatar_placeholder */
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('✨ Gestionnaire de Presets Avatar Glow', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e("Créez et gérez des effets d'aura réutilisables pour vos rédacteurs. Chaque preset aura son propre nom de classe.", 'supersede-css-jlg'); ?></p>

    <div class="ssc-two ssc-align-start">
        <div class="ssc-pane">
            <h3><?php esc_html_e('Éditeur de Presets', 'supersede-css-jlg'); ?></h3>
            <label class="ssc-field-label"><?php esc_html_e('Preset Actif', 'supersede-css-jlg'); ?></label>
            <div class="ssc-actions ssc-field-group">
                <select id="ssc-glow-preset-select" class="regular-text ssc-flex-1"></select>
                <button id="ssc-glow-new-preset" class="button"><?php esc_html_e('Nouveau', 'supersede-css-jlg'); ?></button>
            </div>

            <div id="ssc-glow-editor-fields">
                <hr>
                <div class="ssc-two ssc-align-start">
                    <div><label class="ssc-field-label"><?php esc_html_e('Nom du Preset', 'supersede-css-jlg'); ?></label><input type="text" id="ssc-glow-preset-name" class="regular-text" placeholder="<?php echo esc_attr__('Aura Bleue Rapide', 'supersede-css-jlg'); ?>"></div>
                    <div><label class="ssc-field-label"><?php esc_html_e('Nom de la Classe CSS', 'supersede-css-jlg'); ?></label><input type="text" id="ssc-glow-preset-class" class="regular-text" placeholder="<?php echo esc_attr__('.avatar-glow-blue', 'supersede-css-jlg'); ?>"></div>
                </div>
                <p class="description"><?php printf(wp_kses_post(__('Le nom de la classe doit être unique et commencer par un point (ex: %s).', 'supersede-css-jlg')), '<code>.glow-team-1</code>'); ?></p>
                <hr>

                <h4><?php esc_html_e("Paramètres de l'Effet", 'supersede-css-jlg'); ?></h4>
                <label class="ssc-field-label"><?php esc_html_e('Couleur du dégradé', 'supersede-css-jlg'); ?></label>
                <div class="ssc-actions ssc-field-group">
                    <span><?php esc_html_e('Début :', 'supersede-css-jlg'); ?></span> <input type="color" id="ssc-glow-color1" value="#8b5cf6">
                    <span><?php esc_html_e('Fin :', 'supersede-css-jlg'); ?></span> <input type="color" id="ssc-glow-color2" value="#ec4899">
                </div>
                <label class="ssc-field-label ssc-mt-200"><?php esc_html_e('Vitesse (secondes)', 'supersede-css-jlg'); ?></label>
                <input type="range" id="ssc-glow-speed" min="1" max="20" value="5" step="0.5">
                <span id="ssc-glow-speed-val"><?php echo esc_html__('5s', 'supersede-css-jlg'); ?></span>
                <label class="ssc-field-label ssc-mt-200"><?php esc_html_e('Épaisseur (pixels)', 'supersede-css-jlg'); ?></label>
                <input type="range" id="ssc-glow-thickness" min="2" max="12" value="4" step="1">
                <span id="ssc-glow-thickness-val"><?php echo esc_html__('4px', 'supersede-css-jlg'); ?></span>
            </div>

            <div class="ssc-actions ssc-divider-top">
                <button id="ssc-glow-save-preset" class="button button-primary"><?php esc_html_e('Enregistrer ce Preset', 'supersede-css-jlg'); ?></button>
                <button id="ssc-glow-apply" class="button"><?php esc_html_e('Appliquer sur le site', 'supersede-css-jlg'); ?></button>
                <button id="ssc-glow-delete-preset" class="button button-link-delete ssc-hidden"><?php esc_html_e('Supprimer ce Preset', 'supersede-css-jlg'); ?></button>
            </div>
        </div>

        <div class="ssc-pane">
            <h3><?php esc_html_e('Aperçu en Direct', 'supersede-css-jlg'); ?></h3>
            <div id="ssc-glow-preview-bg" class="ssc-preview-area ssc-preview-area--tall ssc-preview-area--dark">
                <div id="ssc-glow-preview-container" class="ssc-avatar-preview-frame">
                    <img id="ssc-glow-preview-img" class="ssc-avatar-preview-img" src="<?php echo esc_url($avatar_placeholder); ?>" alt="<?php echo esc_attr__('avatar', 'supersede-css-jlg'); ?>">
                </div>
            </div>
            <button id="ssc-glow-upload-btn" class="button ssc-mt-200"><?php esc_html_e("Changer l'image d'avatar", 'supersede-css-jlg'); ?></button>

             <h4 class="ssc-mt-200"><?php esc_html_e("Comment l'utiliser ?", 'supersede-css-jlg'); ?></h4>
             <p class="description"><?php printf(wp_kses_post(__("Une fois le preset enregistré et appliqué, demandez à vos rédacteurs d'ajouter la classe %s au conteneur (la `div`) de leur image.", 'supersede-css-jlg')), '<code id="ssc-glow-how-to-use-class">.avatar-glow-blue</code>'); ?></p>
             <pre id="ssc-glow-css-output" class="ssc-code"></pre>
        </div>
    </div>
</div>
