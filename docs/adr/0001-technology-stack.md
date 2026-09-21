# ADR 0001: Symfony Foundation Stack

## Status

Accepted

## Context

Mudi SACCO needs a ticketing and customer service system that can grow into secure member support, staff queues, SLA tracking, audit trails, notifications, and future integrations with SACCO systems. The starting repository was already a Symfony project with core packages installed but not fully configured.

## Decision

Use Symfony 6.4 LTS as the application framework, Doctrine ORM for relational persistence, Doctrine Migrations for schema change control, Symfony Security for authentication and authorization, Twig for server-rendered UI, Monolog for logging, Messenger for future background work, and Validator/Serializer for input and API boundaries.

Local development defaults to SQLite so the project can run immediately. Production should use PostgreSQL by setting `DATABASE_URL` in the deployment environment.

## Consequences

- The team can build real persisted workflows without adding a separate API framework prematurely.
- The architecture remains modular enough to add mobile/API clients, SMS/email/WhatsApp providers, SACCO core integration, and reporting later.
- Runtime rate limiting needs `symfony/lock` before enabling configured limiters.
- Full PHPUnit tooling needs `symfony/test-pack` when dependency downloads are available.
