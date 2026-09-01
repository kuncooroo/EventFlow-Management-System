# EventFlow Management System — Project Structure

**Document Path:** `docs/PROJECT_STRUCTURE.md`  
**Product:** EventFlow Management System  
**Document Type:** Laravel Directory & Module Structure  
**Authoring Role:** Senior Laravel Architect  
**Architecture Style:** Modular Monolith (logical modules, not package-per-module)  
**Authoritative Sources:** `docs/PRD.md`, `docs/SRS.md`, `docs/SYSTEM_DESIGN.md`, `docs/DATABASE.md`, `docs/BUSINESS_FLOW.md`, `CURSOR.md`  
**Document Version:** 1.0  
**Status:** Recommended for Implementation  

---

## 1. Design Intent

EventFlow is a **Laravel modular monolith**: one deployable app, one MySQL database, clear domain boundaries.

This structure prefers:

1. Laravel conventions over custom frameworks
2. Explicit Actions for critical multi-step workflows
3. Eloquent as the default persistence layer
4. Domain folders for grouping — **not** `nwidart/laravel-modules` or microservice packages
5. Creating directories only when the first real class needs them

It deliberately avoids:

- repository-per-model
- DTO frameworks
- Action + Interface + Repository + Manager stacks for simple CRUD
- CQRS / event sourcing infrastructure
- package-per-module scaffolding before it is needed

---

## 2. Recommended Top-Level Layout

```text
eventflow/
├── app/
│   ├── Actions/                 # Use-case workflows (preferred for critical writes)
│   ├── Console/
│   ├── Enums/                   # Backed enums for statuses, roles, field types
│   ├── Events/                  # Domain/application events (side effects)
│   ├── Exceptions/              # Domain/business exceptions when useful
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/            # Form Requests, grouped by domain
│   ├── Jobs/
│   ├── Listeners/
│   ├── Livewire/                # Interactive UI orchestration
│   ├── Mail/                    # Optional if Notification alone is insufficient
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/
│   ├── Providers/
│   ├── Queries/                 # Complex read/report query objects (not repositories)
│   ├── Rules/                   # Reusable validation rules when shared
│   ├── Services/                # Narrow reusable domain services (sparingly)
│   ├── Support/                 # Small helpers/traits used across domains
│   └── View/                    # Blade component classes (if needed)
│
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docs/                        # PRD, SRS, SYSTEM_DESIGN, this file, etc.
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── components/          # Blade anonymous/class components
│       │   └── layouts/         # app, guest, public layout components (`<x-layouts.*>`)
│       ├── emails/              # Optional email Blade views
│       ├── livewire/            # Livewire view templates
│       ├── pages/               # Full-page Blade views (non-Livewire)
│       └── public/              # Public event / registration / ticket pages
├── routes/
│   ├── console.php
│   ├── web.php                  # Primary MVP routes
│   └── auth.php                 # Optional split if auth routes grow
├── storage/
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── Browser/                 # Optional high-value E2E only
├── composer.json
├── package.json
├── phpunit.xml / pest.php
└── vite.config.js
```

Do **not** create empty folders “for completeness.” Add a folder when the first concrete class or view lands there.

---

## 3. Logical Modules (Not Separate Packages)

Modules are **conceptual ownership boundaries**, expressed as subfolders under Actions, Policies, Livewire, Requests, Queries, etc.

### 3.1 Core Platform

| Module | Owns |
|---|---|
| Identity | Users, auth, password reset, profile |
| Organizations | Organization, settings, workspace context |
| Memberships | Memberships, roles, invitations, last-Owner invariant |
| Files | Uploaded media metadata + filesystem orchestration |
| Notifications | Delivery orchestration (not business decisions) |
| Audit | Immutable activity log writes |

### 3.2 Event Domain

| Module | Owns |
|---|---|
| Events | Lifecycle, publish readiness, public visibility |
| Venues | Venue records tied to events/org |
| Agenda | Agenda items |
| Registration | Availability, capacity, attendees, custom answers |
| Ticket Types | Categories, capacity, pricing display |
| Tickets | Ticket codes, QR tokens |
| Check-In | Attendance validation, duplicate protection |
| Reporting | Dashboards, aggregates, CSV exports |

### 3.3 Dependency Rule

```text
Identity → Organizations → Memberships
Organizations → Events → Registration → Tickets → Check-In
Events / Registration / Tickets / Check-In → Reporting (read-only)
Any material write → Audit
Registration / Events → Notifications (after commit)
```

Core must not depend on check-in or registration internals.  
Reporting may read event-domain data but must not mutate operational state.

---

## 4. Request Flow (Where Code Lives)

```text
Blade / Livewire
      ↓
Form Request or Livewire validation
      ↓
Policy / Gate
      ↓
Action (or small Service / Model for simple cases)
      ↓
Eloquent Model / Query Object
      ↓
MySQL (authoritative)
      ↓
Event → Listener / Job / Notification / Audit / Cache invalidation
```

Primary business decisions happen in Actions before async side effects.

---

## 5. Where Each Concern Belongs

### 5.1 Models — `app/Models/`

Eloquent domain models. Keep flat for MVP; do not nest by module unless the model count becomes hard to navigate.

**May contain:**

- relationships
- casts (including enums)
- local scopes
- accessors/mutators when justified
- small state helpers (`isPublished()`, `isCheckedIn()`)

**Must not contain:**

- registration + ticket + email orchestration
- capacity reservation workflows
- publish readiness multi-step rules
- major observer-driven business pipelines

**Expected MVP models (from DATABASE):**

```text
User
Organization
OrganizationMembership
OrganizationInvitation
OrganizationSetting
Event
EventAssignment
Venue
AgendaItem
RegistrationField
TicketType
Registration
RegistrationAnswer
Ticket
CheckIn
MediaFile
ActivityLog
```

---

### 5.2 Controllers — `app/Http/Controllers/`

Thin HTTP adapters for classic Blade/form posts and simple pages.

```text
app/Http/Controllers/
├── Auth/
├── Organization/
├── Event/
├── Public/
│   ├── EventPageController.php
│   ├── RegistrationController.php
│   └── TicketAccessController.php
└── ...
```

Controllers may:

- accept request
- authorize
- validate via Form Request
- call one Action/Service
- return view/redirect/download

Controllers must **not** implement capacity logic, ticket issuance, check-in races, or notification orchestration.

> Prefer Livewire for interactive admin screens. Controllers remain useful for public pages, downloads, auth endpoints, and simple redirects.

---

### 5.3 Actions — `app/Actions/{Domain}/`

**Primary home for critical write workflows.**

Use an Action when the use case:

- spans multiple models
- needs a DB transaction
- has non-trivial business rules
- may be called from Livewire, controller, command, or job
- dispatches notifications/jobs after commit
- needs focused feature tests

```text
app/Actions/
├── Organizations/
│   ├── InviteMember.php
│   ├── ChangeMemberRole.php
│   └── RemoveMember.php
├── Events/
│   ├── CreateEvent.php
│   ├── UpdateEvent.php
│   ├── PublishEvent.php
│   ├── CancelEvent.php
│   └── ArchiveEvent.php
├── Registrations/
│   ├── RegisterAttendee.php
│   └── CancelRegistration.php
├── Tickets/
│   └── IssueTicket.php          # usually called inside RegisterAttendee
└── CheckIns/
    └── CheckInAttendee.php
```

Naming: verb-focused (`PublishEvent`, not `EventManager`).

Typical Action shape:

```php
public function handle(...): Model|Result
{
    // authorize already done by caller OR explicitly here when needed
    // business validation
    // DB::transaction(...)
    // write authoritative state
    // write audit
    // return
}
// after commit: event/job/notification
```

Do **not** create Actions for trivial single-field updates with no rules.

---

### 5.4 Services — `app/Services/{Domain}/` (sparingly)

Use Services for **reusable domain logic that is not itself a use case**.

Prefer specific names:

```text
app/Services/
├── Events/
│   └── PublishReadinessChecker.php
├── Registrations/
│   └── RegistrationCapacityService.php
└── Reports/
    └── AttendanceMetricsCalculator.php   # only if not better as a Query
```

Avoid:

```text
EventService
CommonService
HelperService
OrganizationManager
```

**Rule of thumb:**

- one business use case → Action
- reusable calculation/rule used by multiple Actions → Service
- simple CRUD → Model + Form Request + Policy is enough

---

### 5.5 DTOs — optional, `app/Data/` only if justified

**Default: do not introduce a DTO layer.**

Prefer:

- Form Request `validated()` arrays
- typed Action method parameters
- Eloquent models as return values

Introduce a small readonly DTO/value object only when:

- an Action is called from multiple entry points with the same structured input
- a complex validated payload is passed through several layers
- you need an explicit immutable result object for a workflow

If used:

```text
app/Data/
├── Registrations/
│   └── RegisterAttendeeData.php
└── Reports/
    └── ExportFilterData.php
```

Do **not** install a DTO package for MVP.

---

### 5.6 Enums — `app/Enums/`

Backed enums for controlled vocabularies from PRD/BUSINESS_FLOW/DATABASE.

```text
app/Enums/
├── OrganizationRole.php          # owner, admin, event_manager, staff, viewer
├── EventStatus.php               # draft, published, ongoing, completed, cancelled, archived
├── RegistrationStatus.php        # confirmed, cancelled
├── InvitationStatus.php
├── RegistrationFieldType.php
└── CheckInMethod.php             # qr, manual (if modeled)
```

Cast enums on models. Keep transition rules in Actions/Services, not inside every enum method unless the rule is tiny and model-local.

---

### 5.7 Policies — `app/Policies/`

Mandatory for protected resources. Organization isolation is a security boundary.

```text
app/Policies/
├── OrganizationPolicy.php
├── OrganizationMembershipPolicy.php
├── EventPolicy.php
├── RegistrationPolicy.php
├── TicketPolicy.php
├── CheckInPolicy.php
├── ReportPolicy.php
└── MediaFilePolicy.php
```

Policies answer “who may do this?” using:

1. authenticated user
2. active membership
3. role
4. event assignment (for Event Manager / Staff / Viewer)
5. resource organization match

Do not rely on hidden menus or Alpine role checks.

---

### 5.8 Form Requests — `app/Http/Requests/{Domain}/`

Use for complex HTTP validation (controllers and any non-Livewire posts).

```text
app/Http/Requests/
├── Organizations/
│   ├── UpdateOrganizationSettingsRequest.php
│   └── InviteMemberRequest.php
├── Events/
│   ├── StoreEventRequest.php
│   └── UpdateEventRequest.php
├── Registrations/
│   └── PublicRegisterRequest.php
└── TicketTypes/
    └── StoreTicketTypeRequest.php
```

Split concerns:

| Layer | Answers |
|---|---|
| Form Request / Livewire rules | Is the input structurally valid? |
| Action / Service | Is the operation valid in current domain state? |

Examples of business validation that belong in Actions:

- Can this event be published?
- Is registration open and capacity available?
- Can this Owner be removed?

---

### 5.9 Jobs — `app/Jobs/{Domain}/`

Async / slow work only. Business decision happens before dispatch.

```text
app/Jobs/
├── Notifications/
│   └── SendRegistrationConfirmationJob.php   # only if not using ShouldQueue on Notification
├── Reports/
│   └── GenerateAttendeeExportJob.php         # if large exports need async later
└── Maintenance/
    └── PruneExpiredInvitationsJob.php
```

Rules:

- retry-safe / idempotent
- dispatch **after commit** when dependent on new rows
- never decide “should registration exist?” inside a job

---

### 5.10 Events — `app/Events/{Domain}/`

Domain/application events for **secondary** effects after a successful write.

```text
app/Events/
├── Events/
│   └── EventPublished.php
├── Registrations/
│   └── RegistrationConfirmed.php
├── Tickets/
│   └── TicketIssued.php
└── CheckIns/
    └── AttendeeCheckedIn.php
```

Do not hide the primary transaction behind a listener chain. The Action remains the readable source of truth.

---

### 5.11 Listeners — `app/Listeners/{Domain}/`

React to events for side effects:

```text
app/Listeners/
├── Registrations/
│   └── SendRegistrationConfirmationNotification.php
├── CheckIns/
│   └── InvalidateAttendanceCache.php
└── Audit/
    └── WriteActivityLog.php          # only if audit is not written inline in Action
```

Prefer writing critical audit rows **inside the same transaction** as the business write when the audit is mandatory for that use case. Use listeners for truly secondary work.

---

### 5.12 Notifications — `app/Notifications/`

Laravel Notifications for MVP emails:

```text
app/Notifications/
├── Auth/
│   └── ResetPasswordNotification.php
├── Organizations/
│   └── OrganizationInvitationNotification.php
├── Registrations/
│   └── RegistrationConfirmationNotification.php
├── Tickets/
│   └── TicketAccessNotification.php
└── Events/
    └── EventReminderNotification.php
```

Email failure must never roll back registration, check-in, or lifecycle transitions.

---

### 5.13 Repositories — **not used by default**

**Decision (SRS / SYSTEM_DESIGN / CURSOR): no repository-per-model layer for MVP.**

Eloquent is the persistence abstraction.

If a complex reusable read appears, prefer:

1. model scopes
2. `app/Queries/{Domain}/` query objects
3. only then a dedicated gateway/repository for multi-source persistence

```text
app/Queries/
├── Dashboard/
│   └── OrganizationDashboardQuery.php
├── Attendees/
│   └── EventAttendeeSearchQuery.php
└── Reports/
    ├── AttendanceReportQuery.php
    └── AttendeeExportQuery.php
```

Do not create `EventRepositoryInterface` + Eloquent implementation pairs.

---

### 5.14 Traits — `app/Support/Traits/` or model-local

Use traits only to remove real duplication.

```text
app/Support/Traits/
├── BelongsToOrganization.php     # relationship helper, not a hidden global tenant scope
└── HasPublicSlug.php
```

Avoid “god traits.” Prefer composition via Actions/Services for behavior.

Do **not** introduce broad organization global scopes casually; prefer explicit scoped queries + policies.

---

### 5.15 Helpers — minimize

Prefer:

- private methods on Actions/Services
- Support classes with clear names
- Enums / Value objects

If a pure function is truly shared:

```text
app/Support/
└── DateTime/
    └── OrganizationTimezone.php
```

Avoid `helpers.php` dumping unrelated utilities. No `CommonHelper`.

---

### 5.16 Livewire Components — `app/Livewire/{Domain}/`

Presentation/application boundary for interactive UI.

```text
app/Livewire/
├── Organizations/
│   ├── MemberIndex.php
│   └── SettingsForm.php
├── Events/
│   ├── EventForm.php
│   ├── EventIndex.php
│   ├── TicketTypeManager.php
│   └── AgendaManager.php
├── Attendees/
│   └── AttendeeIndex.php
├── CheckIn/
│   ├── CheckInScanner.php
│   └── ManualCheckInSearch.php
└── Reports/
    ├── Dashboard.php
    └── AttendanceReport.php
```

Livewire may:

- hold UI state
- validate fields
- authorize
- call an Action
- render query results

Livewire must **not** implement registration capacity transactions, ticket issuance, or multi-model lifecycle rules inline.

Matching views live under `resources/views/livewire/...`.

---

### 5.17 Views — `resources/views/`

```text
resources/views/
├── layouts/
│   ├── app.blade.php              # authenticated SaaS shell
│   ├── guest.blade.php
│   └── public.blade.php           # public event pages
├── components/
│   ├── button.blade.php
│   ├── modal.blade.php
│   ├── table/
│   └── forms/
├── livewire/
│   ├── events/
│   ├── attendees/
│   ├── check-in/
│   └── reports/
├── pages/                         # non-Livewire full pages if any
│   └── dashboard.blade.php
├── public/
│   ├── event-show.blade.php
│   ├── register.blade.php
│   └── ticket.blade.php
└── emails/
    └── ...
```

Follow `docs/UI_UX.md` for navigation hierarchy:

```text
Organization → Event → Entity/action
```

Escape user content by default. Reuse Blade components only for repeated UI patterns.

---

### 5.18 Tests — `tests/`

Mirror domain boundaries, not framework folders alone.

```text
tests/
├── Feature/
│   ├── Auth/
│   ├── Organizations/
│   │   ├── InviteMemberTest.php
│   │   └── LastOwnerInvariantTest.php
│   ├── Events/
│   │   ├── PublishEventTest.php
│   │   └── EventLifecycleTest.php
│   ├── Registrations/
│   │   ├── RegisterAttendeeTest.php
│   │   └── RegistrationCapacityConcurrencyTest.php
│   ├── CheckIns/
│   │   ├── CheckInAttendeeTest.php
│   │   └── DuplicateCheckInTest.php
│   ├── Reports/
│   ├── Isolation/
│   │   └── OrganizationIsolationTest.php
│   └── Authorization/
│       └── RoleMatrixTest.php
├── Unit/
│   ├── Enums/
│   ├── Services/
│   └── Support/
└── Browser/                       # optional, high-value only (check-in UX)
```

Prioritize tests listed in CURSOR.md §36: auth, org isolation, role matrix, lifecycle, registration capacity, check-in races, reporting reconciliation.

Use factories. Fake mail, notifications, queue, and filesystem.

---

## 6. Cross-Cutting Placement Summary

| Concern | Location | Create when… |
|---|---|---|
| Models | `app/Models` | Persisting domain state |
| Controllers | `app/Http/Controllers` | Non-Livewire HTTP endpoints |
| Actions | `app/Actions/{Domain}` | Multi-step / transactional use cases |
| Services | `app/Services/{Domain}` | Reusable non-use-case domain logic |
| DTOs | `app/Data/{Domain}` | Only if shared structured payloads need it |
| Enums | `app/Enums` | Controlled status/role/type values |
| Policies | `app/Policies` | Any protected resource action |
| Form Requests | `app/Http/Requests/{Domain}` | Complex controller validation |
| Jobs | `app/Jobs/{Domain}` | Slow/async after-commit work |
| Events | `app/Events/{Domain}` | Secondary decoupling after success |
| Listeners | `app/Listeners/{Domain}` | Side effects for those events |
| Notifications | `app/Notifications` | User-facing mail/notify channels |
| Queries | `app/Queries/{Domain}` | Complex reads/reports/exports |
| Repositories | — | **Avoid for MVP** |
| Traits | `app/Support/Traits` | Real shared model/support duplication |
| Helpers | `app/Support/...` | Rare pure utilities with clear names |
| Livewire | `app/Livewire/{Domain}` | Interactive admin/operator UI |
| Views | `resources/views` | Blade layouts, pages, Livewire templates |
| Tests | `tests/Feature` + `Unit` | Every important workflow |

---

## 7. Routes Layout

```text
routes/web.php
```

Group by middleware and domain, using named routes:

```text
# Public
events.public.show          /e/{public_slug}
events.public.register
tickets.public.show

# Authenticated + organization context
organizations.*
events.index|show|edit|...
events.publish|cancel|archive
events.attendees.*
events.check-in.*
events.reports.*
members.*
settings.*
```

No public REST API for MVP. Do not invent `routes/api.php` consumers unless product scope changes.

---

## 8. Database & Factories

```text
database/
├── migrations/          # one concern per migration; never edit released migrations
├── factories/           # one factory per model used in tests
└── seeders/
    ├── DatabaseSeeder.php
    ├── DemoOrganizationSeeder.php   # optional local/demo only
    └── RoleReferenceSeeder.php      # only if needed; roles are enum-backed in MVP
```

Schema naming follows `docs/DATABASE.md`.

---

## 9. What Not To Build

Do not add for “future-proofing”:

- `app/Repositories/**` for every model
- `app/Interfaces/**` mirroring every Action
- `app/Managers/**`
- `app/DTO/**` package scaffolding
- `Modules/Event/Providers/...` package modules
- API Resource / Sanctum layer for the Blade app
- microservice folders or bounded-context deployables

Create extension points only when they clarify current code or remove real duplication.

---

## 10. Growth Path (When Structure May Evolve)

Stay with this layout until pain is proven.

| Signal | Possible evolution |
|---|---|
| `app/Models` becomes hard to navigate | Group models into domain subfolders (still one app) |
| Many shared registration inputs | Introduce a few readonly Data objects |
| Reporting queries dominate | Expand `app/Queries/Reports` |
| Second persistence source appears | Add a narrow gateway/repository for that boundary only |
| Team wants stronger isolation | Consider Laravel package modules later — not for MVP |

Until then: **simple Laravel + domain folders + Actions for critical workflows.**

---

## 11. Example: Registration Path Mapped to Folders

```text
resources/views/public/register.blade.php
        or Livewire public registration component
                ↓
app/Http/Requests/Registrations/PublicRegisterRequest.php
                ↓
app/Actions/Registrations/RegisterAttendee.php
        ├── uses Services/Registrations/RegistrationCapacityService.php (if shared)
        ├── writes Models/Registration.php + Ticket.php
        ├── writes Models/ActivityLog.php (audit)
        └── after commit: Events/Registrations/RegistrationConfirmed.php
                ↓
app/Listeners/... → Notifications/Registrations/RegistrationConfirmationNotification.php
                ↓
tests/Feature/Registrations/RegisterAttendeeTest.php
tests/Feature/Registrations/RegistrationCapacityConcurrencyTest.php
```

---

## 12. Engineering Checklist for New Features

1. Identify owning module (Core vs Event domain).
2. Prefer existing Action/Policy/Livewire patterns in that module.
3. Put multi-step writes in an Action with a short transaction.
4. Put complex reads in a Query object or scope — not a repository.
5. Keep Livewire/Controllers thin.
6. Enforce Policy + organization scope server-side.
7. Dispatch Jobs/Notifications after commit.
8. Add Feature tests for happy path, authz, isolation, and failure states.
9. Do not add a new abstraction layer for a single use.

---

## 13. Alignment With Product Docs

| Doc | How this structure honors it |
|---|---|
| PRD | Folders map to event lifecycle and org operations |
| SRS | Actions/Services selective; no repo layer; query objects for reports |
| SYSTEM_DESIGN | Modular monolith + advisory component map expanded here |
| DATABASE | Models/migrations mirror authoritative schema |
| BUSINESS_FLOW | One Action per critical transition (publish, register, check-in, role change) |
| CURSOR.md | Thin UI, explicit Actions, Laravel-native, VPS-simple |

---

**End of PROJECT_STRUCTURE.md**
