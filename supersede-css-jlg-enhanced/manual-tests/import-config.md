# Test manuel : restauration d'une configuration exportée

Objectif : vérifier que le flux « Exporter → Importer » permet de restaurer une configuration complète sans erreur.

## Pré-requis

- Un site WordPress avec le plugin Supersede CSS activé.
- Un compte disposant du droit `manage_options`.

## Étapes

1. **Préparer une configuration d'exemple**
   1. Ouvrir l'écran Supersede CSS et saisir un CSS simple (ex. `body { background: #f5f5f5; }`).
   2. Créer au moins un preset et un token pour disposer de données visibles dans l'export.
2. **Exporter la configuration**
   1. Aller dans l’onglet « Import / Export ».
   2. Cliquer sur **Exporter Config (.json)** et enregistrer le fichier généré.
3. **Modifier les options existantes**
   1. Supprimer manuellement le CSS/preset ajouté lors de l’étape 1 (ex. via l’interface Supersede ou en réinitialisant les sections concernées).
   2. Vérifier que le site ne contient plus les éléments personnalisés.
4. **Importer le fichier exporté**
   1. Toujours dans l’onglet « Import / Export », sélectionner le fichier JSON précédemment exporté.
   2. Cliquer sur **Importer** et s’assurer que le toast « Configuration importée ! » et le message récapitulatif apparaissent.
   3. Ouvrir les outils de développement et vérifier que le conteneur `#ssc-toasts` reste présent avec `role="status"` et l’attribut `aria-live` correspondant, et que chaque toast ajouté possède `role="alert"` (ou un rôle équivalent selon la criticité).
5. **Valider la restauration**
   1. Revenir sur l’éditeur principal et vérifier que le CSS, les presets et les autres réglages sont de nouveau présents.
   2. En cas d’échec, relever le message affiché dans `#ssc-import-msg` ainsi que la réponse REST (onglet Réseau du navigateur) pour faciliter le diagnostic.

## Résultat attendu

- Le message de succès indique le nombre d’options restaurées et, le cas échéant, celles ignorées.
- Les styles et presets supprimés à l’étape 3 sont de nouveau disponibles après l’import.
