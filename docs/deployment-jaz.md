# JAZ deployment preflight

Target: https://edu.china.kg
Database host: localhost
Database name: n66843_master
Database user: n66843_masteruser

## Prerequisites

- PHP version and required Laravel extensions confirmed on JAZ
- Database connection tested from the JAZ server
- Document root points to Laravel public/ directory
- Writable storage/ and bootstrap/cache/
- HTTPS certificate installed
- FTP/SFTP access for GitHub Actions
- Production APP_KEY generated and stored securely
- .env exists on server only; APP_ENV=production and APP_DEBUG=false
- Database backup before first migration
- Deploy and rollback paths verified

## Deployment approach

Build PHP vendor and frontend assets in CI, upload release via SFTP or FTPS, and switch document root to public/. Never copy .env into the repository or public/. Migrations require a secure execution mechanism; do not expose a web migration endpoint.

## Status

Infrastructure not verified; deployment intentionally disabled.
