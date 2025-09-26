<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('Scope Builder', 'supersede-css-jlg'); ?></h2>

    <div class="ssc-panel" style="margin-bottom: 16px;">
        <h3><?php esc_html_e('Comment utiliser le Scope Builder ?', 'supersede-css-jlg'); ?></h3>
        <p><?php esc_html_e('Cet outil vous permet d\'appliquer rapidement des styles CSS à des éléments très spécifiques de votre site sans avoir à naviguer dans l\'éditeur principal.', 'supersede-css-jlg'); ?></p>
        <ol style="margin-left: 20px;">
            <li><?php printf(wp_kses_post(__('<strong>Sélecteur(s) :</strong> C\'est la cible de votre style. Entrez une classe (%1$s), un ID (%2$s) ou une combinaison plus complexe (%3$s).', 'supersede-css-jlg')), '<code>.mon-bouton</code>', '<code>#logo</code>', '<code>.card > a</code>'); ?></li>
            <li><?php printf(wp_kses_post(__('<strong>Pseudo-classe :</strong> (Optionnel) Choisissez un état pour appliquer le style, comme lorsque l\'utilisateur survole l\'élément (%1$s) ou clique dessus (%2$s).', 'supersede-css-jlg')), '<code>:hover</code>', '<code>:focus</code>'); ?></li>
            <li><?php printf(wp_kses_post(__('<strong>Propriétés CSS :</strong> Écrivez le code CSS à appliquer dans la zone de texte. Par exemple : %s', 'supersede-css-jlg')), '<code>background-color: #e73c7e;<br>color: white;<br>border-radius: 50px;</code>'); ?></li>
            <li><?php echo wp_kses_post(__('<strong>Aperçu :</strong> Le résultat est visible en direct sur les éléments de démo à droite.', 'supersede-css-jlg')); ?></li>
            <li><?php echo wp_kses_post(__('<strong>Appliquer :</strong> Ajoute le CSS généré à la feuille de style globale de votre site.', 'supersede-css-jlg')); ?></li>
        </ol>
    </div>

    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <label><?php esc_html_e('Sélecteur(s)', 'supersede-css-jlg'); ?></label><input type="text" id="ssc-sel" class="large-text" placeholder="<?php echo esc_attr__('.btn, .card > a', 'supersede-css-jlg'); ?>">
            <label><?php esc_html_e('Pseudo-classe', 'supersede-css-jlg'); ?></label><select id="ssc-pseudo"><option value=""><?php esc_html_e('(aucune)', 'supersede-css-jlg'); ?></option><option value=":hover">:hover</option><option value=":focus">:focus</option></select>
            <label><?php esc_html_e('Propriétés CSS', 'supersede-css-jlg'); ?></label><textarea id="ssc-css" rows="12" class="code"></textarea>
            <div class="ssc-actions"><button id="ssc-apply" class="button button-primary"><?php esc_html_e('Appliquer', 'supersede-css-jlg'); ?></button><button id="ssc-copy" class="button"><?php esc_html_e('Copier', 'supersede-css-jlg'); ?></button></div>
        </div>
        <div class="ssc-pane"><h3><?php esc_html_e('Preview', 'supersede-css-jlg'); ?></h3><div id="ssc-scope-preview-container" style="border: 1px dashed #ccc; padding: 1em; border-radius: 8px;"><style id="ssc-scope-preview-style"></style><button class="btn demo"><?php esc_html_e('Bouton', 'supersede-css-jlg'); ?></button><a href="#" class="link demo"><?php esc_html_e('Lien', 'supersede-css-jlg'); ?></a></div></div>
    </div>
</div>
