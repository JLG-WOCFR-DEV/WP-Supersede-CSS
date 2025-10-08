# Gouvernance des tokens et workflow d'approbation

Ce document détaille les évolutions à apporter à Supersede CSS JLG (Enhanced) pour aligner la gestion des design tokens et le Debug Center sur les standards professionnels rencontrés dans des outils comme Figma, Design Tokens Studio ou Webflow.

> **Statut (déc. 2024)** : cadrage fonctionnel validé, maquettes en cours. Les migrations de base de données (`wp_ssc_activity_log`, colonnes additionnelles sur `wp_ssc_tokens`) seront proposées dans une PR dédiée avant développement UI.

## 1. Gestionnaire de tokens : métadonnées et contraintes avancées

### 1.1 Nouvelles métadonnées

| Champ | Type | Obligatoire | Description | UI proposée |
| --- | --- | --- | --- | --- |
| `status` | Enum (`draft`, `ready`, `deprecated`) | Oui | Indique l'état de maturité du token. | Badge coloré + filtre dans la liste.
| `owner` | Référence utilisateur WP | Non | Responsabilité fonctionnelle du token. | Sélecteur d'utilisateur + avatar.
| `version` | Chaîne semver | Non | Permet de suivre les évolutions majeures. | Champ texte avec validation `x.y.z`.
| `changelog` | Texte riche | Non | Journal de modifications locales au token. | Zone commentaires avec horodatage automatique.
| `linked_components` | Tableau de slugs | Non | Maintient la traçabilité vers les presets/blocs utilisant le token. | Tags cliquables renvoyant vers les entités liées.

#### Règles métier
- Transition automatique `draft → ready` lorsque le token est approuvé (voir §2).
- Empêcher la suppression d'un token `deprecated` tant qu'il reste référencé dans un preset.
- Historiser les changements de `owner` et `version` dans le journal global (§2.1).

### 1.2 Contraintes de validation

| Type de token | Contrainte | Message d'erreur |
| --- | --- | --- |
| Couleur (`color`) | Palette verrouillée (choix limité aux palettes prédéfinies). | "Sélectionnez une teinte parmi la palette {palette_name}." |
| Taille (`spacing`, `font-size`) | Intervalle numérique (`min`, `max`, `step`) défini par groupe. | "La valeur doit être comprise entre {min} et {max} avec un pas de {step}." |
| Ombre (`shadow`) | Ensemble de presets immuables ; duplication requise pour modifier. | "Les ombres officielles ne sont pas éditables. Dupliquez pour personnaliser." |
| Typographie (`font-family`) | Liste blanche de familles validées. | "Choisissez une famille parmi la bibliothèque approuvée." |

#### Implémentation
- Étendre le schéma REST `ssc/v1/token` avec les nouveaux attributs et règles de validation.
- Ajouter un écran de configuration d'équipe pour définir palettes, intervalles et listes blanches.
- Exposer les erreurs via l'UI de formulaire (notifications et inline error states).

### 1.3 Workflow d'édition

1. Création en statut `draft` avec `owner` par défaut sur l'auteur.
2. Passage en `ready` uniquement après revue via le Debug Center (§2.2).
3. Possibilité de marquer `deprecated` pour signaler un retrait prochain.
4. Les versions suivent SemVer : `major` pour rupture, `minor` pour amélioration compatible, `patch` pour correction.

### 1.4 API et export
- Inclure métadonnées et contraintes dans les exports JSON/CSS existants.
- Ajouter des endpoints pour récupérer les palettes et intervalles afin d'alimenter des outils externes (Design Tokens CLI, Style Dictionary).

## 2. Debug Center : journal d'activité et approbations

### 2.1 Journal horodaté global

| Événement | Détails | Déclencheur |
| --- | --- | --- |
| `token.created` | auteur, statut initial, version | Création de token via UI ou API |
| `token.updated` | diff des champs, ancienne/nouvelle valeur | Sauvegarde depuis UI/API |
| `token.approved` | approbateur, commentaire | Action d'approbation (§2.2) |
| `token.deprecated` | auteur, date cible de retrait | Marquage obsolète |
| `css.published` | snapshot ID, hash | Publication CSS depuis Debug Center |
| `preset.changed` | entité liée, tokens impactés | Modification de preset/bloc |

#### Exigences techniques
- Stocker les événements dans une table dédiée (`wp_ssc_activity_log`) avec index sur `created_at` et `entity_id`.
- Fournir un filtre temporel (24h, 7j, 30j) et par type d'événement.
- Permettre l'export CSV/JSON du journal pour audit.

### 2.2 Workflow d'approbation

1. **Demande** : l'auteur d'un token `draft` soumet une requête d'approbation depuis la sidebar du Debug Center.
2. **Revue** : les utilisateurs disposant de la capability `manage_ssc_approvals` reçoivent une notification (email + centre de notifications WP).
3. **Commentaire bloquant** : l'approbateur peut ajouter un commentaire requis (`blocking_comment`) qui renvoie le token en `draft`.
4. **Approbation** : validation du token → statut `ready`, entrée `token.approved` dans le journal, déclenchement optionnel d'une pipeline (webhook).
5. **Publication CSS** : lors de la publication, le snapshot consigne la liste des tokens `ready` inclus.

#### Interface
- Onglet "Approbations" dans le Debug Center listant les tokens en attente avec badge de priorité.
- Modal de revue affichant historique, métadonnées, commentaires précédents.
- Boutons "Approuver" / "Demander des changements" (avec champ commentaire obligatoire pour le second).

### 2.3 Exports multi-plateformes

| Format | Contenu | Cas d'usage |
| --- | --- | --- |
| Style Dictionary | JSON hiérarchique incluant métadonnées `status`, `version`. | Synchronisation avec pipelines front-end.
| Android | `colors.xml`, `dimens.xml`, thèmes Material 3 alignés sur tokens `ready`. | Design system mobile natif.
| iOS | `xcassets` et fichiers SwiftGen générés depuis tokens approuvés. | Applications iOS/iPadOS.
| Figma Tokens | JSON compatible plugin Tokens Studio, incluant mapping palettes. | Collaboration avec designers.

#### Déclenchement
- Bouton "Exporter" dans le Debug Center avec options de format et portée (`ready`, `deprecated` inclus ou non).
- Webhook REST `ssc/v1/exports` permettant aux CI de récupérer la dernière version approuvée.
- Historisation des exports réalisés dans le journal (`export.generated`).

## 3. Sécurité et performances

- Respecter les capabilities WordPress pour limiter l'accès aux métadonnées sensibles (ex : seuls les administrateurs peuvent modifier `owner`).
- Mettre en place un système de pagination/chargement différé pour le journal afin de ne pas alourdir le Debug Center.
- S'assurer que les exports massifs s'exécutent en tâche asynchrone (WP-Cron ou Action Scheduler) afin de ne pas bloquer l'interface.

## 4. Prochaines étapes

1. Concevoir le schéma de base de données et les migrations nécessaires.
2. Prototyper l'UI (Figma) pour valider la hiérarchie d'information.
3. Implémenter les endpoints REST et tests PHPUnit correspondants.
4. Mettre en place le workflow d'approbation et la génération d'exports.
5. Ouvrir un pilote interne avec un jeu de tokens réel pour valider la gouvernance.

Ces améliorations renforceront la traçabilité, la collaboration et la conformité des design tokens gérés dans Supersede CSS JLG (Enhanced), tout en offrant une visibilité complète sur les changements via le Debug Center.

### Prochain jalon

- ✅ RFC validée côté produit et tech.
- 🛠️ À faire : implémenter les migrations + contrôleurs REST (`ApprovalsController`, `ActivityLogController`, `ExportsController`).
- 🧪 À planifier : tests d’acceptation Playwright couvrant le workflow d’approbation et les exports multi-plateformes.
