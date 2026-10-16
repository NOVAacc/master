# Development status

## Implemented in repository

- Initial migration drafts for master profiles, services, appointments, working hours, and time blocks
- Eloquent models for profiles, services, appointments
- Booking service with database transaction, master-row locking, overlap detection, and blocked-time detection

## Not implemented yet

- Complete Laravel 12 application scaffold (composer.json, artisan, bootstrap, routes)
- Authentication, dashboard, public booking UI, and APIs
- Working-hours enforcement and timezone boundary checks
- Validation of all migrations against a supported MySQL instance
- Automated tests, CI, deployment, and production configuration

The booking service is **not exposed as a public endpoint** until working-hours enforcement, rate limiting, privacy protections, and tests are in place. Current files are source components, not a runnable application.

PHP 8.3 is available on JAZ, but MySQL server is 5.7.44 and cannot be upgraded there. Laravel 12 requires MySQL 8.0+ for officially supported operation. The JAZ database is not connected and no migrations have been run.
