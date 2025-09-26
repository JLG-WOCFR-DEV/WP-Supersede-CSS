# Procédures de test

Ce dossier regroupe les scénarios manuels (fichiers `*.md`) à rejouer avant une publication majeure. Les scénarios peuvent être exécutés dans l'ordre de votre choix en fonction des changements apportés.

## Test UI automatisé

Un test Playwright couvre désormais le gestionnaire de tokens (ajout, édition, suppression et mise à jour du CSS d'aperçu).

1. Installer les dépendances JS (à effectuer une seule fois) :
   ```bash
   cd supersede-css-jlg-enhanced
   npm install
   npx playwright install
   # Selon votre distribution, installez aussi les dépendances système :
   npx playwright install-deps
   ```
2. Lancer le test UI :
   ```bash
   npm run test:ui
   ```

Le test s'exécute de manière isolée grâce au moquage des appels REST. Aucun site WordPress n'est requis.

## Tests manuels disponibles

- `advanced-properties.php`
- `import-config.md`
- `property-syntax.php`
- `sanitize-declarations.php`
- `sanitize-imports.php`
- `sanitize-urls.php`
- `uninstall-multisite.md`

Consultez chaque fichier pour les prérequis et étapes détaillées.
