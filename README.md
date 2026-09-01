# EventFlow Management System

Laravel modular monolith for organization-scoped event operations (registration, ticketing, check-in, reporting).

## Stack

See `docs/STACK_VERSIONS.md` for verified versions.

- Laravel 13 / PHP 8.4 / MySQL 8.4
- Blade + Livewire 4 + Alpine (via Livewire) + Tailwind CSS 4
- VPS-friendly defaults: database sessions, cache, and queue

## Documentation

Canonical product/engineering docs live in `docs/`:

- `docs/PRD.md`
- `docs/SRS.md`
- `docs/SYSTEM_DESIGN.md`
- `docs/DATABASE.md`
- `docs/BUSINESS_FLOW.md`
- `docs/ROADMAP.md`
- `docs/UI_UX.md`
- `docs/PROJECT_STRUCTURE.md`
- `CURSOR.md` (engineering rules for AI/human contributors)

## Local setup

1. Ensure MySQL is running and create databases:

```sql
CREATE DATABASE eventflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE eventflow_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Install dependencies and configure environment:

```bash
composer install
copy .env.example .env   # Windows
php artisan key:generate
# Edit .env DB_* if needed
php artisan migrate
npm install
npm run build
```

3. Run the app:

```bash
php artisan serve
npm run dev
```

4. Smoke URLs:

- `/` — public home
- `/foundation` — Livewire/Alpine/Tailwind smoke page
- `/app/dashboard` — authenticated shell placeholder (auth in TASK-002)
- `/guest` — guest shell placeholder

## Tests

```bash
php artisan test
```

Tests use the `eventflow_testing` MySQL database (see `phpunit.xml`).

## Development tasks

Cursor implementation tasks: `.cursor/tasks/INDEX.md`
