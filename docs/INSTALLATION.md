# EventFlow Management System — Installation Guide

**Document Path:** `docs/INSTALLATION.md`
**Applies to:** EventFlow Management System (Laravel 13 / PHP 8.4 / MySQL 8.4)
**Version:** 1.0

This guide describes how to install EventFlow on a clean server or local
environment. It covers environment requirements, configuration, database setup,
migrations, the initial **Owner** bootstrap, storage, queue/scheduler/mail
configuration, and a post-install health verification.

> Related document: `docs/DEPLOYMENT.md` covers production hardening, process
> supervision, backups, and upgrades.

---

## 1. Overview

A supported installation is one where an operator can:

1. configure the application,
2. connect the database,
3. run migrations,
4. create the initial Owner,
5. log in,
6. create the first organization/event,
7. upload a test asset,
8. confirm the queue worker and scheduler are running,
9. confirm the installation via `eventflow:check-health`.

The installation process **must not** expose `.env`, print secrets, use default
production credentials, or leave debug mode enabled.

Three Artisan commands support installation:

| Command | Purpose |
| --- | --- |
| `php artisan eventflow:install` | Guided install: preflight → migrations → Owner bootstrap → storage → health verification |
| `php artisan eventflow:create-owner` | Bootstrap (or repair) the initial Owner user, organization, and Owner membership |
| `php artisan eventflow:check-health` | Verify environment, application state, database schema, and configuration |

All three commands are idempotent where it matters: `eventflow:create-owner`
reuses existing organizations/users and never creates duplicate memberships.

---

## 2. Requirements

### 2.1 Runtime (server)

- PHP **8.3 or newer** recommended 8.4 (the current development version)
- MySQL **8.x** (the supported engine) or MariaDB 10.6+
- Composer
- A PHP-FPM / web server (Nginx or Apache) — see `docs/DEPLOYMENT.md`

### 2.2 Required PHP extensions

The health check (`eventflow:check-health`) verifies the following extensions:

```
bcmath, ctype, curl, dom, fileinfo, filter, hash, iconv, json, libxml,
mbstring, openssl, pcre, pdo, pdo_mysql, session, tokenizer, xml, xmlwriter, zlib
```

Install them with your distribution's package manager, for example on Debian/Ubuntu:

```bash
sudo apt-get update
sudo apt-get install -y php8.4-cli php8.4-fpm php8.4-mysql php8.4-mbstring \
  php8.4-curl php8.4-dom php8.4-xml php8.4-bcmath php8.4-intl php8.4-zip \
  php8.4-gd php8.4-tokenizer
```

> Ticket QR codes are generated as SVG, so the `gd` extension is **optional** for
> the application core. If you also need PNG/JPEG image processing for uploads,
> install `gd` as well.

### 2.3 Build requirements (first install only)

- Node.js 20+ and npm to build frontend assets.

---

## 3. Step 1 — Obtain code and install dependencies

```bash
# Copy/clone the application into /var/www/eventflow
cd /var/www/eventflow

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Build frontend assets
npm ci
npm run build
```

On the first local setup you can also use the composer convenience script:

```bash
composer run setup   # installs PHP + JS deps, copies .env, keys, migrates, builds assets
```

---

## 4. Step 2 — Configure the environment

Create the environment file from the template:

```bash
cp .env.example .env
```

Edit `.env` and set at least:

| Variable | Example | Notes |
| --- | --- | --- |
| `APP_ENV` | `production` | `local` only on a development machine |
| `APP_DEBUG` | `false` | **must** be `false` in production (§31.10 of the SRS) |
| `APP_URL` | `https://events.example.com` | The public URL of the application |
| `APP_KEY` | *(auto-generated)* | Generate with `php artisan key:generate` or let `eventflow:install` generate it |
| `DB_CONNECTION` | `mysql` | Keep `mysql` |
| `DB_HOST` | `127.0.0.1` | |
| `DB_PORT` | `3306` | |
| `DB_DATABASE` | `eventflow` | Database name you created |
| `DB_USERNAME` | `eventflow` | Dedicated user, **not** root in production |
| `DB_PASSWORD` | *(strong password)* | Never share this value |
| `SESSION_DRIVER` | `database` | Already the default; recommended for VPS deployments |
| `QUEUE_CONNECTION` | `database` | Already the default; the worker runs from this queue |
| `MAIL_MAILER` | `smtp` | `log` is fine for smoke tests only |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | your address / `EventFlow` | Used in all outgoing emails |

Generate the application key if `APP_KEY` is still empty:

```bash
php artisan key:generate --force
```

> `eventflow:install` generates a missing key automatically and refuses to run
> when `.env` is absent.

---

## 5. Step 3 — Create the database

```sql
CREATE DATABASE eventflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'eventflow'@'localhost' IDENTIFIED BY 'a-strong-password';
GRANT ALL PRIVILEGES ON eventflow.* TO 'eventflow'@'localhost';
FLUSH PRIVILEGES;
```

---

## 6. Step 4 — Install, migrate, and bootstrap the Owner

### 6.1 One-shot guided install (recommended)

```bash
php artisan eventflow:install \
    --organization="Example Events" \
    --name="Jane Doe" \
    --email="owner@example.com" \
    --password="a-very-strong-password"
```

What this does, in order:

1. **Preflight** — verifies PHP version, required extensions, writable
   directories, `APP_KEY`, and the database connection. Any failure stops the
   installer with a non-zero exit code.
2. **Migrations** — runs `php artisan migrate --force`.
3. **Owner bootstrap** — runs `eventflow:create-owner` with your details.
4. **Storage** — runs `php artisan storage:link` when `public/storage` is not linked.
5. **Health check** — runs `eventflow:check-health` and reports the result.
6. Prints the remaining manual steps (queue worker, scheduler, mail).

Options:

| Option | Meaning |
| --- | --- |
| `--no-migrate` | Skip the migration step (e.g. after an existing deploy) |
| `--no-owner` | Skip the Owner bootstrap (do it separately) |
| `--no-link` | Skip creating the `public/storage` symlink |
| `--optimize` | Also cache config, routes, views, and events for production |

> The Owner password is never printed by any command.

### 6.2 Step-by-step equivalent

```bash
# 1. Preflight + migrations
php artisan eventflow:install --no-owner

# or manually:
php artisan eventflow:check-health
php artisan migrate --force

# 2. Bootstrap the Owner
php artisan eventflow:create-owner

#         (prompts for organization, name, email, and a hidden password)

# 3. Storage + optimization
php artisan storage:link
php artisan optimize        # config:cache, route:cache, view:cache, event:cache
```

### 6.3 Owner bootstrap details

`eventflow:create-owner` accepts:

```text
--organization=  Organization to create or reuse (by name)
--name=          Display name of the initial owner
--email=         Email address of the initial owner
--password=      Password (prompted, hidden, if omitted)
```

Behavior:

- Creates the organization (slug generated uniquely) and the Owner user.
- Marks the user's email as verified so login works immediately.
- Creates an **Owner** membership for that user in the organization.
- On re-run it reuses the existing organization/user/membership (idempotent).
- If the user already exists in the organization with a lesser role, the
  membership is upgraded to **Owner**.
- If the user is new and no `--password` is supplied in a non-interactive run,
  the command fails rather than generating a weak/default password.

---

## 7. Step 5 — Storage

```bash
php artisan storage:link
```

Make sure the web server can write to:

```text
storage/
storage/framework/cache
storage/framework/sessions
storage/framework/views
storage/logs
bootstrap/cache
```

Example ownership/permissions for PHP-FPM (`www-data`):

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 8. Step 6 — Production optimization

```bash
php artisan optimize
```

This caches configuration, routes, views, and events. `eventflow:install
--optimize` runs the same thing at the end of a guided install. Clear the
caches on every configuration or code change during upgrades.

---

## 9. Step 7 — Verify the installation

```bash
php artisan eventflow:check-health
```

Checks performed:

| Check | Fail condition |
| --- | --- |
| PHP version | Below 8.3 |
| PHP extensions | Any required extension missing |
| Writable directories | `storage` or `bootstrap/cache` not writable |
| Application key | `APP_KEY` empty |
| Database connection | Cannot connect to the configured database |
| Migrations | Migration repository missing or pending migrations |
| Debug mode | `APP_DEBUG=true` while `APP_ENV=production` |
| Storage link | `public/storage` not linked (warning) |
| Queue worker | Worker not verifiable here (warning, see deployment guide) |
| Scheduler | Cron entry not verifiable here (warning, see deployment guide) |

Exit code is `0` when healthy, `1` when any check fails. Pass `--strict` to also
fail on warnings. This makes the command usable as a monitoring / CI gate.

The framework health endpoint is available at `/up` and returns `200 OK` when the
application can boot.

> Warning-level items (storage link, queue worker, scheduler) are not
> automatically detected — confirm them manually as described in
> `docs/DEPLOYMENT.md`.

---

## 10. Queue, scheduler, and mail

These are configured at the system level and are covered in detail by
`docs/DEPLOYMENT.md`. Summary:

- **Queue worker**: run `php artisan queue:work` as a persisted process
  (Supervisor or systemd).
- **Scheduler**: add one cron entry running `php artisan schedule:run` every
  minute. EventFlow schedules e.g. registration reminders and life-stage
  transitions through the scheduler.
- **Mail**: set `MAIL_MAILER=smtp` and the `MAIL_HOST`/`MAIL_PORT`/
  `MAIL_USERNAME`/`MAIL_PASSWORD` variables, then test with
  `php artisan notify:test` if available, or trigger a workflow notification.

---

## 11. Security notes

- `.env` must never be reachable over the web (see `docs/DEPLOYMENT.md` for the
  recommended web-server block).
- No default passwords are created. The initial Owner password is supplied by
  the operator and is never echoed or printed.
- `APP_DEBUG` must remain `false` in production.
- Use a dedicated database user, not `root`, in production.

---

## 12. Re-running install commands

All installer commands are safe to re-run:

- `eventflow:install` re-runs preflight/health checks and reuses existing state.
- `eventflow:create-owner` is idempotent (no duplicate users, organizations, or
  memberships).
- `migrate --force` only applies pending migrations.