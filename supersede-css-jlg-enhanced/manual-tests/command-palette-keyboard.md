# Palette de commandes — navigation clavier

## Pré-requis
- Environnement d'administration WordPress avec le plugin Supersede CSS activé.
- Un utilisateur administrateur connecté.

## Étapes
1. Ouvrir l'administration WordPress et se rendre sur la page Supersede CSS (`/wp-admin/admin.php?page=supersede-css-jlg`).
2. Ouvrir la palette de commandes en utilisant le bouton "Palette" (ou le raccourci ⌘K / Ctrl+K).
3. Vérifier que le champ de recherche est focalisé et que la première option de la liste est visuellement mise en évidence.
4. Appuyer sur `ArrowDown`.
   - La sélection active doit passer à l'option suivante.
   - L'attribut `aria-selected` doit être `true` sur cette option et `false` sur les autres.
   - L'attribut `aria-activedescendant` de la liste (`#ssc-cmdp-results`) doit correspondre à l'identifiant de l'option active.
5. Appuyer sur `ArrowUp`.
   - La première option doit redevenir active.
6. Appuyer sur `End` puis sur `Home`.
   - `End` doit sélectionner la dernière option de la liste.
   - `Home` doit ramener la sélection sur la première option.
7. Appuyer sur `Enter`.
   - L'action associée à l'option active doit être exécutée (navigation vers un lien ou déclenchement d'une action), puis la palette doit se fermer.

## Résultats attendus
- Le focus clavier reste sur le champ de recherche pendant toute la navigation.
- Les options disposent d'identifiants uniques et exposent correctement `aria-selected`.
- L'attribut `aria-activedescendant` reflète toujours l'identifiant de l'option active.
- La mise en évidence visuelle suit l'option active sans interférer avec la navigation tabulaire existante.
