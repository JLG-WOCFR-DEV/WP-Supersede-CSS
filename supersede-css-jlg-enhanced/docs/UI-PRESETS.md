# UI Preset Inspirations

This document lists families of design presets that could be bundled with Supersede CSS to help teams start from a familiar design vocabulary. Each preset references a well known library and explains how we might adapt the underlying aesthetic into configurable tokens and components.

## Minimal & Composable

### Headless UI Inspired
- **Focus**: Unstyled, accessibility-first primitives.
- **Token Priorities**: Spacing scale, focus rings, semantic state colors.
- **Component Ideas**: Dialog, Tabs, Combobox, Menu, Switch. Pair them with CSS utility tokens for layout.
- **Customization Hooks**: Slot driven parts (`--dialog-backdrop`, `--dialog-panel`) to inject brand gradients or glassmorphism.

### shadcn/ui Inspired
- **Focus**: Modern neutrals with subtle radii and shadows.
- **Token Priorities**: Neutral gray palette (50–900), overlay colors, medium radius scale, transition durations.
- **Component Ideas**: Card, Command palette, Data table, Toasts with soft drop shadows.
- **Customization Hooks**: Layered `box-shadow` tokens, optional `backdrop-filter` for frosted glass surfaces.

### Radix UI Inspired
- **Focus**: Highly configurable primitives with state-driven styling.
- **Token Priorities**: Stateful colors (accent, success, destructive), adaptive spacing (compact, cozy, spacious), motion curves.
- **Component Ideas**: Dropdown menu, Tooltip, Slider, Collapsible sections.
- **Customization Hooks**: CSS variables per component part (`--slider-track`, `--slider-thumb`) plus density toggles.

## Classic Framework Energy

### Bootstrap Inspired
- **Focus**: Utility-driven responsive layout with bold accents.
- **Token Priorities**: Brand color scale, typographic scale (h1–h6), breakpoint map, grid gutters.
- **Component Ideas**: Navbar, Buttons, Alerts, Badges, Form controls.
- **Customization Hooks**: Toggleable rounded vs. square shapes, optional `border-width` tokens for outlines.

### Semantic UI Inspired
- **Focus**: Friendly, human-readable class semantics and smooth animations.
- **Token Priorities**: Warm color palette, large shadow presets, transition timing tokens.
- **Component Ideas**: Feed, Comment thread, Statistic cards, Progress bars.
- **Customization Hooks**: Named color aliases (`--color-positive`, `--color-info`) and animation presets for `fade in`, `slide down`.

## Motion & Micro-Interactions

### Anime.js Inspired
- **Focus**: Motion-driven storytelling and kinetic typography.
- **Token Priorities**: Keyframe libraries, easing curves, stagger delays, perspective depths.
- **Component Ideas**: Scroll-triggered hero headline, Animated counters, Icon morphs.
- **Customization Hooks**: Declarative animation maps (`--motion-emphasis`, `--motion-subtle`), integration helpers for CSS `@keyframes` and JS timeline triggers.

## Implementation Notes
- Export each preset as a JSON or CSS token bundle (colors, typography, spacing, radii, motion).
- Provide migration examples showing how to switch presets while keeping custom overrides.
- Ship Storybook stories demonstrating before/after for key components under every preset.
- Ensure accessibility audits run against every preset to validate contrast and focus management.
