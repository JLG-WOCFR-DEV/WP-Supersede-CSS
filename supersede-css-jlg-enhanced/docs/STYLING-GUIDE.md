# Guide de factorisation des styles

Ce document complète `assets/css/foundation.css` et explique comment utiliser les tokens, primitives et utilitaires mis à disposition par le design system de Supersede CSS.

## 1. Design tokens

Tous les tokens de base sont définis dans `foundation.css` au niveau de `:root`. Ils couvrent :

- **Couleurs** (`--ssc-bg`, `--ssc-card`, `--ssc-accent`, etc.)
- **Typographie** (famille, graisses, échelles `--ssc-font-size-xs` → `--ssc-font-size-2xl`)
- **Espacements** (`--ssc-space-050` → `--ssc-space-500`)
- **Rayons & ombres**

Pour garantir la compatibilité avec les anciens écrans, des alias ont été ajoutés :

- `--ssc-font-size-100` → `--ssc-font-size-600` répliquent l'ancienne numérotation par pas de 100.
- `--ssc-space-3xs` → `--ssc-space-3xl` répliquent les espacements "t-shirt" (3xs, 2xs, xs, etc.).
- `--ssc-line-height-base` pointe sur `--ssc-line-height-default` pour les mises en page historiques.

> **Bonnes pratiques :** privilégiez les tokens `xs/sm/md/...` pour les nouveaux écrans. Les alias par incréments (`100`, `200`, etc.) doivent uniquement servir à maintenir du code existant.

## 2. Primitives de surface

Les panneaux, cartes et surfaces éditoriales partagent désormais les mêmes styles via le sélecteur :

```css
:where(.ssc-surface, .ssc-pane, .ssc-panel, .ssc-preview-surface) {
    background: var(--ssc-card);
    border: 1px solid var(--ssc-border);
}
```

- `.ssc-pane` et `.ssc-panel` héritent automatiquement du rayon `--ssc-radius-md` et d'un padding `--ssc-space-md`.
- `.ssc-preview-surface` conserve son rayon spécifique (`--ssc-radius-lg`) mais mutualise le fond et la bordure.

Lorsque vous créez une nouvelle carte, utilisez idéalement l'une de ces classes ou ajoutez `.ssc-surface` pour profiter des mêmes tokens.

## 3. Utilitaires d'espacement

Les utilitaires `.ssc-mt-*`, `.ssc-mb-*` et `.ssc-pt-*` sont maintenant documentés dans `foundation.css`. Ils s'appuient sur l'échelle "t-shirt" (`--ssc-space-sm`, `--ssc-space-lg`, etc.) pour une meilleure lisibilité. Exemple :

- `.ssc-mt-150` → `margin-top: var(--ssc-space-sm);`
- `.ssc-mt-400` → `margin-top: var(--ssc-space-2xl);`

N'hésitez pas à créer un nouvel utilitaire uniquement si l'espacement n'existe pas déjà dans cette grille.

## 4. Thème sombre

Le thème sombre continue de s'activer via `.ssc-dark`. Les tokens définis dans `foundation.css` couvrent la majorité des besoins. Évitez de redéfinir couleurs et contrastes directement dans les vues : surchargez plutôt les tokens au besoin.

## 5. Rayons spécifiques à l'UX immersif

Les écrans immersifs (`ux.css`) conservent des rayons plus généreux. Le fichier redéfinit uniquement `--ssc-radius-sm`, `--ssc-radius-md` et `--ssc-radius-lg`, tout en réutilisant le reste des tokens centralisés. Si un autre contexte nécessite des rayons différents, créez une portée (`:root` scoped) similaire au lieu de dupliquer tout le bloc de tokens.

---

En cas de doute, consultez `foundation.css` : chaque section est commentée et les tokens sont regroupés par type pour faciliter la découverte. Toute nouvelle règle générique doit être envisagée dans ce fichier avant d'être implémentée ailleurs.
