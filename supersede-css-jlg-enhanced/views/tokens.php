<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $tokens_css */
/** @var array<int, array{name: string, value: string, type: string, description: string, group: string}> $tokens_registry */
/** @var array<string, array{label: string, input: string}> $token_types */

if (function_exists('wp_localize_script')) {
    wp_localize_script('ssc-admin-app', 'SSC_TOKENS_DATA', [
        'tokens' => $tokens_registry,
        'types' => $token_types,
        'css' => $tokens_css,
        'i18n' => [
            'addToken' => __('Ajouter un token', 'supersede-css-jlg'),
            'emptyState' => __('Aucun token pour le moment. Utilisez le bouton ci-dessous pour commencer.', 'supersede-css-jlg'),
            'groupLabel' => __('Groupe', 'supersede-css-jlg'),
            'nameLabel' => __('Nom', 'supersede-css-jlg'),
            'valueLabel' => __('Valeur', 'supersede-css-jlg'),
            'typeLabel' => __('Type', 'supersede-css-jlg'),
            'descriptionLabel' => __('Description', 'supersede-css-jlg'),
            'deleteLabel' => __('Supprimer', 'supersede-css-jlg'),
            'saveSuccess' => __('Tokens enregistrés', 'supersede-css-jlg'),
            'saveError' => __('Impossible d’enregistrer les tokens.', 'supersede-css-jlg'),
            'duplicateError' => __('Certains tokens utilisent le même nom. Corrigez les doublons avant d’enregistrer.', 'supersede-css-jlg'),
            'duplicateListPrefix' => __('Doublons :', 'supersede-css-jlg'),
            'copySuccess' => __('Tokens copiés', 'supersede-css-jlg'),
            'reloadConfirm' => __('Des modifications locales non enregistrées seront perdues. Continuer ?', 'supersede-css-jlg'),
        ],
    ]);
}
?>
<div class="ssc-app ssc-fullwidth">
    <div class="ssc-panel">
        <h2><?php esc_html_e('🚀 Bienvenue dans le Gestionnaire de Tokens', 'supersede-css-jlg'); ?></h2>
        <p><?php esc_html_e('Cet outil vous aide à centraliser les valeurs fondamentales de votre design (couleurs, polices, espacements…) pour les réutiliser facilement et maintenir une cohérence parfaite sur votre site.', 'supersede-css-jlg'); ?></p>
    </div>

    <div class="ssc-two" style="margin-top:16px; align-items: flex-start;">
        <div class="ssc-pane">
            <h3><?php esc_html_e('👨‍🏫 Qu\'est-ce qu\'un Token (ou Variable CSS) ?', 'supersede-css-jlg'); ?></h3>
            <p><?php printf(wp_kses_post(__('Imaginez que vous décidiez d\'utiliser une couleur bleue spécifique (%s) pour tous vos boutons et titres. Si un jour vous voulez changer ce bleu, vous devriez chercher et remplacer cette valeur partout dans votre code. C\'est long et risqué !', 'supersede-css-jlg')), '<code>#3498db</code>'); ?></p>
            <p><?php printf(wp_kses_post(__('Un %1$s est un « raccourci ». Vous donnez un nom facile à retenir à votre couleur, comme %2$s. Ensuite, vous utilisez ce nom partout où vous avez besoin de ce bleu.', 'supersede-css-jlg')), '<strong>token</strong>', '<code>--couleur-principale</code>'); ?></p>
            <p><?php echo wp_kses_post(__('<strong>Le jour où vous voulez changer de couleur, il suffit de modifier la valeur du token en un seul endroit, et la modification s\'applique partout !</strong>', 'supersede-css-jlg')); ?></p>
            <hr>
            <h4><?php esc_html_e('Exemple Concret', 'supersede-css-jlg'); ?></h4>
            <p><?php printf(wp_kses_post(__('<strong>1. Définition du Token :</strong><br>On définit le token une seule fois, généralement sur l\'élément %s (la racine de votre page).', 'supersede-css-jlg')), '<code>:root</code>'); ?></p>
            <pre class="ssc-code">:root {
   --couleur-principale: #3498db;
   --radius-arrondi: 8px;
}</pre>
            <p><?php printf(wp_kses_post(__('<strong>2. Utilisation des Tokens :</strong><br>Ensuite, on utilise la fonction %s pour appeler la valeur du token.', 'supersede-css-jlg')), '<code>var()</code>'); ?></p>
            <pre class="ssc-code">.mon-bouton {
   background-color: var(--couleur-principale);
   border-radius: var(--radius-arrondi);
   color: white;
}

.mon-titre {
   color: var(--couleur-principale);
}</pre>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('🎨 Éditeur Visuel de Tokens', 'supersede-css-jlg'); ?></h3>
            <p><?php esc_html_e('Gérez vos tokens sous forme de fiches structurées : nom technique, valeur, type de champ, description et groupe d\'appartenance. Chaque catégorie est listée séparément pour garder une vision claire de votre système de design.', 'supersede-css-jlg'); ?></p>

            <div id="ssc-token-app-root"></div>
        </div>
    </div>

    <div class="ssc-panel" style="margin-top:16px;">
        <p><?php esc_html_e('L’éditeur réactif ci-dessus met automatiquement à jour le CSS et l’aperçu en direct pour simplifier la gestion des tokens.', 'supersede-css-jlg'); ?></p>
    </div>
</div>
