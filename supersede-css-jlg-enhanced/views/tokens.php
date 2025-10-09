<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $tokens_css */
/** @var array<int, array{name: string, value: string, type: string, description: string, group: string, context: string, status: string, owner: int, version: string, changelog: string, linked_components: array<int, string>}> $tokens_registry */
/** @var array<string, array{label: string, input: string, placeholder?: string, help?: string, rows?: int}> $token_types */
/** @var array<int, array{value: string, label: string, preview?: array<string, string>}> $token_contexts */
/** @var string $default_context */
/** @var array<int, array{value: string, label: string, description: string}> $token_statuses */
/** @var array<int, array<string, mixed>> $token_approvals */

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
        'contexts' => $token_contexts,
        'defaultContext' => $default_context,
        'statuses' => $token_statuses,
        'approvals' => $token_approvals,
        'i18n' => [
            'addToken' => __('Ajouter un token', 'supersede-css-jlg'),
            'emptyState' => __('Aucun token pour le moment. Utilisez le bouton ci-dessous pour commencer.', 'supersede-css-jlg'),
            'emptyFilteredState' => __('Aucun token ne correspond à votre recherche ou filtre actuel.', 'supersede-css-jlg'),
            'groupLabel' => __('Groupe', 'supersede-css-jlg'),
            'defaultGroupName' => __('Général', 'supersede-css-jlg'),
            'tokenTypeColorLabel' => __('Couleur', 'supersede-css-jlg'),
            'tokenTypeColorHelp' => __('Utilisez un code hexadécimal (ex. #4f46e5) ou une variable existante.', 'supersede-css-jlg'),
            'tokenTypeTextLabel' => __('Texte', 'supersede-css-jlg'),
            'tokenTypeTextPlaceholder' => __('Ex. 16px ou clamp(1rem, 2vw, 2rem)', 'supersede-css-jlg'),
            'tokenTypeTextHelp' => __('Idéal pour les valeurs libres (unités CSS, fonctions, etc.).', 'supersede-css-jlg'),
            'tokenTypeNumberLabel' => __('Nombre', 'supersede-css-jlg'),
            'tokenTypeNumberHelp' => __('Pour les valeurs strictement numériques (ex. 1.25).', 'supersede-css-jlg'),
            'tokenTypeSpacingLabel' => __('Espacement', 'supersede-css-jlg'),
            'tokenTypeSpacingPlaceholder' => __('Ex. 16px 24px', 'supersede-css-jlg'),
            'tokenTypeSpacingHelp' => __('Convient aux marges/paddings ou aux espacements multiples.', 'supersede-css-jlg'),
            'tokenTypeFontLabel' => __('Typographie', 'supersede-css-jlg'),
            'tokenTypeFontPlaceholder' => __('Ex. "Inter", sans-serif', 'supersede-css-jlg'),
            'tokenTypeFontHelp' => __('Définissez la pile de polices complète avec les guillemets requis.', 'supersede-css-jlg'),
            'tokenTypeShadowLabel' => __('Ombre', 'supersede-css-jlg'),
            'tokenTypeShadowPlaceholder' => __('0 2px 4px rgba(15, 23, 42, 0.25)', 'supersede-css-jlg'),
            'tokenTypeShadowHelp' => __('Accepte plusieurs valeurs box-shadow, une par ligne si nécessaire.', 'supersede-css-jlg'),
            'tokenTypeGradientLabel' => __('Dégradé', 'supersede-css-jlg'),
            'tokenTypeGradientPlaceholder' => __('linear-gradient(135deg, #4f46e5, #7c3aed)', 'supersede-css-jlg'),
            'tokenTypeGradientHelp' => __('Pour les dégradés CSS complexes (linear-, radial-…).', 'supersede-css-jlg'),
            'tokenTypeBorderLabel' => __('Bordure', 'supersede-css-jlg'),
            'tokenTypeBorderPlaceholder' => __('Ex. 1px solid currentColor', 'supersede-css-jlg'),
            'tokenTypeBorderHelp' => __('Combinez largeur, style et couleur de bordure.', 'supersede-css-jlg'),
            'tokenTypeDimensionLabel' => __('Dimensions', 'supersede-css-jlg'),
            'tokenTypeDimensionPlaceholder' => __('Ex. 320px ou clamp(280px, 50vw, 480px)', 'supersede-css-jlg'),
            'tokenTypeDimensionHelp' => __('Largeurs/hauteurs ou tailles maximales avec clamp/min/max.', 'supersede-css-jlg'),
            'tokenTypeTransitionLabel' => __('Transition', 'supersede-css-jlg'),
            'tokenTypeTransitionPlaceholder' => __('all 0.3s ease-in-out\ncolor 150ms ease', 'supersede-css-jlg'),
            'tokenTypeTransitionHelp' => __('Définissez des transitions multi-propriétés, une par ligne.', 'supersede-css-jlg'),
            'nameLabel' => __('Nom', 'supersede-css-jlg'),
            'valueLabel' => __('Valeur', 'supersede-css-jlg'),
            'typeLabel' => __('Type', 'supersede-css-jlg'),
            'descriptionLabel' => __('Description', 'supersede-css-jlg'),
            'contextLabel' => __('Contexte', 'supersede-css-jlg'),
            'deleteLabel' => __('Supprimer', 'supersede-css-jlg'),
            'saveSuccess' => __('Tokens enregistrés', 'supersede-css-jlg'),
            'saveError' => __('Impossible d’enregistrer les tokens.', 'supersede-css-jlg'),
            'newTokenDefaultName' => __('--nouveau-token', 'supersede-css-jlg'),
            'duplicateError' => __('Certains tokens utilisent le même nom. Corrigez les doublons avant d’enregistrer.', 'supersede-css-jlg'),
            'duplicateListPrefix' => __('Doublons :', 'supersede-css-jlg'),
            'copySuccess' => __('Tokens copiés', 'supersede-css-jlg'),
            'reloadConfirm' => __('Des modifications locales non enregistrées seront perdues. Continuer ?', 'supersede-css-jlg'),
            'searchLabel' => __('Rechercher un token', 'supersede-css-jlg'),
            'searchPlaceholder' => __('Rechercher un token…', 'supersede-css-jlg'),
            'typeFilterLabel' => __('Filtrer par type', 'supersede-css-jlg'),
            'typeFilterAll' => __('Tous les types', 'supersede-css-jlg'),
            'resultsCountZero' => __('Aucun token à afficher', 'supersede-css-jlg'),
            'resultsCountZeroFiltered' => __('0 token affiché sur %2$s', 'supersede-css-jlg'),
            'resultsCountSingular' => __('%1$s token affiché sur %2$s', 'supersede-css-jlg'),
            'resultsCountPlural' => __('%1$s tokens affichés sur %2$s', 'supersede-css-jlg'),
            'matchesLabel' => __('Correspondances', 'supersede-css-jlg'),
            'previewContextLabel' => __('Contexte d’aperçu', 'supersede-css-jlg'),
            'previewContextDefault' => __('Contexte par défaut', 'supersede-css-jlg'),
            'devicePresetAnnouncement' => __('Appareil sélectionné : %s', 'supersede-css-jlg'),
            'deviceOrientationLandscape' => __('Orientation paysage', 'supersede-css-jlg'),
            'deviceOrientationPortrait' => __('Orientation portrait', 'supersede-css-jlg'),
            'deviceOrientationLocked' => __('Rotation non disponible pour cet appareil.', 'supersede-css-jlg'),
            'deviceZoomAnnouncement' => __('Zoom défini sur %s %%', 'supersede-css-jlg'),
            'deviceStateAnnouncement' => __('Simulation de l’état : %s', 'supersede-css-jlg'),
            'deviceReducedMotionOn' => __('Préférence « réduction des animations » activée', 'supersede-css-jlg'),
            'deviceReducedMotionOff' => __('Préférence « réduction des animations » désactivée', 'supersede-css-jlg'),
            'statusLabel' => __('Statut', 'supersede-css-jlg'),
            'statusUnknown' => __('Statut inconnu', 'supersede-css-jlg'),
            'approvalRequestLabel' => __('Demander une revue', 'supersede-css-jlg'),
            'approvalRequestDisabledUnsaved' => __('Enregistrez vos tokens avant de demander une revue.', 'supersede-css-jlg'),
            'approvalCommentPrompt' => __('Ajouter un commentaire pour les réviseurs (facultatif) :', 'supersede-css-jlg'),
            'approvalRequestedToast' => __('Demande d’approbation envoyée.', 'supersede-css-jlg'),
            'approvalRequestFailedToast' => __('Impossible d’envoyer la demande d’approbation.', 'supersede-css-jlg'),
            'approvalPendingLabel' => __('Revue en attente', 'supersede-css-jlg'),
            'approvalApprovedLabel' => __('Revue approuvée', 'supersede-css-jlg'),
            'approvalChangesRequestedLabel' => __('Modifications demandées', 'supersede-css-jlg'),
            'approvalTooltipComment' => __('Commentaire', 'supersede-css-jlg'),
            'approvalTooltipRequestedAt' => __('Envoyée le %s', 'supersede-css-jlg'),
            'approvalUnavailableLabel' => __('Les demandes d’approbation nécessitent des droits supplémentaires.', 'supersede-css-jlg'),
        ],
    ]);
}
?>
<div class="ssc-app ssc-fullwidth">
    <div class="ssc-panel">
        <h2><?php esc_html_e('🚀 Bienvenue dans le Gestionnaire de Tokens', 'supersede-css-jlg'); ?></h2>
        <p><?php esc_html_e('Cet outil vous aide à centraliser les valeurs fondamentales de votre design (couleurs, polices, espacements…) pour les réutiliser facilement et maintenir une cohérence parfaite sur votre site.', 'supersede-css-jlg'); ?></p>
    </div>

    <div class="ssc-two ssc-two--align-start ssc-token-layout">
        <div class="ssc-pane ssc-token-help" id="ssc-token-help">
            <div class="ssc-token-help__header">
                <h3><?php esc_html_e('👨‍🏫 Qu\'est-ce qu\'un Token (ou Variable CSS) ?', 'supersede-css-jlg'); ?></h3>
                <button
                    type="button"
                    id="ssc-token-help-toggle"
                    class="button button-secondary ssc-token-help__toggle"
                    data-expanded-label="<?php esc_attr_e('Masquer l’aide pédagogique', 'supersede-css-jlg'); ?>"
                    data-collapsed-label="<?php esc_attr_e('Afficher l’aide pédagogique', 'supersede-css-jlg'); ?>"
                    aria-controls="ssc-token-help-content"
                    aria-expanded="true"
                >
                    <?php esc_html_e('Masquer l’aide pédagogique', 'supersede-css-jlg'); ?>
                </button>
            </div>
            <div class="ssc-token-help__content" id="ssc-token-help-content" aria-hidden="false">
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
        </div>
        <div class="ssc-pane ssc-token-editor">
            <h3><?php esc_html_e('🎨 Éditeur Visuel de Tokens', 'supersede-css-jlg'); ?></h3>
            <p><?php esc_html_e('Gérez vos tokens sous forme de fiches structurées : nom technique, valeur, type de champ, description et groupe d\'appartenance. Chaque catégorie est listée séparément pour garder une vision claire de votre système de design.', 'supersede-css-jlg'); ?></p>

            <div class="ssc-token-toolbar">
                <button id="ssc-token-add" class="button"><?php esc_html_e('+ Ajouter un Token', 'supersede-css-jlg'); ?></button>
                <input
                    type="search"
                    id="ssc-token-search"
                    placeholder="<?php esc_attr_e('Rechercher un token…', 'supersede-css-jlg'); ?>"
                    aria-label="<?php esc_attr_e('Rechercher un token', 'supersede-css-jlg'); ?>"
                >
                <select id="ssc-token-type-filter" aria-label="<?php esc_attr_e('Filtrer par type', 'supersede-css-jlg'); ?>">
                    <option value=""><?php esc_html_e('Tous les types', 'supersede-css-jlg'); ?></option>
                    <?php foreach ($token_types as $type_key => $meta) :
                        if (!is_string($type_key) || $type_key === '') {
                            continue;
                        }

                        $label = isset($meta['label']) ? $meta['label'] : $type_key;
                        ?>
                        <option value="<?php echo esc_attr($type_key); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <span id="ssc-token-results-count" class="ssc-token-results-count" aria-live="polite"></span>
            </div>

            <div id="ssc-token-builder" class="ssc-token-builder" aria-live="polite">
                <!-- Hydraté par JavaScript -->
            </div>

            <hr>

            <h3><?php printf(wp_kses_post(__('📜 Code CSS généré (%s)', 'supersede-css-jlg')), '<code>:root</code>'); ?></h3>
            <p><?php esc_html_e('Le code ci-dessous est synchronisé automatiquement avec la configuration JSON. Il est proposé en lecture seule pour vérification ou copie rapide.', 'supersede-css-jlg'); ?></p>
            <textarea id="ssc-tokens" rows="10" class="large-text" readonly><?php echo esc_textarea($tokens_css); ?></textarea>
            <div class="ssc-actions ssc-mt-100">
                <button id="ssc-tokens-save" class="button button-primary"><?php esc_html_e('Enregistrer les Tokens', 'supersede-css-jlg'); ?></button>
                <button id="ssc-tokens-copy" class="button"><?php esc_html_e('Copier le CSS', 'supersede-css-jlg'); ?></button>
                <button id="ssc-tokens-reload" class="button" type="button"><?php esc_html_e('Recharger', 'supersede-css-jlg'); ?></button>
            </div>
        </div>
    </div>

    <div class="ssc-panel ssc-device-lab-panel ssc-mt-200">
        <div class="ssc-panel-header">
            <div>
                <h3><?php esc_html_e('🧪 Device Lab Responsive', 'supersede-css-jlg'); ?></h3>
                <p class="description">
                    <?php esc_html_e('Inspirez-vous des suites professionnelles : simulez plusieurs appareils, états d’interaction et préférences utilisateurs pour valider vos tokens avant livraison.', 'supersede-css-jlg'); ?>
                </p>
            </div>
        </div>
        <style id="ssc-tokens-preview-style"></style>
        <div class="ssc-device-toolbar">
            <div class="ssc-device-toolbar__group" id="ssc-device-presets" role="group" aria-label="<?php esc_attr_e('Choisir un appareil', 'supersede-css-jlg'); ?>">
                <button type="button" class="button button-secondary is-active" data-device="mobile" aria-pressed="true">
                    <?php esc_html_e('Mobile', 'supersede-css-jlg'); ?>
                </button>
                <button type="button" class="button button-secondary" data-device="tablet" aria-pressed="false">
                    <?php esc_html_e('Tablette', 'supersede-css-jlg'); ?>
                </button>
                <button type="button" class="button button-secondary" data-device="laptop" aria-pressed="false">
                    <?php esc_html_e('Laptop', 'supersede-css-jlg'); ?>
                </button>
                <button type="button" class="button button-secondary" data-device="desktop" aria-pressed="false">
                    <?php esc_html_e('Desktop', 'supersede-css-jlg'); ?>
                </button>
                <button type="button" class="button button-secondary" data-device="ultrawide" aria-pressed="false">
                    <?php esc_html_e('Ultra-wide', 'supersede-css-jlg'); ?>
                </button>
            </div>
            <div class="ssc-device-toolbar__group">
                <label for="ssc-preview-context" class="ssc-device-toolbar__label"><?php esc_html_e('Contexte d’aperçu', 'supersede-css-jlg'); ?></label>
                <select id="ssc-preview-context" aria-label="<?php esc_attr_e('Contexte d’aperçu', 'supersede-css-jlg'); ?>"></select>
            </div>
        </div>

        <div class="ssc-device-controls">
            <div class="ssc-device-controls__group">
                <span class="ssc-device-controls__label"><?php esc_html_e('Dimensions', 'supersede-css-jlg'); ?> :</span>
                <span id="ssc-device-dimensions" class="ssc-device-controls__value" aria-live="polite">375 × 812 px</span>
            </div>
            <div class="ssc-device-controls__group">
                <label for="ssc-device-zoom" class="ssc-device-controls__label"><?php esc_html_e('Zoom', 'supersede-css-jlg'); ?></label>
                <input type="range" id="ssc-device-zoom" min="60" max="140" step="5" value="85" aria-valuemin="60" aria-valuemax="140" aria-valuenow="85" aria-label="<?php esc_attr_e('Zoom du Device Lab', 'supersede-css-jlg'); ?>">
                <span id="ssc-device-zoom-value" class="ssc-device-controls__value">85%</span>
            </div>
            <div class="ssc-device-controls__group">
                <button
                    type="button"
                    id="ssc-device-orientation"
                    class="button button-secondary"
                    aria-pressed="false"
                    aria-label="<?php esc_attr_e('Basculer l’orientation de l’appareil', 'supersede-css-jlg'); ?>"
                    data-label-landscape="<?php esc_attr_e('Orientation : paysage', 'supersede-css-jlg'); ?>"
                    data-label-portrait="<?php esc_attr_e('Orientation : portrait', 'supersede-css-jlg'); ?>"
                    data-label-disabled="<?php esc_attr_e('Rotation verrouillée pour cet appareil', 'supersede-css-jlg'); ?>"
                >
                    <span class="dashicons dashicons-image-flip-vertical" aria-hidden="true"></span>
                    <span class="ssc-device-orientation__text"><?php esc_html_e('Orientation : portrait', 'supersede-css-jlg'); ?></span>
                </button>
            </div>
            <div class="ssc-device-controls__group">
                <label class="ssc-device-toggle">
                    <input type="checkbox" id="ssc-device-motion">
                    <span><?php esc_html_e('Simuler prefers-reduced-motion', 'supersede-css-jlg'); ?></span>
                </label>
            </div>
        </div>

        <div class="ssc-device-statebar">
            <span class="ssc-device-controls__label"><?php esc_html_e('États interactifs', 'supersede-css-jlg'); ?> :</span>
            <div id="ssc-device-states" class="ssc-device-statebar__group" role="group" aria-label="<?php esc_attr_e('Simuler un état utilisateur', 'supersede-css-jlg'); ?>">
                <button type="button" class="button button-secondary is-active" data-state="default" aria-pressed="true"><?php esc_html_e('Standard', 'supersede-css-jlg'); ?></button>
                <button type="button" class="button button-secondary" data-state="hover" aria-pressed="false"><?php esc_html_e(':hover', 'supersede-css-jlg'); ?></button>
                <button type="button" class="button button-secondary" data-state="focus" aria-pressed="false"><?php esc_html_e(':focus', 'supersede-css-jlg'); ?></button>
                <button type="button" class="button button-secondary" data-state="active" aria-pressed="false"><?php esc_html_e(':active', 'supersede-css-jlg'); ?></button>
            </div>
        </div>

        <div class="ssc-device-stage" id="ssc-device-stage" data-device="mobile" data-orientation="portrait">
            <div class="ssc-device-viewport-shell">
                <div class="ssc-device-viewport" id="ssc-device-viewport">
                    <div id="ssc-tokens-preview" class="ssc-device-preview" data-simulated-state="default">
                        <div class="ssc-device-preview__topbar">
                            <div class="ssc-device-preview__badge" data-preview-focus>
                                <?php esc_html_e('Sprint 24', 'supersede-css-jlg'); ?>
                            </div>
                            <span class="ssc-device-preview__timestamp">
                                <?php esc_html_e('Mis à jour il y a 3 min', 'supersede-css-jlg'); ?>
                            </span>
                        </div>
                        <header class="ssc-device-preview__header">
                            <h4><?php esc_html_e('Design Tokens Review', 'supersede-css-jlg'); ?></h4>
                            <p><?php esc_html_e('Contrôlez rapidement la cohérence des couleurs, typographies et rayons avant de publier le thème.', 'supersede-css-jlg'); ?></p>
                        </header>
                        <div class="ssc-device-preview__actions">
                            <button type="button" class="ssc-device-preview__button ssc-device-preview__button--primary" data-preview-focus>
                                <?php esc_html_e('Valider la release', 'supersede-css-jlg'); ?>
                            </button>
                            <button type="button" class="ssc-device-preview__button" data-preview-focus>
                                <?php esc_html_e('Partager le rapport', 'supersede-css-jlg'); ?>
                            </button>
                        </div>
                        <div class="ssc-device-preview__grid">
                            <article class="ssc-device-preview__card">
                                <h5><?php esc_html_e('Tokens alignés', 'supersede-css-jlg'); ?></h5>
                                <p class="ssc-device-preview__metric">72%</p>
                                <p class="ssc-device-preview__meta"><?php esc_html_e('8 sur 11 valides', 'supersede-css-jlg'); ?></p>
                            </article>
                            <article class="ssc-device-preview__card">
                                <h5><?php esc_html_e('Contraste AA', 'supersede-css-jlg'); ?></h5>
                                <p class="ssc-device-preview__metric">4.8</p>
                                <p class="ssc-device-preview__meta"><?php esc_html_e('Boutons et liens conformes', 'supersede-css-jlg'); ?></p>
                            </article>
                            <article class="ssc-device-preview__card">
                                <h5><?php esc_html_e('Poids CSS généré', 'supersede-css-jlg'); ?></h5>
                                <p class="ssc-device-preview__metric">34 KB</p>
                                <p class="ssc-device-preview__meta"><?php esc_html_e('Aucun doublon détecté', 'supersede-css-jlg'); ?></p>
                            </article>
                        </div>
                        <footer class="ssc-device-preview__footer">
                            <div class="ssc-device-preview__avatars" aria-hidden="true">
                                <span class="ssc-device-preview__avatar"></span>
                                <span class="ssc-device-preview__avatar"></span>
                                <span class="ssc-device-preview__avatar"></span>
                            </div>
                            <a href="#" class="ssc-device-preview__link" data-preview-focus>
                                <?php esc_html_e('Ouvrir l’activité détaillée', 'supersede-css-jlg'); ?>
                            </a>
                        </footer>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
