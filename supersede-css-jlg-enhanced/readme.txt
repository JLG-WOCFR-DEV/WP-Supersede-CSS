=== Supersede CSS JLG (Enhanced) ===
Stable tag: 10.0.7
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Description: Boîte à outils visuelle pour CSS avec presets, éditeurs live, tokens, et un centre de débogage amélioré.

== Description ==
Cette version a été entièrement refactorisée pour améliorer la stabilité, l'expérience utilisateur et les performances. Elle intègre de nouveaux modules créatifs et simplifie les interfaces complexes.

== Sécurité ==
* Toutes les écritures de CSS passent désormais par `SSC\Support\CssSanitizer` qui retire les balises HTML avec `wp_kses()` avant d'analyser chaque déclaration avec `safe_style_css()` via `safecss_filter_attr()`.
* Les fonctions identifient et neutralisent les protocoles dangereux (`javascript:`, `vbscript:`) à l'aide de `wp_kses_bad_protocol()` tout en conservant les URL légitimes et les valeurs attendues.
* Les propriétés personnalisées (`--var`) et autres déclarations modernes sont sauvegardées après un nettoyage ciblé afin d'éviter de casser des styles valides.
* Les jeux de presets (scope, propriétés) et les effets Avatar Glow sont normalisés (textes, sélecteurs, couleurs, URLs) avant persistance afin d'éviter les injections sans perdre la configuration enregistrée.
* Scénario manuel : ajouter un bloc CSS contenant `background-image: url("data:image/svg+xml;base64,PHN2Zy4uLg==");` puis un autre avec `background-image: url("javascript:alert(1)");`. Après sauvegarde, vérifier que la première propriété est intacte tandis que la seconde est supprimée du CSS généré dans l'interface.

== Changelog ==
= 10.0.7 =
* NEW: Commande `wp ssc css flush` pour vider manuellement le cache CSS généré par le plugin.
* IMPROVEMENT: Option `--rebuild` pour régénérer immédiatement un CSS assaini lors des déploiements automatisés.

= 10.0.6 =
* NEW: Module "CSS Performance Analyzer" pour visualiser le poids total, les doublons de sélecteurs et recevoir des recommandations d’optimisation.
* IMPROVEMENT: Ajout de cartes de métriques et de callouts cohérents avec la nouvelle fondation design.

= 10.0.0 =
* REFONTE MAJEURE : Correction de bugs critiques de namespace, amélioration des contrastes et de l'ergonomie.
* NOUVEAU : Module "Générateur d'Effets Visuels" avec effets CRT, fonds animés (spatial, dégradé) et ECG.
* NOUVEAU : Module "Tron Grid Animator" pour créer des fonds de grille animés.
* AMÉLIORATION : Le module "Avatar Glow" est entièrement visuel avec upload d'image et effet comète.
* AMÉLIORATION : Les modules "Grid Editor" et "Animation Studio" sont désormais visuels et basés sur des presets.
* AMÉLIORATION : Le module "Token Manager" inclut une section éducative et un éditeur visuel.
* NETTOYAGE : Suppression du code obsolète et unification des scripts.
