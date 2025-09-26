<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $avatar_placeholder */
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('✨ Gestionnaire de Presets Avatar Glow', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e("Créez et gérez des effets d'aura réutilisables pour vos rédacteurs. Chaque preset aura son propre nom de classe.", 'supersede-css-jlg'); ?></p>

    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3><?php esc_html_e('Éditeur de Presets', 'supersede-css-jlg'); ?></h3>
            <label><strong><?php esc_html_e('Preset Actif', 'supersede-css-jlg'); ?></strong></label>
            <div class="ssc-actions">
                <select id="ssc-glow-preset-select" class="regular-text" style="flex: 1;"></select>
                <button id="ssc-glow-new-preset" class="button"><?php esc_html_e('Nouveau', 'supersede-css-jlg'); ?></button>
            </div>

            <div id="ssc-glow-editor-fields">
                <hr>
                <div class="ssc-two">
                    <div><label><strong><?php esc_html_e('Nom du Preset', 'supersede-css-jlg'); ?></strong></label><input type="text" id="ssc-glow-preset-name" class="regular-text" placeholder="<?php echo esc_attr__('Aura Bleue Rapide', 'supersede-css-jlg'); ?>"></div>
                    <div><label><strong><?php esc_html_e('Nom de la Classe CSS', 'supersede-css-jlg'); ?></strong></label><input type="text" id="ssc-glow-preset-class" class="regular-text" placeholder="<?php echo esc_attr__('.avatar-glow-blue', 'supersede-css-jlg'); ?>"></div>
                </div>
                <p class="description"><?php printf(wp_kses_post(__('Le nom de la classe doit être unique et commencer par un point (ex: %s).', 'supersede-css-jlg')), '<code>.glow-team-1</code>'); ?></p>
                <hr>

                <h4><?php esc_html_e("Paramètres de l'Effet", 'supersede-css-jlg'); ?></h4>
                <label><strong><?php esc_html_e('Couleur du dégradé', 'supersede-css-jlg'); ?></strong></label>
                <div class="ssc-actions">
                    <span><?php esc_html_e('Début :', 'supersede-css-jlg'); ?></span> <input type="color" id="ssc-glow-color1" value="#8b5cf6">
                    <span><?php esc_html_e('Fin :', 'supersede-css-jlg'); ?></span> <input type="color" id="ssc-glow-color2" value="#ec4899">
                </div>
                <label style="margin-top:16px;"><strong><?php esc_html_e('Vitesse (secondes)', 'supersede-css-jlg'); ?></strong></label>
                <input type="range" id="ssc-glow-speed" min="1" max="20" value="5" step="0.5">
                <span id="ssc-glow-speed-val">5s</span>
                <label style="margin-top:16px;"><strong><?php esc_html_e('Épaisseur (pixels)', 'supersede-css-jlg'); ?></strong></label>
                <input type="range" id="ssc-glow-thickness" min="2" max="12" value="4" step="1">
                <span id="ssc-glow-thickness-val">4px</span>
            </div>

            <div class="ssc-actions" style="margin-top:24px; border-top: 1px solid var(--ssc-border); padding-top: 16px;">
                <button id="ssc-glow-save-preset" class="button button-primary"><?php esc_html_e('Enregistrer ce Preset', 'supersede-css-jlg'); ?></button>
                <button id="ssc-glow-apply" class="button"><?php esc_html_e('Appliquer sur le site', 'supersede-css-jlg'); ?></button>
                <button id="ssc-glow-delete-preset" class="button button-link-delete" style="display: none;"><?php esc_html_e('Supprimer ce Preset', 'supersede-css-jlg'); ?></button>
            </div>
        </div>

        <div class="ssc-pane">
            <h3><?php esc_html_e('Aperçu en Direct', 'supersede-css-jlg'); ?></h3>
            <div id="ssc-glow-preview-bg" style="display:grid; place-items:center; height:250px; background: #0b1020; border-radius: 12px; transition: background 0.3s; border: 1px solid var(--ssc-border);">
                <div id="ssc-glow-preview-container" style="width: 128px; height: 128px;">
                    <img id="ssc-glow-preview-img" src="<?php echo esc_url($avatar_placeholder); ?>" alt="<?php echo esc_attr__('avatar', 'supersede-css-jlg'); ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                </div>
            </div>
            <button id="ssc-glow-upload-btn" class="button" style="margin-top:16px;"><?php esc_html_e("Changer l'image d'avatar", 'supersede-css-jlg'); ?></button>

             <h4 style="margin-top:16px;"><?php esc_html_e("Comment l'utiliser ?", 'supersede-css-jlg'); ?></h4>
             <p class="description"><?php printf(wp_kses_post(__("Une fois le preset enregistré et appliqué, demandez à vos rédacteurs d'ajouter la classe %s au conteneur (la `div`) de leur image.", 'supersede-css-jlg')), '<code id="ssc-glow-how-to-use-class">.avatar-glow-blue</code>'); ?></p>
             <pre id="ssc-glow-css-output" class="ssc-code"></pre>
        </div>
    </div>
</div>
