<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var string $tokens_page_url */
?>
<style>
    .ssc-layout-grid { display: grid; height: 400px; border: 2px dashed var(--ssc-accent); padding: 10px; border-radius: 8px; background: var(--ssc-bg); }
    .ssc-layout-block { background: var(--ssc-card); border: 1px solid var(--ssc-border); border-radius: 4px; display: grid; place-items: center; font-weight: bold; }
    .ssc-layout-preview-mobile { width: 375px; margin-left: auto; margin-right: auto; }
    .ssc-tutorial-panel h4 { margin-top: 1.2em; margin-bottom: 0.5em; }
    .ssc-tutorial-panel ul, .ssc-tutorial-panel ol { margin-left: 20px; }
</style>
<div class="ssc-app ssc-fullwidth">
    <h2><?php esc_html_e('📐 Maquettage de Page (CSS Grid)', 'supersede-css-jlg'); ?></h2>
    <p><?php esc_html_e('Préparez des mises en page complexes pour vos thèmes ou des sections spécifiques de vos pages.', 'supersede-css-jlg'); ?></p>

    <div class="ssc-panel ssc-tutorial-panel" style="margin-bottom:16px;">
        <h3><?php esc_html_e('💡 Tutoriel : Comment Utiliser le Maquettage de Page dans WordPress', 'supersede-css-jlg'); ?></h3>
        <p><?php esc_html_e('Cet outil génère le "plan" CSS de votre mise en page. Pour l\'utiliser, vous devez ensuite construire la structure HTML correspondante dans votre page WordPress.', 'supersede-css-jlg'); ?></p>

        <h4><?php esc_html_e('Étape 1 : Générer et Appliquer le CSS', 'supersede-css-jlg'); ?></h4>
        <ol>
            <li><?php esc_html_e('Choisissez un "Modèle de layout" dans le menu déroulant ci-dessous. Le code CSS est généré instantanément.', 'supersede-css-jlg'); ?></li>
            <li><?php esc_html_e("Copiez l'intégralité de ce code.", 'supersede-css-jlg'); ?></li>
            <li><?php echo wp_kses_post(__('Allez dans le menu <strong>Supersede CSS → Utilities</strong>, collez le code dans l\'éditeur (onglet Desktop) et cliquez sur <strong>"Save CSS"</strong>.', 'supersede-css-jlg')); ?></li>
        </ol>

        <h4><?php esc_html_e('Étape 2 : Créer la Structure HTML avec l’Éditeur de Blocs', 'supersede-css-jlg'); ?></h4>
        <ol>
            <li><?php esc_html_e("Modifiez la page ou l'article où vous souhaitez appliquer cette mise en page.", 'supersede-css-jlg'); ?></li>
            <li><?php echo wp_kses_post(__('Ajoutez un bloc <strong>Groupe</strong>. Ce sera votre conteneur principal.', 'supersede-css-jlg')); ?></li>
            <li><?php echo wp_kses_post(__('Sélectionnez ce bloc Groupe, allez dans le panneau des réglages à droite, section <strong>"Avancé"</strong>.', 'supersede-css-jlg')); ?></li>
            <li><?php echo wp_kses_post(__('Dans le champ "Classe(s) CSS additionnelle(s)", collez la classe principale du layout (par exemple, <code>ssc-layout-holy-grail</code>).', 'supersede-css-jlg')); ?></li>
            <li><?php echo wp_kses_post(__('À l\'intérieur de ce groupe principal, ajoutez un bloc (un "Groupe" est idéal) pour chaque zone définie dans le CSS (par exemple, 5 blocs pour le "Saint Graal").', 'supersede-css-jlg')); ?></li>
            <li><?php echo wp_kses_post(__('Pour chaque bloc intérieur, assignez la classe CSS de sa zone dans ses réglages "Avancé" (<code>header</code>, <code>content</code>, <code>footer</code>, etc.).', 'supersede-css-jlg')); ?></li>
            <li><?php esc_html_e('Vous pouvez maintenant remplir ces blocs de zone avec votre contenu (textes, images, titres...).', 'supersede-css-jlg'); ?></li>
        </ol>
        <p><?php esc_html_e('Votre mise en page est prête ! Elle s’adaptera automatiquement sur les écrans plus petits.', 'supersede-css-jlg'); ?></p>
    </div>


    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3><?php esc_html_e('Paramètres & Code', 'supersede-css-jlg'); ?></h3>
            <label><strong><?php esc_html_e('Modèle de layout', 'supersede-css-jlg'); ?></strong></label>
            <select id="layout-preset">
                <option value="holy-grail"><?php esc_html_e('Saint Graal (Header, 3 colonnes, Footer)', 'supersede-css-jlg'); ?></option>
                <option value="sidebar-right"><?php esc_html_e('Contenu + Sidebar à Droite', 'supersede-css-jlg'); ?></option>
                <option value="hero-features"><?php esc_html_e('Section Héro + 3 Cartes', 'supersede-css-jlg'); ?></option>
                <option value="dashboard"><?php esc_html_e('Tableau de Bord Asymétrique', 'supersede-css-jlg'); ?></option>
            </select>
            <hr>
            <label><strong><?php esc_html_e('Vue :', 'supersede-css-jlg'); ?></strong></label>
            <div class="ssc-actions">
                <button class="button button-primary" id="view-desktop"><?php esc_html_e('Desktop', 'supersede-css-jlg'); ?></button>
                <button class="button" id="view-mobile"><?php esc_html_e('Mobile', 'supersede-css-jlg'); ?></button>
            </div>
            <h3 style="margin-top:24px;"><?php esc_html_e('Code CSS Généré', 'supersede-css-jlg'); ?></h3>
            <pre id="layout-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3><?php esc_html_e('Aperçu Visuel', 'supersede-css-jlg'); ?></h3>
            <div id="layout-preview-container">
                <div id="layout-grid-desktop" class="ssc-layout-grid"></div>
                <div id="layout-grid-mobile" class="ssc-layout-grid ssc-layout-preview-mobile" style="display:none;"></div>
            </div>
        </div>
    </div>

    <div class="ssc-panel ssc-tutorial-panel" style="margin-top:16px;">
        <h3><?php esc_html_e('🚀 Idées d’Amélioration & Inspiration', 'supersede-css-jlg'); ?></h3>
        <h4><?php esc_html_e('Ajouter de l’Espacement (Gap)', 'supersede-css-jlg'); ?></h4>
        <p><?php echo wp_kses_post(__('Par défaut, les blocs sont collés. Pour ajouter un espacement uniforme entre toutes les zones, modifiez la classe principale dans votre CSS et ajoutez la propriété <code>gap</code> :', 'supersede-css-jlg')); ?></p>
        <pre class="ssc-code">.ssc-layout-holy-grail {
  display: grid;
  gap: 1rem; /* ou 16px, 2em, etc. */
  /* ... autres propriétés ... */
}</pre>

        <h4><?php esc_html_e('Layouts pour des Sections de Page', 'supersede-css-jlg'); ?></h4>
        <p><?php esc_html_e('N’hésitez pas à utiliser ces layouts non pas pour une page entière, mais pour une section spécifique. Le modèle "Héro + 3 Cartes" est parfait pour une section "Nos services" sur votre page d’accueil.', 'supersede-css-jlg'); ?></p>

        <h4><?php esc_html_e('Combiner avec les Tokens', 'supersede-css-jlg'); ?></h4>
        <p><?php printf(wp_kses_post(__('Pour une maintenance facile, définissez vos espacements ou tailles de colonnes avec des %s. Par exemple :', 'supersede-css-jlg')), '<a href="' . esc_url($tokens_page_url) . '">' . esc_html__('Tokens', 'supersede-css-jlg') . '</a>'); ?></p>
        <pre class="ssc-code">:root { --spacing-medium: 1.5rem; }

.ssc-layout-sidebar-right {
  display: grid;
  grid-template-columns: 3fr 1fr;
  gap: var(--spacing-medium);
}</pre>

        <h4><?php esc_html_e('Créer vos propres modèles', 'supersede-css-jlg'); ?></h4>
        <p><?php echo wp_kses_post(__('Utilisez les modèles générés comme base. En modifiant les valeurs de <code>grid-template-areas</code> et <code>grid-template-columns</code>, vous pouvez inventer n\'importe quelle mise en page imaginable !', 'supersede-css-jlg')); ?></p>
    </div>
</div>
