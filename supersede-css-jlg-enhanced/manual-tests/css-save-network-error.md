# Échec réseau lors de l'enregistrement du CSS

## Objectif

Vérifier qu'une erreur réseau lors de l'appel à `ssc/v1/save-css` affiche une notification d'échec lisible et que le bouton « Enregistrer le CSS » redevient utilisable.

## Pré-requis

- Être connecté à l'interface d'administration WordPress.
- Accéder à la page « Supersede CSS » contenant les onglets Desktop/Tablet/Mobile.

## Étapes

1. Ouvrir les outils de développement du navigateur et activer le mode « Hors connexion » (ou utiliser l'onglet Network pour bloquer les requêtes).
2. Cliquer sur « Enregistrer le CSS ».
3. Observer l'apparition d'un toast d'erreur décrivant l'échec réseau.
4. Revenir en mode connecté et vérifier que le bouton « Enregistrer le CSS » est de nouveau cliquable (son état `disabled` est retiré).

## Résultat attendu

- Une notification (toast) affichant un message d'erreur explicite s'affiche après l'échec.
- Le bouton « Enregistrer le CSS » est réactivé automatiquement après l'affichage de l'erreur.
