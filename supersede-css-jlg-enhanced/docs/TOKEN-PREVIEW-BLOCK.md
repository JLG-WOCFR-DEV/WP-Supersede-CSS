# Bloc « Supersede Token Preview »

Le bloc *Supersede › Token Preview* permet d'afficher automatiquement la bibliothèque de tokens CSS gérée par Supersede directement depuis l'éditeur WordPress.

> **Évolution prévue** : le bloc passera en version 2 avec filtres par statut (`draft`, `ready`, `deprecated`) et pagination. L’API REST exposera également les métadonnées `owner` et `version` décrites dans la note de gouvernance.

## Installation dans une page ou un article

1. Ouvrez l'éditeur de blocs (Gutenberg).
2. Cliquez sur « Ajouter un bloc » puis cherchez « Supersede ».
3. Sélectionnez le bloc **Supersede › Token Preview**.
4. Publiez ou mettez à jour le contenu.

> 💡 Astuce : combinez ce bloc avec un paragraphe explicatif pour guider les rédacteurs sur la manière d'utiliser les tokens.

## Fonctionnement

- Le bloc appelle l'API REST Supersede (`/ssc/v1/tokens`) pour récupérer la liste des tokens et le CSS compilé.
- Les styles sont injectés via la fonction PHP `ssc_get_cached_css()` afin que le rendu soit identique dans l'éditeur et sur le frontal.
- Chaque token est présenté avec sa valeur, sa description et, pour les couleurs, un échantillon visuel.

## Pourquoi l'utiliser ?

- Plus besoin de copier/coller des classes : les tokens sont visualisés et documentés directement dans la page.
- Les équipes éditoriales disposent d'un catalogue à jour, synchronisé avec les presets Supersede.
- La mise en page reste fidèle au thème grâce au CSS partagé entre l'admin et le site public.

## Améliorations planifiées

- Ajouter un panneau latéral Gutenberg permettant de choisir le tri (par catégorie, statut, owner).
- Supporter la sélection d’un preset (`data-ssc-preset`) pour prévisualiser les tokens dans un contexte spécifique.
- Exposer un mode « compact » (tableau) vs « visuel » (cartes) pour s’adapter aux guides de style.
