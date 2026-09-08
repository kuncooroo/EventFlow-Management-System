# Security Checklist

Cross-system security hardening review for release (Phase 10 deliverable). Each item
documents the current posture and, where applicable, the verification that proves it.

Status legend: ✅ verified by automated test · 📋 manual verification required.

## 1. Organization Isolation

- ✅ Tenant isolation contract suite: `tests/Feature/Security/OrganizationIsolationTest.php`
  asserts a member of Org A cannot reach (`403`/`404`) Org B resources through parameterized
  routes (event edit/setup/dashboard/attendees/attendee-show/check-in/report export) and that
  no foreign org names leak onto the dashboard.
- ✅ Per-feature foreign-access tests cover events, attendees, check-in, members, settings,
  media uploads, report export, and organization switching (see `tests/Feature/Events/*`,
  `tests/Feature/Organizations/*`, `tests/Feature/Attendees/*`, `tests/Feature/Reports/*`,
  `tests/Feature/Files/*`).
- ✅ `ReportPolicy` + `AccessibleEventsQuery`/`ScopesToAccessibleEvents` scope every report and
  export to the current member's accessible events; export 404s for foreign/missing events.
- ✅ Livewire components resolve models scoped to the current `OrganizationContext` and authorize
  through policies before mutating (`AuthorizesRequests` / `Gate`).

## 2. Authorization & Server-Side Validation

- ✅ Protected mutations invoke server authorization via policies and `Gate::forUser(...)->authorize(...)`
  (events, registrations, check-in, members, settings, media, reports).
- ✅ Server-side validation dominates; client validation is only UX (Livewire validates with the same
  rules: `RegistrationForm::validationRules`, `SettingsForm`, event/ticket forms).
- ✅ Role escalation attempts return `403`; covered by role-matrix tests
  (`SettingsAuthorizationTest`, `MemberAuthorizationTest`, `EventCrudTest`, livewire action tests).
- ✅ CSRF: `web` group uses the framework CSRF middleware; Livewire partially relies on it for session
  auth and is validated in `VerifyCsrfToken` flow (Livewire-compatible).

## 3. XSS / Escaping

- ✅ All user-generated output is escaped (`{{ }}`); a grep of `resources/views` shows exactly one
  raw output: the server-generated QR `svg` placeholder (`public/ticket.blade.php`), produced by
  `TicketQrCodeService` via Endroid — not user HTML.
- ✅ Uploads reject SVG (not in the MIME allowlist), preventing SVG-based stored XSS.
- ⚠️ Recommended: reverse proxy should emit `X-Content-Type-Options: nosniff`,
  `X-Frame-Options`, `Referrer-Policy` for defense in depth.
- ✅ SQL injection: all queries are parameterized via Eloquent/query builder; user input enters via
  validation-constrained filters (`search`, `status`, ids).

## 4. Public Route Inventory

Accessible without authentication:

| Method | Path | Route | Notes |
| ------ | ---- | ----- | ----- |
| GET | `/` | `home` | landing page |
| GET | `/up` | (health) | Laravel health endpoint |
| GET | `/foundation` | `public.foundation` | dev smoke page — **404 in production** |
| GET | `/livewire-smoke` | `livewire.smoke` | dev smoke component — **404 in production** |
| GET | `/e/{slug}` | `public.events.show` | only published/ongoing/completed/cancelled events (`PublicEventQuery`) |
| GET | `/e/{slug}/register` | `public.events.register` | form only; submission rate-limited in-component |
| GET | `/t/{token}` | `tickets.public.show` | `throttle:tickets`; token = random hex ticket code |
| GET | `/app/invitations/{token}` | `app.invitations.show` | invitation token (hashed 40-char random); accept requires login |
| GET/POST | `/login` | `login` | POST throttled |
| GET/POST | `/forgot-password` | `password.request/.email` | POST throttled |
| GET | `/reset-password/{token}` | `password.reset` | reset token page |
| POST | `/reset-password` | `password.update` | **throttled (new)** |

- ✅ Smoke routes disabled in production (`tests/Feature/Security/ProductionProtectionTest.php`).
- ✅ `PublicEventQuery` only surfaces non-draft, non-archived events; registration CTA respects
  status, window, capacity, and enabled flag.
- ✅ Public registration submit is rate limited per IP (component-level).
- ✅ Invitation and ticket lookups are high-entropy tokens (see § 10) and throttled/authenticated.

## 5. Protected Route Inventory

Requires `auth` (and `organization` context where scoped): preflight checks with CLI:

```
php artisan route:list --except-vendor
```

| Path prefix | Guard |
| ----------- | ----- |
| `app/organizations/create`, `app/organizations` (POST), `app/organizations/switch` | `auth` |
| `app/profile` (GET/PUT) | `auth` |
| `app/dashboard` | `auth` + `organization` |
| `app/members` | `auth` + `organization` (+ role policy) |
| `app/settings` | `auth` + `organization` — Owner/Admin via `manageSettings` |
| `app/events/**`, `app/events/{event}/**` | `auth` + `organization` + event policy |
| `app/events/{event}/attendees/**`, `{event}/check-in` | `auth` + `organization` + event policy |
| `app/reports/**` | `auth` + `organization` + `ReportPolicy` |
| POST `logout` | `auth` |

- ✅ `EnsureOrganizationContext` middleware enforces an active membership session per request.
- ✅ No protected route executes without server-side authorization.

## 6. Rate-Limit Policy

| Endpoint | Limit | Implementation |
| -------- | ----- | -------------- |
| POST `/login` | 5 / min per email+IP | named limiter `login` (`bootstrap/app.php`) |
| GET `/t/{token}` | 20 / min per IP | named limiter `tickets` |
| POST `/forgot-password` | 6 / min | `throttle:6,1` |
| POST `/reset-password` | 6 / min | `throttle:6,1` (**added in hardening pass**) |
| `/e/{slug}/register` submit | 20 / 60 s per IP | component `RateLimiter::tooManyAttempts` on IP |

- ✅ Covered by `tests/Feature/Security/AuthRateLimitingTest.php`.
- Precedent: registration volume is inherently capped by event/ticket capacity; invite and ticket
  tokens are high entropy so only IP-based throttle is needed.

## 7. Upload Security

- ✅ Content-derived MIME allowlist (JPEG/PNG/WebP/GIF) via `StoreMediaFile::MIME_EXTENSIONS`;
  extension comes from the server map, never from the client filename.
- ✅ Per-category size caps via `MediaCategory::maxSizeBytes()`.
- ✅ Server-generated UUID filenames; files stored on configured disk; DB stores metadata only
  (test asserts non-binary rows in MySQL).
- ✅ Gated by `update`/`manageSettings`; event-bound uploads validate the event belongs to the
  caller's organization.
- ✅ Rejection tests: unsupported type, oversized file, cross-org, staff (`tests/Feature/Files/MediaFilesTest.php`).
- ⚠️ Operational: served through storage, no executable content allowed (extension allowlist + SVG ban).

## 8. Production Error Behavior

- ✅ `config('app.debug')` defaults to `env('APP_DEBUG', false)` — production must never set `APP_DEBUG=true`.
- ✅ `tests/Feature/Security/ProductionProtectionTest.php` proves that with debug off, unhandled
  exceptions render generic `500` responses (JSON `Server Error` and HTML) that do not include the
  internal exception message or stack trace.
- ⚠️ Manual: verify `.env.production` sets `APP_DEBUG=false`, `APP_ENV=production`.

## 9. Secrets / Configuration / Log Sanitization

- ✅ Only `.env.example` is tracked; `.env`, `.env.backup`, `.env.production` are gitignored
  (`git check-ignore` confirmed).
- ✅ Repository secret scan (AWS keys, private keys, GitHub tokens, `base64:` keys) found only empty
  placeholders in `.env.example`.
- ✅ Password/reset/session secrets are never recorded: activity logs store
  subject/action/properties (no credentials), check-in logs store method/ticket/`checked_in_at`
  only, `qr_token` is never logged (bounded by `.ai/rules/check-ins.md`).
- ⚠️ Manual: rotate `APP_KEY` if `.env.backup` was ever shared; enable
  `SESSION_SECURE_COOKIE=true` and consider `SESSION_ENCRYPT=true` in production.
- ⚠️ Manual: remove untracked `.env.backup` / `.env.production` copy leftovers from developer machines.

## 10. Ticket / QR / Token Entropy

- ✅ `ticket_code`: 26-char lowercase hex, 104-bit, from `bin2hex(random_bytes(13))` —
  **changed from ULID** so public ticket URLs are non-sequential/unpredictable (SRS SFR-TKT-001).
- ✅ `qr_token`: 64-char hex, 256-bit from `bin2hex(random_bytes(32))` — the check-in credential,
  kept out of URLs/logs.
- ✅ `registration_code`: 26-char hex, 104-bit (changed from ULID) — attendee public reference.
- ✅ Organization invitation tokens: 40-char `Str::random(...)`, stored only as SHA-256 `token_hash`.
- ✅ Verified by `tests/Feature/Security/TokenEntropyTest.php` plus existing ticket access tests.

## 11. Session / Cookie Configuration

| Setting | Value | Note |
| ------- | ----- | ---- |
| `driver` | database | env `SESSION_DRIVER` |
| `http_only` | `true` | ✅ default |
| `same_site` | `lax` | ✅ CSRF mitigation |
| `serialization` | `json` | ✅ avoids PHP gadget risk |
| `secure` | env `SESSION_SECURE_COOKIE` | ⚠️ set `true` in production |
| `encrypt` | env `SESSION_ENCRYPT` (false) | ⚠️ consider `true` in production |
| `lifetime` | 120 min | ✅ |

## 12. Dependencies

- ✅ `composer audit` — no security advisories found.
- ✅ `npm audit` — 0 vulnerabilities.
- ✅ PHP 8.4, Laravel 13.x, Livewire 4.x on current maintained releases (see `docs/STACK_VERSIONS.md`).
- ⚠️ Re-run both audits before release and after dependency upgrades.

## 13. Backup Access Policy

- No backup infrastructure is configured yet (hosting provider TBD).
- ⚠️ When chosen: backups encrypted at rest, restricted to ops/emergency roles, restore
  verified regularly, and never stored next to application secrets.

---

## Release Verification (manual)

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` in production env
- [ ] Reverse proxy security headers emitted
- [ ] `composer audit` and `npm audit` clean on the release commit
- [ ] `.env` not present in the deploy artifact; `APP_KEY` regenerated per environment
- [ ] Full test suite green: `php artisan test --compact`