# Analyse UI/UX et Pistes d'Amélioration

> **Journal de bord (déc. 2024)** : ateliers UX terminés, wireframes basse fidélité prêts. Phase suivante : prototypage interactif pour la nouvelle navigation et tests utilisateurs ciblés (janvier 2025).

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

### Kanban synthétique

- 🟢 **Doing** : prototypage Figma de la barre latérale dockable + définition des tokens de typographie.
- 🟡 **Next** : audit accessibilité (contrastes, focus) + recherche utilisateur sur les aperçus.
- ⚪️ **Later** : mode offline et personnalisation avancée des layouts.

## Inspirations d’applications professionnelles
Ces pistes prolongent l’ambition « studio » en s’inspirant des workflows d’outils comme Figma, Webflow ou LottieFiles.

1. **Animation timeline & calques dédiés**
   - L’Animation Studio se limite aujourd’hui à un preset unique et à un slider de durée sans visualisation temporelle ou gestion de multiples propriétés (délai, easing, iterations). 【F:supersede-css-jlg-enhanced/views/animation-studio.php†L11-L28】
   - Introduire une timeline avec scrubbing, calques imbriqués et gestion des keyframes (position, opacité, transforms), afin de se rapprocher d’un éditeur façon After Effects et de réduire les allers-retours vers des outils externes.
   - Prévoir une architecture en deux panneaux : à gauche la liste des calques et propriétés animables (transform, filters, custom CSS) ; au centre la timeline avec zoom horizontal, curseur de lecture et aimantation des keyframes ; à droite un inspecteur contextuel pour ajuster easing, delays et déclencheurs.
   - Ajouter un mode « preview loop » avec controls (lecture, pause, vitesse), une comparaison avant/après sur hover et la possibilité d’exporter une capture vidéo/GIF rapide pour partage Slack.
   - Intégrer une gestion d’états (hover, focus, scroll into view) afin d’aligner la timeline sur les interactions multi-triggers utilisées par Webflow ou Framer.

2. **Bibliothèque de presets intelligents**
   - Le dropdown statique des presets empêche de sauvegarder ou partager des combinaisons personnalisées, contrairement aux bibliothèques cloud de LottieFiles. 【F:supersede-css-jlg-enhanced/views/animation-studio.php†L12-L24】
   - Mettre en place un système de presets taggés (marketing, micro-interactions, accessibilité) avec recherche, favoris et suggestions basées sur l’historique du site.
   - Ajouter une grille visuelle de presets avec preview animée au survol, informations de performance (poids CSS, compatibilité navigateurs) et possibilité de dupliquer un preset pour le personnaliser.
   - Synchroniser ces presets avec un espace d’équipe (export/import JSON ou hub SaaS) pour accélérer l’adoption côté agences et designers, et exposer une API REST pour l’intégration continue.
   - Installer un mécanisme de recommandation : scoring automatique en fonction des composants déjà utilisés sur le site, suggestions de presets complémentaires et alertes sur les combinaisons à risque (trop de propriétés animées simultanément, par exemple).

3. **Canvas d’animation contextualisé**
   - L’aperçu actuel reste un simple carré neutre, sans aucun décor ou variation de surface. 【F:supersede-css-jlg-enhanced/views/animation-studio.php†L31-L34】
   - Offrir des scènes préremplies (card produit, avatar, CTA) et la possibilité de switcher entre breakpoints (mobile/tablette/desktop) comme dans Framer augmente la projection et aide à détecter les artefacts visuels.
   - Ajouter une librairie de devices (iPhone, tablette, desktop, mailer) et des overlays (grilles 8pt, safe areas, lignes de pliage pour email) activables via toggles pour tester la lisibilité réelle.
   - Préciser un mode « inspecteur de rendu » qui affiche les valeurs CSS résultantes, l’ordre des animations et un heatmap des performances (durées d’exécution, warnings Core Web Vitals) pour orienter les décisions.
   - Prévoir des réglages de fond avancés : gradients multi-stops, import d’images, vidéos, ou captures de page WordPress existante pour prévisualiser l’animation dans son contexte final.

4. **Manipulation directe de la grille**
   - L’éditeur de grid repose sur deux sliders (colonnes, gap) et un bouton d’application ; les manipulations se font donc via des formulaires plutôt qu’en drag & drop. 【F:supersede-css-jlg-enhanced/views/grid-editor.php†L13-L36】
   - Ajouter un canvas interactif où l’on peut tracer/dupliquer/supprimer des tracks, fusionner des cellules ou appliquer des areas nommées directement à la souris, à la manière du designer de Webflow ou Builder.io.
   - Compléter le canvas par une mini-carte des breakpoints : vue synchrone des layouts Desktop/Tablet/Mobile, possibilité de verrouiller des zones et d’hériter/surcharger les paramètres par breakpoint.
   - Prévoir des templates professionnels (hero, pricing, blog layout) avec aperçu responsive instantané et annotations sur les breakpoints.
   - Ajouter des aides contextuelles : alignement magnétique, mesure en pixels/rem/%, estimation automatique des ratios d’image et suggestions d’espacement optimisées pour la typographie choisie.

5. **Panneaux modulaires et modes de focus**
   - L’interface utilise une grille à deux colonnes rigide (`.ssc-two`) sans dock modulable, ce qui limite la personnalisation de l’espace de travail par rôle (designer vs. intégrateur). 【F:supersede-css-jlg-enhanced/assets/css/admin.css†L81-L101】
   - Introduire un système de panneaux dockables/collapsables, un mode focus plein écran pour les étapes complexes et des raccourcis clavier (⌘B pour basculer la bibliothèque, ⇧+E pour exporter) sur le modèle des outils professionnels.
   - Offrir une gestion de layouts mémorisés : l’utilisateur choisit un preset « Designer », « Intégrateur », « QA » avec panneaux prédéfinis, ou sauvegarde son arrangement personnalisé synchronisé via user meta.
   - Ajouter une barre de statut en bas de l’écran avec logs en temps réel (dernière sauvegarde, erreurs de lint, recommandations d’accessibilité) pour sécuriser les workflows intensifs.
   - Implémenter un centre de notifications contextualisé (succès, erreurs, suggestions) avec historique et possibilité d’assigner des actions (ex. « Ajouter une version responsive ») pour rapprocher l’outil d’une suite collaborative moderne.

6. **Organisation des modes Simple / Expert**
   - Les réglages clés (onglets multi-device, viewport custom, picker, sliders) sont concentrés dans une seule vue, sans segmentation progressive : on passe directement du formulaire principal à des fonctions avancées dans la même colonne. 【F:supersede-css-jlg-enhanced/views/utilities.php†L11-L145】
   - S’inspirer de Webflow et Figma qui proposent un mode « Quick actions » (propriétés essentielles visibles) et un panneau « Advanced » repliable : exposer par défaut un mode Simple (onglet Desktop + sauvegarde + presets) et masquer les réglages experts derrière un toggle global.
   - Prévoir un chemin de montée en compétence : tooltip « Passer en mode Expert » avec aperçu des gains (breakpoints multiples, picker, viewport custom). Un badge « Beta » ou « Pro » permet d’aligner l’image du produit sur les standards SaaS premium.
   - Côté IA et assistants, proposer un panneau « Suggestions instantanées » dans le mode simple (ex. trois variantes générées automatiquement) et réserver les règles personnalisées, breakpoints arbitraires et scripts de build au mode Expert.
   - Garder la parité clavier/lecteur d’écran : le toggle Simple ↔ Expert doit être un vrai bouton `<button role="switch">` avec annonce ARIA, et mémoriser le choix utilisateur côté `user_meta` pour éviter un retour en Simple à chaque chargement.

7. **Accessibilité et lecture avancée**
   - Bien que les onglets soient déjà accessibles via `role="tab"` et navigation clavier, le focus est parfois perdu lors du rafraîchissement de CodeMirror, ce qui crée une rupture avec les lecteurs d’écran. 【F:supersede-css-jlg-enhanced/assets/js/utilities.js†L54-L108】
   - Implémenter une gestion de focus persistante à la façon de Notion ou Linear : après changement d’onglet, replacer manuellement le focus dans l’éditeur et annoncer la ligne/colonne active via `aria-live`.
   - Ajouter des tailles de police adaptatives (slider zoom ou raccourcis ⌘+ / ⌘-) comme le proposent VS Code et Webflow pour réduire la fatigue visuelle sur des sessions longues.
   - Prévoir un mode contraste élevé et un thème clair/dark synchronisé avec `prefers-color-scheme`, plus des thèmes d’éditeur (clair, sombre, solaire) afin d’aligner l’outil avec les attentes professionnelles.
   - Étendre le tutoriel `@media` en version audio/texte simplifié, téléchargeable, afin d’accompagner les profils non experts et renforcer la montée en compétence sans quitter l’écran principal.

8. **Fiabilité et résilience produit**
   - L’enregistrement repose sur une requête Ajax unique, avec fallback alert/console si `window.sscToast` n’est pas chargé, sans réessai ni file d’attente. 【F:supersede-css-jlg-enhanced/assets/js/utilities.js†L20-L90】【F:supersede-css-jlg-enhanced/assets/js/utilities.js†L208-L283】
   - Mettre en place une file d’opérations inspirée de Notion : chaque sauvegarde est placée dans une queue, réessayée automatiquement (exponentiel backoff) et journalisée dans un panneau « Activité ». Afficher un état « Draft non synchronisé » pour renforcer la confiance.
   - Ajouter un mode hors-ligne : stocker le CSS dans IndexedDB et afficher un badge « Hors connexion » avec compteur de diff, puis resynchroniser à la reconnexion comme le fait Figma.
   - Diversifier les notifications : toasts pour les succès, bandeau sticky en cas de panne prolongée (API REST 500) et e-mails d’alerte pour les sites multi-utilisateurs. Utiliser `wp.a11y.speak` pour rendre les erreurs vocalisées par les lecteurs d’écran.
   - Documenter ces scénarios dans les tests end-to-end (Playwright) en plus des tests manuels existants sur les erreurs réseau pour augmenter la couverture de fiabilité. 【F:supersede-css-jlg-enhanced/manual-tests/css-save-network-error.md†L1-L25】
