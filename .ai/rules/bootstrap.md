---
paths:
  - bootstrap/app.php
---

# Bootstrap

## Enable event auto-discovery via withEvents()
Event auto-discovery of `app/Events` + `app/Listeners` only works because `bootstrap/app.php` chains `->withEvents()` on the Application configuration. Without it, listener classes silently never fire.
