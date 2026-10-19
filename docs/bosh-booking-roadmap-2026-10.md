# BOSH: Altegio-inspired booking roadmap (2026-10-19)

This document defines the requested scope without implying that all modules are already live.

## Included from research stage 2

- **2.3 Statuses**: retain appointment statuses already in the app (pending, confirmed, cancelled, completed, no_show). Future: separate booking, attendance, and payment states, with explicit transition guards.
- **2.4 Notifications**: preserve current WhatsApp notification records and revision logic. Future: event/outbox queue, scheduled reminders, idempotency keys, delivery retries with safe fallback.
- **2.5 CRM**: first incremental implementation in this branch: tenant-scoped client records populated during successful public booking. Customers still do not need accounts. No cross-master data sharing.
- **2.8 Storage model**: future tables for payments, refunds, policies, events and resources; do not create unused production tables prematurely.
- **2.9 Security**: preserve existing transactional master-row locks, tenant ownership checks, CSRF and rate limits. Add regression tests for isolation and concurrent writes.
- **2.10 Delivery phases**: appointment reliability, then CRM and notifications, then revenue, then teams and integrations.

## Included from research stage 3

- **3.1 Group sessions**: future group-event and participant records with atomic capacity checks. Never treat each attendee as a separate exclusive master-time block.
- **3.3 Rescheduling/cancellations**: currently master-controlled; future configurable customer self-service with expiring tokens, limits and transactionally safe rescheduling.
- **3.5 Roles (future)**: keep owner as the sole effective account role until server-side permissions and tests exist; never rely solely on UI visibility.
- **3.7 Deep links**: existing /m/{slug}?service={id} selection; extend with canonical multi-service and future staff links without introducing unauthenticated mutations.
- **3.8 Tests**: verify concurrent booking, group capacity, transfers, idempotent notifications, client isolation, historical price snapshots, refunds, discontinued services.
- **3.9 Expansion (future)**: owner, professional, team, and company-scale modes should all use one domain model and progressive disclosure.

## Deployment guardrails

1. Work on this isolated feature branch; no automatic deployment from main until checks pass and changes are reviewed.
2. MySQL 5.7 must be used in the integration suite. Test migrations before enabling any new feature.
3. Never expose raw phone numbers or client details through public booking routes.
4. Legacy bookings predate the new client index. Backfill only through an explicit, resumable and reviewed migration/job, not a slow schema migration.
5. No payment, multi-staff, group-session, external webhook, or reminder sender is enabled by this first slice.

## Current slice checklist

- [x] Schema: master-scoped client registry and nullable appointment link.
- [x] Register client as part of a successful booking under existing row lock.
- [x] Minimal CRM list in authenticated dashboard.
- [ ] Backfill clients from previous appointments.
- [ ] Client history, editing notes and merged duplicates.
- [ ] Automated tests and MySQL 5.7 verification.
- [ ] Customer self-service and group bookings.
- [ ] Event outbox, reminder scheduling and role system.
