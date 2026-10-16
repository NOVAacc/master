# JAZ hosting compatibility

Checked with hosting panel information supplied by project owner on 2026-10-16.

- PHP: 8.1
- MySQL: 5.7.44-cll-lve
- MySQL host: localhost
- Database: n66843_master
- Database user: n66843_masteruser
- Deployment hostname: edu.china.kg

## Decision

Do not deploy a current Laravel release on PHP 8.1. Request PHP 8.3 or 8.4 (preferably) for the subdomain. MySQL 5.7 is end-of-life: request MySQL 8.0+ or a supported MariaDB version and verify the exact Laravel version's database requirements before selecting framework dependencies.

Prepare application code and schema independently of hosting while waiting for a compatible runtime. Do not run migrations on the hosting database until the environment and backup strategy are confirmed.

Production requires APP_DEBUG=false, HTTPS, a private .env, secure document root, and tested rollback.
