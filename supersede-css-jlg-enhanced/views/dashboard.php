<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var array{utilities?:string,tokens?:string,avatar?:string,debug_center?:string} $quick_links */
?>
<div class="ssc-wrap wrap">
    <h1><?php echo esc_html__('Supersede CSS — Dashboard', 'supersede-css-jlg'); ?></h1>
    <p class="description"><?php echo esc_html__('Bienvenue ! Utilisez le menu ou la palette de commande (⌘/Ctrl + K) pour naviguer.', 'supersede-css-jlg'); ?></p>

    <div class="ssc-panel" style="margin-top: 24px;">
        <h2><?php echo esc_html__('Accès Rapide', 'supersede-css-jlg'); ?></h2>
        <p>
            <a class="button button-primary" href="<?php echo esc_url($quick_links['utilities'] ?? '#'); ?>"><?php esc_html_e('Éditeur CSS', 'supersede-css-jlg'); ?></a>
            <a class="button" href="<?php echo esc_url($quick_links['tokens'] ?? '#'); ?>"><?php esc_html_e('Tokens Manager', 'supersede-css-jlg'); ?></a>
            <a class="button" href="<?php echo esc_url($quick_links['avatar'] ?? '#'); ?>"><?php esc_html_e('Avatar Glow', 'supersede-css-jlg'); ?></a>
            <a class="button" href="<?php echo esc_url($quick_links['debug_center'] ?? '#'); ?>"><?php esc_html_e('Centre de Débogage', 'supersede-css-jlg'); ?></a>
        </p>
    </div>

    <div class="ssc-panel" style="margin-top: 24px;">
        <h2><?php esc_html_e('💡 Comprendre le Workflow (Créer et Activer un Style)', 'supersede-css-jlg'); ?></h2>
        <p><?php echo wp_kses_post(__("Pour utiliser efficacement les modules créatifs comme <strong>Avatar Glow</strong> ou <strong>Preset Designer</strong>, suivez ces 3 étapes logiques :", 'supersede-css-jlg')); ?></p>
        <ol style="list-style-type: decimal; margin-left: 20px;">
            <li style="margin-bottom: 15px;">
                <strong><?php esc_html_e('ÉTAPE 1 : CRÉER ET ENREGISTRER', 'supersede-css-jlg'); ?></strong><br>
                <?php echo wp_kses_post(__("Allez dans un module (ex: Avatar Glow). Personnalisez votre effet (couleurs, vitesse...). Donnez-lui un nom et une classe CSS unique (ex: <code>.aura-speciale</code>), puis cliquez sur <strong>\"Enregistrer le Preset\"</strong>.", 'supersede-css-jlg')); ?><br>
                <em><?php echo wp_kses_post(__("➡️ <strong>Résultat :</strong> La \"recette\" de votre effet est sauvegardée dans la bibliothèque du plugin. Elle n'est pas encore visible sur le site.", 'supersede-css-jlg')); ?></em>
            </li>
            <li style="margin-bottom: 15px;">
                <strong><?php esc_html_e('ÉTAPE 2 : APPLIQUER (Activer)', 'supersede-css-jlg'); ?></strong><br>
                <?php echo wp_kses_post(__("Avec votre preset fraîchement enregistré toujours sélectionné, cliquez sur <strong>\"Appliquer sur le site\"</strong>.", 'supersede-css-jlg')); ?><br>
                <em><?php echo wp_kses_post(__("➡️ <strong>Résultat :</strong> Le code CSS de votre effet est ajouté à la feuille de style globale de votre site. L'effet est maintenant \"disponible\" et prêt à être utilisé.", 'supersede-css-jlg')); ?></em>
            </li>
            <li style="margin-bottom: 15px;">
                <strong><?php esc_html_e('ÉTAPE 3 : UTILISER', 'supersede-css-jlg'); ?></strong><br>
                <?php echo wp_kses_post(__("Vos rédacteurs peuvent maintenant aller dans l'éditeur de page ou d'article, sélectionner le conteneur d'une image et lui ajouter la classe CSS que vous avez définie (<code>aura-speciale</code>) dans les réglages avancés du bloc.", 'supersede-css-jlg')); ?><br>
                <em><?php echo wp_kses_post(__("➡️ <strong>Résultat :</strong> L'effet d'aura apparaît sur l'image sur le site public !", 'supersede-css-jlg')); ?></em>
            </li>
        </ol>
        <p><?php echo wp_kses_post(__("En résumé : <strong>On enregistre</strong> un preset pour le sauvegarder pour le futur, et <strong>on l'applique</strong> pour le rendre utilisable dès maintenant.", 'supersede-css-jlg')); ?></p>
    </div>
</div>
