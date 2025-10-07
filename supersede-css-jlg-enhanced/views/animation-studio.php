<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-animation-studio">
    <h1><?php esc_html_e('🎬 Animation Studio', 'supersede-css-jlg'); ?></h1>
    <p class="description">
        <?php esc_html_e("Définissez votre animation, choisissez la surface d'aperçu et voyez instantanément le rendu sur un composant de carte contextualisé.", 'supersede-css-jlg'); ?>
    </p>
    <div class="ssc-two ssc-align-start">
        <section class="ssc-pane" aria-labelledby="ssc-anim-settings-title">
            <h2 id="ssc-anim-settings-title"><?php esc_html_e("Paramètres de l'animation", 'supersede-css-jlg'); ?></h2>
            <label class="ssc-field-label" for="ssc-anim-preset"><?php esc_html_e("Preset d'animation", 'supersede-css-jlg'); ?></label>
            <select id="ssc-anim-preset" class="regular-text">
                <option value="bounce"><?php esc_html_e('Bounce (Rebond)', 'supersede-css-jlg'); ?></option>
                <option value="pulse"><?php esc_html_e('Pulse (Pulsation)', 'supersede-css-jlg'); ?></option>
                <option value="fade-in"><?php esc_html_e('Fade In (Apparition)', 'supersede-css-jlg'); ?></option>
                <option value="slide-in-left"><?php esc_html_e('Slide In Left (Glisse depuis la gauche)', 'supersede-css-jlg'); ?></option>
            </select>

            <label class="ssc-field-label ssc-mt-200" for="ssc-anim-duration"><?php esc_html_e('Durée (secondes)', 'supersede-css-jlg'); ?></label>
            <div class="ssc-stack">
                <input type="range" id="ssc-anim-duration" min="0.1" max="5" value="1.5" step="0.1" aria-describedby="ssc-anim-duration-val">
                <span id="ssc-anim-duration-val" aria-live="polite"><?php echo esc_html__('1.5s', 'supersede-css-jlg'); ?></span>
            </div>

            <fieldset class="ssc-fieldset ssc-mt-200">
                <legend><?php esc_html_e('Élément à animer', 'supersede-css-jlg'); ?></legend>
                <p class="description"><?php esc_html_e('Appliquez l’effet sur la carte entière, le badge, l’avatar ou l’appel à l’action pour comparer les rendus.', 'supersede-css-jlg'); ?></p>
                <label class="ssc-choice">
                    <input type="radio" name="ssc-anim-target" value="card" checked>
                    <span><?php esc_html_e('Carte complète', 'supersede-css-jlg'); ?></span>
                </label>
                <label class="ssc-choice">
                    <input type="radio" name="ssc-anim-target" value="badge">
                    <span><?php esc_html_e('Badge', 'supersede-css-jlg'); ?></span>
                </label>
                <label class="ssc-choice">
                    <input type="radio" name="ssc-anim-target" value="avatar">
                    <span><?php esc_html_e('Avatar', 'supersede-css-jlg'); ?></span>
                </label>
                <label class="ssc-choice">
                    <input type="radio" name="ssc-anim-target" value="cta">
                    <span><?php esc_html_e('Bouton principal', 'supersede-css-jlg'); ?></span>
                </label>
            </fieldset>

            <div class="ssc-actions ssc-divider-top">
                <button id="ssc-anim-apply" class="button button-primary"><?php esc_html_e('Appliquer sur le site', 'supersede-css-jlg'); ?></button>
                <button id="ssc-anim-copy" class="button"><?php esc_html_e('Copier le CSS', 'supersede-css-jlg'); ?></button>
            </div>

            <h2 class="ssc-mt-300"><?php esc_html_e('Code CSS généré', 'supersede-css-jlg'); ?></h2>
            <p class="description"><?php printf(wp_kses_post(__('Ajoutez %1$s puis la classe du preset (ex.&nbsp;: %2$s) sur votre élément.', 'supersede-css-jlg')), '<code>.ssc-animated</code>', '<code>.ssc-bounce</code>'); ?></p>
            <pre id="ssc-anim-css" class="ssc-code" aria-live="polite"></pre>
        </section>

        <section class="ssc-pane" aria-labelledby="ssc-anim-preview-title">
            <h2 id="ssc-anim-preview-title"><?php esc_html_e('Aperçu contextualisé', 'supersede-css-jlg'); ?></h2>
            <div class="ssc-preview-studio">
                <div class="ssc-preview-toolbar" role="toolbar" aria-label="<?php esc_attr_e('Contrôles de prévisualisation', 'supersede-css-jlg'); ?>">
                    <div class="ssc-preview-toolbar-group" role="group" aria-label="<?php esc_attr_e('Choisir un appareil', 'supersede-css-jlg'); ?>">
                        <span class="ssc-preview-toolbar-label"><?php esc_html_e('Surface', 'supersede-css-jlg'); ?></span>
                        <button type="button" class="ssc-toolbar-button ssc-preview-device-toggle" data-surface="desktop" aria-pressed="true">
                            <span class="dashicons dashicons-desktop" aria-hidden="true"></span>
                            <span><?php esc_html_e('Desktop', 'supersede-css-jlg'); ?></span>
                        </button>
                        <button type="button" class="ssc-toolbar-button ssc-preview-device-toggle" data-surface="tablet" aria-pressed="false">
                            <span class="dashicons dashicons-tablet" aria-hidden="true"></span>
                            <span><?php esc_html_e('Tablette', 'supersede-css-jlg'); ?></span>
                        </button>
                        <button type="button" class="ssc-toolbar-button ssc-preview-device-toggle" data-surface="mobile" aria-pressed="false">
                            <span class="dashicons dashicons-smartphone" aria-hidden="true"></span>
                            <span><?php esc_html_e('Mobile', 'supersede-css-jlg'); ?></span>
                        </button>
                    </div>
                    <div class="ssc-preview-toolbar-group" role="group" aria-label="<?php esc_attr_e('Choisir le fond de scène', 'supersede-css-jlg'); ?>">
                        <span class="ssc-preview-toolbar-label"><?php esc_html_e('Atmosphère', 'supersede-css-jlg'); ?></span>
                        <button type="button" class="ssc-toolbar-button ssc-preview-bg-toggle" aria-pressed="false">
                            <span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
                            <span><?php esc_html_e('Fond sombre', 'supersede-css-jlg'); ?></span>
                        </button>
                    </div>
                </div>

                <div class="ssc-preview-stage" data-surface="desktop" id="ssc-anim-preview-stage">
                    <div class="ssc-preview-device">
                        <div class="ssc-preview-card ssc-anim-target" id="ssc-anim-preview-card">
                            <div class="ssc-preview-avatar ssc-anim-target" id="ssc-anim-preview-avatar" aria-hidden="true">AR</div>
                            <div class="ssc-preview-content">
                                <span class="ssc-anim-target" id="ssc-anim-preview-badge"><?php esc_html_e('Nouveau preset', 'supersede-css-jlg'); ?></span>
                                <h4><?php esc_html_e('Séquence Aurora', 'supersede-css-jlg'); ?></h4>
                                <p><?php esc_html_e('Une animation de halo progressive idéale pour les CTA à fort impact marketing.', 'supersede-css-jlg'); ?></p>
                                <div class="ssc-preview-actions">
                                    <button type="button" class="button button-primary ssc-anim-target" id="ssc-anim-preview-cta"><?php esc_html_e('Activer l’effet', 'supersede-css-jlg'); ?></button>
                                    <button type="button" class="button ssc-anim-target" id="ssc-anim-preview-secondary"><?php esc_html_e('Prévisualiser', 'supersede-css-jlg'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="ssc-preview-note">
                    <span class="dashicons dashicons-controls-repeat" aria-hidden="true"></span>
                    <?php esc_html_e('Les animations se relancent automatiquement à chaque modification de preset ou de cible.', 'supersede-css-jlg'); ?>
                </p>
            </div>
        </section>
    </div>
</div>
