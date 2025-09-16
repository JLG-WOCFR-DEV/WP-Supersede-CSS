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
            <a class="button button-primary" href="<?php echo esc_url($quick_links['utilities'] ?? '#'); ?>">Éditeur CSS</a>
            <a class="button" href="<?php echo esc_url($quick_links['tokens'] ?? '#'); ?>">Tokens Manager</a>
            <a class="button" href="<?php echo esc_url($quick_links['avatar'] ?? '#'); ?>">Avatar Glow</a>
            <a class="button" href="<?php echo esc_url($quick_links['debug_center'] ?? '#'); ?>">Centre de Débogage</a>
        </p>
    </div>

    <div class="ssc-panel" style="margin-top: 24px;">
        <h2>💡 Comprendre le Workflow (Créer et Activer un Style)</h2>
        <p>Pour utiliser efficacement les modules créatifs comme <strong>Avatar Glow</strong> ou <strong>Preset Designer</strong>, suivez ces 3 étapes logiques :</p>
        <ol style="list-style-type: decimal; margin-left: 20px;">
            <li style="margin-bottom: 15px;">
                <strong>ÉTAPE 1 : CRÉER ET ENREGISTRER</strong><br>
                Allez dans un module (ex: Avatar Glow). Personnalisez votre effet (couleurs, vitesse...). Donnez-lui un nom et une classe CSS unique (ex: <code>.aura-speciale</code>), puis cliquez sur <strong>"Enregistrer le Preset"</strong>.<br>
                <em>➡️ <strong>Résultat :</strong> La "recette" de votre effet est sauvegardée dans la bibliothèque du plugin. Elle n'est pas encore visible sur le site.</em>
            </li>
            <li style="margin-bottom: 15px;">
                <strong>ÉTAPE 2 : APPLIQUER (Activer)</strong><br>
                Avec votre preset fraîchement enregistré toujours sélectionné, cliquez sur <strong>"Appliquer sur le site"</strong>.<br>
                <em>➡️ <strong>Résultat :</strong> Le code CSS de votre effet est ajouté à la feuille de style globale de votre site. L'effet est maintenant "disponible" et prêt à être utilisé.</em>
            </li>
            <li style="margin-bottom: 15px;">
                <strong>ÉTAPE 3 : UTILISER</strong><br>
                Vos rédacteurs peuvent maintenant aller dans l'éditeur de page ou d'article, sélectionner le conteneur d'une image et lui ajouter la classe CSS que vous avez définie (<code>aura-speciale</code>) dans les réglages avancés du bloc.<br>
                <em>➡️ <strong>Résultat :</strong> L'effet d'aura apparaît sur l'image sur le site public !</em>
            </li>
        </ol>
        <p>En résumé : <strong>On enregistre</strong> un preset pour le sauvegarder pour le futur, et <strong>on l'applique</strong> pour le rendre utilisable dès maintenant.</p>
    </div>
</div>
