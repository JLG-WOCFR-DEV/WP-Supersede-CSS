# Test manuel : désinstallation complète en multisite

Objectif : vérifier qu'une suppression du plugin en environnement multisite élimine toutes les options `ssc_*` sur l'ensemble du réseau.

## Pré-requis

- Un réseau WordPress multisite (au moins deux sites) avec Supersede CSS activé sur chaque site.
- Accès administrateur au réseau et, idéalement, à WP-CLI.

## Étapes

1. **Générer des données sur plusieurs sites**
   1. Depuis l'admin du site principal, ouvrir Supersede CSS et ajouter un CSS personnalisé (ex. `body { background: #efe; }`).
   2. Répéter l'opération sur au moins un site secondaire (via « Mes sites > [Nom du site] > Tableau de bord »).
2. **Confirmer la présence des options avant la désinstallation**
   1. Dans un terminal, exécuter `wp site list --fields=blog_id` afin d'obtenir la liste des sites.
   2. Pour chaque `blog_id`, exécuter `wp option list --url=<url-du-site> --search='ssc_%'` et vérifier qu'au moins une option est retournée.
3. **Supprimer le plugin**
   1. Aller dans « Mes sites > Admin réseau > Extensions ».
   2. Désactiver Supersede CSS puis cliquer sur **Supprimer** afin de lancer le processus de désinstallation.
4. **Valider le nettoyage des options**
   1. Réexécuter la commande `wp site list --fields=blog_id`.
   2. Pour chaque site, exécuter à nouveau `wp option list --url=<url-du-site> --search='ssc_%'`.
   3. Vérifier qu'aucune ligne n'est retournée.

## Résultat attendu

- Plus aucune option commençant par `ssc_` n'est présente dans les tables `wp_options` (ou équivalentes) de chacun des sites du réseau après la suppression du plugin.
- Aucune option réseau `ssc_*` n'apparaît dans `wp_sitemeta` (`wp site meta list --search='ssc_%'`).
