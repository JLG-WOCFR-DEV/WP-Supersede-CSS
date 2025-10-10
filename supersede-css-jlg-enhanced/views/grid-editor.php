<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('📏 Visual Grid Editor', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Construisez des mises en page CSS Grid de manière intuitive, sans écrire de code.', 'supersede-css-jlg'); ?></p>
    <div class="ssc-two ssc-two--align-start">
        <div class="ssc-pane">
            <h3><?php esc_html_e('Paramètres de la Grille', 'supersede-css-jlg'); ?></h3>

            <div class="ssc-form-field">
                <label class="ssc-form-label" for="ssc-grid-cols"><?php esc_html_e('Nombre de colonnes', 'supersede-css-jlg'); ?></label>
                <div class="ssc-range-control">
                    <input type="range" id="ssc-grid-cols" min="1" max="12" value="3" step="1">
                    <span id="ssc-grid-cols-val" class="ssc-range-output"><?php echo esc_html__('3', 'supersede-css-jlg'); ?></span>
                </div>
            </div>

            <div class="ssc-form-field">
                <label class="ssc-form-label" for="ssc-grid-gap"><?php esc_html_e('Espacement (gap) en pixels', 'supersede-css-jlg'); ?></label>
                <div class="ssc-range-control">
                    <input type="range" id="ssc-grid-gap" min="0" max="100" value="24" step="1">
                    <span id="ssc-grid-gap-val" class="ssc-range-output"><?php echo esc_html__('24px', 'supersede-css-jlg'); ?></span>
                </div>
            </div>

            <div class="ssc-form-actions ssc-form-actions--separated">
                <button id="ssc-grid-apply" class="button button-primary"><?php esc_html_e('Appliquer', 'supersede-css-jlg'); ?></button>
                <button id="ssc-grid-copy" class="button"><?php esc_html_e('Copier CSS', 'supersede-css-jlg'); ?></button>
            </div>

            <h3 class="ssc-section-heading"><?php esc_html_e('Code CSS Généré', 'supersede-css-jlg'); ?></h3>
            <p class="description"><?php printf(wp_kses_post(__('Appliquez la classe %s à votre conteneur.', 'supersede-css-jlg')), '<code>.ssc-grid-container</code>'); ?></p>
            <pre id="ssc-grid-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Aperçu en Direct', 'supersede-css-jlg'); ?></h3>
            <div class="ssc-grid-preview-controls">
                <p class="ssc-grid-preview-heading">
                    <span class="ssc-grid-preview-heading__label"><?php esc_html_e('Scène sélectionnée', 'supersede-css-jlg'); ?></span>
                    <strong id="ssc-grid-preview-template-name"><?php esc_html_e('Cartes éditoriales', 'supersede-css-jlg'); ?></strong>
                </p>
                <div class="ssc-grid-preview-toolbar" role="group" aria-label="<?php echo esc_attr__('Scènes de prévisualisation', 'supersede-css-jlg'); ?>">
                    <button
                        type="button"
                        class="button button-secondary ssc-grid-preview-template is-active"
                        data-template="cards"
                        data-template-label="<?php echo esc_attr__('Cartes éditoriales', 'supersede-css-jlg'); ?>"
                        data-template-description="<?php echo esc_attr__('Simule une grille d’articles premium pour visualiser les colonnes et l’espacement recommandés.', 'supersede-css-jlg'); ?>"
                        data-template-announce="<?php echo esc_attr__('Scène « Cartes éditoriales » activée. Valeurs recommandées appliquées.', 'supersede-css-jlg'); ?>"
                        data-template-cols="3"
                        data-template-gap="24"
                        data-template-toast="<?php echo esc_attr__('Scène « Cartes éditoriales » chargée.', 'supersede-css-jlg'); ?>"
                        aria-pressed="true"
                    >
                        <span class="ssc-grid-preview-template__icon" aria-hidden="true">📰</span>
                        <span class="ssc-grid-preview-template__text"><?php esc_html_e('Cartes éditoriales', 'supersede-css-jlg'); ?></span>
                    </button>
                    <button
                        type="button"
                        class="button button-secondary ssc-grid-preview-template"
                        data-template="team"
                        data-template-label="<?php echo esc_attr__('Équipe produit', 'supersede-css-jlg'); ?>"
                        data-template-description="<?php echo esc_attr__('Montre un trombinoscope moderne pour vérifier la densité et l’alignement des éléments.', 'supersede-css-jlg'); ?>"
                        data-template-announce="<?php echo esc_attr__('Scène « Équipe produit » activée. Valeurs recommandées appliquées.', 'supersede-css-jlg'); ?>"
                        data-template-cols="4"
                        data-template-gap="32"
                        data-template-toast="<?php echo esc_attr__('Scène « Équipe produit » chargée.', 'supersede-css-jlg'); ?>"
                        aria-pressed="false"
                    >
                        <span class="ssc-grid-preview-template__icon" aria-hidden="true">👥</span>
                        <span class="ssc-grid-preview-template__text"><?php esc_html_e('Équipe produit', 'supersede-css-jlg'); ?></span>
                    </button>
                    <button
                        type="button"
                        class="button button-secondary ssc-grid-preview-template"
                        data-template="pricing"
                        data-template-label="<?php echo esc_attr__('Offres & tarifs', 'supersede-css-jlg'); ?>"
                        data-template-description="<?php echo esc_attr__('Projetez une grille de plans tarifaires pour contrôler les ratios et les call-to-action.', 'supersede-css-jlg'); ?>"
                        data-template-announce="<?php echo esc_attr__('Scène « Offres & tarifs » activée. Valeurs recommandées appliquées.', 'supersede-css-jlg'); ?>"
                        data-template-cols="3"
                        data-template-gap="28"
                        data-template-toast="<?php echo esc_attr__('Scène « Offres & tarifs » chargée.', 'supersede-css-jlg'); ?>"
                        aria-pressed="false"
                    >
                        <span class="ssc-grid-preview-template__icon" aria-hidden="true">💼</span>
                        <span class="ssc-grid-preview-template__text"><?php esc_html_e('Offres & tarifs', 'supersede-css-jlg'); ?></span>
                    </button>
                </div>
                <p class="ssc-grid-preview-helper" id="ssc-grid-preview-template-description"><?php esc_html_e('Simule une grille d’articles premium pour visualiser les colonnes et l’espacement recommandés.', 'supersede-css-jlg'); ?></p>
                <div class="ssc-grid-preview-meta" aria-live="polite">
                    <div class="ssc-grid-preview-meta__item">
                        <span><?php esc_html_e('Colonnes', 'supersede-css-jlg'); ?></span>
                        <strong id="ssc-grid-preview-cols-meta">3</strong>
                    </div>
                    <div class="ssc-grid-preview-meta__item">
                        <span><?php esc_html_e('Espacement', 'supersede-css-jlg'); ?></span>
                        <strong id="ssc-grid-preview-gap-meta">24px</strong>
                    </div>
                </div>
                <div class="ssc-grid-preview-toolbar ssc-grid-preview-toolbar--surfaces" role="group" aria-label="<?php echo esc_attr__('Surface de prévisualisation', 'supersede-css-jlg'); ?>">
                    <button
                        type="button"
                        class="button ssc-grid-preview-surface is-active"
                        data-surface="light"
                        data-surface-announce="<?php echo esc_attr__('Surface claire appliquée à l’aperçu.', 'supersede-css-jlg'); ?>"
                        aria-pressed="true"
                    >
                        <?php esc_html_e('Surface claire', 'supersede-css-jlg'); ?>
                    </button>
                    <button
                        type="button"
                        class="button ssc-grid-preview-surface"
                        data-surface="dark"
                        data-surface-announce="<?php echo esc_attr__('Surface sombre appliquée à l’aperçu.', 'supersede-css-jlg'); ?>"
                        aria-pressed="false"
                    >
                        <?php esc_html_e('Surface sombre', 'supersede-css-jlg'); ?>
                    </button>
                </div>
            </div>
            <div
                id="ssc-grid-preview"
                class="ssc-grid-preview ssc-grid-preview--surface-light"
                role="presentation"
                data-announce-template="<?php echo esc_attr__('Grille mise à jour : %1$s colonnes, %2$spx d’espacement.', 'supersede-css-jlg'); ?>"
            >
                <!-- Les éléments de la grille seront générés par JS -->
            </div>
            <p id="ssc-grid-preview-live" class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></p>
        </div>
    </div>
</div>

<template id="ssc-grid-template-cards">
    <article class="ssc-grid-preview-card" data-variant="card">
        <div class="ssc-grid-preview-card__media" role="presentation">
            <span class="ssc-grid-preview-card__emoji" aria-hidden="true">🎨</span>
        </div>
        <div class="ssc-grid-preview-card__content">
            <span class="ssc-grid-preview-card__badge"><?php esc_html_e('Article', 'supersede-css-jlg'); ?></span>
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Système de spacing cohérent', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__excerpt"><?php esc_html_e('Définissez une échelle d’espacement harmonisée pour accélérer vos maquettes.', 'supersede-css-jlg'); ?></p>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__meta"><?php esc_html_e('5 min de lecture', 'supersede-css-jlg'); ?></span>
            <span class="ssc-grid-preview-card__action"><?php esc_html_e('Lire', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
    <article class="ssc-grid-preview-card" data-variant="card">
        <div class="ssc-grid-preview-card__media" role="presentation">
            <span class="ssc-grid-preview-card__emoji" aria-hidden="true">⚡</span>
        </div>
        <div class="ssc-grid-preview-card__content">
            <span class="ssc-grid-preview-card__badge"><?php esc_html_e('Étude de cas', 'supersede-css-jlg'); ?></span>
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Fluidifier la typographie responsive', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__excerpt"><?php esc_html_e('Utilisez clamp() et les presets Supersede pour garder une lecture optimale.', 'supersede-css-jlg'); ?></p>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__meta"><?php esc_html_e('Nouveau', 'supersede-css-jlg'); ?></span>
            <span class="ssc-grid-preview-card__action"><?php esc_html_e('Explorer', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
    <article class="ssc-grid-preview-card" data-variant="card">
        <div class="ssc-grid-preview-card__media" role="presentation">
            <span class="ssc-grid-preview-card__emoji" aria-hidden="true">🧠</span>
        </div>
        <div class="ssc-grid-preview-card__content">
            <span class="ssc-grid-preview-card__badge"><?php esc_html_e('Guide', 'supersede-css-jlg'); ?></span>
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Workflow d’approbation des tokens', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__excerpt"><?php esc_html_e('Suivez chaque évolution de vos variables via le Debug Center amélioré.', 'supersede-css-jlg'); ?></p>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__meta"><?php esc_html_e('Mis à jour', 'supersede-css-jlg'); ?></span>
            <span class="ssc-grid-preview-card__action"><?php esc_html_e('Consulter', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
    <article class="ssc-grid-preview-card" data-variant="card">
        <div class="ssc-grid-preview-card__media" role="presentation">
            <span class="ssc-grid-preview-card__emoji" aria-hidden="true">🧩</span>
        </div>
        <div class="ssc-grid-preview-card__content">
            <span class="ssc-grid-preview-card__badge"><?php esc_html_e('Tutoriel', 'supersede-css-jlg'); ?></span>
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Assembler des presets UI', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__excerpt"><?php esc_html_e('Combinez tokens, presets et layouts pour livrer un design system complet.', 'supersede-css-jlg'); ?></p>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__meta"><?php esc_html_e('8 min de lecture', 'supersede-css-jlg'); ?></span>
            <span class="ssc-grid-preview-card__action"><?php esc_html_e('Suivre', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
</template>

<template id="ssc-grid-template-team">
    <article class="ssc-grid-preview-card ssc-grid-preview-card--team" data-variant="team">
        <div class="ssc-grid-preview-avatar" role="presentation">
            <span aria-hidden="true">👩‍💻</span>
        </div>
        <div class="ssc-grid-preview-card__content">
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Lina Marques', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__excerpt"><?php esc_html_e('Lead Product Designer · Tokens & UI presets', 'supersede-css-jlg'); ?></p>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__meta"><?php esc_html_e('Paris · UTC+1', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
    <article class="ssc-grid-preview-card ssc-grid-preview-card--team" data-variant="team">
        <div class="ssc-grid-preview-avatar" role="presentation">
            <span aria-hidden="true">🧑‍🚀</span>
        </div>
        <div class="ssc-grid-preview-card__content">
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Jules Perrin', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__excerpt"><?php esc_html_e('Staff Engineer · Performance & DevTools', 'supersede-css-jlg'); ?></p>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__meta"><?php esc_html_e('Montréal · UTC-5', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
    <article class="ssc-grid-preview-card ssc-grid-preview-card--team" data-variant="team">
        <div class="ssc-grid-preview-avatar" role="presentation">
            <span aria-hidden="true">🧑‍💼</span>
        </div>
        <div class="ssc-grid-preview-card__content">
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Noah Abadie', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__excerpt"><?php esc_html_e('Product Manager · Workflow & gouvernance', 'supersede-css-jlg'); ?></p>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__meta"><?php esc_html_e('Lyon · UTC+1', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
    <article class="ssc-grid-preview-card ssc-grid-preview-card--team" data-variant="team">
        <div class="ssc-grid-preview-avatar" role="presentation">
            <span aria-hidden="true">🧑‍🎨</span>
        </div>
        <div class="ssc-grid-preview-card__content">
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Aya Benali', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__excerpt"><?php esc_html_e('Brand Designer · Motion & storytelling', 'supersede-css-jlg'); ?></p>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__meta"><?php esc_html_e('Remote · Global', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
    <article class="ssc-grid-preview-card ssc-grid-preview-card--team" data-variant="team">
        <div class="ssc-grid-preview-avatar" role="presentation">
            <span aria-hidden="true">🧑‍🔬</span>
        </div>
        <div class="ssc-grid-preview-card__content">
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Elliot Faure', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__excerpt"><?php esc_html_e('UX Researcher · Insights & tests utilisateur', 'supersede-css-jlg'); ?></p>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__meta"><?php esc_html_e('Lisbonne · UTC+0', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
    <article class="ssc-grid-preview-card ssc-grid-preview-card--team" data-variant="team">
        <div class="ssc-grid-preview-avatar" role="presentation">
            <span aria-hidden="true">🧑‍🏫</span>
        </div>
        <div class="ssc-grid-preview-card__content">
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Nina Kermadec', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__excerpt"><?php esc_html_e('Lead QA · Tests Playwright & résilience', 'supersede-css-jlg'); ?></p>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__meta"><?php esc_html_e('Toulouse · UTC+1', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
</template>

<template id="ssc-grid-template-pricing">
    <article class="ssc-grid-preview-card ssc-grid-preview-card--pricing" data-variant="pricing">
        <div class="ssc-grid-preview-card__content">
            <span class="ssc-grid-preview-card__badge"><?php esc_html_e('Starter', 'supersede-css-jlg'); ?></span>
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Pack Launch', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__price">
                <strong>€39</strong>
                <span><?php esc_html_e('/ mois', 'supersede-css-jlg'); ?></span>
            </p>
            <ul class="ssc-grid-preview-card__list">
                <li><?php esc_html_e('Jusqu’à 3 projets', 'supersede-css-jlg'); ?></li>
                <li><?php esc_html_e('Exports JSON & CSS', 'supersede-css-jlg'); ?></li>
                <li><?php esc_html_e('Support community', 'supersede-css-jlg'); ?></li>
            </ul>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__action"><?php esc_html_e('Démarrer', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
    <article class="ssc-grid-preview-card ssc-grid-preview-card--pricing is-highlighted" data-variant="pricing">
        <div class="ssc-grid-preview-card__content">
            <span class="ssc-grid-preview-card__badge"><?php esc_html_e('Populaire', 'supersede-css-jlg'); ?></span>
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Pack Studio', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__price">
                <strong>€89</strong>
                <span><?php esc_html_e('/ mois', 'supersede-css-jlg'); ?></span>
            </p>
            <ul class="ssc-grid-preview-card__list">
                <li><?php esc_html_e('Tokens illimités', 'supersede-css-jlg'); ?></li>
                <li><?php esc_html_e('Workflows d’approbation', 'supersede-css-jlg'); ?></li>
                <li><?php esc_html_e('Support prioritaire 24h', 'supersede-css-jlg'); ?></li>
            </ul>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__action"><?php esc_html_e('Essayer', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
    <article class="ssc-grid-preview-card ssc-grid-preview-card--pricing" data-variant="pricing">
        <div class="ssc-grid-preview-card__content">
            <span class="ssc-grid-preview-card__badge"><?php esc_html_e('Enterprise', 'supersede-css-jlg'); ?></span>
            <h4 class="ssc-grid-preview-card__title"><?php esc_html_e('Pack Scale', 'supersede-css-jlg'); ?></h4>
            <p class="ssc-grid-preview-card__price">
                <strong>€169</strong>
                <span><?php esc_html_e('/ mois', 'supersede-css-jlg'); ?></span>
            </p>
            <ul class="ssc-grid-preview-card__list">
                <li><?php esc_html_e('Intégrations CI/CD', 'supersede-css-jlg'); ?></li>
                <li><?php esc_html_e('Exports natifs iOS & Android', 'supersede-css-jlg'); ?></li>
                <li><?php esc_html_e('Audit design system trimestriel', 'supersede-css-jlg'); ?></li>
            </ul>
        </div>
        <footer class="ssc-grid-preview-card__footer">
            <span class="ssc-grid-preview-card__action"><?php esc_html_e('Contacter', 'supersede-css-jlg'); ?></span>
        </footer>
    </article>
</template>
