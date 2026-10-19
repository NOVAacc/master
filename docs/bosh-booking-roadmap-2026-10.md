# BOSH: Altegio-inspired booking roadmap (2026-10-19)

This document defines the requested scope without implying that all modules are already live.

## Numbered implementation queue

1. **CRM completion**: history and private notes implemented; an explicit dry-run capable historical backfill command added. Duplicate handling and full isolation regression pending.
2. **Status model**: nullable attendance/payment fields added; new bookings start with expected attendance, historical states remain unclassified; master status updates synchronize attendance. Payment settlement workflow remains future work.
3. **Notification events**: an idempotent reminder-planning ledger and dry-run command now exist. A verified approved reminder template, delivery worker, robust final dispatch checks and webhook-based delivery status are still pending.
4. **Client self-service**: cancellation and rescheduling limits, signed scoped links and atomic changes.
5. **Group bookings**: sessions, attendees, capacity locks and participant status.
6. **Deep links**: canonical links to service combinations and eventually specific staff.
7. **Data model and safety**: payment and refund records only when actual flows are built; security tests and audit trails.
8. **Future role permissions**: owner, administrator, master, observer with backend authorization.
9. **Future product modes**: progressive disclosure from solo master to teams, branches and enterprise.
10. **Regression suite and rollout**: concurrent slots, legacy data migration, financial snapshots, notifications, tenant isolation, MySQL 5.7, production smoke tests.

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
- [x] Add explicit resumable command for historical client linking (`php artisan bosh:backfill-clients --dry-run` then `php artisan bosh:backfill-clients`).
- [ ] Execute backfill after deployment and review of dry-run result.
- [x] Client visit history (new linked bookings) and editing private notes.
- [ ] Legacy backfill execution, safe handling of shared family phone numbers, and duplicate resolution.
- [ ] Automated tests and MySQL 5.7 verification.
- [ ] Customer self-service and group bookings.
- [ ] Event outbox, reminder scheduling and role system.


## Reminder planner (staged, no outbound messages)

The CLI `php artisan bosh:plan-reminders --dry-run` finds eligible confirmed bookings over the next 48 hours and reports potential 24-hour reminders. `php artisan bosh:plan-reminders` stages them in `master_reminders`. Planning is repeatable because the database uniquely identifies appointment, notification revision, channel and recipient.

Eligibility requires client WhatsApp consent, confirmed booking, a valid phone and a visit more than one hour ahead. Changed/cancelled/opted-out bookings cause superseded **scheduled** entries to be cancelled. Entries accepted by an external provider are deliberately retained as immutable delivery history.

**Safety:** No message dispatch worker, cron schedule or automatic sender was enabled. Do not send any reminders until a separately approved Meta template is configured, delivery-status webhooks are verified, and consent/phone/status/revision are rechecked within the claim-and-send flow. Connection timeouts must be treated as unknown to avoid duplicated WhatsApp messages.

The initial integration only tests empty planner execution and command repeatability. Integration tests for eligible bookings, revisions and cancellations remain required.
