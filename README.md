# Supersede CSS JLG (Enhanced)

**Version:** 10.0.5  
**Author:** JLG (Enhanced by AI)

Supersede CSS JLG (Enhanced) est une boîte à outils visuelle pour accélérer la création de styles WordPress. Elle combine des éditeurs temps réel, des générateurs de presets et un moteur de tokens pour produire un CSS cohérent sans écrire de code à la main.

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

## Catalogue des modules

### Tableau de bord
Vue synthétique avec liens rapides vers les zones critiques : éditeur CSS, tokens, Avatar Glow et Debug Center pour gagner du temps en production.

### Utilities (Éditeur CSS responsive)
Éditeur multi-onglets (desktop/tablette/mobile) avec tutoriel `@media`, prévisualisation embarquée, sélecteur visuel 🎯 et toggles responsive pour tester le rendu dans une iframe sandboxée.

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

### Debug Center
Centre de diagnostic : infos système, health check JSON, zone de danger pour réinitialiser le CSS, export de révisions et filtres par date/utilisateur.

## Tests

Exécuter la suite de tests automatisés depuis le dossier du plugin :

```bash
cd supersede-css-jlg-enhanced
composer install
vendor/bin/phpunit
```

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

## Pistes d'amélioration

- **Mode « starter site »** : proposer des scénarios guidés pour générer la structure CSS complète d’un nouveau site (tokens, presets, grilles) en quelques étapes.
- **Assistant IA contextuel** : intégrer un panel facultatif exploitant l’API OpenAI pour suggérer des classes ou corriger automatiquement le CSS généré.
- **Analyse de performance CSS** : ajouter un module qui mesure la taille et la couverture du CSS produit, avec suggestions de réduction et alertes sur les sélecteurs orphelins.
- **Marketplace de presets** : permettre l’import direct de presets partagés par la communauté via une galerie en ligne avec notes et prévisualisations.
- **Export Figma** : fournir un connecteur pour synchroniser tokens et styles Supersede avec une bibliothèque de composants Figma, afin de garder design et développement alignés.
