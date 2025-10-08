# Revue de code — 21 novembre 2024

## Points forts
- Le pipeline de `CssSanitizer` supprime systématiquement les balises HTML, neutralise les `@import`/URLs risqués et reconstruit les blocs de manière sûre avant de fournir le CSS nettoyé, ce qui réduit considérablement la surface d’attaque côté éditeur et frontend.【F:src/Support/CssSanitizer.php†L13-L119】
- Les contrôleurs REST héritent d’une autorisation partagée qui vérifie nonce, authentification hors cookies et capacité requise, ce qui homogénéise la sécurité des endpoints personnalisés du plugin.【F:src/Infra/Rest/BaseController.php†L13-L67】
- La gestion des révisions CSS nettoie chaque entrée, synchronise les segments (desktop/tablette/mobile) et invalide le cache automatiquement, apportant une piste d’audit utile pour les équipes éditoriales.【F:src/Support/CssRevisions.php†L20-L83】【F:src/Support/CssRevisions.php†L118-L171】

## Axes d’amélioration proposés
- Les libellés par défaut de l’éditeur de tokens côté JavaScript sont codés en dur en français ; s’appuyer sur la fonction `translate()` (alimentée par `wp_localize_script`) pour ces chaînes permettrait d’exposer facilement des traductions ou du contenu sur-mesure.【F:assets/js/tokens.js†L5-L113】
- Le nombre maximum de révisions était figé à 20 ; l’introduction d’un filtre pour ajuster cette limite selon la volumétrie du site (gros médias vs petits sites) sécurise mieux les besoins de rétention ou de conformité.【F:src/Support/CssRevisions.php†L44-L67】
- Les tests d’intégration autour des révisions gagnent à valider le comportement lorsque la limite est filtrée, pour éviter toute régression dans les environnements qui personnalisent la valeur par défaut.【F:tests/Support/CssRevisionsTest.php†L303-L331】

### Suivi post-revue

- 🟢 `ssc/css-revisions` : filtre `ssc_max_css_revisions` intégré (PR #142 en production).
- 🟢 Internationalisation des libellés JS : localisation implémentée dans `assets/js/tokens.js` via `wp_localize_script` et `wp.i18n` (PR #214).
- ⚪️ Tests d’intégration supplémentaires : en attente de la migration PHPUnit 10 pour disposer des data providers typés (planifié Q1 2025).
