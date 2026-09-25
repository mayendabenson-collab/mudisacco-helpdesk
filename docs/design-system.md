# Design System

The UI foundation is defined in `public/assets/styles/app.css` and reusable Twig partials under `templates/components`.

## Brand direction

Mudi SACCO Support should feel professional, modern, trustworthy, and restrained. The palette uses deep institutional ink, white operational surfaces, green for primary action, amber for caution, and red only for errors.

## Tokens

- Typography: system sans stack, 15px body, compact operational headings.
- Spacing: 4px base scale from `--space-1` through `--space-7`.
- Radius: 4px small and 8px medium; no oversized rounded cards.
- Surfaces: white panels on a light gray-green application background.
- Tables: dense, bordered, and scan-friendly for repeated operational use.

## Components

Current reusable partials:

- `components/_badge.html.twig`
- `components/_button_link.html.twig`
- `components/_empty_state.html.twig`
- `components/_error_state.html.twig`
- `components/_form_field.html.twig`
- `components/_loading_state.html.twig`
- `components/_status_indicator.html.twig`
- `components/_table_empty.html.twig`

New pages should compose these components first before introducing new UI patterns.
