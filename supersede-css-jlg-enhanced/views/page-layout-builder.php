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
    <h2>📐 Maquettage de Page (CSS Grid)</h2>
    <p>Préparez des mises en page complexes pour vos thèmes ou des sections spécifiques de vos pages.</p>

    <div class="ssc-panel ssc-tutorial-panel" style="margin-bottom:16px;">
        <h3>💡 Tutoriel : Comment Utiliser le Maquettage de Page dans WordPress</h3>
        <p>Cet outil génère le "plan" CSS de votre mise en page. Pour l'utiliser, vous devez ensuite construire la structure HTML correspondante dans votre page WordPress.</p>

        <h4>Étape 1 : Générer et Appliquer le CSS</h4>
        <ol>
            <li>Choisissez un "Modèle de layout" dans le menu déroulant ci-dessous. Le code CSS est généré instantanément.</li>
            <li>Copiez l'intégralité de ce code.</li>
            <li>Allez dans le menu <strong>Supersede CSS → Utilities</strong>, collez le code dans l'éditeur (onglet Desktop) et cliquez sur <strong>"Save CSS"</strong>.</li>
        </ol>

        <h4>Étape 2 : Créer la Structure HTML avec l'Éditeur de Blocs</h4>
        <ol>
            <li>Modifiez la page ou l'article où vous souhaitez appliquer cette mise en page.</li>
            <li>Ajoutez un bloc <strong>Groupe</strong>. Ce sera votre conteneur principal.</li>
            <li>Sélectionnez ce bloc Groupe, allez dans le panneau des réglages à droite, section <strong>"Avancé"</strong>.</li>
            <li>Dans le champ "Classe(s) CSS additionnelle(s)", collez la classe principale du layout (par exemple, <code>ssc-layout-holy-grail</code>).</li>
            <li>À l'intérieur de ce groupe principal, ajoutez un bloc (un "Groupe" est idéal) pour chaque zone définie dans le CSS (par exemple, 5 blocs pour le "Saint Graal").</li>
            <li>Pour chaque bloc intérieur, assignez la classe CSS de sa zone dans ses réglages "Avancé" (<code>header</code>, <code>content</code>, <code>footer</code>, etc.).</li>
            <li>Vous pouvez maintenant remplir ces blocs de zone avec votre contenu (textes, images, titres...).</li>
        </ol>
        <p>Votre mise en page est prête ! Elle s'adaptera automatiquement sur les écrans plus petits.</p>
    </div>


    <div class="ssc-two" style="align-items: flex-start;">
        <div class="ssc-pane">
            <h3>Paramètres & Code</h3>
            <label><strong>Modèle de layout</strong></label>
            <select id="layout-preset">
                <option value="holy-grail">Saint Graal (Header, 3 colonnes, Footer)</option>
                <option value="sidebar-right">Contenu + Sidebar à Droite</option>
                <option value="hero-features">Section Héro + 3 Cartes</option>
                <option value="dashboard">Tableau de Bord Asymétrique</option>
            </select>
            <hr>
            <label><strong>Vue :</strong></label>
            <div class="ssc-actions">
                <button class="button button-primary" id="view-desktop">Desktop</button>
                <button class="button" id="view-mobile">Mobile</button>
            </div>
            <h3 style="margin-top:24px;">Code CSS Généré</h3>
            <pre id="layout-css" class="ssc-code"></pre>
        </div>
        <div class="ssc-pane">
            <h3>Aperçu Visuel</h3>
            <div id="layout-preview-container">
                <div id="layout-grid-desktop" class="ssc-layout-grid"></div>
                <div id="layout-grid-mobile" class="ssc-layout-grid ssc-layout-preview-mobile" style="display:none;"></div>
            </div>
        </div>
    </div>

    <div class="ssc-panel ssc-tutorial-panel" style="margin-top:16px;">
        <h3>🚀 Idées d'Amélioration & Inspiration</h3>
        <h4>Ajouter de l'Espacement (Gap)</h4>
        <p>Par défaut, les blocs sont collés. Pour ajouter un espacement uniforme entre toutes les zones, modifiez la classe principale dans votre CSS et ajoutez la propriété <code>gap</code> :</p>
        <pre class="ssc-code">.ssc-layout-holy-grail {
  display: grid;
  gap: 1rem; /* ou 16px, 2em, etc. */
  /* ... autres propriétés ... */
}</pre>

        <h4>Layouts pour des Sections de Page</h4>
        <p>N'hésitez pas à utiliser ces layouts non pas pour une page entière, mais pour une section spécifique. Le modèle "Héro + 3 Cartes" est parfait pour une section "Nos services" sur votre page d'accueil.</p>

        <h4>Combiner avec les Tokens</h4>
        <p>Pour une maintenance facile, définissez vos espacements ou tailles de colonnes avec des <a href="<?php echo esc_url($tokens_page_url); ?>">Tokens</a>. Par exemple :</p>
        <pre class="ssc-code">:root { --spacing-medium: 1.5rem; }

.ssc-layout-sidebar-right {
  display: grid;
  grid-template-columns: 3fr 1fr;
  gap: var(--spacing-medium);
}</pre>

        <h4>Créer vos propres modèles</h4>
        <p>Utilisez les modèles générés comme base. En modifiant les valeurs de <code>grid-template-areas</code> et <code>grid-template-columns</code>, vous pouvez inventer n'importe quelle mise en page imaginable !</p>
    </div>
</div>
