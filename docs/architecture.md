# Mudi SACCO Support Architecture

## Current assessment

The repository started as a minimal Symfony 6.4 skeleton. It already had the right family of dependencies in `composer.json`, but only `FrameworkBundle` was enabled. The foundation now keeps Symfony 6.4 LTS and activates Doctrine, Doctrine Migrations, Security, Twig, and Monolog so the installed stack is actually usable.

## Technology decisions

- Backend: Symfony 6.4 LTS with attribute routes and service autowiring.
- Database: Doctrine ORM with migrations. Local development uses SQLite for immediate setup; production should use PostgreSQL through `DATABASE_URL`.
- Authentication: Symfony Security form login with password hashing through `UserPasswordHasherInterface`.
- Authorization: database-backed roles and permissions, plus a dedicated ticket access voter so member ticket isolation is enforced at record level.
- UI: Twig templates and static CSS design tokens. No frontend build tool is introduced until the product needs richer client-side behavior.
- Logging: Monolog with separate `security`, `audit`, and `notifications` channels ready for production handlers.
- Background work: Messenger is configured with `sync://` as a safe default. A queue transport should replace it when an async infrastructure choice is made.

## Structure

- `src/Entity`: relational model for users, roles, permissions, members, departments, categories, tickets, ticket messages, attachments, assignments, status history, notifications, audit logs, SLA rules, and satisfaction ratings.
- `src/Enum`: constrained values for ticket status, priority, message visibility, member status, notification channel, and user type.
- `src/Security`: system roles, permission definitions, and voters.
- `src/Ticket`: ticket workflow rules and the lifecycle service that applies controlled status changes.
- `src/Controller/Api`: API endpoints, beginning with health and foundation metadata.
- `templates/components`: reusable Twig UI pieces.
- `public/assets/styles`: Mudi SACCO design tokens and application CSS.
- `migrations`: database schema migrations generated from Doctrine metadata.
- `docs`: architecture, development, and decision records.

## Data model

The schema is normalized around these boundaries:

- Identity: `users`, `roles`, `permissions`, `user_roles`, `role_permissions`.
- SACCO member records: `members`, linked optionally to portal users.
- Service configuration: `departments`, `categories`, `sla_rules`.
- Ticket operations: `tickets`, `ticket_messages`, `ticket_attachments`, `ticket_assignments`, `ticket_status_history`.
- Governance: `audit_logs`, `notifications`, `satisfaction_ratings`.

UUID primary keys are used throughout. The schema includes unique constraints for stable business identifiers and indexes for common access paths such as ticket status, priority, member, assigned staff, and department. Migrations are ordered by foreign-key dependency so the schema can be created on stricter relational databases such as PostgreSQL, not only SQLite.

## Ticket lifecycle

Ticket status transitions are defined by `App\Ticket\TicketStatusWorkflow` and applied through `App\Ticket\TicketLifecycleService`, which validates transitions, updates lifecycle timestamps, and records `TicketStatusHistory`:

```text
OPEN -> IN_PROGRESS -> WAITING_FOR_MEMBER -> IN_PROGRESS -> RESOLVED -> CLOSED
RESOLVED -> REOPENED -> IN_PROGRESS
```

Skipping directly from `OPEN` to `CLOSED`, or mutating a closed ticket, is intentionally rejected by the workflow service.

## Security posture

- Passwords are hashed by Symfony Security, never stored directly.
- Role permissions are database-backed and seeded through `app:seed-foundation`.
- Ticket access is subject-aware through `TicketAccessVoter`; member ownership is checked before access is granted.
- Twig auto-escaping, CSRF-protected login, validation, and parameterized Doctrine queries are used by default.
- Attachment naming is handled by `SecureFileNameGenerator`, which restricts file extensions and avoids trusting user-provided names.
- Runtime rate limiting is documented but not enabled because this skeleton does not include `symfony/lock`, which Symfony requires for configured limiters.

## What is intentionally not implemented yet

The foundation does not claim to complete the ticket workflow, reports, external notification delivery, file upload storage, SLA escalation jobs, SACCO core-system integration, or analytics. Those should be added after the base entities, migrations, security rules, and UI components are stable.
