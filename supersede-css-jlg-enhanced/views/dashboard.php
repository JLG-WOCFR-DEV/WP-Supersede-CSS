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
        <h2><?php esc_html_e('🧩 Nouveau : Bloc « Token Preview »', 'supersede-css-jlg'); ?></h2>
        <p><?php echo esc_html__('Dans l’éditeur de blocs WordPress, insérez le bloc « Supersede › Token Preview » pour visualiser instantanément les tokens et presets activés (couleurs, espacements, etc.).', 'supersede-css-jlg'); ?></p>
        <p><?php echo esc_html__('Le bloc injecte automatiquement les mêmes styles que le frontal : plus besoin de copier les classes manuellement, il suffit de placer le bloc à l’endroit voulu pour partager votre bibliothèque de tokens avec l’équipe éditoriale.', 'supersede-css-jlg'); ?></p>
    </div>

    <div class="ssc-panel" style="margin-top: 24px;">
        <h2><?php esc_html_e('💡 Comprendre le Workflow (Créer et Activer un Style)', 'supersede-css-jlg'); ?></h2>
        <p><?php printf(wp_kses_post(__('Pour utiliser efficacement les modules créatifs comme %1$s ou %2$s, suivez ces 3 étapes logiques :', 'supersede-css-jlg')), '<strong>Avatar Glow</strong>', '<strong>Preset Designer</strong>'); ?></p>
        <ol style="list-style-type: decimal; margin-left: 20px;">
            <li style="margin-bottom: 15px;">
                <strong><?php esc_html_e('ÉTAPE 1 : CRÉER ET ENREGISTRER', 'supersede-css-jlg'); ?></strong><br>
                <?php printf(wp_kses_post(__('Allez dans un module (ex: Avatar Glow). Personnalisez votre effet (couleurs, vitesse...). Donnez-lui un nom et une classe CSS unique (ex: %1$s), puis cliquez sur %2$s.', 'supersede-css-jlg')), '<code>.aura-speciale</code>', '<strong>"Enregistrer le Preset"</strong>'); ?><br>
                <em><?php printf(wp_kses_post(__('➡️ %1$s La "recette" de votre effet est sauvegardée dans la bibliothèque du plugin. Elle n\'est pas encore visible sur le site.', 'supersede-css-jlg')), '<strong>Résultat :</strong>'); ?></em>
            </li>
            <li style="margin-bottom: 15px;">
                <strong><?php esc_html_e('ÉTAPE 2 : APPLIQUER (Activer)', 'supersede-css-jlg'); ?></strong><br>
                <?php printf(wp_kses_post(__('Avec votre preset fraîchement enregistré toujours sélectionné, cliquez sur %s.', 'supersede-css-jlg')), '<strong>"Appliquer sur le site"</strong>'); ?><br>
                <em><?php printf(wp_kses_post(__('➡️ %1$s Le code CSS de votre effet est ajouté à la feuille de style globale de votre site. L\'effet est maintenant "disponible" et prêt à être utilisé.', 'supersede-css-jlg')), '<strong>Résultat :</strong>'); ?></em>
            </li>
            <li style="margin-bottom: 15px;">
                <strong><?php esc_html_e('ÉTAPE 3 : UTILISER', 'supersede-css-jlg'); ?></strong><br>
                <?php printf(wp_kses_post(__('Vos rédacteurs peuvent maintenant aller dans l\'éditeur de page ou d\'article, sélectionner le conteneur d\'une image et lui ajouter la classe CSS que vous avez définie (%s) dans les réglages avancés du bloc.', 'supersede-css-jlg')), '<code>aura-speciale</code>'); ?><br>
                <em><?php printf(wp_kses_post(__('➡️ %1$s L\'effet d\'aura apparaît sur l\'image sur le site public !', 'supersede-css-jlg')), '<strong>Résultat :</strong>'); ?></em>
            </li>
        </ol>
        <p><?php printf(wp_kses_post(__('En résumé : %1$s un preset pour le sauvegarder pour le futur, et %2$s pour le rendre utilisable dès maintenant.', 'supersede-css-jlg')), '<strong>On enregistre</strong>', '<strong>on l\'applique</strong>'); ?></p>
    </div>
</div>
