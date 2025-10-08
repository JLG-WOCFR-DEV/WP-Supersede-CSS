# Organisation des endpoints REST

Ce plugin expose ses endpoints via une série de contrôleurs spécialisés situés dans `src/Infra/Rest`. Chaque contrôleur est responsable d'un domaine fonctionnel précis :

- `CssController` : sauvegarde du CSS actif, gestion des révisions et réinitialisation des options.
- `TokensController` : lecture et écriture des Design Tokens.
- `PresetsController` : gestion des presets et des presets Avatar Glow.
- `ImportExportController` : flux d'import/export de configuration et d'assets CSS.
- `LogsController` : nettoyage du journal interne.
- `SystemController` : route de diagnostic/health-check qui vérifie l'état des assets, les versions et l'intégrité des composants critiques (autoload des classes principales, fonctions de cache CSS, statut du registre de tokens).

Les contrôleurs implémentent l'interface `ControllerInterface` et héritent de `BaseController`, qui centralise la logique d'autorisation REST (nonce, authentification alternative et contrôle de capacité).

L'enregistrement des routes s'effectue via `SSC\Infra\Routes`. À l'initialisation (`Routes::register()`), un service de sanitisation (`SSC\Infra\Import\Sanitizer`) est instancié puis partagé entre les contrôleurs qui en ont besoin (`CssController` et `ImportExportController`). Chaque contrôleur expose ensuite ses routes au moment du hook `rest_api_init`.

## Évolutions planifiées (roadmap)

La prochaine phase de développement introduira des contrôleurs supplémentaires afin de supporter les workflows décrits dans la note [_Gouvernance des tokens et workflow d’approbation_](./TOKEN-GOVERNANCE-AND-DEBUG.md) :

- `ApprovalsController` : reçoit les demandes d’approbation de tokens, valide les capacités `manage_ssc_approvals` et orchestre la transition des statuts `draft → ready`.
- `ActivityLogController` : expose un journal paginé (`wp_ssc_activity_log`) avec filtres temporels et export CSV/JSON.
- `ExportsController` : gère les exports multi-plateformes (Style Dictionary, Android, iOS) et déclenche des tâches asynchrones via Action Scheduler.

Ces services partageront une couche commune `EventRecorder` responsable de persister les événements et d’émettre des webhooks sortants. Le schéma suivant synthétise les dépendances prévues :

```mermaid
graph TD
    Routes --> ApprovalsController
    Routes --> ActivityLogController
    Routes --> ExportsController
    ApprovalsController --> EventRecorder
    ActivityLogController --> EventRecorder
    ExportsController --> EventRecorder
    EventRecorder --> Sanitizer
```

> 📌 **Statut** : la conception des modèles de données et migrations est en cours. Une RFC pour l’API des exports sera partagée avant implémentation.

## Sanitizer d'import

Le service `SSC\Infra\Import\Sanitizer` contient l'ensemble des helpers de nettoyage utilisés lors des imports (normalisation des tableaux JSON, vérification des doublons, combinaison des variantes responsives, etc.). Les méthodes `sanitizeImport*` sont réutilisées par les contrôleurs qui acceptent des payloads JSON et garantissent une validation homogène des données entrantes.

## Diagramme de dépendances simplifié

```mermaid
graph TD
    Routes --> CssController
    Routes --> TokensController
    Routes --> PresetsController
    Routes --> ImportExportController
    Routes --> LogsController
    Routes --> SystemController
    CssController --> Sanitizer
    ImportExportController --> Sanitizer
```

Ce découpage facilite l'ajout de nouveaux endpoints (création d'un contrôleur dédié) et permet le partage explicite des composants transverses comme le sanitiseur d'import.
