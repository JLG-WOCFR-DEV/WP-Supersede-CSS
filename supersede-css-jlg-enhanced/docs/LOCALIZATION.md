# Localisation de l'interface admin

Les chaînes injectées dans `window.SSC.i18n` pilotent l'UX côté JavaScript. Les clés suivantes sont disponibles pour la palette de commandes, le menu mobile et les toasts de presse-papier :

| Clé | Description | Littéral de repli |
| --- | --- | --- |
| `commandPaletteTitle` | Titre affiché dans l'en-tête de la palette de commandes. | `Supersede CSS command palette` |
| `commandPaletteSearchPlaceholder` | Placeholder du champ de recherche. | `Navigate or run an action…` |
| `commandPaletteSearchLabel` | Libellé ARIA du champ de recherche. | `Command palette search` |
| `commandPaletteEmptyState` | Message affiché quand aucune commande n'est disponible. | `Aucun résultat` |
| `commandPaletteResultsAnnouncement` | Modèle de phrase annoncé via `wp.a11y.speak` (utiliser `%d` pour injecter le nombre). | `%d result(s) available.` |
| `mobileMenuShowLabel` | Libellé/ARIA pour ouvrir le menu mobile. | `Afficher le menu` |
| `mobileMenuHideLabel` | Libellé/ARIA pour refermer le menu mobile. | `Masquer le menu` |
| `mobileMenuToggleSrLabel` | Libellé du bouton toggle pour lecteurs d'écran. | `Menu` |
| `clipboardSuccess` | Toast succès affiché après une copie dans le presse-papier. | `Texte copié !` |
| `clipboardError` | Toast erreur si la copie échoue. | `Impossible de copier le texte.` |

> ℹ️ Les traducteurs peuvent fournir une fonction JavaScript pour `commandPaletteResultsAnnouncement`. Elle recevra le nombre de résultats et doit renvoyer la phrase complète à annoncer.
