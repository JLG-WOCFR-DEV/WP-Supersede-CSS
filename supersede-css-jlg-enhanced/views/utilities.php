<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $css_desktop */
/** @var string $css_tablet */
/** @var string $css_mobile */
/** @var string $preview_url */
/** @var string $editor_mode */
?>
<div class="ssc-wrap ssc-utilities-wrap" data-editor-mode="<?php echo esc_attr($editor_mode); ?>">
    <div class="ssc-editor-layout">
        <div class="ssc-editor-column">
            <div class="ssc-editor-header">
                <h2><?php echo esc_html__('Éditeur CSS', 'supersede-css-jlg'); ?></h2>
                <div class="ssc-actions">
                    <button id="ssc-save-css" class="button button-primary"><?php echo esc_html__('Enregistrer le CSS', 'supersede-css-jlg'); ?></button>
                </div>
            </div>
            <div class="ssc-mode-switch">
                <div class="ssc-mode-switch__labels">
                    <span class="ssc-mode-switch__title"><?php esc_html_e('Mode d\'édition', 'supersede-css-jlg'); ?></span>
                    <span class="ssc-mode-switch__badge" data-mode-visibility="simple"><?php esc_html_e('Simple', 'supersede-css-jlg'); ?></span>
                    <span class="ssc-mode-switch__badge" data-mode-visibility="expert"><?php esc_html_e('Expert', 'supersede-css-jlg'); ?></span>
                </div>
                <button
                    type="button"
                    id="ssc-editor-mode-toggle"
                    class="ssc-mode-switch__button"
                    role="switch"
                    aria-checked="<?php echo $editor_mode === 'expert' ? 'true' : 'false'; ?>"
                    aria-describedby="ssc-editor-mode-helper-simple ssc-editor-mode-helper-expert"
                    data-label-simple="<?php echo esc_attr__('Mode Simple', 'supersede-css-jlg'); ?>"
                    data-label-expert="<?php echo esc_attr__('Mode Expert', 'supersede-css-jlg'); ?>"
                    data-toast-simple="<?php echo esc_attr__('Mode Simple activé : focus sur les réglages essentiels.', 'supersede-css-jlg'); ?>"
                    data-toast-expert="<?php echo esc_attr__('Mode Expert activé : options avancées visibles.', 'supersede-css-jlg'); ?>"
                >
                    <span class="ssc-mode-switch__track" aria-hidden="true">
                        <span class="ssc-mode-switch__thumb"></span>
                    </span>
                    <span class="ssc-mode-switch__text" id="ssc-editor-mode-label">
                        <?php echo esc_html($editor_mode === 'expert' ? __('Mode Expert', 'supersede-css-jlg') : __('Mode Simple', 'supersede-css-jlg')); ?>
                    </span>
                </button>
                <p class="ssc-mode-switch__helper" id="ssc-editor-mode-helper-simple" data-mode-visibility="simple">
                    <?php esc_html_e('Gardez l\'essentiel sous la main : onglet Desktop, sauvegarde rapide et aperçu.', 'supersede-css-jlg'); ?>
                </p>
                <p class="ssc-mode-switch__helper" id="ssc-editor-mode-helper-expert" data-mode-visibility="expert">
                    <?php esc_html_e('Activez les breakpoints supplémentaires, le picker d\'éléments et les contrôles avancés.', 'supersede-css-jlg'); ?>
                </p>
            </div>
            <p id="ssc-editor-mode-status" class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></p>
            <div class="ssc-editor-tabs" role="tablist" aria-label="<?php echo esc_attr__('Modes d\'édition CSS', 'supersede-css-jlg'); ?>">
                <button
                    type="button"
                    class="ssc-editor-tab active"
                    id="ssc-editor-tab-desktop"
                    role="tab"
                    aria-selected="true"
                    aria-controls="ssc-editor-panel-desktop"
                    data-tab="desktop"
                    data-announcement="<?php echo esc_attr__('Vue Desktop', 'supersede-css-jlg'); ?>"
                >
                    <?php esc_html_e('🖥️ Desktop', 'supersede-css-jlg'); ?>
                </button>
                <button
                    type="button"
                    class="ssc-editor-tab"
                    id="ssc-editor-tab-tablet"
                    role="tab"
                    aria-selected="false"
                    aria-controls="ssc-editor-panel-tablet"
                    data-tab="tablet"
                    data-mode-visibility="expert"
                    data-announcement="<?php echo esc_attr__('Vue Tablette', 'supersede-css-jlg'); ?>"
                >
                    <?php esc_html_e('📲 Tablette', 'supersede-css-jlg'); ?>
                </button>
                <button
                    type="button"
                    class="ssc-editor-tab"
                    id="ssc-editor-tab-mobile"
                    role="tab"
                    aria-selected="false"
                    aria-controls="ssc-editor-panel-mobile"
                    data-tab="mobile"
                    data-mode-visibility="expert"
                    data-announcement="<?php echo esc_attr__('Vue Mobile', 'supersede-css-jlg'); ?>"
                >
                    <?php esc_html_e('📱 Mobile', 'supersede-css-jlg'); ?>
                </button>
                <button
                    type="button"
                    class="ssc-editor-tab"
                    id="ssc-editor-tab-tutorial"
                    role="tab"
                    aria-selected="false"
                    aria-controls="ssc-editor-panel-tutorial"
                    data-tab="tutorial"
                    data-mode-visibility="expert"
                >
                    <?php esc_html_e('💡 Tutoriel @media queries', 'supersede-css-jlg'); ?>
                </button>
            </div>
            <p id="ssc-editor-focus-status" class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></p>
            <div class="ssc-editor-container">
                <div id="ssc-editor-panel-desktop" class="ssc-editor-panel active" role="tabpanel" aria-labelledby="ssc-editor-tab-desktop" tabindex="0">
                    <label class="screen-reader-text" for="ssc-css-editor-desktop"><?php esc_html_e('CSS pour la vue ordinateur', 'supersede-css-jlg'); ?></label>
                    <textarea id="ssc-css-editor-desktop"><?php echo esc_textarea($css_desktop); ?></textarea>
                </div>
                <div id="ssc-editor-panel-tablet" class="ssc-editor-panel" role="tabpanel" aria-labelledby="ssc-editor-tab-tablet" tabindex="0" hidden data-mode-visibility="expert">
                    <label class="screen-reader-text" for="ssc-css-editor-tablet"><?php esc_html_e('CSS pour la vue tablette', 'supersede-css-jlg'); ?></label>
                    <textarea id="ssc-css-editor-tablet"><?php echo esc_textarea($css_tablet); ?></textarea>
                </div>
                <div id="ssc-editor-panel-mobile" class="ssc-editor-panel" role="tabpanel" aria-labelledby="ssc-editor-tab-mobile" tabindex="0" hidden data-mode-visibility="expert">
                    <label class="screen-reader-text" for="ssc-css-editor-mobile"><?php esc_html_e('CSS pour la vue mobile', 'supersede-css-jlg'); ?></label>
                    <textarea id="ssc-css-editor-mobile"><?php echo esc_textarea($css_mobile); ?></textarea>
                </div>
                <div id="ssc-editor-panel-tutorial" class="ssc-editor-panel ssc-tutorial-content" role="tabpanel" aria-labelledby="ssc-editor-tab-tutorial" tabindex="0" hidden data-mode-visibility="expert">
                    <details>
                        <summary><?php esc_html_e('Afficher le tutoriel complet sur les breakpoints', 'supersede-css-jlg'); ?></summary>
                        <h3><?php esc_html_e('Le Principe : "Desktop First" Simplifié', 'supersede-css-jlg'); ?></h3>
                        <p><?php esc_html_e('Pensez à votre design comme à la construction d\'une maison :', 'supersede-css-jlg'); ?></p>
                        <ol>
                            <li><?php printf(wp_kses_post(__('%1$s C\'est ici que vous définissez tous les styles fondamentaux (couleurs, polices, espacements). Ces styles s\'appliquent par défaut à %2$s.', 'supersede-css-jlg')), '<strong>L\'onglet <code>Desktop</code> est le plan de base de la maison.</strong>', '<strong>toutes les tailles d\'écran</strong>'); ?></li>
                            <li><?php echo wp_kses_post(__('<strong>L\'onglet <code>Tablette</code> est l\'aménagement pour les pièces moyennes.</strong> Vous ne redessinez pas tout, vous spécifiez uniquement les changements. Par exemple, réduire la taille d\'un titre.', 'supersede-css-jlg')); ?></li>
                            <li><?php echo wp_kses_post(__('<strong>L\'onglet <code>Mobile</code> est pour les plus petites pièces.</strong> Vous faites les derniers ajustements pour que tout soit parfait sur un petit écran.', 'supersede-css-jlg')); ?></li>
                        </ol>
                        <p><?php printf(wp_kses_post(__('En coulisses, le plugin enveloppe automatiquement le code des onglets Tablette et Mobile dans des %s, vous faisant gagner du temps.', 'supersede-css-jlg')), '<strong>@media queries</strong>'); ?></p>
                        <hr>
                        <h4><?php esc_html_e('Exemple Concret : Un Titre Adaptatif', 'supersede-css-jlg'); ?></h4>
                        <p><?php printf(wp_kses_post(__('%1$s Un titre %2$s qui change de taille et d\'alignement.', 'supersede-css-jlg')), '<strong>Objectif :</strong>', '<code>.mon-titre</code>'); ?></p>
                        <p><?php echo wp_kses_post(__('<strong>1. Onglet <code>Desktop</code> (la base) :</strong>', 'supersede-css-jlg')); ?></p>
                        <pre class="ssc-code">.mon-titre {
  font-size: 48px;
  color: blue;
  font-weight: bold;
}</pre>
                        <p><?php echo wp_kses_post(__('<strong>2. Onglet <code>Tablette</code> (premier ajustement) :</strong>', 'supersede-css-jlg')); ?></p>
                        <pre class="ssc-code">.mon-titre {
  font-size: 36px;
}</pre>
                        <p><?php echo wp_kses_post(__('<strong>3. Onglet <code>Mobile</code> (ajustement final) :</strong>', 'supersede-css-jlg')); ?></p>
                        <pre class="ssc-code">.mon-titre {
  font-size: 24px;
  text-align: center;
}</pre>
                    </details>
                </div>
            </div>
        </div>
        <button
            type="button"
            id="ssc-preview-toggle"
            class="button ssc-preview-toggle"
            data-show="<?php echo esc_attr__('Afficher l\'aperçu', 'supersede-css-jlg'); ?>"
            data-hide="<?php echo esc_attr__('Masquer l\'aperçu', 'supersede-css-jlg'); ?>"
            data-announce-show="<?php echo esc_attr__('Colonne d\'aperçu affichée.', 'supersede-css-jlg'); ?>"
            data-announce-hide="<?php echo esc_attr__('Colonne d\'aperçu masquée.', 'supersede-css-jlg'); ?>"
            aria-expanded="false"
            aria-controls="ssc-preview-column"
            aria-label="<?php echo esc_attr__('Afficher l\'aperçu', 'supersede-css-jlg'); ?>"
        >
            <?php echo esc_html__('Afficher l\'aperçu', 'supersede-css-jlg'); ?>
        </button>
        <p id="ssc-preview-visibility-status" class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></p>
        <div class="ssc-preview-column" id="ssc-preview-column" tabindex="-1" aria-hidden="false">
            <div class="ssc-preview-header">
                <div class="ssc-url-bar">
                    <label class="screen-reader-text" for="ssc-preview-url"><?php esc_html_e('URL de l\'aperçu', 'supersede-css-jlg'); ?></label>
                    <input type="url" id="ssc-preview-url" value="<?php echo esc_url($preview_url); ?>" aria-describedby="ssc-preview-url-help">
                    <button class="button" id="ssc-preview-load"><?php echo esc_html__('Load', 'supersede-css-jlg'); ?></button>
                    <button
                        class="button"
                        id="ssc-element-picker-toggle"
                        title="<?php echo esc_attr__('Cibler un élément', 'supersede-css-jlg'); ?>"
                        aria-label="<?php echo esc_attr__('Cibler un élément', 'supersede-css-jlg'); ?>"
                        data-mode-visibility="expert"
                    >
                        🎯
                        <span class="screen-reader-text"><?php esc_html_e('Cibler un élément', 'supersede-css-jlg'); ?></span>
                    </button>
                </div>
                <p id="ssc-preview-url-help" class="screen-reader-text"><?php esc_html_e('Saisissez une URL du même domaine que l\'administration WordPress pour charger l\'aperçu.', 'supersede-css-jlg'); ?></p>
                <div class="ssc-responsive-toggles" role="group" aria-label="<?php echo esc_attr__('Basculer le viewport de l\'aperçu', 'supersede-css-jlg'); ?>" data-mode-visibility="expert">
                    <button
                        type="button"
                        class="button button-primary"
                        data-vp="desktop"
                        data-width="1440"
                        data-label="<?php echo esc_attr__('Desktop', 'supersede-css-jlg'); ?>"
                        title="<?php echo esc_attr__('Desktop', 'supersede-css-jlg'); ?>"
                        aria-label="<?php echo esc_attr__('Desktop', 'supersede-css-jlg'); ?>"
                        aria-pressed="true"
                    >
                        🖥️
                        <span class="screen-reader-text"><?php esc_html_e('Basculer vers la vue ordinateur', 'supersede-css-jlg'); ?></span>
                    </button>
                    <button
                        type="button"
                        class="button"
                        data-vp="tablet"
                        data-width="768"
                        data-label="<?php echo esc_attr__('Tablette', 'supersede-css-jlg'); ?>"
                        title="<?php echo esc_attr__('Tablet', 'supersede-css-jlg'); ?>"
                        aria-label="<?php echo esc_attr__('Tablet', 'supersede-css-jlg'); ?>"
                        aria-pressed="false"
                    >
                        📲
                        <span class="screen-reader-text"><?php esc_html_e('Basculer vers la vue tablette', 'supersede-css-jlg'); ?></span>
                    </button>
                    <button
                        type="button"
                        class="button"
                        data-vp="mobile"
                        data-width="375"
                        data-label="<?php echo esc_attr__('Mobile', 'supersede-css-jlg'); ?>"
                        title="<?php echo esc_attr__('Mobile', 'supersede-css-jlg'); ?>"
                        aria-label="<?php echo esc_attr__('Mobile', 'supersede-css-jlg'); ?>"
                        aria-pressed="false"
                    >
                        📱
                        <span class="screen-reader-text"><?php esc_html_e('Basculer vers la vue mobile', 'supersede-css-jlg'); ?></span>
                    </button>
                </div>
                <div id="ssc-viewport-status" class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></div>
                <div class="ssc-viewport-width-control" data-mode-visibility="expert">
                    <label for="ssc-viewport-width"><?php esc_html_e('Largeur personnalisée de l\'aperçu (en pixels)', 'supersede-css-jlg'); ?></label>
                    <p id="ssc-viewport-width-help" class="description"><?php esc_html_e('Ajustez la largeur pour simuler une taille d\'écran spécifique. Les boutons ci-dessus appliquent des largeurs préconfigurées.', 'supersede-css-jlg'); ?></p>
                    <div class="ssc-viewport-width-inputs">
                        <input
                            type="range"
                            id="ssc-viewport-width"
                            min="320"
                            max="1920"
                            step="10"
                            value="1024"
                            aria-describedby="ssc-viewport-width-help"
                        >
                        <label class="screen-reader-text" for="ssc-viewport-width-number"><?php esc_html_e('Saisir une largeur personnalisée (en pixels)', 'supersede-css-jlg'); ?></label>
                        <input
                            type="number"
                            id="ssc-viewport-width-number"
                            min="320"
                            max="1920"
                            step="10"
                            value="1024"
                            inputmode="numeric"
                            aria-describedby="ssc-viewport-width-help"
                        >
                    </div>
                </div>
            </div>
            <div class="ssc-preview-frame-container">
                <div class="ssc-preview-frame-viewport">
                    <div id="ssc-picker-overlay"></div>
                    <div id="ssc-picker-tooltip"></div>
                    <iframe id="ssc-preview-frame" title="<?php echo esc_attr__('Aperçu en direct du CSS', 'supersede-css-jlg'); ?>" sandbox="allow-same-origin allow-forms allow-scripts"></iframe>
                    <div
                        id="ssc-preview-resize-handle"
                        class="ssc-preview-resize-handle"
                        role="slider"
                        tabindex="0"
                        aria-label="<?php echo esc_attr__('Glisser pour redimensionner la largeur de l\'aperçu', 'supersede-css-jlg'); ?>"
                        aria-describedby="ssc-viewport-width-help"
                        aria-orientation="horizontal"
                        aria-valuemin="320"
                        aria-valuemax="1920"
                        aria-valuenow="1024"
                        aria-valuetext="<?php echo esc_attr__('1024 pixels', 'supersede-css-jlg'); ?>"
                        data-mode-visibility="expert"
                    >
                        <span class="ssc-resize-grip" aria-hidden="true"></span>
                    </div>
                </div>
            </div>
            <div class="ssc-picked-selector" data-mode-visibility="expert">
                <label><?php esc_html_e('Sélecteur Ciblé :', 'supersede-css-jlg'); ?></label>
                <input type="text" id="ssc-picked-selector" readonly class="large-text" placeholder="<?php echo esc_attr__('Cliquez sur 🎯 puis sur un élément dans l\'aperçu.', 'supersede-css-jlg'); ?>">
            </div>
        </div>
    </div>
</div>
