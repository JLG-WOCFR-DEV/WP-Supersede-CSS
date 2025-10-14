# Supersede CSS JLG (Enhanced)

**Version:** 10.0.7
**Author:** JLG (Enhanced by AI)

Supersede CSS JLG (Enhanced) est une boîte à outils visuelle pour accélérer la création de styles WordPress. Elle combine des éditeurs temps réel, des générateurs de presets et un moteur de tokens pour produire un CSS cohérent sans écrire de code à la main.

## Sommaire

- [Installation](#installation)
- [Fonctionnalités clés](#fonctionnalités-clés)
- [Catalogue des modules](#catalogue-des-modules)
- [Architecture du plugin](#architecture-du-plugin)
- [Commandes npm utiles](#commandes-npm-utiles)
- [Tests](#tests)
- [Commandes WP-CLI](#commandes-wp-cli)
- [Hooks](#hooks)
- [Licence](#licence)
- [Contribuer](#contribuer)
- [Pistes d'amélioration](#pistes-damélioration)

## Installation

1. Assurez-vous que votre site exécute **WordPress 5.8 ou supérieur** et **PHP 8.0+**. Aucune dépendance serveur additionnelle n'est requise.
2. Téléchargez ou clonez ce dépôt.
3. Depuis le dossier `supersede-css-jlg-enhanced`, exécutez `npm install` pour installer les dépendances front-end.
4. Envoyez le dossier `supersede-css-jlg-enhanced` dans `wp-content/plugins/`.
5. Dans l'administration WordPress, ouvrez **Extensions → Supersede CSS JLG (Enhanced)** puis cliquez sur **Activer**.

## Fonctionnalités clés

- **Interface modulaire** – Chaque éditeur vit dans un onglet dédié : effets visuels, tokens, layouts, animations, etc., afin de limiter le contexte à manipuler.
- **CSS mis en cache et assaini** – Le CSS généré est concaténé, filtré et mis en cache à la volée pour le frontal et l’éditeur de blocs, avec invalidation automatique lors des mises à jour du plugin.
- **Bloc Gutenberg « Token Preview »** – Un bloc dédié affiche la bibliothèque de tokens dans Gutenberg en se connectant à l’API REST Supersede.
- **Filtre de capacité** – Ajustez la capacité requise (`manage_options` par défaut) via le hook `ssc_required_capability` pour déléguer l’accès à vos équipes.
- **Tests automatisés** – Playwright valide l’interface du gestionnaire de tokens contre un WordPress de test orchestré par `@wordpress/env` et PHPUnit couvre la couche PHP.
- **Workflow d’approbation et journal d’activité** – Soumettez des demandes d’approbation, exportez les tokens approuvés (JSON, Style Dictionary, Android, iOS) et auditez toutes les actions via les endpoints `ssc/v1/approvals`, `ssc/v1/activity-log` et `ssc/v1/exports`.

## Catalogue des modules

### Tableau de bord
Vue synthétique avec liens rapides vers les zones critiques : éditeur CSS, tokens, Avatar Glow et Debug Center pour gagner du temps en production.

### Utilities (Éditeur CSS responsive)
Éditeur multi-onglets (desktop/tablette/mobile) avec tutoriel `@media`, prévisualisation embarquée, sélecteur visuel 🎯 et toggles responsive pour tester le rendu dans une iframe sandboxée.

> ℹ️ **À propos de l'avertissement "allow-scripts" / "allow-same-origin" dans la console**
>
> Le panneau de prévisualisation utilise une iframe sandboxée afin d'empêcher le contenu chargé d'interagir avec l'administration WordPress. Pour permettre l'injection du surlignage 🎯 et des interactions clavier, nous devons cumuler les flags `allow-scripts` et `allow-same-origin`. Les navigateurs signalent alors que l'iframe peut théoriquement "échapper" au sandbox. Dans notre cas, l'URL préchargée appartient déjà à votre site et l'iframe reste confinée à la zone d'aperçu. L'avertissement est donc attendu et n'indique pas une faille supplémentaire ; veillez simplement à ne pas charger de sites tiers non fiables dans l'aperçu.

### Tokens Manager
Builder visuel de tokens CSS : fiches typées, moteur de recherche, filtres, compteur de résultats, aperçu direct et synchronisation JSON ↔️ CSS pour garder un design system fiable.

### Page Layout Builder
Génère des layouts CSS Grid (Holy Grail, sidebar, dashboard, etc.), explique comment les intégrer dans le bloc Groupe et fournit un aperçu desktop/mobile.

### Scope Builder
Assistant pour cibler rapidement des sélecteurs + pseudo-classes, écrire les propriétés CSS, tester le rendu et appliquer ou copier le snippet obtenu.

### Preset Designer
Crée, liste et applique des presets de styles réutilisables avec champs nom, sélecteur, builder de propriétés et recherche rapide pour un déploiement instantané.

### Visual Effects Studio
Pack d’effets animés : fonds étoilés ou dégradés avec presets enregistrables, ECG avec logo central, effet CRT paramétrable et export CSS/ application directe.

### Tron Grid Animator
Générateur de grilles rétro animées (couleurs, taille, vitesse, épaisseur) avec tutoriel pour multiplier les variations et classes dédiées.

### Avatar Glow Presets
Gestionnaire de halos d’avatars : presets nommés, classes personnalisées, gradients animés, upload d’aperçu et export CSS prêt à appliquer aux rédacteurs.

### Animation Studio
Bibliothèque d’animations (bounce, pulse, fade, slide) ajustable en durée avec aperçu direct et classes utilitaires pour activer les effets.

### Grid Editor
Construit des grilles CSS (colonnes, gap) avec aperçu en direct et export/application instantané sur la classe `.ssc-grid-container`.

### Gradient Editor
Interface pour linéaire/radial/conique, gestion des stops, prévisualisation et copie du CSS généré.

### Shadow Editor
Empileur de couches d’ombre avec aperçu temps réel et actions appliquer/copier pour injecter les ombres complexes sans douleur.

### Filter & Glass Editor
Sliders pour `filter()` (blur, brightness, contrast, etc.), activation du `backdrop-filter` et aperçu glassmorphism sur image personnalisée.

### Clip-Path Generator
Formes prédéfinies (cercle, hexagone, étoile, etc.), réglage de l’aperçu et export du CSS `clip-path` correspondant.

### Fluid Typography
Calculateur `clamp()` : tailles min/max de police et viewport, aperçu interactif et export CSS pour une typographie fluide maîtrisée.

### Import / Export
Transferts JSON ou CSS avec sélection des modules inclus, import assisté et messages d’état pour migration ou sauvegarde.

### CSS Viewer
Affiche les options `ssc_active_css` et `ssc_tokens_css` telles qu’enregistrées en base pour inspection ou debug rapide.

### CSS Performance Analyzer
Mesure la taille brute/gzip, le nombre de règles et les sélecteurs complexes du CSS généré. Fournit des alertes sur les `@import`,
les doublons ou l’usage excessif de `!important`, ainsi que des recommandations d’optimisation pour garder le front rapide. Un journal automatique des snapshots conserve la dernière analyse afin de comparer le build courant (écarts, pourcentages, alertes de dérive) et de suivre la dette CSS au fil des itérations.

### Debug Center
Centre de diagnostic : infos système, health check JSON, zone de danger pour réinitialiser le CSS, export de révisions et filtres par date/utilisateur.

## Architecture du plugin

```
supersede-css-jlg-enhanced/
├── assets/                # JS/SCSS compilés et médias partagés
├── blocks/                # Blocs Gutenberg personnalisés
├── docs/                  # Guides, diagrammes et notes techniques
├── src/                   # Classes PHP du noyau du plugin
├── views/                 # Templates Twig utilisés dans l’admin
├── tests/                 # Tests automatisés (PHPUnit & Playwright)
└── manual-tests/          # Scénarios de QA manuelle documentés
```

Cette cartographie permet de naviguer rapidement dans le projet :

- Les classes de service et d’intégration WordPress résident dans `src/` et suivent une organisation PSR-4.
- Les scripts front-end sont gérés via Vite ; les sources vivent dans `assets/` et sont packagées vers `build/` lors du `npm run build`.
- Les gabarits Twig du back-office sont stockés dans `views/` afin de séparer structure HTML et logique PHP.
- La documentation technique et les RFC expérimentales sont regroupées dans `docs/` pour guider les contributions futures.

## Commandes npm utiles

| Commande | Description |
| --- | --- |
| `npm run dev` | Compile les assets avec Vite en mode développement (HMR et sourcemaps). |
| `npm run build` | Génère les bundles optimisés utilisés en production dans WordPress. |
| `npm run env:start` / `npm run env:stop` / `npm run env:destroy` | Démarre, arrête ou supprime l’instance WordPress de test orchestrée par `@wordpress/env`. |
| `npm run lint` | Analyse le code JavaScript/TypeScript avec ESLint selon la configuration du projet. |
| `npm run format` | Applique Prettier pour harmoniser le style des fichiers front-end. |

## Tests

Exécuter la suite de tests automatisés depuis le dossier du plugin :

```bash
cd supersede-css-jlg-enhanced
composer install
vendor/bin/phpunit
```

> ℹ️ Le fichier `tests/wp-tests-config.php` utilise par défaut l’utilisateur `root@127.0.0.1` avec le mot de passe `root`. Si
> votre instance MySQL locale est configurée différemment, définissez les variables d’environnement `WP_TESTS_DB_USER`,
> `WP_TESTS_DB_PASSWORD` et `WP_TESTS_DB_HOST` avant d’exécuter la suite.

### Tests UI

Un scénario Playwright de bout en bout valide le gestionnaire de tokens réel contre une instance WordPress jetable démarrée avec `@wordpress/env`.

#### Prérequis

- Docker en cours d’exécution (requis par `@wordpress/env`).
- Node.js 18+.

#### Installer les dépendances

```
cd supersede-css-jlg-enhanced
npm install
npx playwright install --with-deps chromium
```

#### Lancer la suite UI

```
npx playwright test
```

La commande démarre automatiquement `wp-env`, exécute les tests, puis arrête et détruit les conteneurs. Vous pouvez aussi gérer l’environnement manuellement avec `npm run env:start`, `npm run env:stop` et `npm run env:destroy`.

## Commandes WP-CLI

Administrez le cache CSS Supersede depuis vos scripts de déploiement ou lors d’une maintenance ponctuelle :

```bash
wp ssc css flush           # Vide le cache inline généré par le plugin
wp ssc css flush --rebuild # Vide puis reconstruit immédiatement le cache assaini
```

L’option `--rebuild` force l’exécution de `ssc_get_cached_css()` après invalidation afin de recalculer un CSS cohérent avec les options `ssc_active_css` et `ssc_tokens_css`.

## Hooks

### `ssc_required_capability`

Filtre la capacité requise pour accéder aux pages d’administration Supersede CSS et aux endpoints REST. Par défaut l’extension utilise `manage_options`, mais vous pouvez déléguer l’accès via un simple filtre :

```php
add_filter('ssc_required_capability', function () {
    return 'edit_theme_options';
});
```

## Licence

Supersede CSS JLG (Enhanced) est distribué sous licence [GPLv2 ou ultérieure](https://www.gnu.org/licenses/gpl-2.0.html).

## Contribuer

Les contributions sont les bienvenues ! Forkez le projet, créez une branche avec votre fonctionnalité ou correction, puis ouvrez une pull request. Pour les changements majeurs, créez d’abord une issue afin d’en discuter.

### Initiatives en cours

Plusieurs chantiers sont décrits dans la documentation afin d’aligner Supersede CSS JLG (Enhanced) sur les standards des studios professionnels :

- **Gouvernance des tokens et journal d’activité** : introduction de métadonnées (`status`, `owner`, `version`), d’un workflow d’approbation et d’exports multi-plateformes. Voir la note détaillée dans [`docs/TOKEN-GOVERNANCE-AND-DEBUG.md`](./supersede-css-jlg-enhanced/docs/TOKEN-GOVERNANCE-AND-DEBUG.md).
- **Refonte UI/UX** : amélioration de la hiérarchie visuelle, des aperçus et de la navigation par panneaux. Synthétisée dans [`docs/UI-UX-IMPROVEMENTS.md`](./supersede-css-jlg-enhanced/docs/UI-UX-IMPROVEMENTS.md).
- **Bibliothèque de presets & Device Lab** : packaging de familles de presets prêts à l’emploi et prévisualisations multi-appareils, décrits dans [`docs/UI-PRESETS.md`](./supersede-css-jlg-enhanced/docs/UI-PRESETS.md) et [`docs/COMPETITIVE-ANALYSIS.md`](./supersede-css-jlg-enhanced/docs/COMPETITIVE-ANALYSIS.md).
- **API REST enrichie** : nouveaux contrôleurs (approbations, exports) et sanitizers partagés pour les futurs workflows. Les choix d’architecture sont consignés dans [`docs/REST-ARCHITECTURE.md`](./supersede-css-jlg-enhanced/docs/REST-ARCHITECTURE.md).

Chaque chantier est découpé en étapes (design, migrations, tests) et sera référencé dans les issues GitHub correspondantes.

## Pistes d'amélioration

Les axes prioritaires détaillés dans [`docs/FUTURE-IMPROVEMENTS.md`](./supersede-css-jlg-enhanced/docs/FUTURE-IMPROVEMENTS.md) servent de socle aux prochaines itérations :

- **Mode « starter site »** : scénarios guidés pour générer la structure CSS complète d’un nouveau site (tokens, presets, grilles) en quelques étapes.
- **Assistant IA contextuel** : panel facultatif exploitant l’API OpenAI pour suggérer des classes ou corriger automatiquement le CSS généré.
- ✅ **Analyse de performance CSS** : le module « CSS Performance Analyzer » identifie la taille livrée, les doublons, propose des recommandations concrètes et permet désormais d’exporter le rapport en Markdown/JSON pour suivre les optimisations dans vos tickets.
- **Marketplace de presets** : import direct de presets partagés par la communauté via une galerie en ligne avec notes et prévisualisations.
- **Export Figma** : connecteur pour synchroniser tokens et styles Supersede avec une bibliothèque de composants Figma et garder design/développement alignés.
- **Mode hors ligne** : empaqueter les dépendances critiques et proposer une synchronisation différée pour travailler en mobilité sans connexion stable.
- **Widgets de monitoring** : graphiques (taille CSS, temps de génération) pour suivre l’évolution de la dette front-end depuis le tableau de bord WordPress.
