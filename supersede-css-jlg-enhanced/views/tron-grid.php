<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('🌐 Tron Grid Animator', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Générez un fond de grille animé et personnalisable. Idéal pour des bannières ou des fonds de section futuristes.', 'supersede-css-jlg'); ?></p>

    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3><?php esc_html_e('Paramètres de la Grille', 'supersede-css-jlg'); ?></h3>

            <label><strong><?php esc_html_e('Couleur des lignes', 'supersede-css-jlg'); ?></strong></label>
            <input type="color" id="ssc-tron-line-color" value="#00ffff">

            <label style="margin-top:16px; display:block;"><strong><?php esc_html_e('Couleur de fond (Dégradé)', 'supersede-css-jlg'); ?></strong></label>
            <div class="ssc-actions">
                <span><?php esc_html_e('Haut :', 'supersede-css-jlg'); ?></span> <input type="color" id="ssc-tron-bg1" value="#0a0a23">
                <span><?php esc_html_e('Bas :', 'supersede-css-jlg'); ?></span> <input type="color" id="ssc-tron-bg2" value="#000000">
            </div>

            <label style="margin-top:16px; display:block;"><strong><?php esc_html_e('Taille de la grille (pixels)', 'supersede-css-jlg'); ?></strong></label>
            <input type="range" id="ssc-tron-size" min="20" max="200" value="50" step="5">
            <span id="ssc-tron-size-val">50px</span>

            <label style="margin-top:16px; display:block;"><strong><?php esc_html_e('Épaisseur des lignes (pixels)', 'supersede-css-jlg'); ?></strong></label>
            <input type="range" id="ssc-tron-thickness" min="1" max="5" value="1" step="1">
            <span id="ssc-tron-thickness-val">1px</span>

            <label style="margin-top:16px; display:block;"><strong><?php esc_html_e("Vitesse de l'animation (secondes)", 'supersede-css-jlg'); ?></strong></label>
            <input type="range" id="ssc-tron-speed" min="1" max="30" value="10" step="1">
            <span id="ssc-tron-speed-val">10s</span>

            <div class="ssc-actions" style="margin-top:24px; border-top: 1px solid var(--ssc-border); padding-top: 16px;">
                <button id="ssc-tron-apply" class="button button-primary"><?php esc_html_e('Appliquer sur le site', 'supersede-css-jlg'); ?></button>
                <button id="ssc-tron-copy" class="button"><?php esc_html_e('Copier le CSS', 'supersede-css-jlg'); ?></button>
            </div>

            <h3 style="margin-top:24px;"><?php esc_html_e('Comment utiliser cet effet ?', 'supersede-css-jlg'); ?></h3>
            <p class="description">
                <?php echo wp_kses_post(__('Le bouton <strong>"Appliquer sur le site"</strong> ajoute le code CSS généré à la feuille de style globale de votre site. Vous pouvez ensuite utiliser la classe <code>.ssc-tron-grid-bg</code> sur n\'importe quel élément (div, section, etc.) pour lui appliquer ce fond.', 'supersede-css-jlg')); ?>
            </p>
            <p class="description">
                <?php echo wp_kses_post(__('<strong>Pour créer plusieurs grilles différentes :</strong>', 'supersede-css-jlg')); ?>
            </p>
            <ol style="padding-left: 20px;" class="description">
                <li><?php esc_html_e('Personnalisez votre première grille ici.', 'supersede-css-jlg'); ?></li>
                <li><?php echo wp_kses_post(__('Cliquez sur <strong>"Copier CSS"</strong>.', 'supersede-css-jlg')); ?></li>
                <li><?php echo wp_kses_post(__('Allez dans le module <strong>"Utilities"</strong> (l\'éditeur CSS principal).', 'supersede-css-jlg')); ?></li>
                <li><?php echo wp_kses_post(__('Collez le code et renommez la classe principale, par exemple en <code>.ma-grille-bleue</code>.', 'supersede-css-jlg')); ?></li>
                <li><?php echo wp_kses_post(__('Revenez ici, créez une autre variation, et répétez l\'opération avec un nouveau nom de classe (ex: <code>.ma-grille-rouge</code>).', 'supersede-css-jlg')); ?></li>
            </ol>

            <pre id="ssc-tron-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Aperçu en Direct', 'supersede-css-jlg'); ?></h3>
            <div id="ssc-tron-preview" style="height: 300px; border-radius: 12px; border: 1px solid var(--ssc-border); overflow: hidden;"></div>
        </div>
    </div>
</div>
