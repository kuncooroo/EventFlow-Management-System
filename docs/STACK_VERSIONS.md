# EventFlow — Verified Stack Versions

**Document Path:** `docs/STACK_VERSIONS.md`  
**Created by:** TASK-001 Project Foundation  
**Verified on:** 2026-09-01  

This file records **actual installed versions** after project initialization. Target intentions from SRS remain guidance; runtime truth is here and in lockfiles.

## Runtime

| Component | Target intent (SRS) | Verified installed |
|---|---|---|
| PHP | 8.4.x | **8.4.25** |
| MySQL | 8.4.x LTS | **8.4.11** |
| Composer | — | **2.8.12** |
| Node.js | — | **22.20.0** |
| npm | — | **10.9.3** |

## Backend (Composer)

| Package | Verified |
|---|---|
| `laravel/laravel` (skeleton) | **v13.10.1** |
| `laravel/framework` | **v13.29.0** (constraint `^13.17`) |
| `livewire/livewire` | **v4.4.3** (constraint `^4.4`) |
| `phpunit/phpunit` | **12.5.x** (see `composer.lock`) |

Exact package resolution: see `composer.lock`.

## Frontend (npm)

| Package | Verified from lockfile |
|---|---|
| `vite` | **8.2.2** |
| `tailwindcss` | **4.3.3** |
| `@tailwindcss/vite` | (see `package-lock.json`) |
| `laravel-vite-plugin` | **3.2.0** |
| Alpine.js | Provided by **Livewire 4** (no separate npm Alpine dependency) |

Exact package resolution: see `package-lock.json` after `npm install`.

## Laravel infrastructure defaults (MVP / VPS)

| Concern | Driver |
|---|---|
| Database | `mysql` (`eventflow`) |
| Testing database | `mysql` (`eventflow_testing`) |
| Sessions | `database` |
| Cache | `database` |
| Queue | `database` |
| Mail (local) | `log` |

## Notes

- `pdo_sqlite` is **not** enabled in the current PHP build; tests use MySQL `eventflow_testing`.
- Redis is optional and not required for MVP.
- No Flux / Breeze / Jetstream starter kit was installed; UI uses Blade + Livewire + Tailwind.

## Re-verify

```bash
php -v
composer show laravel/framework livewire/livewire --format=json
node -v
npm -v
php artisan about
```
