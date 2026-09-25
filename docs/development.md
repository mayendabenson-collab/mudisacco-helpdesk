# Development

## Start from a clean checkout

```bash
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:seed-foundation --no-interaction
```

The seed command creates foundational roles, permissions, departments, categories, SLA rules, and an administrator account. To avoid a generated password, pass one explicitly:

```bash
php bin/console app:seed-foundation --admin-email=admin@mudi-sacco.local --admin-password='change-this-locally'
```

## Run checks

```bash
composer lint:yaml
composer lint:container
composer doctrine:validate
composer test
```

## API endpoints

- `GET /api/health` is public and verifies database connectivity.
- `GET /api/foundation` is protected for staff users and exposes foundational enums and workflow transitions.

## Production notes

- Set `APP_ENV=prod` and a real `APP_SECRET` outside committed files.
- Use PostgreSQL for production through `DATABASE_URL`.
- Replace `MESSENGER_TRANSPORT_DSN=sync://` with a durable transport when background jobs are introduced.
- Install `symfony/lock` before enabling configured login/API rate limiters.
- Install `symfony/test-pack` before adding PHPUnit-based unit, integration, and end-to-end tests.
