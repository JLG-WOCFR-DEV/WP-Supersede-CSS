# Supersede CSS — Scénarios d'erreur REST critiques

Ce document centralise les scénarios d'erreur à vérifier pour les points d'entrée REST utilisés par le Centre de débogage. Les cas listés ci-dessous sont couverts par des tests automatisés (`tests/Infra/Rest/CssControllerTest.php`) et servent de base pour les revues manuelles.

## `POST /ssc/v1/save-css`

| Situation | Résultat attendu | Notes |
| --- | --- | --- |
| `css`, `css_desktop`, `css_tablet` ou `css_mobile` n'est pas une chaîne | `400` – `{"ok":false,"message":"Invalid CSS segment."}` | Le corps est validé avant toute écriture. |
| Option inconnue (paramètre `option_name` invalide) | `400` – erreur contextualisée | Les hooks ne doivent pas créer d'options arbitraires. |
| Corps vide ou identique aux valeurs existantes | `200` – `{"ok":true,"unchanged":true}` | Permet d'éviter les révisions inutiles et préserve le cache courant. |
| Nonce REST expiré | `401/403` | Le middleware WP core renvoie l'erreur, les tests simulent ce cas via des nonces invalides. |
| CSS valide | `200` – `{"ok":true}` | Le cache est invalidé et une révision est enregistrée. |

## `POST /ssc/v1/css-revisions/<revision>/restore`

| Situation | Résultat attendu | Notes |
| --- | --- | --- |
| Révision inexistante | `404` – `{"ok":false,"message":"Revision not found"}` | Les identifiants sont validés côté serveur, aucune écriture si la révision n'existe pas. |
| Conflit de nonce REST | `401/403` | Identique à `save-css`. |
| Révision valide | `200` – `{"ok":true}` | Réinjecte la valeur associée et invalide le cache si nécessaire. |

## Suivi et actions manuelles

- Vérifier régulièrement les logs REST (`wp-content/debug.log`) pour détecter des statuts inattendus ou des corps vides.
- Documenter les nouveaux points d'entrée REST dès leur création et ajouter un scénario d'erreur à cette checklist.
- Lors de tests exploratoires, utiliser `wp rest` ou `curl` pour simuler les cas ci-dessus et confirmer les messages renvoyés dans l'interface du Centre de débogage.

## Ressources

- Tests automatisés : `tests/Infra/Rest/CssControllerTest.php`
- Centre de débogage : `views/debug-center.php`
- Commandes CLI associées : `src/Infra/Cli/CssCacheCommand.php`
