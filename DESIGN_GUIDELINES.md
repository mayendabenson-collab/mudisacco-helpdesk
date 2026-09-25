DESIGN GUIDELINES — Mudi SACCO Support Desk

Purpose
-------
This document records the visual system and microcopy rules for the Mudi SACCO internal ticketing application. Follow these rules when updating styles or UI components so the product feels enterprise-grade, purposeful, and immediately useful for staff.

Design Principles (summary)
---------------------------
- Prioritize function over decoration. UI exists to help staff work quickly.
- Avoid AI-generated or marketing UI patterns.
- Use a restrained, consistent palette with one primary color and a small set of semantic colors.
- Use straight-forward microcopy; no marketing language.
- Design for desktop-first internal use, with responsive fallback for smaller screens.

Brand & Palette
---------------
Primary brand:
- Primary accent: #0f7b5f (Mudi green) — used for primary actions, success states, brand mark.
- Primary strong: #095843 — hover/active states for primary actions.

Neutrals:
- Ink (text): #18212f
- Muted text: #667085
- Page background: #f4f7f6
- Surface (cards): #ffffff
- Borders / lines: #d8dee8
- Sidebar background: #13251f (deep green/teal)

Semantic colors (single purpose; do not use for decoration):
- Success: #0f7b5f (same as primary)
- Warning / Due soon: #b54708
- Danger / Overdue / Escalation: #b42318
- Info / Links: #175cd3

Typography
----------
- System font stack: Inter, Segoe UI, Arial, sans-serif
- Base size: 15px
- Headings: use clear hierarchy; no decorative fonts.

Components & Patterns
---------------------
- Buttons: primary, secondary. Primary uses `--color-accent`; secondary uses neutral surface with border.
- Badges: small, high-contrast, semantic color backgrounds for status only.
- Tables: clear rows, subtle borders, compact spacing.
- Forms: single-column, minimal fields, sensible defaults, inline validation messages.
- Permission controls: grouped checkboxes with short labels and short descriptions.

Microcopy
---------
- Use concise, direct labels: "Create Ticket", "Assign Ticket", "Escalate", "Mark as Resolved".
- Status labels should be operational: "Open", "In Progress", "Waiting for Member", "Resolved", "Closed", "Overdue", "Escalated".
- Empty states use neutral tone: e.g., "No open tickets". Provide quick actions where helpful.

Accessibility
-------------
- Ensure sufficient color contrast for text and UI elements.
- Interactive controls must be reachable by keyboard.

When Not to Change
------------------
- Do not introduce additional decorative brand colors.
- Avoid large gradients, glass effects, floating shapes, or non-functional animations.

Implementation Notes
--------------------
- Centralize colors as CSS variables in `public/assets/styles/app.css`.
- Keep components small and composable in `templates/components/`.
- When introducing a new component, document behavior and keyboard interactions in this file.

Contact
-------
If design trade-offs are required, document the decision and rationale in the PR description and consult the product owner.
