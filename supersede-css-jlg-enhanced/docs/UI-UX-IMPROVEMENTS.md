# Analyse UI/UX et Pistes d'Amélioration

## Constat vs. niveau « app pro »
- **Densité typographique élevée** : la base admin utilise une taille de police unique de 14px et un contraste limité entre les textes primaires et secondaires, ce qui fatigue l’œil sur de longues sessions. 【F:supersede-css-jlg-enhanced/assets/css/admin.css†L2-L29】
- **Styles en ligne récurrents** : de nombreux composants d’édition (ex. Grid Editor) définissent marges, bordures et alignements directement dans le HTML, ce qui complique la maintenance et l’harmonisation visuelle à l’échelle de l’app. 【F:supersede-css-jlg-enhanced/views/grid-editor.php†L9-L33】
- **Hiérarchie visuelle limitée** : les panneaux partagent la même densité (mêmes couleurs, mêmes paddings), ce qui rend difficile la distinction entre actions critiques et secondaires et donne une impression « formulaire WordPress » plutôt qu’un studio créatif premium. 【F:supersede-css-jlg-enhanced/assets/css/admin.css†L39-L96】
- **Zones d’aperçu peu guidées** : plusieurs aperçus utilisent des conteneurs vides ou un simple carré coloré sans guidage supplémentaire, ce qui est moins engageant qu’un rendu contextualisé comme on le voit dans des apps pro (Figma, Webflow). 【F:supersede-css-jlg-enhanced/views/animation-studio.php†L24-L34】

## Améliorations prioritaires
1. **Système de design plus structuré**
   - Introduire une échelle typographique (14/16/20/24) et des variables de graisse pour différencier titres, sous-titres, métadonnées.
   - Définir un set de variables de spacing (`--space-100/200/300`) et les appliquer à la place des marges en ligne.
   - Séparer les boutons primaires/secondaires/tertiaires avec couleurs et icônes dédiées, plutôt que d’utiliser les styles WordPress par défaut.

2. **Refonte des panneaux et de la navigation**
   - Ajouter une barre latérale persistante ou un dock d’outils avec icônes + labels pour refléter le nombre de modules créatifs disponibles (actuellement accessibles seulement via le menu WP ou la palette). Cela réduit la dépendance au bouton « Retour » du navigateur et rapproche l’expérience d’un SaaS spécialisé.
   - Regrouper chaque panneau (`.ssc-pane`, `.ssc-panel`) en cartes modulaires avec headers accentués, badges de statut et fond différencié pour les zones « danger », afin d’améliorer le scanning visuel.

3. **Améliorer les aperçus et le feedback utilisateur**
   - Remplacer les placeholders génériques par des scénarios réels (ex. un avatar + halo animé pour l’Animation Studio) avec la possibilité de basculer entre plusieurs surfaces (mobile/tablette/desktop).
   - Ajouter des micro-feedbacks : toasts de confirmation après « Copier CSS », skeleton loaders dans les prévisualisations, et états vides explicites quand aucune donnée n’est configurée.

4. **Accessibilité et clarté**
   - Augmenter le contraste texte/fond (objectif AA 4.5:1) et prévoir un toggle clair/sombre accessible globalement plutôt que des surcharges ponctuelles (`.ssc-dark`). 【F:supersede-css-jlg-enhanced/assets/css/admin.css†L61-L72】
   - Associer chaque contrôle d’entrée à un label visuel et un helper text aligné (actuellement certains labels sont en `strong` sans spacing contrôlé). 【F:supersede-css-jlg-enhanced/views/grid-editor.php†L13-L23】

5. **Industrialisation / maintenabilité**
   - Externaliser toutes les règles de style dans les feuilles CSS modulaires (`assets/css/*.css`) et introduire un linting UI (Stylelint) pour prévenir les styles en ligne.
   - Documenter les patterns d’interface (cards, toolbars, formulaires, modales) dans Storybook ou dans un dossier `docs/design-system` afin de converger vers une expérience homogène.

## Feuille de route suggérée
1. Sprint « Foundation » : audit colorimétrique, création de tokens typographiques et de spacing, migration des styles en ligne -> CSS.
2. Sprint « Navigation & Layout » : mise en place d’une shell applicative avec barre latérale + topbar, refonte des panneaux et des feedbacks.
3. Sprint « Preview Experience » : création de prévisualisations contextuelles, réglages rapides (ex. sliders à double poignée) et options responsive.
4. Sprint « Quality & Accessibilité » : contrastes, focus states, raccourcis clavier mis en avant et tests utilisateurs ciblant designers/intégrateurs.
