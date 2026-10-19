# BOSH: Altegio-inspired booking roadmap (2026-10-19)

This document defines the requested scope without implying that all modules are already live.

## Numbered implementation queue

1. **CRM completion**: history and private notes implemented; an explicit dry-run capable historical backfill command added. Duplicate handling and full isolation regression pending.
2. **Status model**: nullable attendance/payment fields added; new bookings start with expected attendance, historical states remain unclassified; master status updates synchronize attendance. Payment settlement workflow remains future work.
3. **Notification events**: an idempotent reminder-planning ledger and dry-run command now exist. A verified approved reminder template, delivery worker, robust final dispatch checks and webhook-based delivery status are still pending.
4. **Client self-service**: initial signed-link cancellation and transactional rescheduling implemented. The client receives a 30-day link in the booking receipt; 2-hour change cutoff, two-reschedule maximum, revision invalidation, overlap and work-hours checks. Notification templates and fuller integration tests remain pending.
5. **Group bookings**: initial separate session/participant tables, master-only creation, public signup with master-row lock and seat counter, standalone group listing on booking page, dashboard listing and collision checks added. Cancellation, attendee management, duplicate booking handling, messaging and in-depth concurrency tests remain pending.
6. **Deep links**: implemented share links in the authenticated service catalog for one active service and user-selected bundles. Uses /m/{slug}?service={id} or /m/{slug}?selection=1&services[]={id}...; specific staff links await multi-staff support.
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


## Initial self-service workflow (feature branch only)

After creating a public booking, the user receives a temporary signed link in the receipt (expires in 30 days). The link allows the customer to see the selected service, price and current time; cancel or move the booking without an account. Mutating actions are POST-only, signed, CSRF-protected and rate-limited. Only pending/confirmed appointments may change, no later than two hours before the original appointment. A new slot must be within 90 days, at a 30-minute boundary and fully available in the business schedule. The master row is locked first, preventing concurrent conflicting bookings. Two customer-initiated reschedules are allowed. After any change, the old link becomes invalid and a fresh signed link is issued. The client must save the replacement URL.

This initial capability is only placed on the booking receipt. It is not inserted into unapproved Meta templates or retroactively issued to legacy customers. Customers whose links expired or were invalidated must contact the master. Per-master customizable cutoffs, self-service links delivered via verified communications and event-level history require further work.


## Group sessions (initial development slice)

An authenticated master can create a group session using an existing active service, its duration, a local start time and a capacity of 2–100 seats. New sessions cannot overlap working breaks, blocked intervals, other group sessions or individual appointments. An open session blocks the master's time **once**. Public signup adds a participant and increments the reserved count under the same master-level transaction lock used by individual bookings, so capacity is enforced on the server. The booking page displays up to 30 future sessions with available seats; the master dashboard lists up to 100 sessions.

Known limits: no participant cancellation or attendance tracking UI, no reusable recurring classes, no payment processing, no Meta confirmation or management token for a group attendee. The route is rate limited and uses the existing web CSRF middleware. Check phone formatting and prevent repeated registrations appropriately before opening the feature to all production users. Full group signup concurrency testing and management workflows are still required.


## Service deep links (current development slice)

The existing public booking controller already validates a single `service` query argument and a multi-service `services[]` selection against the profile's **active** services; invalid or foreign IDs return 404. The service catalog now offers per-service copy controls and a checkbox-based bundle selector that generates URLs using `URL.searchParams` (to handle brackets and other encoding safely). The preselected services flow directly into the existing availability calculator; no authentication or privileged action is granted by the link itself. Inactive services do not get share controls. Deep links to individual staff members are deferred until team accounts have correct access controls and scheduling rules.

The group-session registration uniqueness guard now keys on group, normalized contact phone, and participant name rather than only the contact phone. This allows family members with a shared phone to book separate seats, while rejecting an identical repeat. It is still not a general-purpose idempotency-key scheme: dedicated idempotency protection and member management remain for further work.


## Integrity and security audit — phase 7

New MySQL/HTTP regression tests (`tests/Feature/BookingIntegrityTest.php`) cover:

1. Signed customer link invalidation after cancellation and refusal to replay the old revision.
2. Signed URL tampering to another appointment.
3. Tenant isolation: another master cannot read or update a customer's private notes.
4. Group participant deduplication with alternative phone formats, shared phone across different people, and capacity ceiling.
5. Group events blocking overlapping individual appointments.

New CRM normalization uses the same canonical international phone format in public booking, master edits and historical backfill, with fallback to the legacy raw-digits format for supported pre-existing inputs. This is a compatibility change; shared household phones can still represent more than one actual visitor. No automatic multi-person merge is currently attempted.

**Pending before production release:** test true multi-process concurrency over the final group seat (not just sequential POST requests), inspect MySQL migrations against hosting schema, verify migration rollback after real data, and confirm full app security controls. The checks in CI are not a substitute for a staging smoke test or security penetration assessment.


### Read-only booking integrity audit

A new command `php artisan bosh:audit-bookings` audits four invariants without modifying production records: sessions exceeding capacity, mismatches between confirmed participants and reserved-seat counts, client links crossing business boundaries, and active group events overlapping individual appointments. The command exits nonzero and reports anomaly counts so it can be incorporated into a deployment review or manual incident check.

This is a limited database-consistency check, **not** a security penetration test, data recovery operation or complete duplicate-booking checker. The backup, historical CRM backfill dry run and true multiprocess race tests remain explicit gates before merging.
