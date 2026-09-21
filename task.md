# Task List

## Bug Fix
- [x] Fix `scopeTickets()` in `TicketController.php` — staff with CLOSE_TICKETS see dept tickets

## UI & Dashboard Overhaul
- [x] `app.css` — full design system refresh (avatars, stat cards, hover rows, dots, nav, responsive shell)
- [x] `base.html.twig` — improved sidebar with SVG icons + labels, clean topbar, and flash alerts
- [x] `dashboard/index.html.twig` — stat cards with SVG icons, oversight workload panel, breakdown cards, and recent tickets table
- [x] `tickets/index.html.twig` — 4-metric queue summary cards, avatar initials, dot + badge priority/status, clean filter bar
- [x] `tickets/show.html.twig` — tightened layout, clean icon headings, streamlined action panels
- [x] `tickets/new.html.twig` — step-by-step customer search, clear intake form
- [x] `admin/staff/index.html.twig` & `form.html.twig` — replaced emojis with SVG icon callouts, role responsibilities
- [x] `admin/staff/setup_link.html.twig` — replaced emoji warning with SVG security alert
- [x] `admin/departments/index.html.twig` & `form.html.twig` — fixed `.html.wig` template typo, modern styling
- [x] `admin/categories/index.html.twig` & `form.html.twig` — updated to design system classes and SVG icons
- [x] `admin/sla/index.html.twig` & `form.html.twig` — updated to design system classes, clear target columns
- [x] `admin/members/` (index, form, import) — fixed `{% block content %}` block mismatch, removed Bootstrap dependencies, fully aligned with Mudi SACCO design system
