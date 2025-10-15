<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('🌐 Tron Grid Animator', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Générez un fond de grille animé et personnalisable. Idéal pour des bannières ou des fonds de section futuristes.', 'supersede-css-jlg'); ?></p>

    <div class="ssc-two ssc-two--align-start">
        <div class="ssc-pane">
            <h3><?php esc_html_e('Paramètres de la Grille', 'supersede-css-jlg'); ?></h3>

            <div class="ssc-form-field">
                <label class="ssc-form-label" for="ssc-tron-line-color"><?php esc_html_e('Couleur des lignes', 'supersede-css-jlg'); ?></label>
                <input type="color" id="ssc-tron-line-color" value="#00ffff">
            </div>

            <label class="ssc-form-label ssc-mt-200" for="ssc-tron-bg1"><?php esc_html_e('Couleur de fond (Dégradé)', 'supersede-css-jlg'); ?></label>
            <div class="ssc-form-control-row">
                <span><?php esc_html_e('Haut :', 'supersede-css-jlg'); ?></span>
                <input type="color" id="ssc-tron-bg1" value="#0a0a23">
                <span><?php esc_html_e('Bas :', 'supersede-css-jlg'); ?></span>
                <input type="color" id="ssc-tron-bg2" value="#000000">
            </div>

            <label class="ssc-form-label ssc-mt-200" for="ssc-tron-size"><?php esc_html_e('Taille de la grille (pixels)', 'supersede-css-jlg'); ?></label>
            <input type="range" id="ssc-tron-size" min="20" max="200" value="50" step="5" aria-describedby="ssc-tron-size-val">
            <span id="ssc-tron-size-val" role="status" aria-live="polite"><?php echo esc_html__('50px', 'supersede-css-jlg'); ?></span>

            <label class="ssc-form-label ssc-mt-200" for="ssc-tron-thickness"><?php esc_html_e('Épaisseur des lignes (pixels)', 'supersede-css-jlg'); ?></label>
            <input type="range" id="ssc-tron-thickness" min="1" max="5" value="1" step="1" aria-describedby="ssc-tron-thickness-val">
            <span id="ssc-tron-thickness-val" role="status" aria-live="polite"><?php echo esc_html__('1px', 'supersede-css-jlg'); ?></span>

            <label class="ssc-form-label ssc-mt-200" for="ssc-tron-speed"><?php esc_html_e('Vitesse de l\'animation (secondes)', 'supersede-css-jlg'); ?></label>
            <input type="range" id="ssc-tron-speed" min="1" max="30" value="10" step="1" aria-describedby="ssc-tron-speed-val">
            <span id="ssc-tron-speed-val" role="status" aria-live="polite"><?php echo esc_html__('10s', 'supersede-css-jlg'); ?></span>

            <div class="ssc-form-actions ssc-form-actions--separated">
                <button id="ssc-tron-apply" class="button button-primary"><?php esc_html_e('Appliquer sur le site', 'supersede-css-jlg'); ?></button>
                <button id="ssc-tron-copy" class="button button-secondary"><?php esc_html_e('Copier le CSS', 'supersede-css-jlg'); ?></button>
            </div>

            <h3 class="ssc-section-heading"><?php esc_html_e('Comment utiliser cet effet ?', 'supersede-css-jlg'); ?></h3>
            <p class="description">
                <?php printf(wp_kses_post(__('Le bouton %1$s ajoute le code CSS généré à la feuille de style globale de votre site. Vous pouvez ensuite utiliser la classe %2$s sur n\'importe quel élément (div, section, etc.) pour lui appliquer ce fond.', 'supersede-css-jlg')), '<strong>"Appliquer sur le site"</strong>', '<code>.ssc-tron-grid-bg</code>'); ?>
            </p>
            <p class="description">
                <?php echo wp_kses_post(__('<strong>Pour créer plusieurs grilles différentes :</strong>', 'supersede-css-jlg')); ?>
            </p>
            <ol class="ssc-ordered-list description">
                <li><?php esc_html_e('Personnalisez votre première grille ici.', 'supersede-css-jlg'); ?></li>
                <li><?php printf(wp_kses_post(__('Cliquez sur %s.', 'supersede-css-jlg')), '<strong>' . esc_html__('"Copier CSS"', 'supersede-css-jlg') . '</strong>'); ?></li>
                <li><?php printf(wp_kses_post(__('Allez dans le module %s (l\'éditeur CSS principal).', 'supersede-css-jlg')), '<strong>' . esc_html__('"Utilities"', 'supersede-css-jlg') . '</strong>'); ?></li>
                <li><?php printf(wp_kses_post(__('Collez le code et renommez la classe principale, par exemple en %s.', 'supersede-css-jlg')), '<code>.ma-grille-bleue</code>'); ?></li>
                <li><?php printf(wp_kses_post(__('Revenez ici, créez une autre variation, et répétez l\'opération avec un nouveau nom de classe (ex: %s).', 'supersede-css-jlg')), '<code>.ma-grille-rouge</code>'); ?></li>
            </ol>

            <pre id="ssc-tron-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Aperçu en Direct', 'supersede-css-jlg'); ?></h3>
            <div id="ssc-tron-preview" class="ssc-tron-preview"></div>
        </div>
    </div>
</div>
