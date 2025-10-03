<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $tokens_css */
/** @var array<int, array{name: string, value: string, type: string, description: string, group: string}> $tokens_registry */
/** @var array<string, array{label: string, input: string, placeholder?: string, help?: string, rows?: int}> $token_types */

if (function_exists('wp_localize_script')) {
    $localized_types = [];
    foreach ($token_types as $type_key => $meta) {
        if (!is_string($type_key) || $type_key === '') {
            continue;
        }

        $normalized_meta = [
            'label' => $meta['label'],
            'input' => $meta['input'],
        ];

        if (isset($meta['placeholder'])) {
            $normalized_meta['placeholder'] = $meta['placeholder'];
        }

        if (isset($meta['help'])) {
            $normalized_meta['help'] = $meta['help'];
        }

        if (isset($meta['rows'])) {
            $normalized_meta['rows'] = (int) $meta['rows'];
        }

        $localized_types[$type_key] = $normalized_meta;
    }

    wp_localize_script('ssc-tokens', 'SSC_TOKENS_DATA', [
        'tokens' => $tokens_registry,
        'types' => $localized_types,
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
<?php
$help_toggle_expanded = __('Masquer l\'aide', 'supersede-css-jlg');
$help_toggle_collapsed = __('Afficher l\'aide', 'supersede-css-jlg');
?>
<div class="ssc-app ssc-fullwidth">
    <div class="ssc-panel">
        <h2><?php esc_html_e('🚀 Bienvenue dans le Gestionnaire de Tokens', 'supersede-css-jlg'); ?></h2>
        <p><?php esc_html_e('Cet outil vous aide à centraliser les valeurs fondamentales de votre design (couleurs, polices, espacements…) pour les réutiliser facilement et maintenir une cohérence parfaite sur votre site.', 'supersede-css-jlg'); ?></p>
    </div>

    <div class="ssc-two ssc-token-layout">
        <details id="ssc-token-help-toggle" class="ssc-pane ssc-token-help" open>
            <summary class="ssc-token-help__summary">
                <span class="ssc-token-help__title" role="heading" aria-level="3"><?php esc_html_e('👨‍🏫 Qu\'est-ce qu\'un Token (ou Variable CSS) ?', 'supersede-css-jlg'); ?></span>
                <span class="ssc-token-help__state" data-expanded="<?php echo esc_attr($help_toggle_expanded); ?>" data-collapsed="<?php echo esc_attr($help_toggle_collapsed); ?>"><?php echo esc_html($help_toggle_expanded); ?></span>
            </summary>
            <div class="ssc-token-help__content">
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
        </details>
        <div class="ssc-pane ssc-token-editor">
            <h3><?php esc_html_e('🎨 Éditeur Visuel de Tokens', 'supersede-css-jlg'); ?></h3>
            <p><?php esc_html_e('Gérez vos tokens sous forme de fiches structurées : nom technique, valeur, type de champ, description et groupe d\'appartenance. Chaque catégorie est listée séparément pour garder une vision claire de votre système de design.', 'supersede-css-jlg'); ?></p>

            <div class="ssc-token-toolbar" style="margin-bottom:12px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <button id="ssc-token-add" class="button"><?php esc_html_e('+ Ajouter un Token', 'supersede-css-jlg'); ?></button>
            </div>

            <div id="ssc-token-builder" class="ssc-token-builder" aria-live="polite">
                <!-- Hydraté par JavaScript -->
            </div>

            <hr>

            <h3><?php printf(wp_kses_post(__('📜 Code CSS généré (%s)', 'supersede-css-jlg')), '<code>:root</code>'); ?></h3>
            <p><?php esc_html_e('Le code ci-dessous est synchronisé automatiquement avec la configuration JSON. Il est proposé en lecture seule pour vérification ou copie rapide.', 'supersede-css-jlg'); ?></p>
            <textarea id="ssc-tokens" rows="10" class="large-text" readonly><?php echo esc_textarea($tokens_css); ?></textarea>
            <div class="ssc-actions" style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                <button id="ssc-tokens-save" class="button button-primary"><?php esc_html_e('Enregistrer les Tokens', 'supersede-css-jlg'); ?></button>
                <button id="ssc-tokens-copy" class="button"><?php esc_html_e('Copier le CSS', 'supersede-css-jlg'); ?></button>
                <button id="ssc-tokens-reload" class="button" type="button"><?php esc_html_e('Recharger', 'supersede-css-jlg'); ?></button>
            </div>
        </div>
    </div>

    <div class="ssc-panel" style="margin-top:16px;">
        <h3><?php esc_html_e('👁️ Aperçu en Direct', 'supersede-css-jlg'); ?></h3>
        <p><?php esc_html_e('Voyez comment vos tokens affectent les éléments. Le style de cet aperçu est directement contrôlé par le code CSS ci-dessus.', 'supersede-css-jlg'); ?></p>
        <style id="ssc-tokens-preview-style"></style>
        <div id="ssc-tokens-preview" style="padding: 24px; border: 2px dashed var(--couleur-principale, #ccc); border-radius: var(--radius-moyen, 8px); background: #fff;">
            <button class="button button-primary" style="background-color: var(--couleur-principale); border-radius: var(--radius-moyen);"><?php esc_html_e('Bouton Principal', 'supersede-css-jlg'); ?></button>
            <a href="#" style="color: var(--couleur-principale); margin-left: 16px;"><?php esc_html_e('Lien Principal', 'supersede-css-jlg'); ?></a>
        </div>
    </div>
</div>
