# Tests

This repository currently ships with a runnable foundation check:

```bash
composer test
```

The check validates the controlled ticket status lifecycle, invalid transition rejection, lifecycle timestamps, and status history recording without requiring packages that are not installed in the current skeleton.

For full unit and integration tests, install Symfony's test tooling when dependency downloads are available:

```bash
composer require --dev symfony/test-pack
```

After that, add PHPUnit tests under `tests/` and keep `bin/foundation-check.php` as a fast smoke check for architectural rules that should never regress.
