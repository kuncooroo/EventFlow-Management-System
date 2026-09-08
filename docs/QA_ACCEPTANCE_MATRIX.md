# EventFlow MVP — QA Acceptance Matrix

**Task:** TASK-033 — MVP System Testing & Regression  
**Phase:** Phase 11 — Testing  
**Intended readers:** QA, Release reviewer  
**Principle:** A release is blocked while any AC below is unmet or any critical-severity defect is open.

This matrix maps each PRD acceptance criterion (AC-01..AC-18) to the automated tests that prove it, and to the manual checklist items where automation is intentionally out of scope (real browser / responsive layout).

---

## Legend

- **Status:** `PASS` (automated suite green), `MANUAL` (verified manually only; no browser-testing package installed), `N/A`.
- Coverage is considered **PASS** when at least one feature test asserts the observable AC outcome and the full suite is green.
- Run the full regression gate: `vendor/bin/phpunit` (currently **469 tests / 1335 assertions**).

---

## Acceptance Matrix

| AC | Criterion | Status | Automated coverage (class → method) | Manual / Note |
|---|---|---|---|---|
| AC-01 | Authentication | PASS | `Auth\LoginTest` (valid credentials, invalid password, regenerate session); `Auth\LogoutTest`; `Auth\ProtectedRouteTest` (guest redirect, authed dashboard access) | — |
| AC-02 | Organization Isolation | PASS | `Security\OrganizationIsolationTest` (cross-org routes 403/404, no name leak); `Reports\ReportPolicyTest`; `Organizations\SettingsAuthorizationTest` / `MemberAuthorizationTest` (foreign org) | — |
| AC-03 | Event Creation | PASS | `Events\EventCrudTest` (owner/admin create, validation, cross-org blocked) | Note: Event Manager does **not** create events (by design — role is assignment-scoped). |
| AC-04 | Event Publication | PASS | `Events\PublishEventTest` (unready rejected w/ validation, ready publishes w/ slug + audit, non-draft rejected) | — |
| AC-05 | Public Event Page | PASS | `Public\EventPageTest` (published 200, draft/archived 404, ticket types, venue visibility, description, banner, notes not leaked) | — |
| AC-06 | Closed Registration | PASS | `Registrations\PublicRegistrationTest` (ended / not-started / disabled → 0 rows); `Public\EventPageTest` (closed / opens-on date states) | — |
| AC-07 | Capacity | PASS | `Registrations\PublicRegistrationTest` (full ticket type / full event rejected, capacity release); `Events\TicketTypesTest` (zero capacity); `CancelRegistrationTest` | — |
| AC-08 | Registration | PASS | `Registrations\PublicRegistrationTest` (exactly one confirmed, code generated, custom answers, validation, after-commit dispatch) | — |
| AC-09 | Ticket | PASS | `Tickets\IssueTicketTest` (unique entropy-safe tokens, idempotent reissue); `Tickets\PublicTicketAccessTest` (QR page, payload = token, sequential guess 404) | — |
| AC-10 | Attendee Search | PASS | `Attendees\AttendeeIndexTest` (name/email/code search, filters, pagination); `Attendees\EventAttendeeSearchQueryTest` (N+1 protected, ≤5 queries / 10 attendees) | — |
| AC-11 | QR Check-In | PASS | `CheckIns\QrCheckInTest` (valid QR → success + audit); `CheckIns\ManualCheckInTest` (manual path) | Camera scan path is manual-only (no browser Q/A). |
| AC-12 | Duplicate Check-In | PASS | `CheckIns\QrCheckInTest` (duplicate → Duplicate + no 2nd row); `CheckIns\ManualCheckInTest` (sequential duplicate, DB unique index, **concurrent race → exactly one row** `test_concurrent_duplicate_check_in_race_creates_exactly_one_row`) | — |
| AC-13 | Invalid QR | PASS | `CheckIns\QrCheckInTest` (unknown token, wrong event, cancelled registration → 0 rows) | — |
| AC-14 | Dashboard | PASS | `Dashboard\EventDashboardTest` (metrics reconcile w/ report data, ticket-type summary, zero-attendee); `Dashboard\OrganizationDashboardTest`; `Reports\AttendanceReportTest` / `RegistrationReportTest` (report ≡ dashboard totals) | — |
| AC-15 | Export | PASS | `Reports\ExportTest` (CSV headers/rows, no QR secrets, filters, escaping, streaming 10k rows, cross-org denied) | — |
| AC-16 | Audit Trail | PASS | `Events\PublishEventTest`, `Events\LifecycleTransitionsTest`, `CancelRegistrationTest`, `CheckIns\QrCheckInTest` / `ManualCheckInTest`, `Organizations\OrganizationSettingsTest` (actor/action/entity/timestamp) | — |
| AC-17 | Permissions | PASS | `Organizations\SettingsAuthorizationTest` (Staff/EventManager/Viewer → 403 on settings); `MemberAuthorizationTest`; `Events\LifecycleAuthorizationTest` (Staff can't transition); `PublishEventTest` (Staff can't publish) | — |
| AC-18 | Mobile Registration | MANUAL | — | No browser-testing package installed. Responsive layout + horizontal-scroll waiver must be confirmed manually on a common mobile viewport (see checklist below). |

---

## Edge-Case Matrix

| Edge case | Covered | Evidence |
|---|---|---|
| Capacity race (final seat, concurrent) | PASS | `PublicRegistrationTest::test_concurrent_final_seat_race_accepts_exactly_one` (uses `Concurrency::run`) |
| Duplicate check-in race (concurrent) | PASS | `ManualCheckInTest::test_concurrent_duplicate_check_in_race_creates_exactly_one_row` (added in TASK-033) + DB unique index test |
| Last Owner protection | PASS | `Organizations\LastOwnerTest` (demote/remove blocked, cascading protection) |
| Sold-out ticket type | PASS | `PublicRegistrationTest` (full ticket type / full event), `TicketTypesTest::test_zero_capacity_is_allowed` |
| Cancelled registration check-in | PASS | `QrCheckInTest::test_cancelled_registration_qr_is_invalid`; `ManualCheckInTest::test_cancelled_registration_cannot_be_checked_in` |
| Zero attendees / no eligible attendee dashboard | PASS | `EventDashboardTest::test_zero_attendee_event_has_no_attendance_percentage_and_renders`; `AttendanceReportTest`; `ExportTest` (empty header row) |

---

## Security / Hardening Re-Run (TASK-032 dependency)

The isolation contract suite in `tests/Feature/Security/**` is part of the regression gate and is green:

- `Security\OrganizationIsolationTest`
- `Security\AuthRateLimitingTest`
- `Security\TokenEntropyTest`
- `Security\ProductionProtectionTest`

---

## Performance Targets (from SRS §36 / PRD §31)

These are validated by tests where an automated proxy exists; full timing claims require representative seeded data on a normal host (see `docs/SRS.md §36.7`).

| Target | Automated proxy | Green? |
|---|---|---|
| PERF-001 page ≤ 3s | `EventAttendeeSearchQueryTest` N+1 guard (≤5 queries/10 attendees) | Yes |
| PERF-002 search ≤ 2s @ 10k | Fast search query + N+1 guard | Yes |
| PERF-003 QR ≤ 3s | `QrCheckInTest` success path | Yes |
| PERF-005 CSV 10k rows no mem failure | `ExportTest::test_large_export_streams_without_exhausting_memory` | Yes |

---

## AC-18 / Mobile Manual Checklist

To be executed by a human momentarily before release sign-off (no `laravel/dusk` or browser package installed; app convention defers real-browser testing to manual QA).

1. Open a published event's public page on a 375px-wide mobile viewport.
2. Confirm the registration form's core content (name, email, ticket selection, submit) renders **without horizontal scrolling**.
3. Confirm labels are visible/associated on every form field (NFR-ACC-001).
4. Confirm validation errors render associated with their fields (NFR-ACC-002).
5. Confirm the primary registration workflow is keyboard-operable where practical (NFR-ACC-003).

---

## Release Gate

- [ ] `vendor/bin/phpunit` green (469 tests, 1335 assertions — current baseline)
- [ ] All AC-01..AC-17 PASS; AC-18 manual checklist executed
- [ ] All edge cases in matrix verified
- [ ] No open critical-severity defect (see `docs/QA_DEFECTS.md`)
- [ ] Security hardening suite re-run green (TASK-032 dependency)

**End of matrix.**
