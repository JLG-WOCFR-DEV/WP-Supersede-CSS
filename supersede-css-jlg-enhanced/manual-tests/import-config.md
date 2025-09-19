# Test manuel – Import d'une configuration Supersede CSS

Ce test permet de vérifier que l'import d'un export JSON restaure correctement les options principales du plugin.

## Pré-requis
- Un site WordPress avec le plugin Supersede CSS activé.
- Un fichier d'export valide obtenu via **Exporter Config (.json)** dans l'interface du plugin.

## Étapes
1. Accédez à l'écran **Supersede CSS → Import / Export** dans l'administration WordPress.
2. Vérifiez que les options (presets, CSS actifs, etc.) sont actuellement différentes de celles contenues dans votre export (facultatif mais recommandé).
3. Dans le bloc **Importer**, sélectionnez votre fichier d'export via le champ « Importer (.json) ».
4. Cliquez sur le bouton **Importer**.
5. Attendez la notification de succès indiquant le nombre d'options mises à jour.

## Résultats attendus
- Un toast et un message sous le bouton confirment l'import réussi avec le nombre d'options restaurées.
- Les options suivantes sont mises à jour d'après l'export : `ssc_active_css`, `ssc_css_desktop`, `ssc_css_tablet`, `ssc_css_mobile`, `ssc_tokens_css`, `ssc_presets`, `ssc_avatar_glow_presets` (si présentes dans le fichier).
- En cas de fichier invalide, un message d'erreur clair est affiché et aucune option n'est modifiée.
