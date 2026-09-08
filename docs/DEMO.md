# Demo Mode

Demo mode turns EventFlow into a safe, shared public playground: a synthetic organization with realistic events, registrations, tickets and check-ins, a persistent banner so visitors always know they are in demo, and backend guards that stop anyone from permanently destroying the demo baseline.

Scope: Phase 13 (Demo System). See `docs/ROADMAP.md` (Phase 13) and `docs/UI_UX.md` §30 for the product requirements this implements.

## Enabling demo mode

```bash
cp .env.example .env   # if not already configured
```

Set in `.env`:

```dotenv
DEMO_MODE=true
DEMO_ORGANIZATION_SLUG=demo-acme
```

`DEMO_MODE` is evaluated with a strict boolean cast (`true`, `false`, `1`, `0`, `"1"`, etc.), so plain `DEMO_MODE=false` will not accidentally enable it.

Disable it again simply by setting `DEMO_MODE=false` (and clearing config cache if you use it: `php artisan config:clear`).

## Seeding the demo data

From a clean database:

```bash
php artisan db:seed --class=Database\\Seeders\\DemoSeeder
```

The seeder is idempotent: if the demo organization already exists (matched by slug), it does nothing.

## Resetting to the baseline

```bash
php artisan eventflow:demo:reset
```

This command:

1. Deletes every row belonging to the demo organization (check-ins, tickets, registration answers and registrations, registration fields, ticket types, agenda items, venues, event reminders, assignments, media rows + their stored files, activity logs, invitations, settings, memberships, then the organization itself, then the dedicated demo users), in dependency order within a transaction.
2. Runs `DemoSeeder` again.

Data outside the demo organization is never touched, so non-demo organizations sharing the same instance are safe.

## Demo accounts

After seeding, sign in with any of these accounts (password for all three: `eventflow-demo`):

| Email | Role |
| --- | --- |
| `demo.owner@eventflow.demo` | Owner |
| `demo.admin@eventflow.demo` | Admin |
| `demo.staff@eventflow.demo` | Staff |

Seeded events (public registration pages live at `/e/{slug}`):

| Event | Status | Mode |
| --- | --- | --- |
| Technology Conference 2026 | Published | Offline |
| Digital Marketing Workshop | Published | Online |
| Campus Career Fair | Published | Hybrid |
| Spring Product Expo | Completed | Offline |

The published events get registrations, tickets and (for the relevant ones) check-in history so dashboard, attendees, reports and check-in flows all have realistic data to explore.

## What demo mode changes

All behavior is centralized in `App\Support\Demo\DemoMode` and `App\Support\Demo\DemoGate` — there are no one-off conditionals scattered across business logic.

**UI.** The `x-demo-banner` component renders *"Demo Mode / Changes may be reset periodically."* at the top of the app, public and guest layouts whenever demo mode is enabled.

**Mail sink.** In demo mode the mail transport is forced to the `log` sink (`DemoMode::apply()`, called from `AppServiceProvider::boot()`). Invitations and password-reset links are therefore never delivered to real inboxes — use the demo accounts above.

**Restricted actions (demo organization only).** The following are blocked with a 403 whenever the affected data belongs to the demo organization:

- Removing a member (`RemoveMember`)
- Changing a member's role (`ChangeMemberRole`)
- Permanently deleting media (`DeleteMediaFile`)

**Restricted actions (instance-wide while demo is on).** Creating a new organization (`OrganizationPolicy::create`) is blocked in demo mode so visitors cannot leave scratch organizations behind; `eventflow:demo:reset` then restores a truly clean baseline.

Anything not listed above remains open for exploration (event lifecycle transitions, registrations, check-in, reports, invitations, settings preview). Because these changes are soft/status-based, the reset restores the baseline.

## Notes

- The UI never promises permanent persistence, consistent with `docs/UI_UX.md` §30.6.
- Disabling dangerous actions for the shared demo follows `docs/UI_UX.md` §30.5 (option A).
- Non-demo organizations keep full capabilities even while demo mode is on; only `OrganizationPolicy::create` is instance-wide, and that exists to keep the reset output honest.