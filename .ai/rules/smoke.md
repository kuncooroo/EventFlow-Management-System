---
paths:
  - 'routes/**, app/Livewire/Smoke/**'
---

# Smoke

## Rate limits and production-gated smoke routes
POST /reset-password (password.reset) must stay throttle:6,1. Named limiters login (5/min email|ip) and tickets (20/min ip) live in bootstrap/app.php. Public registration submit is limited in-component (20/60s per ip). /foundation and /livewire-smoke abort(404) when app()->isProduction() — dev smoke pages must never ship to production.
