# Demande d'approbation d'un token depuis l'UI

## Objectif

Vérifier que l'écran « Gestionnaire de tokens » permet de soumettre une demande d'approbation via l'API REST (`POST /ssc/v1/approvals`) et qu'un badge « Revue en attente » est affiché tant que la décision n'est pas prise.

## Pré-requis

- Être connecté à l'administration WordPress avec un compte disposant de la capability `manage_options` (par défaut les administrateurs).
- Vérifier qu'au moins un utilisateur de revue possède la capability `manage_ssc_approvals` ; sinon, un message « Les demandes d’approbation nécessitent des droits supplémentaires. » est affiché à la place du bouton.
- Avoir au moins un token existant avec un statut `draft` (par exemple `--primary-color`).
- S'assurer qu'aucune modification locale n'est en cours (bouton « Enregistrer les Tokens » inactif ou grisé).

## Étapes

1. Ouvrir **Supersede CSS → Tokens** dans le menu d'administration.
2. Repérer la ligne du token à approuver et vérifier la présence du badge « Statut : Brouillon ».
3. Cliquer sur le bouton « Demander une revue » affiché dans l'entête de la fiche.
4. Renseigner un commentaire facultatif dans la boîte de dialogue (ex. « Prêt pour QA visuelle ») puis valider.
5. Observer le toast de confirmation et la désactivation du bouton.
6. Vérifier que le badge d'état s'est enrichi d'un indicateur « Revue en attente ». Survoler le badge pour afficher le commentaire.
7. (Optionnel) Consulter le journal d'activité ou interroger `GET /ssc/v1/approvals` pour confirmer la création de l'entrée.

## Résultat attendu

- Le bouton est désactivé tant que le token n'est pas enregistré ou qu'une revue est en attente.
- Un toast confirme l'envoi de la demande.
- Un badge « Revue en attente » s'affiche avec le commentaire saisi et la date d'envoi.
- Aucune re-navigation ni rechargement complet de la page n'est nécessaire.

### Notes supplémentaires

- Si une modification locale est présente, la demande doit être bloquée avec un toast rappelant d'enregistrer les changements.
- Une fois la décision prise via `POST /ssc/v1/approvals/{id}`, recharger la page pour vérifier l'évolution des badges (`Revue approuvée` ou `Modifications demandées`).
- Pour une décision « Modifications demandées », renseigner un commentaire est obligatoire ; l'API renvoie un statut `400` si le commentaire est absent.
