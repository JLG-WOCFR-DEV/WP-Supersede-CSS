# Procédures de test

Ce dossier regroupe les scénarios manuels (fichiers `*.md`) à rejouer avant une publication majeure. Les scénarios peuvent être exécutés dans l'ordre de votre choix en fonction des changements apportés.

> **À noter** : la refonte Debug Center + gouvernance des tokens a désormais ses endpoints REST (`/approvals`, `/activity-log`, `/exports`). Les scénarios manuels décrivant l’UI seront ajoutés au fil de l’intégration front-end.

## Tests UI automatisés

Deux scénarios Playwright vérifient l'interface d'administration :

- `tests/ui/tokens.spec.js` couvre le gestionnaire de tokens (ajout, édition, suppression et mise à jour du CSS d'aperçu).
- `tests/ui/shell-accessibility.spec.js` vérifie l'accessibilité de l'enveloppe Supersede CSS (menu mobile et palette de commandes).

1. Installer les dépendances JS (à effectuer une seule fois) :
   ```bash
   cd supersede-css-jlg-enhanced
   npm install
   npx playwright install
   # Selon votre distribution, installez aussi les dépendances système :
   npx playwright install-deps
   ```
2. Lancer la suite complète :
   ```bash
   npm run test:ui
   ```

   Pour exécuter uniquement le scénario d'accessibilité du shell :
   ```bash
   npm run test:ui:shell
   ```

Ces tests s'exécutent de manière isolée grâce au moquage des appels REST. Aucun site WordPress n'est requis.

## Tests manuels disponibles

- `advanced-properties.php`
- `import-config.md`
- `property-syntax.php`
- `sanitize-declarations.php`
- `sanitize-imports.php`
- `sanitize-urls.php`
- `uninstall-multisite.md`
- `css-save-network-error.md`
- `command-palette-keyboard.md`
- `token-approval-request.md`

Consultez chaque fichier pour les prérequis et étapes détaillées.

### Scénarios à rédiger

- 🟡 _Exports Style Dictionary_ (vérifier la génération et le téléchargement des archives `GET /ssc/v1/exports`).
- 🟡 _Activity log pagination_ (navigation clavier + lecteurs d’écran sur `GET /ssc/v1/activity-log`).
