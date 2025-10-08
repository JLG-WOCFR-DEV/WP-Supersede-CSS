# UI Preset Inspirations

This document lists families of design presets that could be bundled with Supersede CSS to help teams start from a familiar design vocabulary. Each preset references a well known library and explains how we might adapt the underlying aesthetic into configurable tokens and components.

## Minimal & Composable

### Headless UI Inspired
- **Focus**: Unstyled, accessibility-first primitives.
- **Token Priorities**: Spacing scale, focus rings, semantic state colors.
- **Component Ideas**: Dialog, Tabs, Combobox, Menu, Switch. Pair them with CSS utility tokens for layout.
- **Customization Hooks**: Slot driven parts (`--dialog-backdrop`, `--dialog-panel`) to inject brand gradients or glassmorphism.
- **Scope enregistré**: `:root[data-ssc-preset="headless-ui"]`

```css
:root[data-ssc-preset="headless-ui"] {
  --surface-base: #ffffff;
  --surface-muted: #f8fafc;
  --surface-inverse: #0f172a;
  --radius-md: 0.75rem;
  --focus-ring: 0 0 0 3px rgba(79, 70, 229, 0.35);
  --shadow-soft: 0 20px 35px -20px rgba(15, 23, 42, 0.45);
  --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
  --spacing-compact: 0.75rem;
  --spacing-cozy: 1.25rem;
  --font-family-base: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}
```

### shadcn/ui Inspired
- **Focus**: Modern neutrals with subtle radii and shadows.
- **Token Priorities**: Neutral gray palette (50–900), overlay colors, medium radius scale, transition durations.
- **Component Ideas**: Card, Command palette, Data table, Toasts with soft drop shadows.
- **Customization Hooks**: Layered `box-shadow` tokens, optional `backdrop-filter` for frosted glass surfaces.
- **Scope enregistré**: `:root[data-ssc-preset="shadcn"]`

```css
:root[data-ssc-preset="shadcn"] {
  --surface-base: #111827;
  --surface-muted: #1f2937;
  --surface-elevated: #151c2c;
  --text-color: #e2e8f0;
  --accent: #22d3ee;
  --shadow-layer-1: 0 10px 30px -15px rgba(15, 23, 42, 0.5);
  --shadow-layer-2: 0 25px 50px -25px rgba(15, 23, 42, 0.65);
  --border-radius-lg: 1rem;
  --transition-medium: 220ms cubic-bezier(0.4, 0, 0.2, 1);
  --overlay-color: rgba(15, 23, 42, 0.75);
}
```

### Radix UI Inspired
- **Focus**: Highly configurable primitives with state-driven styling.
- **Token Priorities**: Stateful colors (accent, success, destructive), adaptive spacing (compact, cozy, spacious), motion curves.
- **Component Ideas**: Dropdown menu, Tooltip, Slider, Collapsible sections.
- **Customization Hooks**: CSS variables per component part (`--slider-track`, `--slider-thumb`) plus density toggles.
- **Scope enregistré**: `:root[data-ssc-preset="radix"]`

```css
:root[data-ssc-preset="radix"] {
  --accent-color: #7c3aed;
  --accent-contrast: #ffffff;
  --success-color: #10b981;
  --destructive-color: #ef4444;
  --radius-tight: 0.375rem;
  --radius-cozy: 0.625rem;
  --radius-spacious: 0.75rem;
  --shadow-focus: 0 0 0 2px rgba(124, 58, 237, 0.45);
  --motion-snap: 180ms cubic-bezier(0.25, 0.9, 0.3, 1.4);
  --density-compact: 0.5rem;
  --density-spacious: 1.4rem;
}
```

## Classic Framework Energy

### Bootstrap Inspired
- **Focus**: Utility-driven responsive layout with bold accents.
- **Token Priorities**: Brand color scale, typographic scale (h1–h6), breakpoint map, grid gutters.
- **Component Ideas**: Navbar, Buttons, Alerts, Badges, Form controls.
- **Customization Hooks**: Toggleable rounded vs. square shapes, optional `border-width` tokens for outlines.
- **Scope enregistré**: `:root[data-ssc-preset="bootstrap"]`

```css
:root[data-ssc-preset="bootstrap"] {
  --brand-primary: #0d6efd;
  --brand-secondary: #6c757d;
  --brand-success: #198754;
  --brand-warning: #ffc107;
  --brand-danger: #dc3545;
  --font-family-base: "Helvetica Neue", Arial, sans-serif;
  --border-radius-base: 0.5rem;
  --border-width-base: 1px;
  --grid-gutter: 1.5rem;
  --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
}
```

### Semantic UI Inspired
- **Focus**: Friendly, human-readable class semantics and smooth animations.
- **Token Priorities**: Warm color palette, large shadow presets, transition timing tokens.
- **Component Ideas**: Feed, Comment thread, Statistic cards, Progress bars.
- **Customization Hooks**: Named color aliases (`--color-positive`, `--color-info`) and animation presets for `fade in`, `slide down`.
- **Scope enregistré**: `:root[data-ssc-preset="semantic"]`

```css
:root[data-ssc-preset="semantic"] {
  --color-positive: #21ba45;
  --color-info: #31ccec;
  --color-warning: #fbbd08;
  --color-negative: #db2828;
  --shadow-floating: 0 12px 30px -12px rgba(36, 41, 46, 0.25);
  --shadow-raised: 0 22px 45px -20px rgba(36, 41, 46, 0.3);
  --transition-primary: 260ms cubic-bezier(0.23, 1, 0.32, 1);
  --transition-subtle: 180ms cubic-bezier(0.4, 0, 0.2, 1);
  --border-radius-pill: 9999px;
  --font-family-heading: "Lato", "Helvetica Neue", Arial, sans-serif;
}
```

## Motion & Micro-Interactions

### Anime.js Inspired
- **Focus**: Motion-driven storytelling and kinetic typography.
- **Token Priorities**: Keyframe libraries, easing curves, stagger delays, perspective depths.
- **Component Ideas**: Scroll-triggered hero headline, Animated counters, Icon morphs.
- **Customization Hooks**: Declarative animation maps (`--motion-emphasis`, `--motion-subtle`), integration helpers for CSS `@keyframes` and JS timeline triggers.
- **Scope enregistré**: `:root[data-ssc-preset="animejs"]`

```css
:root[data-ssc-preset="animejs"] {
  --motion-emphasis: cubic-bezier(0.22, 1, 0.36, 1);
  --motion-subtle: cubic-bezier(0.4, 0, 0.2, 1);
  --motion-stagger: 120ms;
  --motion-loop: infinite;
  --motion-perspective: 900px;
  --motion-depth: translateZ(0);
  --surface-base: #050815;
  --surface-contrast: #f8fafc;
  --accent-primary: #f472b6;
  --accent-secondary: #38bdf8;
  --text-glow: 0 0 18px rgba(56, 189, 248, 0.55);
}
```

## Implementation Notes
- Export each preset as a JSON or CSS token bundle (colors, typography, spacing, radii, motion).
- Provide migration examples showing how to switch presets while keeping custom overrides.
- Ship Storybook stories demonstrating before/after for key components under every preset.
- Ensure accessibility audits run against every preset to validate contrast and focus management.
